<?
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

/**
 * Outputs the image file to the browser.
 * 
 * Although it would be faster to rsize the image and *then* rotate, it doesn't give the
 * expected result in PHP. As an example, take an image of 4000 x 6000 pixels that needs
 * to be displayed on a 1600x1200 screen:
 * 
 *   Image (4000,6000) -> Resize (800,1200)     -> Rotate 90 (1200,800) = Image of 1200 x 800
 *   Image (4000,6000) -> Rotate 90 (6000,4000) -> Resize (1600,1066)   = Image of 1600 x 1066
 *
 * @param integer $file_id
 * @param string $filename
 * @param integer $x
 * @param integer $y
 */

  function output_image( $file_id, $filename, $x, $y)
  {
    $cache_file = cache_filename($filename, $x, $y);
    if ( $cache_file !== false && file_exists($cache_file) )
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
      elseif ( file_exists($filename) || substr($filename,0,4) == 'http' )
        $image->load_from_file($filename); 
      else  
        send_to_log(1,'Unable to process image specified : '.$filename);  
            
      // Rotate/mirror the image as specified in the EXIF data (if enabled)
      if (get_sys_pref('IMAGE_ROTATE','YES'))
      {
        $orientation = db_value("select exif_orientation from photos where file_id = $file_id");
        
        if ( $orientation == 5 || $orientation == 6 || $orientation == 7)
          $image->rotate(90);          
        elseif ( $orientation == 8 )
          $image->rotate(270);
  
        // Only resize images to make them smaller!
        if ( $image->get_width() > $x || $image->get_height() > $y)
          $image->resize($x, $y);
  
        // Any required flips of the image can be done after the resize to improve performance
        
        if ( $orientation == 2 || $orientation == 5 || $orientation == 3 )
          $image->flip_horizontal();
  
        if ( $orientation == 4 || $orientation == 7 || $orientation == 3 )
          $image->flip_vertical();
      }
      else 
      {
        // Only resize images to make them smaller!
        if ( $image->get_width() > $x || $image->get_height() > $y)
          $image->resize($x, $y);        
      }
      
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
  		
      if (!$fbytestoget || (($fbuf=fgets($fh,$fbytestoget)) === FALSE) ) 
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

  $server       = server_address();
  $file_id      = $_REQUEST["file_id"];
  $media        = $_REQUEST["media_type"];
  $table        = db_value("select media_table from media_types where media_id = ".$media);          
  $location     = db_value("select concat(dirname,filename) from $table where file_id= $file_id");
  $req_ext      = $_REQUEST["ext"];
  $redirect_url = make_url_path($location);
  $subtitles    = array('.srt','.sub', '.ssa', '.smi');
  $headers      = array();

  // Determine what to do with the file request...      
  if ( in_array(strtolower($req_ext),$subtitles) )
  {
    // The showcenter is requesting subtitles for this particular piece of media.  
    $location = preg_replace('/(.*)\..*/u','\1'.$req_ext, $location);
    output_subtitles($location);    
  }
  elseif (!is_readable($location))
  {
    // Sanity check - Do we have permissions to read this file?
    send_to_log(1,"Error: SwissCenter does not have permissions to read the file '$location'");
  }
  elseif ($media == 1) // Music
  {
    // Store the request details, and then send a redirect header to the player with the real location of the media file.
    send_to_log(7,'Attempting to stream the following Audio file',array( "File ID"=>$file_id, "Media Type"=>$media, "Location"=>$location ));
    store_request_details( $media, $file_id);  

    $duration = db_value("select length from $table where file_id= $file_id");
    if ($duration > 0)
      $headers[] = "TimeSeekRange.dlna.org: npt=0-/".$duration."\r\n";

    $headers[] = "Content-type: audio/x-mpeg";
    $headers[] = "Last-Changed: ".date('r',filemtime($location));
    stream_file($media, $file_id, $location, $headers);
  }
  elseif ($media == 2) // Photos
  {
    // We have to perform on-the-fly resizing for images because we can't redirect them through the thumb.php 
    // script. No idea why, but it seems to hand the showcenter firmware responsible for displaying slideshows.  
    send_to_log(7,'Attempting to stream the following Photo',array( "File ID"=>$file_id, "Location"=>$location ));
    store_request_details( $media, $file_id);  
    output_image( $file_id, ucfirst($location), convert_x(1000, SCREEN_COORDS), convert_y(1000, SCREEN_COORDS) );
  }
  else 
  { 
    // Store the request details, and then send a redirect header to the player with the real location of the media file.
    send_to_log(7,'Attempting to stream the following file',array( "File ID"=>$file_id, "Media Type"=>$media, "Location"=>$location ));
    store_request_details( $media, $file_id);  
      
    send_to_log(8,'Redirecting to '.$server.$redirect_url);
    header ("HTTP/1.0 307 Temporary redirect");
    header ("location: ".$server.$redirect_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

