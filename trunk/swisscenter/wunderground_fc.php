<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/wunderground.php'));

  // Users chosen location
  $loc_id = $_REQUEST["loc"];

  $wunderground = new Wunderground();

  // Get the weather feed
  $feed = $wunderground->getRequest(array('conditions', 'forecast7day', 'satellite', 'webcams'), $loc_id);
  send_to_log(2,'$$forecast',$feed);

  if (isset($feed['response']['features']['forecast7day']) && $feed['response']['features']['forecast7day'] == 1)
  {
    $forecast = $feed["forecast"]["simpleforecast"];
    $current  = $feed["current_observation"];

    // Display Weather Icon and statistics
    page_header($current["display_location"]["full"]);

    $menu = new menu();
    $menu->add_item( str('WEATHER_CURRENT') ,'wunderground_cc.php?loc='.$loc_id);

    echo '<table border=0 cellspacing=0 cellpadding=0 width="100%">
            <tr>';

    for ($i=0; $i<7; $i++)
    {

      $units = 'celsius';
      $fc = new infotab();
      $fc->set_col_attrib(1,'width',convert_x(54));
      $fc->set_col_attrib(1,'align','right');
      $fc->set_col_attrib(2,'align','right');
      $fc->set_col_attrib(2,'width',convert_x(70));
      $fc->add_item('Hi', $forecast["forecastday"][$i]['high'][$units].chr(176));
      $fc->add_item('Lo', $forecast['forecastday'][$i]['low'][$units].chr(176));
  //    $fc->add_item('', $forecast['forecastday'][$i]['conditions']);
      $day = $forecast['forecastday'][$i]['date']['weekday_short'];

      echo '<td><center><font color="'.style_value("PAGE_TITLE_COLOUR").'" size="4">'.$day.'</font><p>'
           .img_gen($forecast['forecastday'][$i]['icon_url'],100,130,false,false,'RESIZE').'<br>';
            $fc->display( 1,100).'<br>'.$forecast['forecastday'][$i]['conditions'];
      echo '</center><p></td>';
    }

    echo '  </tr>
          </table>';
    echo '<table cellspacing=0 border=0 cellpadding=0 width="100%">
            <tr>
              <td width="'.convert_x(640).'"></td>
              <td align="center" valign="top">'.img_gen($current["image"]["url"],260,160,false,false,'RESIZE').'</a></td>
            </tr>
          </table>';

    // Make sure the "back" button goes to the correct page:
    page_footer( page_hist_previous() );
  }
  else
  {
    page_inform(str('WUNDERGROUND_ERROR'), page_hist_previous());
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
