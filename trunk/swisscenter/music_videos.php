<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/audio/theaudiodb.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = current_url();

  // Get artist, album, and track details
  $artist  = isset($_REQUEST["artist"]) ? un_magic_quote(rawurldecode($_REQUEST["artist"])) : false;
  $album   = isset($_REQUEST["album"]) ? un_magic_quote(rawurldecode($_REQUEST["album"])) : false;
  $track   = isset($_REQUEST["track"]) ? un_magic_quote(rawurldecode($_REQUEST["track"])) : false;

  // Get data from TheAudioDB
  $data   = tadb_artist_getInfo($artist);
  $logo   = isset($data['strArtistLogo'])   ? $data['strArtistLogo']   : null;
  $fanart = isset($data['strArtistFanart']) ? $data['strArtistFanart'] : null;
  $videos = tadb_artist_videos($artist, $album, $track);

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  if ( count($videos) == 0 )
  {
    page_inform(2, page_hist_previous(), $artist, str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $entry_list = array();
    $video_list = array();

    // Add videos
    foreach ($videos as $video)
    {
      $text = utf8_decode($video['track']['strTrack']);
      $video_id = preg_get('/watch\?v=(.*)/', $video['track']['strMusicVid']);
      $url  = 'href="'.url_add_params('stream_url.php?'.current_session(), array('user_agent' => rawurlencode('QuickTime/7.6'),
                                                                                                           		'youtube_id' => $video_id,
                                                                                                           		'ext'        => '.mp4" vod '));
      $thumb = empty($video['track']['strTrackThumb']) ? style_img('NOW_NO_ALBUMART',true) : $video['track']['strTrackThumb'];
      // Do not add duplicate videos
      if (!in_array($video_id, $video_list))
      {
        $video_list[] = $video_id;
        $entry_list[] = array('thumb' => $thumb,
                              'text'  => $text,
                              'url'   => $url);
      }
    }

    // Order the video list
    array_sort($entry_list, 'text');

    // Page headings
    page_header( $artist, '', '', 1, false, '', $fanart, $logo, 'PAGE_TEXT_BACKGROUND' );

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $entry_list, $page);

    // Output ABC buttons
    $buttons = array();
    $_SESSION["play_now"]["spec"] = implode(',', $video_list);
    $buttons[] = array('text' => str('QUICK_PLAY'),'url' => 'href="gen_playlist_youtube.php?'.current_session().'&seed='.mt_rand().'" vod="playlist" ');
    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)));

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons, 0, true, 'PAGE_TEXT_BACKGROUND');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
