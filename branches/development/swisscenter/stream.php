<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));

  //-------------------------------------------------------------------------------------------------
  // Outputs the image file to the browser.
  //-------------------------------------------------------------------------------------------------

  function output_image( $filename, $x, $y)
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
      
      $image->resize($x, $y);
      $image->output('jpg');
    }
  }

  //-------------------------------------------------------------------------------------------------
  // Outputs the requested subtitles file to the browser (if it exists).
  //-------------------------------------------------------------------------------------------------
  
  function output_subtitles( $fsp )
  {
    if (file_exists($fsp))
    {
      $startArray = sscanf( $_SERVER["HTTP_RANGE"], "bytes=%d-" );
      $start      = (empty($startArray[0]) ? 0 : $startArray[0]);
      $size       = filesize($fsp);
  
      send_to_log(5,"Subtitles File  : ".$fsp); 
      send_to_log(8,"Subtitles Range : ".$start." to ".($size-1)."/".$size); 
  
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

  //-------------------------------------------------------------------------------------------------
  // Increment the downloads counter so that we can track which files are played by which user, and
  // how often. Also store the details on the last file played in the user's preferences.
  //-------------------------------------------------------------------------------------------------
  
  function store_request_details( $media, $file_id, $location )
  {  
    // Current user
    $user_id = get_current_user_id();

    // Increment the downloads counter for this file
    if ( db_value("select count(*) from viewings where user_id=$user_id and media_type=$media and media_id=$file_id") == 0)
    {
      db_sqlcommand("insert into viewings ( user_id, media_type, media_id, last_viewed, total_viewings )
                     values ( $user_id, $media, $file_id, now(), 1) ");
    }
    else
    {
      db_sqlcommand("update viewings set total_viewings = total_viewings+1 , last_viewed = now() 
                     where user_id=$user_id and media_type=$media and media_id=$file_id");
    }
  
    set_user_pref('LAST_PLAYED', $location);
    set_user_pref('LAST_PLAYED_ID', $file_id);  
  }
  
  //*************************************************************************************************
  // Main logic
  //*************************************************************************************************

  $server     = server_address();
  $file_id    = $_REQUEST["file_id"];
  $media      = $_REQUEST["media_type"];
  $table      = db_value("select media_table from media_types where media_id = ".$media);          
  $location   = db_value("select concat(dirname,filename) from $table where file_id= $file_id");
  $req_ext    = $_REQUEST["ext"];
  $subtitles  = array('.srt','.sub', '.ssa', '.smi');
  
  // If using Simese, then use UTF-8 encoding
  if (is_server_simese())
    $redirect_url = make_url_path( mb_convert_encoding($location,'UTF-8','ISO-8859-1') );
  else
    $redirect_url = make_url_path($location);

  // Determine what to do with the file request...      
  if ( in_array(strtolower($req_ext),$subtitles) )
  {
    // The showcenter is requesting subtitles for this particular piece of media.    
    $location = preg_replace('/(.*)\..*/u','\1'.$req_ext, $location);
    output_subtitles($locations);    
  }
  elseif (!is_readable($location))
  {
    // Sanity check - Do we have permissions to read this file?
    send_to_log(1,"Error: SwissCenter does not have permissions to read the file '$location'");
  }
  elseif ($media == 2) // Photos
  {
    // We have to perform on-the-fly resizing for images because we can't redirect them through the thumb.php 
    // script. No idea why, but it seems to hand the showcenter firmware responsible for displaying slideshows.  
    store_request_details( $media, $file_id, $location);  
    output_image( ucfirst($location), convert_x(1000, SCREEN_COORDS), convert_y(1000, SCREEN_COORDS) );
  }
  else 
  { 
    // Store the request details, and then send a redirect header to the player with the real location of
    // the media file.
    store_request_details( $media, $file_id, $location);  
    header ("HTTP/1.0 307 Temporary redirect");
    header ("location: ".$server.$redirect_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

