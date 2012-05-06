<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/trailers/film_trailer_feeds.php'));

  function display_film_trailer_menu($items)
  {
    $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE;
    $end        = min($start+MAX_PER_PAGE,count($items));
    $last_page  = ceil(count($items)/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_params(current_url(), array('page'=>($page > 1 ? ($page-1) : $last_page))) );
      $menu->add_down( url_add_params(current_url(), array('page'=>($page < $last_page ? ($page+1) : 1))) );
    }

    for ($i=$start; $i<$end; $i++)
      $menu->add_item($items[$i]["title"], 'film_trailer_browse.php?genre='.$items[$i]["url"], true);

    // Display the menu
    $menu->display(1, 520);
  }

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  if ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'genres' )
    $subtitle = str('BROWSE_GENRE');
  else
    $subtitle = '';

  page_header( str('FILM_TRAILERS'), $subtitle );

  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('FILMTRAILER',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  if ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'genres' )
  {
    display_film_trailer_menu( get_film_trailer_genres() );
  }
  else
  {
    $menu = new menu();
    $menu->add_item(str('NOW-50'), 'film_trailer_browse.php?feed=now-50', true);
    $menu->add_item(str('COMING-50'), 'film_trailer_browse.php?feed=coming-50', true);
    $menu->add_item(str('NEWEST-50'), 'film_trailer_browse.php?feed=newest-50', true);
    $menu->add_item(str('BROWSE_GENRE'), 'film_trailer.php?menu=genres', true);
    $menu->display(1, 520);

    // Output ABC buttons
    $buttons[] = array('text' => str('SEARCH'), 'url' => 'film_trailer_search.php');
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
