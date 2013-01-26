<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/ext/lastfm/lastfm.php'));

  // Log details of the stream request
  send_to_log(1,"------------------------------------------------------------------------------");
  send_to_log(1,"Stream Requested : ".$_SERVER["REQUEST_METHOD"]." ".current_url()." by client (".client_ip().")");

/**
 * Outputs the image file to the browser.
 *
 *   Note: We only use a cache file if it is relatively new (<5 mins) so that changes
 *         to slideshow options are picked up.
 *
 * @param integer $file_id
 * @param string $filename
 * @param integer $x
 * @param integer $y
 */

  function output_image( $file_id, $filename )
  {
    $x = convert_x(1000, BROWSER_SCREEN_COORDS);
    $y = convert_y(1000, BROWSER_SCREEN_COORDS);

    // If the file is on the internet, download it into a temporary location first
    if ( is_remote_file($filename) )
    {
      $filename = download_and_cache_image($filename);
      if ( !$filename )
      {
        header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found');
        exit;
      }
    }

    $cache_file = cache_filename($filename, $x, $y);

    if ( !false &&  $cache_file !== false && Fsw::file_exists($cache_file) && (time()-Fsw::filemtime($filename) < 300) )
    {
      send_to_log(6,"Cached file exists for $filename at ($x x $y)");
      output_cached_file($cache_file, 'jpg');
    }
    else
    {
      $image = new CImage();

      // Load the image from disk
      if (strtolower(file_ext($filename)) == 'sql')
        $image->load_from_database( substr($filename,0,-4) );
      elseif ( Fsw::file_exists($filename) )
        $image->load_from_file($filename);
      else
        send_to_log(1,'Unable to process image specified : '.$filename);

      // Will a rotate (due to the exif information) be required?
      $aspect_changes = ( get_sys_pref('IMAGE_ROTATE','YES')!='NO' && $image->rotate_by_exif_changes_aspect() );

      // Optimisation: If a rotate needs to be done, swap the X/Y sizes over
      if ($aspect_changes)
        list($x,$y) = array($y,$x);

      // Resize the image to fit the screen (but only scale-up the images if the user has specified that we should)
      if ( get_sys_pref('IMAGE_SCALE_UP','YES') == 'YES' || $image->get_width() > $x || $image->get_height() > $y )
      {
        $aspects_match       = ( ($image->get_width() > $image->get_height()) == ($x > $y) );
        $output_is_landscape = ( ($x > $y && !$aspect_changes) || ($x < $y && $aspect_changes) );

        // Fill the screen completely?
        if ( get_sys_pref('IMAGE_LANDSCAPE_CROP','YES') == 'YES' && $aspects_match && $output_is_landscape )
        {
          send_to_log(1,"Original output size is $x x $y");
          $old = $image;
          $image = new CImage($x,$y);

          // Calculate the necessary size to ensure the screen is filled whilst maintaining the aspect ratio
          if (!$aspect_changes)
            $x = floor($y * ($old->get_width() / $old->get_height()));
          else
            $y = floor($x * ($old->get_height() / $old->get_width()));

          // Resize and then crop to the exact screen size
          $old->resize($x, $y);
          $image->copy($old, -floor(($old->get_width()-$image->get_width())/2) , -floor(($old->get_height()-$image->get_height())/2) );
        }
        else
        {
          $image->resize($x, $y);
        }
      }

      // Rotate/mirror the image as specified in the EXIF data (if enabled)
      if (get_sys_pref('IMAGE_ROTATE','YES') == 'YES')
        $image->rotate_by_exif();

      $image->output('jpg');
    }
  }

/**
 * Outputs the requested subtitles file to the browser (if it exists).
 *
 * @param string $fsp
 */

  function output_subtitles( $fsp )
  {
    if (file_exists($fsp))
    {
      $startArray = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-" );
      $start      = (empty($startArray[0]) ? 0 : $startArray[0]);
      $size       = Fsw::filesize($fsp);

      send_to_log(5,"Subtitles File  : ".$fsp);
      send_to_log(8,"Subtitles Range : ".$start." to ".($size-1)."/".$size);

      session_write_close();
      header("Content-type: application/octet-stream");
      header("Content-Disposition: attachment; filename=\"".basename($fsp)."\"");
      header("Content-Length: ".(string)($size-$start));
      header("Content-Transfer-Encoding: binary\n");
      readfile($fsp);
    }
    else
    {
      // Tell the showcenter that we don't have the requested file
      send_to_log(7,"Subtitles File  : ".$fsp);
      send_to_log(7,"File not found - sending '".$_SERVER["SERVER_PROTOCOL"]." 404' to the player");
      header($_SERVER["SERVER_PROTOCOL"].' 404 Not Found');
    }
  }

/**
 * Streams a file down to the hardware player.
 *
 * @param integer $media
 * @param integer $file_id
 * @param filename $location
 * @param array $headers
 */

  function stream_file($media, $file_id, $location, $headers = array() )
  {
    // Sanity check - file exists
    if ( !Fsw::is_file($location) )
      return;

    $startArray  = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-%d" );
    $fsize       = large_filesize($location);
    $fstart      = (empty($startArray[0]) ? 0 : $startArray[0]);
    $fend        = (empty($startArray[1]) ? ($fsize-1) : $startArray[1]);
    $fbytes      = $fend-$fstart+1;

    send_to_log(8,$_SERVER["REQUEST_METHOD"].' Request for HTTP Range : '.$_SERVER["HTTP_RANGE"]);
    send_to_log(8,"Sending : $fstart-$fend/$fsize ($fbytes bytes)");

    // Only store request details when end of file has been requested.
    if ($fend == $fsize-1)
      store_request_details( $media, $file_id);

    header($_SERVER["SERVER_PROTOCOL"].' 206 Partial Content');
    header('Accept-Ranges: bytes');
    header('Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
    header('Content-Length: '.$fbytes);
    header('Connection: Keep-Alive');

    foreach ($headers as $html_header)
    {
      header ($html_header);
    }
    send_to_log(8,'HTTP Response Headers:',headers_list());

    // Send any pending output (inc headers) and stop timeouts or user aborts killing the script
    ob_end_flush();
    ignore_user_abort(TRUE);
    session_write_close();
    @set_time_limit(0);
    @set_magic_quotes_runtime(0);
    @mb_http_output("pass");

    // Only send the content body with GET request.
    if ( $_SERVER["REQUEST_METHOD"] == 'GET' )
    {
      // Open the file
      $fh=Fsw::fopen($location, 'rb');
      fseek($fh, $fstart);
      $fbytessofar = 0;
      $fbytestoget = 1024*16;

      // Loop while the connection is open
      while ( !feof($fh) && ($fbytessofar < $fbytes) && (connection_status() == CONNECTION_NORMAL) )
      {
        $fbytestoget = min($fbytestoget, $fbytes-$fbytessofar);
        $fbuf = fread($fh,$fbytestoget);
        $fbytessofar += strlen($fbuf);

        ob_start();
        echo $fbuf;
        ob_end_flush();

        // Put SQL command to update the amount of file served here (on a per-user basis)
        $bookmark = $fstart + $fbytessofar;
      }

      if (!$fh || feof($fh))
        $bookmark = "NULL";

      fclose($fh);
    }
  }

  //*************************************************************************************************
  // Main logic
  //*************************************************************************************************

  // Retrieve the tracklist, and the index (idx) of the item to stream.
  $tracklist    = nvl($_REQUEST["tracklist"],'');
  $tracks       = get_tracklist($tracklist);
  $idx          = $_REQUEST["idx"];

  $_SESSION["LAST_RESPONSE_IDX"] = $idx;

  $server       = server_address();
  $media        = $_REQUEST["media_type"];
  $file_id      = $tracks[$idx]["FILE_ID"];
  $location     = $tracks[$idx]["DIRNAME"].$tracks[$idx]["FILENAME"];
  $redirect_url = (!is_remote_file($location) ? $server.make_url_path($location) : $location);
  $req_ext      = $_REQUEST["ext"];
  $subtitles    = array('.srt','.sub', '.ssa', '.smi');
  $headers      = array();

  // Determine what to do with the file request...
  if ( in_array(strtolower($req_ext),$subtitles) )
  {
    // The showcenter is requesting subtitles for this particular piece of media.
    $location = preg_replace('/(.*)\..*/u','\1'.$req_ext, $location);
    output_subtitles($location);
  }
  elseif (!is_remote_file($location) && !Fsw::is_readable($location))
  {
    // Sanity check - Do we have permissions to read this file?
    send_to_log(1,"Error: SwissCenter does not have permissions to read the file '$location'");
  }
//  elseif ($media == MEDIA_TYPE_VIDEO || $media == MEDIA_TYPE_TV)
//  {
//    // Store the request details
//    send_to_log(7,'Attempting to stream the following Video file',$tracks[$idx]);
//
//    $headers[] = "Content-type: ".mime_content_type($tracks[$idx]["FILENAME"]);
//    $headers[] = "Last-Modified: ".date('r',filemtime($location));
//    stream_file($media, $file_id, $location, $headers);
//  }
  elseif ($media == MEDIA_TYPE_MUSIC )
  {
    send_to_log(7,'Attempting to stream the following Audio file',$tracks[$idx]);
    if ($tracks[$idx]["LENGTH"] > 0)
      $headers[] = "TimeSeekRange.dlna.org: npt=0-/".$tracks[$idx]["LENGTH"];

    $headers[] = "Content-Type: ".mime_content_type(Fsw::setName($location));
 //   $headers[] = 'ETag: "'.file_etag($location).'"';
    $headers[] = "Last-Modified: ".gmdate('D, d M Y H:i:s',Fsw::filemtime($location))." GMT";
    $headers[] = "Date: ".gmdate('d M Y H:i:s').' GMT';

    // Override Simese no-cache headers
 //   $headers[] = "Pragma: public";
 //   $headers[] = "Expires: ".gmdate('D, d M Y H:i:s', time()+3600)." GMT";
 //   $headers[] = "Cache-Control: max-age=3600";

    // Submit the track to Last.fm
    if (lastfm_scrobble_enabled() && (!isset($_SESSION["last_scrobbled"]) || $_SESSION["last_scrobbled"] !== $idx))
    {
      lastfm_now_playing( $tracks[$idx]["ARTIST"], $tracks[$idx]["TITLE"], $tracks[$idx]["ALBUM"], $tracks[$idx]["LENGTH"], $tracks[$idx]["TRACK"] );
      lastfm_scrobble( $tracks[$idx]["ARTIST"], $tracks[$idx]["TITLE"], $tracks[$idx]["ALBUM"], $tracks[$idx]["LENGTH"], $tracks[$idx]["TRACK"] );
      $_SESSION["last_scrobbled"] = $idx;
    }

    // PCH 200 series only
    if ( get_player_model() >= 408 )
    {
      // Suspend screensaver
      $response = file_get_contents('http://'.$_SERVER['REMOTE_ADDR'].':8008/system?arg0=suspend_screensaver&arg1=1');
      send_to_log(7,'- PCH 200 detected: Screensaver suspended',$response);
    }

    stream_file($media, $file_id, $location, $headers);

    // PCH 200 series (pre May 2012 firmware only)
    if ( get_player_model() >= 408 && get_player_firmware_datestr() < '120501' )
    {
      // Disable the OSD when second chunk is requested, ie. start byte = 512k
      $startArray  = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-%d" );
      if ( $startArray[0] == 524288 )
      {
        // Send Info key to player, to remove OSD
        $response = file_get_contents('http://'.$_SERVER['REMOTE_ADDR'].':8008/system?arg0=send_key&arg1=info&arg2=playback');
        send_to_log(7,'- PCH 200 detected: Sent Info key, to remove OSD',$response);
      }
      // Enable screensaver
      $response = file_get_contents('http://'.$_SERVER['REMOTE_ADDR'].':8008/system?arg0=suspend_screensaver&arg1=0');
      send_to_log(7,'- PCH 200 detected: Screensaver enabled',$response);
    }
  }
  elseif ($media == MEDIA_TYPE_PHOTO)
  {
    // We have to perform on-the-fly resizing for images because we can't redirect them through the thumb.php
    // script. No idea why, but it seems the showcenter firmware responsible for displaying slideshows doesn't support redirects.
    send_to_log(7,'Attempting to stream the following Photo',$tracks[$idx]);
    store_request_details( $media, $file_id);

    // PCH 200 series only
    if ( get_player_model() >= 408 )
    {
      // Suspend screensaver
      $response = file_get_contents('http://'.$_SERVER['REMOTE_ADDR'].':8008/system?arg0=suspend_screensaver&arg1=1');
      send_to_log(7,'- PCH 200 detected: Screensaver suspended',$response);
    }

    // If passthru enabled then redirect to image, to let the player handle resizing
    if (get_sys_pref('IMAGE_RESIZING','RESAMPLE') == 'PASSTHRU')
    {
      send_to_log(8,'Redirecting to '.$redirect_url);
      header ("Location: ".$redirect_url);
    }
    else
      output_image( $file_id, $location );

    // PCH 200 series only
    if ( get_player_model() >= 408 )
    {
      // Enable screensaver
      $response = file_get_contents('http://'.$_SERVER['REMOTE_ADDR'].':8008/system?arg0=suspend_screensaver&arg1=0');
      send_to_log(7,'- PCH 200 detected: Screensaver enabled',$response);
    }
  }
  else
  {
    // Store the request details, and then send a redirect header to the player with the real location of the media file.
    send_to_log(7,'Attempting to stream the following file',$tracks[$idx]);
    store_request_details( $media, $file_id);

    send_to_log(8,'Redirecting to '.$redirect_url);
    header ("Location: ".$redirect_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
