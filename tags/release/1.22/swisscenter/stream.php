<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/ext/lastfm/lastfm.php'));

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

  function output_image( $file_id, $filename)
  {
    $x = convert_x(1000, SCREEN_COORDS);
    $y = convert_y(1000, SCREEN_COORDS);

    // If the file is on the internet, download it into a temporary location first
    if ( is_remote_file($filename) )
      $filename = download_and_cache_image($filename);
    
    $cache_file = cache_filename($filename, $x, $y);
    if ( !false &&  $cache_file !== false && file_exists($cache_file) && (time()-filemtime($filename) < 300) )
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
      elseif ( file_exists($filename) )
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
      if (get_sys_pref('IMAGE_ROTATE','YES') =='YES')
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
      $size       = filesize($fsp);

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
      send_to_log(7,"File not found - sending 'HTTP/1.0 404' to the player");
      header ("HTTP/1.0 404 - Not Found");
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
    $startArray  = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-%d" );
    $fsize       = large_filesize($location);
    $fstart      = (empty($startArray[0]) ? 0 : $startArray[0]);
    $fend        = (empty($startArray[1]) ? ($fsize-1) : $startArray[1]);
    $fbytes      = $fend-$fstart+1;

    send_to_log(8,'Requesting HTTP Range : '.$_SERVER["HTTP_RANGE"]);
    send_to_log(8,"Sending : $fstart-$fend/$fsize ($fbytes bytes)");

    if ($fbytes == $fsize)
    {
      send_to_log(8,'Content-Length: '.$fsize);
      send_to_log(8,'Accept-Ranges: bytes');
      header('Content-Length: '.$fsize);
      header('Accept-Ranges: bytes');
    }
    else
    {
      send_to_log(8,'Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
      send_to_log(8,'Content-Length: '.$fbytes);
      header('Content-Range: bytes '.$fstart.'-'.$fend.'/'.$fsize);
      header('Content-Length: '.$fbytes);
      header("HTTP/1.1 206 Partial Content");
    }

    foreach ($headers as $html_header)
    {
      send_to_log(8,'Header: '.$html_header);
      header ($html_header);
    }

    // Send any pending output (inc headers) and stop timeouts or user aborts killing the script
    ob_end_flush();
    ignore_user_abort(TRUE);
    set_time_limit(0);
    session_write_close();

    // Open the file
    $fh=fopen($location, 'rb');
    fseek($fh, $fstart);
    $fbytessofar = 0;
    $fbytestoget = 20480;

    // Loop while the connection is open
    while( $fh && (connection_status()==0) )
    {
	  	$fbytestoget = min($fbytestoget, $fbytes-$fbytessofar);

      if (!$fbytestoget || (($fbuf=fread($fh,$fbytestoget)) === FALSE) )
        break;

  	  $fbytessofar += strlen($fbuf);
      echo $fbuf;
  	  flush();

      // Put SQL command to update the amount of file served here (on a per-user basis)
  	  $bookmark = $fstart + $fbytessofar;
    }

    if (!$fh || feof($fh))
      $bookmark = "NULL";

    fclose($fh);
  }

  //*************************************************************************************************
  // Main logic
  //*************************************************************************************************

  // Retrieve the tracklist, and the index (idx) of the item to stream.
  send_to_log(1,'Stream request');

  $tracklist    = nvl($_REQUEST["tracklist"],'');
  $tracks       = get_tracklist($tracklist);
  $idx          = $_REQUEST["idx"];

  $_SESSION["LAST_RESPONSE_IDX"] = $idx;

  $server       = server_address();
  $media        = $_REQUEST["media_type"];
  $file_id      = $tracks[$idx]["FILE_ID"];
  $location     = $tracks[$idx]["DIRNAME"].$tracks[$idx]["FILENAME"];
  $redirect_url = make_url_path($location);
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
  elseif (!is_remote_file($location) && !is_readable($location))
  {
    // Sanity check - Do we have permissions to read this file?
    send_to_log(1,"Error: SwissCenter does not have permissions to read the file '$location'");
  }
  elseif ($media == MEDIA_TYPE_MUSIC)
  {
    // Store the request details
    send_to_log(7,'Attempting to stream the following Audio file',$tracks[$idx]);
    store_request_details( $media, $file_id);

    if ($tracks[$idx]["LENGTH"] > 0)
      $headers[] = "TimeSeekRange.dlna.org: npt=0-/".$tracks[$idx]["LENGTH"];

    $headers[] = "Content-type: audio/x-mpeg";
    $headers[] = "Last-Changed: ".date('r',filemtime($location));
    stream_file($media, $file_id, $location, $headers);

    // Submit the track to last.FM
    if (lastfm_scrobble_enabled())
      lastfm_scrobble( $tracks[$idx]["ARTIST"], $tracks[$idx]["TITLE"], $tracks[$idx]["ALBUM"], $tracks[$idx]["LENGTH"], $tracks[$idx]["TRACK"] );
  }
  elseif ($media == MEDIA_TYPE_PHOTO)
  {
    // We have to perform on-the-fly resizing for images because we can't redirect them through the thumb.php
    // script. No idea why, but it seems the showcenter firmware responsible for displaying slideshows doesn't support redirects.
    send_to_log(7,'Attempting to stream the following Photo',$tracks[$idx]);
    store_request_details( $media, $file_id);
    output_image( $file_id, $location );
  }
  else
  {
    // Store the request details, and then send a redirect header to the player with the real location of the media file.
    send_to_log(7,'Attempting to stream the following file',$tracks[$idx]);
    store_request_details( $media, $file_id);

    send_to_log(8,'Redirecting to '.$server.$redirect_url);
    header ("HTTP/1.0 307 Temporary redirect");
    header ("location: ".$server.$redirect_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
