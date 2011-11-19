<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/tfl_feeds.php'));

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  // Page headings
  page_header( str('TFL') );

  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('TFL',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  $menu = new menu();
  $menu->add_item(str('TFL_TUBE_THIS_WEEKEND'), 'tfl_tube.php');
  $menu->add_item(str('TFL_LIVE_TRAFFIC_CAMERA'), 'tfl_cameras.php', true);
  $menu->display(1, 520);

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page
  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
