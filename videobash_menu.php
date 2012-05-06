<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/videobash.php'));

  function display_videobash_categories($items, $type = 'videos')
  {
    $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start = ($page-1) * MAX_PER_PAGE;
    $end = min($start+MAX_PER_PAGE,count($items[2]));
    $last_page = ceil(count($items[2])/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items[2]) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param(current_url(), 'page', ($page > 1 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param(current_url(), 'page', ($page < $last_page ? ($page+1) : 1)) );
    }

    for ($i=$start; $i<$end; $i++)
      $menu->add_item(ucwords($items[2][$i]), url_add_params('videobash_browse.php', array('type'=>$type, 'cat'=>$items[1][$i], 'sort'=>'mr')), true);

    // Display the menu
    $menu->display(1, 520);
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS", "FULL"); }

  $type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : 'videos';

  // Display the page
  page_header(str('VIDEOBASH'), str($type));
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('VIDEOBASH',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  $videobash = new VideoBash();
  $items = $videobash->getCategories($type);
  display_videobash_categories( $items, $type );

  echo '    </td>
          </tr>
        </table>';

  // Output ABC buttons
  $buttons = array();
  if ($type == 'videos')
    $buttons[] = array('text'=>str('PHOTOS'), 'url'=>url_add_params('videobash_menu.php', array('type'=>'photos', 'hist'=>PAGE_HISTORY_REPLACE)));
  else
    $buttons[] = array('text'=>str('VIDEOS'), 'url'=>url_add_params('videobash_menu.php', array('type'=>'videos', 'hist'=>PAGE_HISTORY_REPLACE)));
  $buttons[] = array('text'=>str('SEARCH'), 'url'=>url_add_param('videobash_search.php', 'type', $type));

  // Make sure the "back" button goes to the correct page:
  page_footer(page_hist_previous(), $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>