<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/audio/jamendo.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $type     = (isset($_REQUEST["type"]) ? $_REQUEST["type"] : '');
  $this_url = current_url();

  $unit   = isset($_REQUEST["unit"])   ? $_REQUEST["unit"]   : 'track';
  $fields = isset($_REQUEST["fields"]) ? $_REQUEST["fields"] : 'all';
  $params = isset($_REQUEST["params"]) ? $_REQUEST["params"] : '';

  $jamendo = new Jamendo();
  $items = $jamendo->getQuery($fields, $unit, $params);

  if ( count($items[0]) == 0 )
  {
    page_inform(2, page_hist_previous(), str('JAMENDO'), str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $entry_list = array();

    // Add entries from selected feed
    foreach ($items as $item)
    {
      switch ($unit)
      {
        case 'track':
          $id = $item['id']; break;
        case 'album':
          $id = $item['album_id']; break;
        default:
          $id = $item['artist_id']; break;
      }
      $url = url_add_params('jamendo_selected.php', array($unit=>$id));
      $entry_list[] = array('thumb' => $item['album_image'],
                            'text'  => $item['name'].' - '.$item['artist_name'],
                            'url'   => $url);

      // Playlist used for Quick Play.
      $playlist[] = array('TITLE'    => $item['name'],
                          'ALBUM'    => $item['album_name'],
                          'ARTIST'   => $item['artist_name'],
                          'FILENAME' => $item['stream'].'&ext=.mp3');
    }

    // Page headings
    page_header(str('JAMENDO'), str($type));

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $entry_list, $page);

    // Output ABC buttons
    $buttons = array();

    $buttons[] = array('text' => str('QUICK_PLAY'),'url' => play_array_list(MEDIA_TYPE_MUSIC, $playlist));

    // Sort parameter
    if ( in_array($type, array('browse', 'most_listened')) )
    {
      if ( $sort == '?class=all' )
      $buttons[] = array('text'=>str('SHORT_ALBUMS'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'?class=short')));
      elseif ( $sort == '?class=short' )
      $buttons[] = array('text'=>str('FULL_LENGHT_ALBUMS'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'?class=long')));
      elseif ( $sort == '?class=long' )
      $buttons[] = array('text'=>str('ALL_ALBUMS'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'?class=all')));
    }

    // Time parameter
    if ( in_array($type, array('category', 'browse', 'most_listened')) )
    {
      if ( $time == '?order=date_desc' )
      $buttons[] = array('text' => str('MOST_DOWNLOADED'), 'url'=>url_add_params($this_url, array('time'=>'?order=downloaded_desc', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == '?order=downloaded_desc' )
      $buttons[] = array('text' => str('THIS_WEEK'), 'url'=>url_add_params($this_url, array('time'=>'?order=ratingweek_desc', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == '?order=ratingweek_desc' || $time == '' )
      $buttons[] = array('text' => str('THIS_MONTH'), 'url'=>url_add_params($this_url, array('time'=>'?order=ratingmonth_desc', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == '?order=ratingmonth_desc' )
      $buttons[] = array('text' => str('MOST_LISTENED'), 'url'=>url_add_params($this_url, array('time'=>'?order=listened_desc', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == '?order=listened_desc' )
      $buttons[] = array('text' => str('RATING'), 'url'=>url_add_params($this_url, array('time'=>'order=starred_desc', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'order=starred_desc' )
      $buttons[] = array('text' => str('NEW'), 'url'=>url_add_params($this_url, array('time'=> '?order=date_desc', 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
      $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_params($this_url, array('shuffle'=>'on', 'hist'=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_params($this_url, array('shuffle'=>'off', 'hist'=>PAGE_HISTORY_REPLACE)) );

    // Only add the change view type button if less than 3 have been defined
    if ( count($buttons) < 3 )
    {
      if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
        $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
        $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
