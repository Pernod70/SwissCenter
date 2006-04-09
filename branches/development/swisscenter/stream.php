<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

  $server     = server_address();
  $file_id    = $_REQUEST["file_id"];
  $table      = db_value("select media_table from media_types where media_id = ".$_REQUEST["media_type"]);          
  $location   = db_value("select concat(dirname,filename) from $table where file_id= $file_id");

  // **Sanity check** - Do we have permissions to read this file?
  if (!is_readable($local))
    send_to_log("Error: SwissCenter does not have permissions to read the file '$location'");
  
  // We have to perform on-the-fly resizing for images, so we need to redirect them through the thumb.php 
  // script. For other file types, we don't need to do anything.
  
  if ($media == 2 ) // Photos
  {
    $x = convert_x(1000, SCREEN_COORDS);
    $y = convert_y(1000, SCREEN_COORDS);
    $redirect_url = "thumb.php?type=jpg&x=$x&y=$y&src=".rawurlencode(ucfirst($row["DIRNAME"]).$row["FILENAME"]);
  }
  else 
  {
    $redirect_url = make_url_path($location);
  }

  // Increment the downloads counter for this file
  db_sqlcommand("update $table set viewings = viewings + 1 where file_id=$file_id");

  // Send a redirect header to the player with the real location of the media file.
  header ("HTTP/1.0 307 Temporary redirect");
  header ("location: ".$server.$redirect_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

