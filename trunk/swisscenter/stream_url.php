<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/ext/json/json.php'));

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
      $redirect = is_array($headers["location"]) ? array_pop($headers["location"]) : $headers["location"];
      send_to_log(7,'Redirecting to :', $redirect);
      stream_remote_file($redirect, $filename, $user_agent);
      return;
    }

    // Parse the URL into its components
    $url_parts = array();
    $url_parts = parse_url($url);
    $hostname  = $url_parts["host"];
    $port      = isset($url_parts["port"]) ? $url_parts["port"] : 80;

    // Open a connection to the remote file
    $fh = fsockopen($hostname, $port, &$errno, &$errstr);
    if (!$fh)
    {
       send_to_log(2,'Failed to connect to remote file : '.$url, $errstr);
       return false;
    }
    else
    {
      $fsize       = $headers["content-length"];
      $ftype       = $headers["content-type"];
      $startArray  = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-%d" );
      $fstart      = (empty($startArray[0]) ? 0 : $startArray[0]);
      $fend        = (empty($startArray[1]) ? ($fsize-1) : $startArray[1]);
      $fbytes      = $fend-$fstart+1;

      send_to_log(8,'Request method : '.$_SERVER["REQUEST_METHOD"]);
      send_to_log(8,'Requesting HTTP Range : '.$_SERVER["HTTP_RANGE"]);
      send_to_log(8,"Sending : $fstart-$fend/$fsize ($fbytes bytes)");

      // Send request for required range
      fwrite($fh, "GET ".$url_parts["path"].($url_parts["query"] ? '?'.$url_parts["query"] : "")." HTTP/1.1\r\n".
                  "Host: ".$url_parts["host"]."\r\n".
                  "User-Agent: ".$user_agent."\r\n".
                  "Accept-Ranges: bytes\r\n".
                  "Range: bytes=".$fstart."-".$fend."\r\n".
                  "Connection: Close\r\n\r\n");

      if ($fbytes == $fsize)
      {
        send_to_log(8,'Content-Length: '.$fsize);
        send_to_log(8,'Accept-Ranges: bytes');
        send_to_log(8,'Content-Type: '.$ftype);
        header('Content-Length: '.$fsize);
        header('Accept-Ranges: bytes');
        header('Content-Type: '.$ftype);
      }
      else
      {
        send_to_log(8,'Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
        send_to_log(8,'Content-Length: '.$fbytes);
        send_to_log(8,'HTTP/1.1 206 Partial Content');
        send_to_log(8,'Content-Type: '.$ftype);
        header('Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
        header('Content-Length: '.$fbytes);
        header("HTTP/1.1 206 Partial Content");
        header('Content-Type: '.$ftype);
      }
      // Force a "File Download" Dialog Box when using a browser
      header('Content-Disposition: attachment; filename="'.$filename.'"');

      // Send any pending output (inc headers) and stop timeouts or user aborts killing the script
      ob_end_flush();
      ignore_user_abort(TRUE);
      session_write_close();
      @set_time_limit(0);
      @set_magic_quotes_runtime(0);
      @mb_http_output("pass");

      $fbytessofar = 0;
      $fbytestoget = 1024*128;

      // Loop while the connection is open
      while ( !feof($fh) && (connection_status() == CONNECTION_NORMAL) )
      {
        // $fbytestoget = min($fbytestoget, $fbytes-$fbytessofar);
        $fbuf = fread($fh, $fbytestoget);
        $fbytessofar += strlen($fbuf);

        // Remove any headers
        if (($pos = strpos($fbuf, "\r\n\r\n")) !== false)
        {
          $headers = substr($fbuf,0,$pos);
          send_to_log(8,'Header of received data :', $headers);
          $fbuf = substr($fbuf, $pos + 4);
        }
        echo $fbuf;
        ob_flush();
        flush();
      }

      fclose($fh);
    }
  }

  //*************************************************************************************************
  // Main logic
  //*************************************************************************************************

  if (isset($_REQUEST["youtube_id"]))
  {
    $video_id  = $_REQUEST["youtube_id"];
    $video_url = 'http://www.youtube.com/watch?v='.$video_id;
    $html      = @file_get_contents($video_url);

    // Get the SWF_ARGS from the page
    $swf_args = json_decode('['.preg_get('/SWF_ARGS.*({.*})/Ui',$html).']');

    // Determine whether to request the HD stream
    $fmt = 18;
    if (get_sys_pref('YOUTUBE_HD','YES') == 'YES' && preg_get('/IS_HD_AVAILABLE.*(true|false)/U',$html) == 'true')
      $fmt = 22;

    if ( isset($swf_args[0]->t) )
    {
      // Form URL of YouTube video to stream
      $url = 'http://www.youtube.com/get_video?fmt='.$fmt.'&video_id='.$video_id.'&t='.$swf_args[0]->t;
      $filename = 'video.mp4';
    }
    else
    {
      send_to_log(2,'Failed to determine hash code for YouTube video : '.$video_url);
      return false;
    }
  }
  else
  {
    $url = $_REQUEST["url"];
    $filename = basename($url);
  }

  send_to_log(7,'Attempting to stream the following remote file', $url);

  // Set User-Agent to use when requesting remote file
  $user_agent = isset($_REQUEST["user_agent"]) ? $_REQUEST["user_agent"] : 'Mozilla/5.0';
  ini_set('user_agent', $user_agent);

  stream_remote_file($url, $filename, $user_agent);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>