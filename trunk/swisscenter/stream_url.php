<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
//  require_once( realpath(dirname(__FILE__).'/ext/vlc/Vlc.php'));

  // Log details of the stream request
  send_to_log(1,"------------------------------------------------------------------------------");
  send_to_log(1,"Stream Requested : ".current_url()." by client (".client_ip().")");

  /**
   * Streams a remote file down to the hardware player.
   *
   * @param string $url - URL of remote file to stream
   * @param string $filename - actual filename of remote file
   * @param string $user_agent - user-agent to use when requesting the file
   */
  function stream_remote_file($url, $filename, $user_agent)
  {
    // Get headers containing file details
    $headers = array();
    $headers = array_change_key_case(get_headers($url, 1));
    send_to_log(8,'Header of requested file :', $headers);

    // Check for a redirect
    if (isset($headers["location"]))
    {
      $redirect = is_array($headers["location"]) ? array_last($headers["location"]) : $headers["location"];
      send_to_log(7,'Redirecting to :', $redirect);
      stream_remote_file($redirect, $filename, $user_agent);
      return;
    }

    // Parse the URL into its components
    $url_parts = array();
    $url_parts = parse_url($url);
    $hostname  = $url_parts["host"];
    $port      = isset($url_parts["port"]) ? $url_parts["port"] : 80;

    $fsize       = $headers["content-length"];
    $ftype       = $headers["content-type"];
    $startArray  = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-%d" );
    $fstart      = (empty($startArray[0]) ? 0 : $startArray[0]);
    $fend        = (empty($startArray[1]) ? ($fsize-1) : $startArray[1]);
    $fbytes      = $fend-$fstart+1;

    send_to_log(8,'Request method : '.$_SERVER["REQUEST_METHOD"]);
    send_to_log(8,'Requesting HTTP Range : '.$_SERVER["HTTP_RANGE"]);

    send_to_log(8,'Accept-Ranges: bytes');
    send_to_log(8,'Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
    send_to_log(8,'Content-Length: '.$fbytes);
    send_to_log(8,'Content-Type: '.$ftype);

    header('Accept-Ranges: bytes');
    header('Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
    header('Content-Length: '.$fbytes);
    header('Content-Type: '.$ftype);
    header('HTTP/1.1 206 Partial Content');

    // Send additional headers from source
    foreach ($headers as $field=>$header)
    {
      send_to_log(2,'field',$field);
      send_to_log(2,'header',$header);
      if ( in_array($field, array('etag', 'last-modified')) )
      {
        send_to_log(8,ucfirst($field).': '.$header);
        header(ucfirst($field).': '.$header);
      }
    }

    // Override Simese no-cache headers
    header("Pragma: public");
    header("Expires: ".gmdate('D, d M Y H:i:s', time()+1800)." GMT");
    header("Cache-Control: max-age=1800");

    // Force a "File Download" Dialog Box when using a browser
    send_to_log(8,'Content-Disposition: inline; filename="'.$filename.'"');
    header('Content-Disposition: inline; filename="'.$filename.'"');

    // Send any pending output (inc headers) and stop timeouts or user aborts killing the script
    ob_end_flush();
    ignore_user_abort(TRUE);
    session_write_close();
    @set_time_limit(0);
    @set_magic_quotes_runtime(0);
    @mb_http_output("pass");

    if ( $_SERVER["REQUEST_METHOD"] == 'GET' )
    {
      // Open a connection to the remote file
      $fh = fsockopen($hostname, $port, &$errno, &$errstr);
      if (!$fh)
      {
         send_to_log(2,'Failed to connect to remote file : '.$url, $errstr);
         return false;
      }
      else
      {
        send_to_log(8,"Sending : $fstart-$fend/$fsize ($fbytes bytes)");

        // Send request for required range
        fwrite($fh, "GET ".$url_parts["path"].($url_parts["query"] ? '?'.$url_parts["query"] : "")." HTTP/1.1\r\n".
                    "Host: ".$url_parts["host"]."\r\n".
                    "User-Agent: ".$user_agent."\r\n".
                    "Accept-Ranges: bytes\r\n".
                    "Range: bytes=".$fstart."-".$fend."\r\n".
                    "Connection: Keep-Alive\r\n\r\n");

        $fbytessofar = 0;
        $fbytestoget = 1024*16;

        // Loop while the connection is open
        while ( !feof($fh) && ($fbytessofar < $fbytes) && (connection_status() == CONNECTION_NORMAL) )
        {
          $fbytestoget = min($fbytestoget, $fbytes-$fbytessofar);
          $fbuf = fread($fh, $fbytestoget);

          // Remove any headers
          if (($pos = strpos($fbuf, "\r\n\r\n")) !== false)
          {
            $headers = substr($fbuf,0,$pos);
            send_to_log(8,'Header of received data :', $headers);
            $fbuf = substr($fbuf, $pos + 4);
          }
          $fbytessofar += strlen($fbuf);

          echo $fbuf;
          ob_flush();
          flush();
        }

        fclose($fh);
      }
    }
  }

  //*************************************************************************************************
  // Main logic
  //*************************************************************************************************

  $req_ext   = $_REQUEST["ext"];
  $subtitles = array('.srt','.sub', '.ssa', '.smi');

  // Determine what to do with the file request...
  if ( in_array(strtolower($req_ext),$subtitles) )
  {
    // Tell the showcenter that we don't have the requested subtitle file
    send_to_log(7,"Subtitles requested for remote file - sending 'HTTP/1.1 404' to the player");
    header ("HTTP/1.1 404 Not Found");
  }
  else
  {
    if (isset($_REQUEST["youtube_id"]))
    {
      // A YouTube id has been provided so we need to determine the actual location of the video file.
      if (!isset($_SESSION["stream_id"]) || ($_SESSION["stream_id"] !== $_REQUEST["youtube_id"]))
      {
        $video_id  = $_REQUEST["youtube_id"];

        // Get the YouTube video page to parse
        $youtube_url = 'http://www.youtube.com/watch?v='.$video_id;
        $html = file_get_contents($youtube_url);

        //
        // This code is based upon a script found at http://userscripts.org/scripts/review/25105
        //   Version 1.0.4 : Date 2010-07-24
        //
        // Obtain video ID, temporary ticket, formats map
        $video_player  = preg_get('/<div id="watch-player".*<script>(.*)<\/script>/Usm', $html);
        if ( !empty($video_player) )
        {
          $video_ticket  = preg_get('/\&t=([^(\&|$)]*)/', $video_player);
          $video_formats = preg_get('/\&fmt_url_map=([^(\&|$)]*)/', $video_player);

          if ( empty($video_ticket) ) // new UI
          {
            // TODO
          }

          // Video title
          $video_title = preg_get('/<title>(.*)<\/title>/Usm', $html);
          $video_title = 'video';

          // Available formats:
          //  5 - FLV 240p
          // 18 - MP4 360p
          // 22 - MP4 720p (HD)
          // 34 - FLV 360p
          // 35 - FLV 480p
          // 37 - MP4 1080p (HD)
          // 38 - MP4 Original (HD)
          // 43 - WebM 480p
          // 45 - WebM 720p (HD)

          // Parse fmt_url_map
          $video_url = array();
          $sep1 = '%2C';
          $sep2 = '%7C';
          if ( strpos($video_formats, ',') !== false ) // new UI
          {
            $sep1 = ',';
            $sep2 = '|';
          }
          $video_formats_group = explode($sep1, $video_formats);
          for ($i=0; $i<count($video_formats_group); $i++)
          {
            $video_formats_elem = explode($sep2, $video_formats_group[$i]);
            $video_url[$video_formats_elem[0]] = rawurldecode($video_formats_elem[1]);
          }
          if (!isset($video_url[18]))
          {
            // Add standard MP4 format (fmt18), even if it's not included
            $video_url[18] = 'http://www.youtube.com/get_video?fmt=18&video_id='.$video_id.'&t='.$video_ticket.'&asv=3';
          }
          send_to_log(6,'Available YouTube formats for : '.$youtube_url, $video_url);

          // Determine whether to request the HD format
          $fmt = 18;
          if ( get_sys_pref('YOUTUBE_HD','YES') == 'YES' && isset($video_url[22]) )
            $fmt = 22;

          // Store parsed details in $_SESSION to avoid reparsing.
          if ( !empty($video_ticket) )
          {
            $_SESSION["stream_url"] = $video_url[$fmt];
            $_SESSION["stream_filename"] = $video_title.'.mp4';
            $_SESSION["stream_id"] = $video_id;
          }
          else
          {
            unset($_SESSION["stream_url"]);
            send_to_log(2,'Failed to determine ticket for YouTube video : '.$youtube_url);
          }
        }
        else
        {
          unset($_SESSION["stream_url"]);
          send_to_log(2,'Failed to parse page for YouTube video : '.$youtube_url);
        }
      }
    }
//    elseif (isset($_REQUEST["use_vlc"]))
//    {
//      $stream_url = $_REQUEST["url"];
//
//      $vlc_path = get_sys_pref('VLC_PATH');
//      $adm_port = get_sys_pref('VLC_ADMIN_PORT',8088);
//      $stream   = get_sys_pref('VLC_STREAM_PORT',1234);
//      $vcodec   = get_sys_pref('VLC_VIDEO_CODEC','mp2v');
//      $vb       = get_sys_pref('VLC_VIDEO_BITRATE',800);
//      $acodec   = get_sys_pref('VLC_AUDIO_CODEC','mp3');
//      $ab       = get_sys_pref('VLC_AUDIO_BITRATE',128);
//      $channels = get_sys_pref('VLC_AUDIO_CHANNELS',2);
//
//      $vlc = new Vlc($vlc_path, $adm_port);
//      $vlc->setStreamPort($stream);
//      $vlc->setAudioCodec($acodec, $ab, $channels);
//      $vlc->setVideoCodec($vcodec, $vb);
//      $vlc->start($stream_url, $mux, $vidcodec, $vb, $acodec, $ab, $channels);
//
//      $_SESSION["stream_url"] = 'http://127.0.0.1:'.$stream;
//    }
    else
    {
      $_SESSION["stream_url"] = $_REQUEST["url"];
      $_SESSION["stream_filename"] = basename($_REQUEST["url"]);
    }

    send_to_log(7,'Attempting to stream the following remote file', $_SESSION["stream_url"]);

    // If a User-Agent is specified then we must stream the file ourselves.
    if (isset($_REQUEST["user_agent"]))
    {
      // Set User-Agent to use when requesting remote file
      $user_agent = isset($_REQUEST["user_agent"]) ? $_REQUEST["user_agent"] : 'Mozilla/5.0';
      ini_set('user_agent', $user_agent);

      stream_remote_file($_SESSION["stream_url"], $_SESSION["stream_filename"], $user_agent);
    }
    elseif (isset($_SESSION["stream_url"]))
    {
      // Redirect to the actual video file.
      send_to_log(8,'Redirecting to '.$_SESSION["stream_url"]);
      header ("location: ".$_SESSION["stream_url"]);
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>