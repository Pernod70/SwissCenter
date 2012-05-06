<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

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
      if (array_key_exists('attributes',$xml_elem))
      {
        $php_stmt .= '[$xml_elem[\'tag\']][] = $xml_elem[\'attributes\'];';
      }
      else
      {
        $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
      }
      eval($php_stmt);
    }
  }
  return array_shift($tree);
}

//
// Returns the web address of the Weather Channel, complete with partner ID.
//

function weather_link()
{
  return 'http://www.weather.com/?prod=xoap&par='.PARTNER_ID;
}

//
// Returns a array of XML data for the given location (from either the database or weather.com, according
// the the caching requirements).
//

function get_weather_xml( $loc, $type, $val )
{
  $expire = ($type == 'cc' ? 1800 : 7200);
  $cache = new cache_api_request('weather', $expire);

  $units  = get_user_pref("weather_units");
  $url    = 'http://xoap.weather.com/weather/local/'.$loc.'?unit='.$units.'&prod=xoap&link=xoap&par='.PARTNER_ID.'&key='.LICENSE_KEY.'&'.$type.'='.$val;
  $xml    = $cache->getCached($url);

  if (empty($xml))
  {
    send_to_log(4,"Fetching weather information from", $url);
    if (($xml = file_get_contents($url)) !== false)
      $cache->cache($url, $xml, 7200);
  }
  $xml_array = xml2tree($xml);
  if (isset($xml_array["err"]))
    send_to_log(2,'Error from Weather.com: '.$url, $xml_array["err"]);

  return $xml_array;
}

//
// Returns a array of RSS data for the given location (from either the database or weather.com, according
// the the caching requirements).
//

function get_yahoo_xml( $loc, $type, $val )
{
  $cache = new cache_api_request('weather', 1800);

  $units  = (get_user_pref("weather_units") == 'm') ? 'c' : 'f';
  $url    = 'http://xml.weather.yahoo.com/forecastrss/'.$loc.'_'.$units.'.xml';
  $xml    = $cache->getCached($url);

  if (empty($xml))
  {
    send_to_log(4,"Fetching weather information from", $url);
    if (($xml = file_get_contents($url)) !== false)
      $cache->cache($url, $xml);
  }
  $xml_array = xml2tree($xml);
  if (strpos($xml_array["title"], 'Error'))
    send_to_log(2,$xml_array["description"].': '.$url, $xml_array["item"]["title"]);
  else
  {
    $xml_array["head"]["ut"] = $xml_array["channel"]["yweather:units"][0]["temperature"];
    $xml_array["head"]["ud"] = $xml_array["channel"]["yweather:units"][0]["distance"];
    $xml_array["head"]["up"] = $xml_array["channel"]["yweather:units"][0]["pressure"];
    $xml_array["head"]["us"] = $xml_array["channel"]["yweather:units"][0]["speed"];

    $xml_array["cc"]["flik"]      = $xml_array["channel"]["yweather:wind"][0]["chill"];
    $xml_array["cc"]["wind"]["d"] = $xml_array["channel"]["yweather:wind"][0]["direction"];
    $xml_array["cc"]["wind"]["s"] = $xml_array["channel"]["yweather:wind"][0]["speed"];
    $xml_array["cc"]["wind"]["t"] = '';
    $xml_array["cc"]["hmid"]      = $xml_array["channel"]["yweather:atmosphere"][0]["humidity"];
    $xml_array["cc"]["vis"]       = $xml_array["channel"]["yweather:atmosphere"][0]["visibility"];
    $xml_array["cc"]["bar"]["r"]  = $xml_array["channel"]["yweather:atmosphere"][0]["pressure"];
    $xml_array["cc"]["sun"]["r"]  = $xml_array["channel"]["yweather:astronomy"][0]["sunrise"];
    $xml_array["cc"]["sun"]["s"]  = $xml_array["channel"]["yweather:astronomy"][0]["sunset"];
    $xml_array["cc"]["t"]         = $xml_array["channel"]["item"]["yweather:condition"][0]["text"];
    $xml_array["cc"]["icon"]      = $xml_array["channel"]["item"]["yweather:condition"][0]["code"];
    $xml_array["cc"]["tmp"]       = $xml_array["channel"]["item"]["yweather:condition"][0]["temp"];
    $xml_array["cc"]["lsup"]      = $xml_array["channel"]["item"]["yweather:condition"][0]["date"];

    for ($i=0; $i<5; $i++)
    {
      $xml_array["dayf"][$i]["day"]       = $xml_array["channel"]["item"]["yweather:forecast"][$i]["day"];
      $xml_array["dayf"][$i]["date"]      = $xml_array["channel"]["item"]["yweather:forecast"][$i]["date"];
      $xml_array["dayf"][$i]["low"]       = $xml_array["channel"]["item"]["yweather:forecast"][$i]["low"];
      $xml_array["dayf"][$i]["hi"]        = $xml_array["channel"]["item"]["yweather:forecast"][$i]["high"];
      $xml_array["dayf"][$i]["text"]      = $xml_array["channel"]["item"]["yweather:forecast"][$i]["text"];
      $xml_array["dayf"][$i]["d"]["icon"] = $xml_array["channel"]["item"]["yweather:forecast"][$i]["code"];
    }
  }

  return $xml_array;
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
