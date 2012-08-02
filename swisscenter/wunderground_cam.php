<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/wunderground.php'));

  // Users chosen location
  $loc_id = $_REQUEST["loc"];

  // Selected webcam id
  $cam_id = isset($_REQUEST["camid"]) ? $_REQUEST["camid"] : '';

  $wunderground = new Wunderground();

  // Get the weather feed
  $feed = $wunderground->getRequest(array('conditions', 'forecast7day', 'satellite', 'webcams'), $loc_id);

  if (isset($feed['response']['features']['webcams']) && $feed['response']['features']['webcams'] == 1)
  {
     $webcams = $feed["webcams"];
     $current = $feed["current_observation"];

    // Build list of available webcams
    $menu = new menu();
    $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $image_url = SC_LOCATION.'images/dot.gif';
    $image_title = '';
    foreach ($webcams as $webcam)
    {
      $title = (!empty($webcam["neighborhood"]) ? $webcam["neighborhood"].', ' : '').$webcam["city"];
      if ($cam_id == $webcam["camid"])
      {
        $image_url = $webcam["CURRENTIMAGEURL"].'.jpg';
        $image_title = $title;
      }
      $menu->add_item($title, url_add_params(current_url(), array('camid'=>$webcam["camid"], 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    // Display available and selected webcam image
    page_header(str('WEATHER_WEBCAM'), $image_title);

    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(290).'" align="center">
              <table width="100%"><tr><td height="'.convert_y(10).'"></td></tr><tr><td valign=top>
                <center>'.img_gen($image_url,400,400).'</center>
              </td></tr></table></td>
              <td valign="top">';
              $menu->display_page( $page,1,480 );
    echo '    </td></td></table>';
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
