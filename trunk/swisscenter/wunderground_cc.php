<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/wunderground.php'));

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
      $buttons[] = array('text'=>str('WEATHER_HOME'), 'url'=>'wunderground_cc.php?&home='.$loc_id );
  }
  else
  {
    // User is viewing the default location (their home city).
    $loc_id = 'autoip'; //weather_home();
  }

  // Change between standard/metric measurements.
  if (!empty($_REQUEST["icons"]))
    set_user_pref('WEATHER_ICONS',$_REQUEST["icons"]);

  $wunderground = new Wunderground();

  // Get the weather feed
  $feed = $wunderground->getRequest(array('conditions', 'forecast7day', 'satellite', 'webcams'), $loc_id);

  if (isset($feed['response']['features']['conditions']) && $feed['response']['features']['conditions'] == 1)
  {
    $current = $feed["current_observation"];

    $icon_url = $current["icon_url"]; // 'http://icons.wxug.com/i/c/h/'.$current["icon"].'.gif';

    // Build Current Conditions information table
    $cc = new infotab();
    $cc->add_item(str('WEATHER_FEELS_LIKE'),    $current["windchill_string"]);
    $cc->add_item(str('WEATHER_HUMIDITY'),      $current["relative_humidity"]);
    $cc->add_item(str('WEATHER_PRESSURE'),      $current["pressure_in"].' in ('.$current["pressure_mb"].' mb)');
    $cc->add_item(str('WEATHER_WIND'),          $current["wind_string"]);
    $cc->add_item(str('WEATHER_VISIBILITY'),    $current["visibility_mi"].' mi ('.$current["visibility_km"].' km)');
    $cc->add_item(str('WEATHER_DEWPOINT'),      $current["dewpoint_string"]);
    $cc->add_item(str('WEATHER_PRECIPITATION'), $current["precip_today_string"]);

    $menu = new menu(false);
    $menu->add_item(str('WEATHER_7DAY'), 'wunderground_fc.php?loc='.$loc_id, true);
    $menu->add_item(str('WEATHER_SATELLITE'), 'wunderground_sat.php?loc='.$loc_id, true);
    $menu->add_item(str('WEATHER_WEBCAM'), 'wunderground_cam.php?loc='.$loc_id, true);
    $menu->add_item(str('WEATHER_LOCATION'), 'weather_city_list.php',true);

    // Display Weather Icon and statistics
    page_header($current["display_location"]["full"], $current["observation_time"]);

    echo '<table cellspacing=0 border=0 cellpadding=0 width="100%">
            <tr>
              <td align="center" width="40%">'
                .img_gen($icon_url,200,260,false,false,'RESIZE').
                font_tags(FONTSIZE_BODY).'<br>'.$current["temperature_string"].'
                    <br>'.$current["weather"].'</font>
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
                $menu->display(1, 400);
    echo '    </td>
              <td align="center" valign="middle">'.img_gen($current["image"]["url"],260,160,false,false,'RESIZE').'</a></td>
            </tr>
          </table>';

    $buttons = array();
    $buttons[] = array('text'=>str('WEATHER_ICONS'), 'url'=>'weather_cc.php?loc='.$loc_id.'&icons=m&hist='.PAGE_HISTORY_REPLACE );

    // Make sure the "back" button goes to the correct page:
    page_footer( page_hist_previous(), $buttons );
  }
  else
  {
    page_inform(str('WUNDERGROUND_ERROR'), page_hist_previous());
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
