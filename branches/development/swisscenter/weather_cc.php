<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once('base/page.php');
  require_once('base/prefs.php');
  require_once('base/utils.php');
  require_once('base/infotab.php');
  require_once('base/weather.php');

  $buttons  = array();

  if (!empty($_REQUEST["home"]))
  {
    // User has set this city as their home location.
    $loc_id = $_REQUEST["home"];
    set_user_pref('WEATHER_HOME',$loc_id);
  }
  elseif (!empty($_REQUEST["loc"]))
  {
    // User is viewing a city that is NOT their home location.
    $loc_id  = $_REQUEST["loc"]; 

    // Display the button to make this their home location.
    if ($loc_id != weather_home() )
      $buttons[] = array('text'=>str('WEATHER_HOME'), 'url'=>'weather_cc.php?&home='.$loc_id );
  }
  else
  {
    // User is viewing the default location (their home city).
    $loc_id  = weather_home();
  }
  
  // Change between standard/metric measurements.
  if (!empty($_REQUEST["units"]))
    set_user_pref('WEATHER_UNITS',$_REQUEST["units"]);

  // Get XML data (from DB or from weather.com)
  purge_weather();
  $xml_cc    = get_weather_xml( $loc_id, 'cc', '*');
  $xml_links = get_weather_xml( $loc_id, 'link', 'xoap');
  $time      = split(' ',$xml_cc["cc"]["lsup"]);

  // Build Current Conditions information table
  $cc = new infotab();
  $cc->add_item(str('WEATHER_FEELS_LIKE'), $xml_cc["cc"]["flik"].chr(176).$xml_cc["head"]["ut"]);
  $cc->add_item(str('WEATHER_HUMIDITY'),   $xml_cc["cc"]["hmid"].'%');
  $cc->add_item(str('WEATHER_PRESSURE'),   $xml_cc["cc"]["bar"]["r"].' '.$xml_cc["head"]["up"]);
  $cc->add_item(str('WEATHER_WIND'),       $xml_cc["cc"]["wind"][s].' '.$xml_cc["head"]["us"].' '.$xml_cc["cc"]["wind"]["t"]);
  $cc->add_item(str('WEATHER_VISIBILITY'), $xml_cc["cc"]["vis"].' '.$xml_cc["head"]["ud"]);
  $cc->add_item(str('WEATHER_UV'),         $xml_cc["cc"]["uv"][i].' ('.$xml_cc["cc"]["uv"]["t"].')');
  
  if ( $xml_cc["cc"]["icon"] == '-')
  {
    $xml_cc["cc"]["icon"] = 'na';
    $xml_cc["cc"]["tmp"]  = chr(151);
  }

  $menu = new menu(false);
  $menu->add_item(str('WEATHER_5DAY'),'weather_fc.php?loc='.$loc_id);
  $menu->add_item(str('WEATHER_LOCATION'),'weather_city_list.php',true);

  // Display Weather Icon and statistics
  page_header($xml_cc[$loc_id]["dnam"],'');

  echo '<table cellspacing=0 border=0 cellpadding=0 width="100%">
          <tr>
            <td align="center" width="40%">
              <img width="128px" height="128px" src="weather/large/'.$xml_cc["cc"]["icon"].'.gif">
              <font size="4"><br>'.$xml_cc["cc"]["tmp"].chr(176).$xml_cc["head"]["ut"].'
                  <br>'.$xml_cc["cc"]["t"].'</font>
            </td>
            <td align="center" width="60%">
              <font size="4">'.str('WEATHER_CURRENT').'</font><p>';
              $cc->display();
  echo '    </td>
          </tr>
        </table>
        <table cellspacing=0 border=0 cellpadding=0 width="100%">
          <tr>
            <td width="400">';
              $menu->display(350);
  echo '    </td>
            <td align="center" valign="top">
              <a href="'.weather_link().'"><img border=0 src="weather/logo.gif"></a>
            </td>
          </tr>
        </table>';

  if ( get_user_pref("weather_units") == 'm')
    $buttons[] = array('text'=>str('WEATHER_IMPERIAL'), 'url'=>'weather_cc.php?loc='.$loc_id.'&units=s' );
  else
    $buttons[] = array('text'=>str('WEATHER_METRIC'), 'url'=>'weather_cc.php?loc='.$loc_id.'&units=m' );

  page_footer('index.php', $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>