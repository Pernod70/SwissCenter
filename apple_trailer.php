<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/trailers/apple_trailers.php'));

  function display_apple_trailer_menu($items)
  {
    $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE;
    $end        = min($start+MAX_PER_PAGE,count($items));
    $last_page  = ceil(count($items)/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param(current_url(), 'page', ($page > 1 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param(current_url(), 'page', ($page < $last_page ? ($page+1) : 1)) );
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
      case 'genres':  { $subtitle = str('BROWSE_GENRE'); break; }
      case 'studios': { $subtitle = str('BROWSE_STUDIO'); break; }
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
    display_apple_trailer_menu( get_apple_trailers_genres() );
  }
  elseif ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'studios' )
  {
    display_apple_trailer_menu( get_apple_trailers_studios() );
  }
  else
  {
    $menu = new menu();
    $menu->add_item(str('WEEKEND_BOXOFFICE'), 'apple_trailer_browse.php?feed='.rawurlencode('popular/most_pop').'&cat='.rawurlencode('Weekend Box Office'), true);
    $menu->add_item(str('OPENING_THISWEEK'),  'apple_trailer_browse.php?feed=opening&cat='.rawurlencode('Opening This Week'), true);
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
  page_footer(page_hist_previous(), $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
