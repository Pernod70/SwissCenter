<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/youporn.php'));

  function display_youporn_categories($items)
  {
    $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE;
    $end        = min($start+MAX_PER_PAGE,count($items[2]));
    $last_page  = ceil(count($items[2])/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items[2]) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param(current_url(), 'page', ($page > 1 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param(current_url(), 'page', ($page < $last_page ? ($page+1) : 1)) );
    }

    for ($i=$start; $i<$end; $i++)
      $menu->add_item($items[2][$i], url_add_params('youporn_browse.php', array('type'=>'category', 'cat'=>$items[1][$i], 'sort'=>'time')), true);

    // Display the menu
    $menu->display(1, 520);
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  $category = isset($_REQUEST["category"]) ? $_REQUEST["category"] : '';


  // Display the page
  page_header(str('YOUPORN'));
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('YOUPORN',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  if ( isset($_REQUEST["cat"]) && $_REQUEST["cat"] == 'categories' )
  {
    $youporn = new YouPorn();
    $items = $youporn->getCategories();
    display_youporn_categories( $items );
  }
  else
  {
    $menu = new menu();
    $menu->add_item( str('NEW_VIDEOS'),  url_add_params('youporn_browse.php', array('type'=>'browse', 'sort'=>'rating')), true);
    $menu->add_item( str('TOP_RATED'),   url_add_params('youporn_browse.php', array('type'=>'top_rated', 'time'=>'week')), true);
    $menu->add_item( str('MOST_VIEWED'), url_add_params('youporn_browse.php', array('type'=>'most_viewed', 'time'=>'week')), true);
    $menu->add_item( str('CATEGORIES'),  url_add_params('youporn_menu.php',   array('cat'=>'categories')), true);
    $menu->add_item( str('SEARCH'),      url_add_params('youporn_search.php', array('type'=>'search')), true);
    $menu->display( 1, 520 );
  }

  echo '    </td>
          </tr>
        </table>';

  // Output ABC buttons
  $buttons = array();

  // Make sure the "back" button goes to the correct page:
  page_footer(page_hist_previous(), $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
