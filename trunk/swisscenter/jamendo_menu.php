<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/resources/audio/jamendo.php'));

  function display_jamendo_categories($items)
  {
    $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $start = ($page-1) * MAX_PER_PAGE;
    $end = min($start+MAX_PER_PAGE,count($items));
    $last_page = ceil(count($items)/MAX_PER_PAGE);

    $menu = new menu();

    if (count($items) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param(current_url(), 'page', ($page > 1 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param(current_url(), 'page', ($page < $last_page ? ($page+1) : 1)) );
    }

    for ($i=$start; $i<$end; $i++)
      $menu->add_item($items[$i], url_add_params('jamendo_browse.php', array('unit'=>'track', 'fields'=>rawurlencode('id+name+stream+album_id+album_name+album_image+artist_id+artist_name'), 'params'=>rawurlencode('?n=100&order=ratingmonth_desc&tag_idstr='.$items[$i]))), true);

    // Display the menu
    $menu->display(1, 520);
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  // Display the page
  page_header(str('JAMENDO'));
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
  				<tr>
  					<td valign=top width="'.convert_x(280).'" align="left"><br>
  						'.img_gen(style_img('JAMENDO',true,false),280,450).'
  					</td>
  					<td width="'.convert_x(20).'"></td>
  					<td valign="top">';

  if ( isset($_REQUEST["cat"]) && $_REQUEST["cat"] == 'show' )
  {
    $jamendo = new Jamendo();
    $tags = $jamendo->getQuery('name', 'tag');
    display_jamendo_categories( $tags );
  }
  else
  {
    $menu = new menu();
    $menu->add_item( str('MOST_RECENT'),   url_add_params('jamendo_browse.php', array('type'=>'most_recent', 'unit'=>'album', 'fields'=>rawurlencode('id+name+stream+album_id+album_name+album_image+artist_id+artist_name'), 'params'=>rawurlencode('?n=100&order=releasedate_desc'))), true);
    $menu->add_item( str('MOST_POPULAR'),  url_add_params('jamendo_browse.php', array('type'=>'most_popular', 'unit'=>'track', 'fields'=>rawurlencode('id+name+stream+album_id+album_name+album_image+artist_id+artist_name'), 'params'=>rawurlencode('?n=100&order=ratingmonth_desc'))), true);
    $menu->add_item( str('MOST_LISTENED'), url_add_params('jamendo_browse.php', array('type'=>'most_listened', 'unit'=>'track', 'fields'=>rawurlencode('id+name+stream+album_id+album_name+album_image+artist_id+artist_name'), 'params'=>rawurlencode('?n=100&order=listenedtotal_desc'))), true);
    $menu->add_item( str('CATEGORIES'),    url_add_params('jamendo_menu.php',   array('cat'=>'show')), true);
    $menu->add_item( str('SEARCH'),        url_add_params('jamendo_search.php', array('type'=>'search')), true);
    $menu->display( 1, 520 );
  }

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer(page_hist_previous());

/**************************************************************************************************
                                                End of file
 **************************************************************************************************/
?>