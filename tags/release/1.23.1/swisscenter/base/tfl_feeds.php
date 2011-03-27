<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

define('TFL_URL','http://www.tfl.gov.uk/tfl/businessandpartners/syndication/feed.aspx?email=ngbarnes@hotmail.com');

class Tfl {
  private $service;
  private $response;
  private $cache_table = null;
  private $cache_expire = null;

  /*
   * When your database cache table hits this many rows, a cleanup
   * will occur to get rid of all of the old rows and cleanup the
   * garbage in the table.  For most personal apps, 1000 rows should
   * be more than enough.  If your site gets hit by a lot of traffic
   * or you have a lot of disk space to spare, bump this number up.
   * You should try to set it high enough that the cleanup only
   * happens every once in a while, so this will depend on the growth
   * of your table.
   */
  private $max_cache_rows = 1000;

  function Tfl ()
  {
    $this->service = 'tfl';
    $this->enableCache(3600);
  }

  /**
   * Enable caching to the database
   *
   * @param unknown_type $cache_expire
   * @param unknown_type $table
   */
  private function enableCache($cache_expire = 600, $table = 'cache_api_request')
  {
    if (db_value("SELECT COUNT(*) FROM $table WHERE service = '".$this->service."'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $table WHERE service = '".$this->service."' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
    $this->cache_table = $table;
    $this->cache_expire = $cache_expire;
  }

  private function getCached($request)
  {
    //Checks the database for a cached result to the request.
    //If there is no cache result, it returns a value of false. If it finds one,
    //it returns the unparsed XML.
    $reqhash = md5(serialize($request));

    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result)) {
      return $result;
    }
    return false;
  }

  private function cache($request, $response)
  {
    //Caches the unparsed XML of a request.
    $reqhash = md5(serialize($request));

    if (db_value("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'")) {
      db_sqlcommand( "UPDATE ".$this->cache_table." SET response = '".db_escape_str($response)."', expiration = '".strftime("%Y-%m-%d %H:%M:%S")."' WHERE request = '$reqhash'");
    } else {
      db_sqlcommand( "INSERT INTO ".$this->cache_table." (request, service, response, expiration) VALUES ('$reqhash', '$this->service', '".db_escape_str($response)."', '".strftime("%Y-%m-%d %H:%M:%S")."')");
    }

    return false;
  }

  private function request($request, $nocache = false)
  {
    //Sends a request to Tfl
    send_to_log(6,'Tfl feed request', $request);
    if (!($this->response = $this->getCached($request)) || $nocache) {
      if ($this->response = file_get_contents($request)) {
        $this->cache($request, $this->response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return true;
  }

  /**
   * Return requested feed.
   *
   * @param string $feed
   * @return array
   */
  function getFeed($feed)
  {
    if ( $this->request(TFL_URL.'&feedId='.$feed) ) {
      $data = parse_tfl_xml($this->response);
      return $data;
    } else {
      return false;
    }
  }
}

/**
 * Parse the XML file
 *
 * @param string $filename
 */
function parse_tfl_xml($xml) {
  global $tfl_items;

  // Create XML parser
  $xmlparser = xml_parser_create();
  $success = true;
  if ($xmlparser !== false) {
    xml_set_element_handler($xmlparser, 'start_tag_tfl', 'end_tag_tfl');
    xml_set_character_data_handler($xmlparser, 'tag_contents_tfl');

    // Process XML file
    $xml = preg_replace("/>\s+/u", ">", $xml);
    $xml = preg_replace("/\s+</u", "<", $xml);
    if (!xml_parse($xmlparser, $xml)) {
      send_to_log(8, 'XML parse error: ' . xml_error_string(xml_get_error_code($xmlparser)) . xml_get_current_line_number($xmlparser));
      $result = false;
    }
    else
    {
      $result = $tfl_items;
    }
  } else {
    send_to_log(5, 'Unable to create an expat XML parser - is the "xml" extension loaded into PHP?');
    $result = false;
  }
  return $result;
}

//-------------------------------------------------------------------------------------------------
// Callback functions to perform parsing of the various XML files.
//-------------------------------------------------------------------------------------------------

function start_tag_tfl($parser, $name, $attribs)
{
  global $tag;
  global $section;
  global $camera;
  global $line;
  global $station;

  switch ($name)
  {
    case 'ITEM':
      $camera = array();
      break;
    case 'LINES':
    case 'STATIONS':
      $section = $name;
      break;
    case 'LINE':
      $line = array();
      break;
    case 'STATION':
      $station = array();
      break;
    default:
      $tag = $name;
  }
}

function end_tag_tfl($parser, $name)
{
  global $tfl_items;
  global $camera;
  global $line;
  global $station;

  switch ($name)
  {
    case 'ITEM':
      $tfl_items['CAMERAS'][] = $camera;
      break;
    case 'LINE':
      $tfl_items['LINES'][] = $line;
      break;
    case 'STATION':
      $tfl_items['STATIONS'][] = $station;
      break;
    default:
  }
}

function tag_contents_tfl($parser, $data)
{
  global $tag;
  global $section;
  global $camera;
  global $line;
  global $station;

  switch ($section)
  {
    case 'LINES':
      switch ($tag)
      {
        case 'NAME':     { $line['NAME'] .= $data; break; }
        case 'COLOUR':   { if (empty($line['COLOUR'])) $line['COLOUR'] .= $data; break; }
        case 'BGCOLOUR': { if (empty($line['BGCOLOUR'])) $line['BGCOLOUR'] .= $data; break; }
        case 'TEXT':     { if (empty($line['STATUS'])) $line['STATUS'] .= $data; else $line['MESSAGE'] .= $data; break; }
      }
      break;
    case 'STATIONS':
      switch ($tag)
      {
        case 'NAME':     { $station['NAME'] .= $data; break; }
        case 'COLOUR':   { if (empty($station['COLOUR'])) $station['COLOUR'] .= $data; break; }
        case 'BGCOLOUR': { if (empty($station['BGCOLOUR'])) $station['BGCOLOUR'] .= $data; break; }
        case 'TEXT':     { if (empty($station['STATUS'])) $station['STATUS'] .= $data; else $station['MESSAGE'] .= $data; break; }
      }
      break;
    default:
      switch ($tag)
      {
        case 'TITLE':    { $camera['TITLE'] .= $data; break; }
        case 'LINK':     { $camera['URL'] .= $data; break; }
      }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
