<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/youtube.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $back_url = page_hist_previous();
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = current_url();

  $youtube   = new phpYouTube();
  $feed_type = $_REQUEST["type"];
  $sort      = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : 'name';

  // Query YouTube for required data
  switch ($feed_type)
  {
    // User feeds
    case 'favorites':
    case 'newsubscriptionvideos':
    case 'playlists':
    case 'subscriptions':
    case 'uploads':
      $username = $_REQUEST["username"];
      $feed = $youtube->usersFeed($username, $feed_type);
      $title = utf8_decode($feed['feed']['title']['$t']);
      $subtitle = $username.' -> '.str($feed_type).' -> '.str('SORT_'.strtoupper($sort));
      break;

    // Playlist feeds
    case 'playlist':
      $username = $_REQUEST["username"];
      $time = isset($_REQUEST["time"]) ? $_REQUEST["time"] : 'all_time';
      $playlist_id = $_REQUEST["playlist_id"];
      $feed = $youtube->playlistFeed($playlist_id);
      $title = utf8_decode($feed['feed']['title']['$t']);
      $subtitle = isset($feed['feed']['subtitle']['$t']) ? $feed['feed']['subtitle']['$t'] : $username.' -> '.str($feed_type).' -> '.str($time).' -> '.str('SORT_'.strtoupper($sort));
      break;

    // Standard video feeds
    case 'top_rated':
    case 'top_favorites':
    case 'most_viewed':
    case 'most_popular':
    case 'most_recent':
    case 'most_discussed':
    case 'most_linked':
    case 'most_responded':
    case 'recently_featured':
    case 'watch_on_mobile':
      $time = isset($_REQUEST["time"]) ? $_REQUEST["time"] : 'all_time';
      $category = isset($_REQUEST["cat"]) ? $_REQUEST["cat"] : '';
      $region = isset($_REQUEST["region"]) ? $_REQUEST["region"] : '';
      $feed = $youtube->standardFeed($feed_type, $time, $category, $region);
      $title = utf8_decode($feed['feed']['title']['$t']);
      $subtitle = str($feed_type).' -> '.str($time).' -> '.str('SORT_'.strtoupper($sort));
      break;

    // Channel feeds
    case 'channels':
      $feed = $youtube->channelSearch();
      $title = utf8_decode($feed['feed']['title']['$t']);
      $subtitle = '';
      break;
  }

  if ( count($feed['feed']['entry']) == 0 )
  {
    page_inform(2, $back_url, $title, str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $entry_list = array();
    $video_list = array();

    // Add entries from selected feed
    foreach ($feed['feed']['entry'] as $entry)
    {
      // Check [app$control] tag for rejected or blocked entry
      if ( !isset($entry['app$control']) || $entry['app$control']['yt$state']['name'] == 'restricted' )
      {
        switch ($feed_type)
        {
          case 'favorites':
          case 'newsubscriptionvideos':
          case 'playlist':
          case 'uploads':
          case 'top_rated':
          case 'top_favorites':
          case 'most_viewed':
          case 'most_popular':
          case 'most_recent':
          case 'most_discussed':
          case 'most_linked':
          case 'most_responded':
          case 'recently_featured':
          case 'watch_on_mobile':
            $text = utf8_decode($entry['media$group']['media$title']['$t']);
            $url  = url_add_param('youtube_video_selected.php', 'video_id', $entry['media$group']['yt$videoid']['$t']);
            $thumb = youtube_thumbnail_url($entry['media$group']['media$thumbnail']);
            $video_list[] = $entry['media$group']['yt$videoid']['$t'];
            break;

          case 'playlists':
            $text = utf8_decode($entry['title']['$t']).' ('.$entry['yt$countHint']['$t'].')';
            $url  = url_add_params('youtube_browse.php', array('type'=>'playlist', 'playlist_id'=>$entry['yt$playlistId']['$t']));
            $thumb = false;
            break;

          case 'channels':
            $text = utf8_decode($entry['author'][0]['name']['$t']).' ('.$entry['gd$feedLink'][0]['countHint'].')';
            $url  = url_add_params('youtube_browse.php', array('username'=>utf8_decode($entry['author'][0]['name']['$t']), 'type'=>'uploads'));
            $thumb = false;
            break;

          case 'subscriptions':
            // Subscriptions can contain different entry types
            switch ( youtube_category_scheme($entry, 'http://gdata.youtube.com/schemas/2007/subscriptiontypes.cat') )
            {
              case 'channel':
                $text = utf8_decode($entry['yt$username']['$t']).' ('.$entry['yt$countHint']['$t'].')';
                $url  = url_add_params('youtube_browse.php', array('username'=>utf8_decode($entry['yt$username']['$t']), 'type'=>'uploads'));
                break;

              case 'favorites':
                $text = utf8_decode($entry['yt$username']['$t']).'\'s '.str('FAVORITES');
                $url  = url_add_params('youtube_browse.php', array('username'=>utf8_decode($entry['yt$username']['$t']), 'type'=>'favorites'));
                break;

              case 'playlist':
                $text = '['.utf8_decode($entry['yt$playlistTitle']['$t'].']');
                $url  = url_add_params('youtube_browse.php', array('type'=>'playlist', 'playlist_id'=>$entry['yt$playlistId']['$t']));
                break;

              case 'query':
                $text = utf8_decode($entry['yt$queryString']['$t']);
                break;

              case 'user':
                $text = utf8_decode($entry['yt$username']['$t']);
                $url  = url_add_params('youtube_browse.php', array('username'=>utf8_decode($entry['yt$username']['$t']), 'type'=>'uploads'));
                break;
            }
            $thumb = youtube_thumbnail_url($entry['media$thumbnail']);
            break;
        }

        $entry_list[] = array('thumb'    => empty($thumb) ? $feed['feed']['logo']['$t'] : $thumb,
                              'text'     => $text,
                              'url'      => $url,
                              'name'     => strtolower($text),
                              'time'     => $entry['media$group']['yt$duration']['seconds'],
                              'date'     => $entry['media$group']['yt$uploaded']['$t'],
                              'views'    => $entry['yt$statistics']['viewCount'],
                              'favorite' => $entry['yt$statistics']['favoriteCount'],
                              'rating'   => $entry['gd$rating']['average'] * $entry['gd$rating']['numRaters']);
      }
    }

    // Order the feed list
    array_sort($entry_list, $sort);
    if ($sort !== 'name')
      $entry_list = array_reverse($entry_list);

    // Add a New Videos option for Subscriptions
    if ( $feed_type == 'subscriptions' )
    {
      $entry_list = array_merge(array(array('thumb' => $feed['feed']['logo']['$t'],
                                            'text'  => str('NEWSUBSCRIPTIONVIDEOS'),
                                            'url'   => url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'newsubscriptionvideos')))), $entry_list);
    }

    // Page headings
    page_header($title, $subtitle);

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $entry_list, $page);

    // Output ABC buttons
    $buttons = array();

    if ( count($video_list) !== 0 )
    {
      $_SESSION["play_now"]["spec"] = implode(',', $video_list);
      $buttons[] = array('text' => str('QUICK_PLAY'),'url' => 'href="gen_playlist_youtube.php?'.current_session().'&seed='.mt_rand().'" vod="playlist" ');

      if ( $feed_type == 'top_rated' )
        $buttons[] = array('text'=>str('TOP_FAVORITES'), 'url'=>url_add_params($this_url, array('type'=>'top_favorites', 'sort'=>'favorite', 'page'=>0, 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $feed_type == 'top_favorites' )
        $buttons[] = array('text'=>str('MOST_VIEWED'), 'url'=>url_add_params($this_url, array('type'=>'most_viewed', 'sort'=>'views', 'page'=>0, 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $feed_type == 'most_viewed' )
        $buttons[] = array('text'=>str('MOST_POPULAR'), 'url'=>url_add_params($this_url, array('type'=>'most_popular', 'sort'=>'views', 'page'=>0, 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $feed_type == 'most_popular' )
        $buttons[] = array('text'=>str('MOST_RECENT'), 'url'=>url_add_params($this_url, array('type'=>'most_recent', 'sort'=>'date', 'page'=>0, 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $feed_type == 'most_recent' )
        $buttons[] = array('text'=>str('RECENTLY_FEATURED'), 'url'=>url_add_params($this_url, array('type'=>'recently_featured', 'sort'=>'name', 'page'=>0, 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $feed_type == 'recently_featured' )
        $buttons[] = array('text'=>str('TOP_RATED'), 'url'=>url_add_params($this_url, array('type'=>'top_rated', 'sort'=>'rating', 'page'=>0, 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $sort == 'name' )
        $buttons[] = array('text'=>str('SORT_TIME'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'time')));
      elseif ( $sort == 'time' )
        $buttons[] = array('text'=>str('SORT_DATE'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'date' )));
      elseif ( $sort == 'date' )
        $buttons[] = array('text'=>str('SORT_VIEWS'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'views')));
      elseif ( $sort == 'views' )
        $buttons[] = array('text'=>str('SORT_RATING'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'rating')));
      elseif ( $sort == 'rating' )
        $buttons[] = array('text'=>str('SORT_NAME'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'name')));
    }

    // Time parameter for standard feeds
    if ( in_array($feed_type, array('top_rated', 'top_favorites', 'most_viewed', 'most_popular', 'most_recent', 'recently_featured')) )
    {
      if ( $time == 'today' )
        $buttons[] = array('text' => str('THIS_WEEK'), 'url'=>url_add_params($this_url, array('time'=>'this_week', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'this_week' )
        $buttons[] = array('text' => str('THIS_MONTH'), 'url'=>url_add_params($this_url, array('time'=>'this_month', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'this_month' )
        $buttons[] = array('text' => str('ALL_TIME'), 'url'=>url_add_params($this_url, array('time'=>'all_time', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'all_time' )
        $buttons[] = array('text' => str('TODAY'), 'url'=>url_add_params($this_url, array('time'=>'today', 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    // Toggle between Videos and Playlists
    if ( $feed_type == 'uploads' )
      $buttons[] = array('text' => str('PLAYLISTS'), 'url'=>url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'playlists', 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( $feed_type == 'playlists' )
      $buttons[] = array('text' => str('VIDEOS'),'url'=>url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'uploads', 'hist'=>PAGE_HISTORY_REPLACE)));

    // Only add the change view type button if less than 3 have been defined
    if ( count($buttons) < 3 )
    {
      if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
        $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
        $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    // Make sure the "back" button goes to the correct page:
    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
