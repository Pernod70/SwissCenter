<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/trakt.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $type     = (isset($_REQUEST["type"]) ? $_REQUEST["type"] : 'movies');
  $this_url = url_remove_params(current_url(),array('page','thumbs'));

  // Get the list of recommendations.
  $trakt = new Trakt();

  $user = db_row("select u.user_id, u.name, un.value username, pw.value password
                  from users u join user_prefs un on (un.user_id = u.user_id and un.name = 'TRAKT_USERNAME')
                               join user_prefs pw on (pw.user_id = u.user_id and pw.name = 'TRAKT_PASSWORD')
                  where u.user_id=".get_current_user_id());

  $trakt->setAuth($user['USERNAME'], $user['PASSWORD']);

  if ($type == 'movies')
    $items = $trakt->recommendationsMovies(array('hide_collected' => true));
  else
    $items = $trakt->recommendationsShows(array('hide_collected' => true));

  if ( count($items) == 0 )
  {
    page_inform(2,page_hist_previous(),str('TRAKT'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $item_list = array();
    foreach ($items as $item)
    {
      $text = $item['title'];
      if ($type == 'movies')
        $url = url_add_params('trakt_selected.php', array('type' => $type, 'imdb_id' => $item['imdb_id']));
      else
        $url = url_add_params('trakt_selected.php', array('type' => $type, 'tvdb_id' => $item['tvdb_id']));
      $item_list[] = array('thumb'=>$item['images']['poster'], 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('TRAKT'), str('RECOMMENDATIONS'));

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $item_list, $page);

    // Display ABC buttons
    $buttons = array();
    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)) );
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)) );

    // Make sure the "back" button goes to the correct page
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
