<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/wunderground.php'));

  // Users chosen location
  $loc_id = $_REQUEST["loc"];

  $wunderground = new Wunderground();

  // Get the weather feed
  $feed = $wunderground->getRequest(array('conditions', 'forecast7day', 'satellite', 'webcams'), $loc_id);

  if (isset($feed['response']['features']['satellite']) && $feed['response']['features']['satellite'] == 1)
  {
    $satellite = $feed["satellite"];
    $current   = $feed["current_observation"];

    // Set the size of images to be requested
    $size = 500;
    str_replace(300, $size, $satellite["image_url"]);
    str_replace(300, $size, $satellite["image_url_vis"]);

    // Display satellite images
    page_header($current["display_location"]["full"]);

    echo '<table cellspacing=0 border=0 cellpadding=0 width="100%">
            <tr>
              <td align="center" width="40%">'
                .img_gen($satellite["image_url"].'&ext=.png',$size,$size,false,false,'RESIZE').
                font_tags(FONTSIZE_BODY).'<br>'.str('WEATHER_SATELLITE').'</font>
              </td>
              <td align="center" width="40%">'
                .img_gen($satellite["image_url_vis"].'&ext=.png',$size,$size,false,false,'RESIZE').
                font_tags(FONTSIZE_BODY).'<br>'.str('WEATHER_VISIBILITY').'</font>
              </td>
            </tr>
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
