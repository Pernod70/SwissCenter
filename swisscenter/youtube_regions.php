<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/youtube.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $youtube = new phpYouTube();
  $regions = $youtube->getRegions();
//    $regions    = array_merge( array(str($categories, $special);

  $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
  $start      = ($page-1) * MAX_PER_PAGE;
  $end        = min($start+MAX_PER_PAGE,count($regions));
  $last_page  = ceil(count($regions)/MAX_PER_PAGE);

  $menu = new menu();

  if (count($regions) > MAX_PER_PAGE)
  {
    $menu->add_up( url_add_param(current_url(),'page',($page > 1 ? ($page-1) : $last_page)) );
    $menu->add_down( url_add_param(current_url(),'page',($page < $last_page ? ($page+1) : 1)) );
  }

  for ($i=$start; $i<$end; $i++)
  {
    $menu->add_item($regions[$i]["COUNTRY"], url_add_params('youtube_menu.php', array('region'=>$regions[$i]["REGION_ID"], 'hist'=>PAGE_HISTORY_DELETE)));
  }

  // Display the page
  page_header(str('YOUTUBE'));

  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('YOUTUBE',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  $menu->display(1, 520);

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_back_url() );

 /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>