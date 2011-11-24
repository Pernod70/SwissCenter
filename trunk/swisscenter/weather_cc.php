<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/weather.php'));

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
  $xml_cc    = get_yahoo_xml( $loc_id, 'cc', '*');
  $time      = split(' ',$xml_cc["cc"]["lsup"]);
  $title     = isset($xml_cc[$loc_id]["dnam"]) ? $xml_cc[$loc_id]["dnam"] : $xml_cc["channel"]["yweather:location"][0]["city"].', '.$xml_cc["channel"]["yweather:location"][0]["country"];

  // Build Current Conditions information table
  $cc = new infotab();
  $cc->add_item(str('WEATHER_FEELS_LIKE'), $xml_cc["cc"]["flik"].chr(176).$xml_cc["head"]["ut"]);
  $cc->add_item(str('WEATHER_HUMIDITY'),   $xml_cc["cc"]["hmid"].'%');
  $cc->add_item(str('WEATHER_PRESSURE'),   $xml_cc["cc"]["bar"]["r"].' '.$xml_cc["head"]["up"]);
  $cc->add_item(str('WEATHER_WIND'),       $xml_cc["cc"]["wind"]["s"].' '.$xml_cc["head"]["us"].' '.$xml_cc["cc"]["wind"]["t"]);
  $cc->add_item(str('WEATHER_VISIBILITY'), $xml_cc["cc"]["vis"].' '.$xml_cc["head"]["ud"]);
  if (isset($xml_cc["cc"]["uv"]))
  {
    $cc->add_item(str('WEATHER_UV'),       $xml_cc["cc"]["uv"]["i"].' ('.$xml_cc["cc"]["uv"]["t"].')');
  }
  if (isset($xml_cc["cc"]["sun"]))
  {
    $cc->add_item(str('WEATHER_SUNRISE'),  $xml_cc["cc"]["sun"]["r"]);
    $cc->add_item(str('WEATHER_SUNSET'),   $xml_cc["cc"]["sun"]["s"]);
  }

  if ( $xml_cc["cc"]["icon"] == '-')
  {
    $xml_cc["cc"]["icon"] = 'na';
    $xml_cc["cc"]["tmp"]  = chr(151);
  }

  $menu = new menu(false);
  $menu->add_item(str('WEATHER_5DAY'),'weather_fc.php?loc='.$loc_id);
  $menu->add_item(str('WEATHER_LOCATION'),'weather_city_list.php',true);

  // Display Weather Icon and statistics
  page_header($title);

  echo '<table cellspacing=0 border=0 cellpadding=0 width="100%">
          <tr>
            <td align="center" width="40%">'
              .img_gen(SC_LOCATION.'/weather/large/'.$xml_cc["cc"]["icon"].'.gif',200,260,false,false,'RESIZE').
              font_tags(FONTSIZE_BODY).'<br>'.$xml_cc["cc"]["tmp"].chr(176).$xml_cc["head"]["ut"].'
                  <br>'.$xml_cc["cc"]["t"].'</font>
            </td>
            <td align="center" width="60%">'.
              font_tags(FONTSIZE_BODY).str('WEATHER_CURRENT').'</font><p>';
              $cc->display();
  echo '    </td>
          </tr>
        </table>
        <table cellspacing=0 border=0 cellpadding=0 width="100%">
          <tr>
            <td width="'.convert_x(640).'">';
              $menu->display(1, 560);
  echo '    </td>
            <td align="center" valign="top">'.img_gen(SC_LOCATION.'/weather/logo.gif',130,130,false,false,'RESIZE').'</td>
          </tr>
        </table>';

  if ( get_user_pref("weather_units") == 'm')
    $buttons[] = array('text'=>str('WEATHER_IMPERIAL'), 'url'=>'weather_cc.php?loc='.$loc_id.'&units=s&hist='.PAGE_HISTORY_REPLACE );
  else
    $buttons[] = array('text'=>str('WEATHER_METRIC'), 'url'=>'weather_cc.php?loc='.$loc_id.'&units=m&hist='.PAGE_HISTORY_REPLACE );

  page_footer(page_hist_previous(), $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
