<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));

  // Log details of the playlist request
  send_to_log(0,"Playlist: ".$_SERVER["REQUEST_METHOD"]." ".current_url()." by client (".client_ip().")");

  $url = $_REQUEST["url"];
  $type = $_REQUEST["mt"];
  $server = server_address();

  // Get headers of radio stream
  $headers = array();
  $headers = array_change_key_case(get_headers($url, 1));
  send_to_log(8,'Headers returned from: '.$url,$headers);

  // Determine Content-type of playlist/stream
  $content_type = is_array($headers["content-type"]) ? array_last($headers["content-type"]) : $headers["content-type"];
  if ( strpos($content_type,';') > 0 )
    $content_type = substr($content_type,0,strpos($content_type,';'));

  // Create a playlist array from whatever is returned from the radio stream url
  $playlist = array();
  $url_path = parse_url($url, PHP_URL_PATH);

  // Check whether we have a playlist to parse
  if ( $content_type == 'video/x-ms-asf' || file_ext($url_path) == 'asx' )
    $playlist = load_pl_asx( $url );
  elseif ( in_array($content_type, array('audio/x-mpegurl', 'audio/mpegurl')) || file_ext($url_path) == 'm3u' )
    $playlist = load_pl_m3u( $url );
  elseif ( $content_type == 'audio/x-scpls' || file_ext($url_path) == 'pls' )
    $playlist = load_pl_pls( $url );
  elseif ( $content_type == 'application/xspf+xml' || file_ext($url_path) == 'xspf' )
    $playlist = load_pl_xspf( $url );
  else
    $playlist[] = $url;
  send_to_log(6,'Playlist from internet radio URL: '.$url,$playlist);

  // TuneIn Radio playlists contain further playlists (m3u or pls), these need parsing
  $playlist_streams = array();
  foreach ($playlist as $url)
  {
    $url_path = parse_url($url, PHP_URL_PATH);
    if ( file_ext($url_path) == 'm3u' )
    {
      $playlist_tmp = load_pl_m3u( $url );
      $playlist_streams = array_merge($playlist_streams, $playlist_tmp);
    }
    elseif ( file_ext($url_path) == 'pls' )
    {
      $playlist_tmp = load_pl_pls( $url );
      $playlist_streams = array_merge($playlist_streams, $playlist_tmp);
    }
    else
    {
      $playlist_streams[] = $url;
    }
  }
  send_to_log(6,'Playlist after further processing',$playlist_streams);

  // Store the first stream in $_SESSION to be used by Now Playing screen
  $_SESSION["iradio_stream"] = $playlist_streams[0];

  // Pass the playlist back to the player as a PLS playlist
  send_to_log(7,'Generating list of radio streams to send to the networked media player.');

  echo "[playlist]\n";
  echo "NumberOfEntries=".count($playlist_streams)."\n";

  foreach ($playlist_streams as $id=>$url)
  {
    // Use stream_iradio to parse metadata from the stream
//    $url = $server.'stream_iradio.php?'.current_session().'&stream_url='.$url;
    // If the stream URL includes a path then we should be safe to add a parameter extension
//    if ( !is_null( parse_url($url, PHP_URL_PATH) ) )
//      $url = url_add_param($url, 'ext', '.'.$type);

    // Build up the (pls) playlist to send to the player
    send_to_log(7," - ".$url);

    echo "File".($id+1)."=$url\n";
    echo "Title".($id+1)."=Stream".($id+1)."\n";
    echo "Length".($id+1)."=-1\n";
  }
  echo "Version=2\n";

  header('Content-Disposition: attachment; filename=Playlist.pls');
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header('Content-Type: audio/x-scpls');
  header("Content-Length: ".ob_get_length());
  ob_flush();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>