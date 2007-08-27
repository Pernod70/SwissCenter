<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));

define('PARTNER_ID'  ,'1005644464');
define('LICENSE_KEY' ,'e8f37039809161cc');

//
// Takes some XML data and returns an array representing the XML structure
//
// NOTE: This is a simple function that cannot represent complex xml structure, but it is
//       enough for the weather module.
//

function xml2tree( $xml )
{
  $parser = xml_parser_create();
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
  xml_parse_into_struct($parser,$xml,$vals,$index);
  xml_parser_free($parser);

  $level = array();
  $tree  = array();

  foreach ($vals as $xml_elem)
  {
    if ($xml_elem['type'] == 'open')
    {
     if (array_key_exists('attributes',$xml_elem))
     {
       @list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
     }
     else
     {
       $level[$xml_elem['level']] = $xml_elem['tag'];
     }
    }

    if ($xml_elem['type'] == 'complete')
    {
     $start_level = 1;
     $php_stmt = '$tree';
     while($start_level < $xml_elem['level'])
     {
       $php_stmt .= '[$level['.$start_level.']]';
       $start_level++;
     }
     $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
     eval($php_stmt);
    }
  }
  return array_shift($tree);
}

//
// Returns the web address of the Weather Channel, omplete with partner ID.
//

function weather_link()
{
  return 'http://www.weather.com/?prod=xoap&par='.PARTNER_ID;
}

//
// Purge data that is older than the caching requirements.
//

function purge_weather()
{
  db_sqlcommand("delete from weather where type='cc' and requested < ".(time()-1800));     // 30 mins
  db_sqlcommand("delete from weather where type='dayf' and requested < ".(time()-7200));   // 2 hrs
  db_sqlcommand("delete from weather where type='links' and requested < ".(time()-43200)); // 12 hrs
}

//
// Returns a array of XML data for the given location (from either the database or weather.com, according
// the the caching requirements).
//

function get_weather_xml( $loc, $type, $val )
{
  $units  = get_user_pref("weather_units");
  $url    = 'http://xoap.weather.com/weather/local/'.$loc.'?unit='.$units.'&prod=xoap&par='.PARTNER_ID.'&key='.LICENSE_KEY;
  $xml    = db_value("select xml from weather where url='$url' and type='$type'");

  if (empty($xml))
  {
    $fetch_url = $url.'&'.$type.'='.$val;
    send_to_log(4,"Fetching weather information from", parse_url($fetch_url));
    $xml       = file_get_contents($fetch_url);
    $data      = array("url"       => $url
                      ,"xml"       => $xml
                      ,"requested" => time()
                      ,"type"      => $type );
    if (!db_insert_row( "weather", $data))
      page_error(str('DATABASE_ERROR'));
  }
  return xml2tree($xml);
}

//
// Returns the location ID for the user's home city. If none was defiend within the ini file
// then Bracknell is returned.
//

function weather_home()
{
  if ( get_user_pref("weather_home") != '' )
  {
    $id = get_user_pref("weather_home");
    $code = db_value("select twc_code from cities where twc_code='$id' or name='$id'");
    if (!empty($code))
      return $code;
    else
      return 'UKXX0022';
  }
  else
    return 'UKXX0022';
}

//
// Returns a array of cities (name and location ID) that matches the given string.
//

function get_matching_cities( $name )
{
  $db_data   = array();
  $cnt       = db_value("select count(*) from cities where name='$name' and twc_code is not null");

  if ($cnt == 1)
  {
    // Exact match (complete with code) found in the database, so return an array with just one item.
    $code = db_value("select twc_code from cities where name='$name' and twc_code is not null");
    return array( $code => $name);
  }
  else
  {
    // Either zero or more than one match, so query the weather channel
    $url       = 'http://xoap.weather.com/search/search?where='.rawurlencode($name);
    $xml       = file_get_contents($url);
    $data      = array();

    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parse_into_struct($parser,$xml,$vals,$index);
    xml_parser_free($parser);

    if (empty($index["loc"]))
    {
      // Oh dear, the weather channel does not have any data for this city...
      db_sqlcommand("delete from cities where name='$name'");      
      return array();
    }
    else
    {
      // Process the matches into a more friendly array
      foreach ($index["loc"] as $val_idx)
        $data[$vals[$val_idx]["attributes"]["id"]] = $vals[$val_idx]["value"];

      // Matches were found, so update the database with the exact city names and codes.
      db_sqlcommand("delete from cities where name='$name'");
      foreach ($data as $k => $v)
        db_sqlcommand("insert into cities (name,twc_code) values ('$v','$k')");

      // Return the matches
      asort($data);
      return $data;
    }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
