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

    header('Accept-Ranges: bytes');
    header('Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
    header('Content-Length: '.$fbytes);
    header('Content-Type: '.$ftype);
    header($_SERVER["SERVER_PROTOCOL"].' 206 Partial Content');

    // Send additional headers from source
    foreach ($headers as $field=>$header)
    {
      if ( in_array($field, array('etag', 'last-modified'), true) )
        header(ucfirst($field).': '.$header);
    }

    // Override Simese no-cache headers
    header('Pragma: public');
    header('Expires: '.gmdate('D, d M Y H:i:s', time()+1800).' GMT');
    header('Cache-Control: max-age=1800');

    // Force a "File Download" Dialog Box when using a browser
    header('Content-Disposition: inline; filename="'.$filename.'"');

    send_to_log(8,'HTTP Response Headers:',headers_list());

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
        fwrite($fh, "GET ".$url_parts["path"].($url_parts["query"] ? '?'.$url_parts["query"] : "")." HTTP/1.0\r\n".
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
    send_to_log(7,"Subtitles requested for remote file - sending '".$_SERVER["SERVER_PROTOCOL"]." 404' to the player");
    header ($_SERVER["SERVER_PROTOCOL"].' 404 Not Found');
  }
  else
  {
    if (isset($_REQUEST["youtube_id"]))
    {
      // A YouTube id has been provided so we need to determine the actual location of the video file.
      if (!isset($_SESSION["stream_id"]) || ($_SESSION["stream_id"] !== $_REQUEST["youtube_id"]))
      {
        $videoId  = un_magic_quote(rawurldecode($_REQUEST["youtube_id"]));

        // Get the YouTube video page to parse
        $youtube_url = 'http://www.youtube.com/watch?v='.$videoId;
        $html = file_get_contents($youtube_url);

        //
        // This code is based upon a script found at http://userscripts.org/scripts/show/109103
        //   Version 1.3.8 : Date 2012-02-09
        //
        // Obtain video ID, temporary ticket, formats map
        if (!empty($html))
        {
          $videoTicket  = preg_get('/(?:"|\&amp;t=([^(\&|$)]+)/', $html);
          $videoFormats = preg_get('/(?:"|\&amp;url_encoded_fmt_stream_map=([^(\&|$|\\)]+)/', $html);
        }

        if (empty($videoTicket))
        {
          $videoTicket  = preg_get('/\"t\":\s*\"([^\"]+)\"/', $html);
          $videoFormats = preg_get('/\"url_encoded_fmt_stream_map\":\s*\"([^\"]+)\"/', $html);
        }

        if (!empty($videoTicket) && !empty($videoFormats))
        {
          // Video title
          $videoTitle = preg_get('/<title>(.*)<\/title>/Usm', $html);
          $videoTitle = str_replace(' - YouTube', '', $videoTitle);
          $videoTitle = 'video';

          // Available formats:
          //  5 - FLV 240p
          // 18 - MP4 360p
          // 22 - MP4 720p (HD)
          // 34 - FLV 360p
          // 35 - FLV 480p
          // 37 - MP4 1080p (HD)
          // 38 - MP4 4K (HD)
          // 43 - WebM 360p
          // 44 - WebM 480p
          // 45 - WebM 720p (HD)

          // Parse the formats map
          $sep1 = '%2C';
          $sep2 = '%26';
          $sep3 = '%3D';
          if ( strpos($videoFormats, ',') !== false )
          {
            $sep1 = ',';
            $sep2 = (strpos($videoFormats, '&') !== false ? '&' : '\u0026');
            $sep3 = '=';
          }

          $videoUrl = array();
          $videoFormatsGroup = explode($sep1, $videoFormats);
          for ($i=0; $i<count($videoFormatsGroup); $i++)
          {
            $videoFormatsElem = explode($sep2, $videoFormatsGroup[$i]);
            if (count($videoFormatsElem) < 5) continue;
            $partialResult1 = explode($sep3, $videoFormatsElem[0]);
            if (count($partialResult1) < 2) continue;
            $url = rawurldecode(rawurldecode($partialResult1[1]));
            $url = str_replace('\/','/',$url);
            $url = str_replace('\u0026','&',$url);
            $partialResult2 = explode($sep3, $videoFormatsElem[4]);
            if (count($partialResult2) < 2) continue;
            $itag = $partialResult2[1];
            if (strpos($url, 'http') == 0) // validate URL
              $videoUrl[$itag] = $url;
          }
          send_to_log(6,'Available YouTube formats for : '.$youtube_url, $videoUrl);

          // Determine whether to request the HD format
          $fmt = 18;
          if ( get_sys_pref('YOUTUBE_HD','YES') == 'YES' && isset($videoUrl[22]) )
            $fmt = 22;

          // Store parsed details in $_SESSION to avoid reparsing.
          if ( !empty($videoTicket) )
          {
            $_SESSION["stream_url"] = $videoUrl[$fmt];
            $_SESSION["stream_filename"] = $videoTitle.'.mp4';
            $_SESSION["stream_id"] = $videoId;
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
      $_SESSION["stream_url"] = un_magic_quote(rawurldecode($_REQUEST["url"]));
      $_SESSION["stream_filename"] = basename($_SESSION["stream_url"]);
    }

    send_to_log(7,'Attempting to stream the following remote file', $_SESSION["stream_url"]);

    if (isset($_SESSION["stream_url"]))
    {
      // If a User-Agent is specified then we must stream the file ourselves.
      if (isset($_REQUEST["user_agent"]))
      {
        // Set User-Agent to use when requesting remote file
        $user_agent = isset($_REQUEST["user_agent"]) ? un_magic_quote(rawurldecode($_REQUEST["user_agent"])) : 'Mozilla/5.0';
        ini_set('user_agent', $user_agent);

        stream_remote_file($_SESSION["stream_url"], $_SESSION["stream_filename"], $user_agent);
      }
      else
      {
        // Redirect to the actual video file.
        send_to_log(8,'Redirecting to '.$_SESSION["stream_url"]);
        header ('Location: '.$_SESSION["stream_url"]);
      }
    }
    else
    {
      // File not found!
      header ($_SERVER["SERVER_PROTOCOL"].' 404 Not Found');
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>