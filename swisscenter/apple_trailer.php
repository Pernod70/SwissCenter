<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/apple_trailers.php'));

  function display_apple_trailer_menu($items, $next_page)
  {
    $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE;
    $end        = min($start+MAX_PER_PAGE,count($items));
    $last_page  = ceil(count($items)/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param(current_url(),'page',($page > 1 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param(current_url(),'page',($page < $last_page ? ($page+1) : 1)) );
    }

    for ($i=$start; $i<$end; $i++)
      $menu->add_item($items[$i], $next_page.rawurlencode($items[$i]), true);

    // Display the menu
    $menu->display(1, 520);
  }

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  page_header( str('APPLE_TRAILERS') );

  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('APPLE_TRAILERS',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  if ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'genres' )
  {
    $back_url = apple_trailer_page_params();
    display_apple_trailer_menu(get_apple_trailers_genres(), 'apple_trailer_browse.php?genre=');
  }
  elseif ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'studios' )
  {
    $back_url = apple_trailer_page_params();
    display_apple_trailer_menu(get_apple_trailers_studios(), 'apple_trailer_browse.php?studio=');
  }
  else
  {
    $back_url = 'internet_tv.php';
    apple_trailer_hist_init(url_remove_param(current_url(), 'del'));

    $menu = new menu();
    $menu->add_item(str('JUST_ADDED'),    'apple_trailer_browse.php?feed=just_added', true);
    $menu->add_item(str('EXCLUSIVE'),     'apple_trailer_browse.php?feed=exclusive', true);
    $menu->add_item(str('JUST_HD'),       'apple_trailer_browse.php?feed=just_hd', true);
    $menu->add_item(str('MOST_POPULAR'),  'apple_trailer_browse.php?feed=most_pop', true);
    $menu->add_item(str('BROWSE_GENRE'),  'apple_trailer.php?menu=genres', true);
    $menu->add_item(str('BROWSE_STUDIO'), 'apple_trailer.php?menu=studios', true);
    $menu->add_item(str('SEARCH'),        'apple_trailer_search.php', true);
    $menu->display(1, 520);
  }

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page
  page_footer($back_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
