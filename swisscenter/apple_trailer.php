<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/apple_trailers.php'));

  function display_apple_trailer_menu($items)
  {
    $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE;
    $end        = min($start+MAX_PER_PAGE,count($items));
    $last_page  = ceil(count($items)/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_params(current_url(), array('page'=>($page > 1 ? ($page-1) : $last_page), 'del'=>1)) );
      $menu->add_down( url_add_params(current_url(), array('page'=>($page < $last_page ? ($page+1) : 1), 'del'=>1)) );
    }

    for ($i=$start; $i<$end; $i++)
      $menu->add_item($items[$i]["title"], $items[$i]["url"], true);

    // Display the menu
    $menu->display(1, 520);
  }

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  if ( isset($_REQUEST["menu"]) )
  {
    // Set subtitle for current menu
    switch ($_REQUEST["menu"])
    {
      case 'genres':            { $subtitle = str('BROWSE_GENRE'); break; }
      case 'studios':           { $subtitle = str('BROWSE_STUDIO'); break; }
      case 'weekendboxoffice':  { $subtitle = str('WEEKEND_BOXOFFICE'); break; }
      case 'openingthisweek':   { $subtitle = str('OPENING_THISWEEK'); break; }
    }
  }
  else
  {
    $subtitle = '';
  }

  page_header( str('APPLE_TRAILERS'), $subtitle );
  $buttons = array();

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
    display_apple_trailer_menu( get_apple_trailers_genres() );
  }
  elseif ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'studios' )
  {
    $back_url = apple_trailer_page_params();
    display_apple_trailer_menu( get_apple_trailers_studios() );
  }
  elseif ( isset($_REQUEST["menu"]) )
  {
    $back_url = apple_trailer_page_params();
    display_apple_trailer_menu( get_apple_trailers_page_section($_REQUEST["menu"]) );
  }
  else
  {
    $back_url = 'internet_tv.php';
    apple_trailer_hist_init(url_remove_param(current_url(), 'del'));

    $menu = new menu();
    $menu->add_item(str('WEEKEND_BOXOFFICE'), 'apple_trailer.php?menu=weekendboxoffice', true);
    $menu->add_item(str('OPENING_THISWEEK'),  'apple_trailer.php?menu=openingthisweek', true);
    $menu->add_item(str('JUST_ADDED'),        'apple_trailer_browse.php?feed=just_added', true);
    $menu->add_item(str('EXCLUSIVE'),         'apple_trailer_browse.php?feed=exclusive', true);
    $menu->add_item(str('JUST_HD'),           'apple_trailer_browse.php?feed=just_hd', true);
    $menu->add_item(str('MOST_POPULAR'),      'apple_trailer_browse.php?feed=most_pop', true);
    $menu->add_item(str('BROWSE_GENRE'),      'apple_trailer.php?menu=genres', true);
    $menu->add_item(str('BROWSE_STUDIO'),     'apple_trailer.php?menu=studios', true);
    $menu->display(1, 520);

    // Output ABC buttons
    $buttons[] = array('text' => str('SEARCH'), 'url' => 'apple_trailer_search.php');
  }

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page
  page_footer($back_url, $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
