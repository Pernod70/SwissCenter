<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));

  // Parameters to the script. Need to do more extensive checking on them!
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

  //*************************************************************************************************
  // Main logic
  //*************************************************************************************************

  $server     = server_address();
  $file_id    = $_REQUEST["file_id"];
  $media      = $_REQUEST["media_type"];
  $table      = db_value("select media_table from media_types where media_id = ".$media);          
  $location   = db_value("select concat(dirname,filename) from $table where file_id= $file_id");
  $user_id    = get_current_user_id();

  // **Sanity check** - Do we have permissions to read this file?
  if (!is_readable($location))
    send_to_log(1,"Error: SwissCenter does not have permissions to read the file '$location'");
  
  // Increment the downloads counter for this file
  if ( db_value("select count(*) from viewings where user_id=$user_id and media_type=$media and media_id=$file_id") == 0)
  {
    db_sqlcommand("insert into viewings ( user_id, media_type, media_id, last_viewed, total_viewings )
                   values ( $user_id, $media, $file_id, now(), 1) ");
  }
  else
  {
    db_sqlcommand("update viewings 
                      set total_viewings = total_viewings+1
                        , last_viewed = now() 
                    where user_id=$user_id 
                      and media_type=$media
                      and media_id=$file_id");
  }

  set_user_pref('LAST_PLAYED', $location);
  set_user_pref('LAST_PLAYED_ID', $file_id);

  // We have to perform on-the-fly resizing for images because we can't redirect them through the thumb.php 
  // script. No idea why, but it seems to hand the showcenter firmware responsible for displaying slideshows.
  
  if ($media == 2 ) // Photos
  {
    output_image( ucfirst($location), convert_x(1000, SCREEN_COORDS), convert_y(1000, SCREEN_COORDS) );
  }
  else 
  { 
    // If using Simese, then use UTF-8 encoding
    if (is_server_simese())
      $redirect_url = make_url_path( mb_convert_encoding($location,'UTF-8','ISO-8859-1') );
    else
      $redirect_url = make_url_path($location);

    // Send a redirect header to the player with the real location of the media file.
    header ("HTTP/1.0 307 Temporary redirect");
    header ("location: ".$server.$redirect_url);
 }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

