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

  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = url_remove_params(current_url(),array('page','thumbs'));

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
    $cat  = isset($_REQUEST["cat"]) ? $_REQUEST["cat"] : '';
    $apple = new AppleTrailers();
    $trailers = $apple->getFeed($feed);
    switch ($feed)
    {
      case 'popular/most_pop': { $subtitle = str('WEEKEND_BOXOFFICE'); break; }
      case 'opening':          { $subtitle = str('OPENING_THISWEEK'); break; }
      case 'most_pop':         { $subtitle = str('MOST_POPULAR'); break; }
      default:                 { $subtitle = str($feed); }
    }
  }

  // Refine trailers to selected category
  if ( !empty($cat) )
  {
    foreach ($trailers["items"] as $id=>$item)
    {
      if ( $item["category"] == $cat )
      {
        $trailers = $trailers["items"][$id]["thumbnails"];
        break;
      }
    }
  }

  if ( count($trailers) == 0 )
  {
    page_inform(2,page_hist_back_url(),str('APPLE_TRAILERS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $trailer_list = array();
    foreach ($trailers as $id=>$trailer)
    {
      $text = utf8_decode($trailer["title"]).(count($trailer["trailers"]) > 1 ? ' ('.count($trailer["trailers"]).')' : '');
      if ( !empty($cat) )
        $url = url_add_params('apple_trailer_selected.php', array('query'=>$trailer["title"]));
      else
        $url = url_add_params('apple_trailer_selected.php', array('feed'=>$feed, 'id'=>$id));
      $trailer_list[] = array('thumb'=>$trailer["poster"], 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('APPLE_TRAILERS'), $subtitle);

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
    page_footer(page_hist_back_url(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
