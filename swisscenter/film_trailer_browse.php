<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/trailers/film_trailer_feeds.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $back_url = page_hist_previous();
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = url_remove_params(current_url(),array('page','thumbs'));

  $filmtrailer = new FilmTrailer();
  $filmtrailer->set_region_code(substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2));

  // Get the list of trailers.
  if (isset($_REQUEST["genre"]))
  {
    $feed = 'genres';
    $subtitle = rawurldecode($_REQUEST["genre"]);
    $trailers = $filmtrailer->getGenreFeed($subtitle);
  }
  else
  {
    $feed = $_REQUEST["feed"];
    $subtitle = str($feed);
    $trailers = $filmtrailer->getFeed($feed);
  }

  if ( count($trailers) == 0 )
  {
    page_inform(2,$back_url,str('FILM_TRAILERS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $trailer_list = array();
    foreach ($trailers as $trailer)
    {
      $text = $trailer["ORIGINAL_TITLE"].(count($trailer["CLIPS"]) > 1 ? ' ('.count($trailer["CLIPS"]).')' : '');
      $url  = url_add_param('film_trailer_selected.php', 'id', $trailer["MOVIE_ID"]);
      $trailer_list[] = array('thumb'=>$trailer["PICTURES"][1]['URL'], 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('FILM_TRAILERS'), $subtitle);

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $trailer_list, $page);

    // Display ABC buttons
    $buttons = array();
    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)) );
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)) );

    // Make sure the "back" button goes to the correct page
    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
