<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

define('TFL_URL','http://www.tfl.gov.uk/tfl/businessandpartners/syndication/feed.aspx?email=ngbarnes@hotmail.com');

class Tfl {
  private $service = 'tfl';
  private $cache_expire = 3600;
  private $cache;

  function Tfl ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  /**
   * Return requested feed.
   *
   * @param string $feed
   * @return array
   */
  function getFeed($feed)
  {
    // Form the request URL
    $request = TFL_URL.'&feedId='.$feed;

    //Sends a request to Tfl
    send_to_log(6,'Tfl feed request', $request);

    // Use a cached response if available
    if ( !($response = $this->cache->getCached($request)) ) {
      if (($response = file_get_contents($request)) !== false) {
        $this->cache->cache($request, $response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return parse_tfl_xml($response);
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
    $xml = preg_replace('/>\s+/u', '>', $xml);
    $xml = preg_replace('/\s+</u', '<', $xml);
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
