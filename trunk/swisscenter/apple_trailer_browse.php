<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/apple_trailers.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $back_url = apple_trailer_page_params();
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = url_remove_params(current_url(),array('page','thumbs','del'));

  // Get the list of trailers.
  if (isset($_REQUEST["studio"]))
  {
    $feed = 'studios';
    $subtitle = rawurldecode($_REQUEST["studio"]);
    $trailers = get_apple_trailers_by_studio($subtitle);
  }
  elseif (isset($_REQUEST["genre"]))
  {
    $feed = 'genres';
    $subtitle = rawurldecode($_REQUEST["genre"]);
    $trailers = get_apple_trailers_by_genre($subtitle);
  }
  else
  {
    $feed = $_REQUEST["feed"];
    $apple = new AppleTrailers();
    $trailers = $apple->getFeed($feed);
    switch ($feed)
    {
      case 'most_pop': { $subtitle = str('MOST_POPULAR'); break; }
      default:         { $subtitle = str($feed); }
    }
  }

  if ( count($trailers) == 0 )
  {
    page_inform(2,$back_url,str('APPLE_TRAILERS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $trailer_list = array();
    foreach ($trailers as $id=>$trailer)
    {
      $text = utf8_decode($trailer["title"]).(count($trailer["trailers"]) > 1 ? ' ('.count($trailer["trailers"]).')' : '');
      $url  = url_add_params('apple_trailer_selected.php', array('feed'=>$feed, 'id'=>$id));
      $trailer_list[] = array('thumb'=>$trailer["poster"], 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('APPLE_TRAILERS'), $subtitle);

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(url_add_param(current_url(), 'del', 1), $trailer_list, $page);

    // Display ABC buttons
    $buttons = array();
    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'del'=>1)) );
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'del'=>1)) );

    // Make sure the "back" button goes to the correct page
    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
