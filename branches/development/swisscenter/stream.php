<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

  $server     = server_address();
  $file_id    = $_REQUEST["file_id"];
  $table      = db_value("select media_table from media_types where media_id = ".$_REQUEST["media_type"]);          
  $location   = db_value("select concat(dirname,filename) from $table where file_id= $file_id");

  // Increment the downloads counter for this file
//  db_sqlcommand("update $table set downloads = downloads + 1 where file_id=$file_id");

  // Send a redirect header to the player with the real location of the media file.
  header ("HTTP/1.0 307 Temporary redirect");
  header ("location: ".$server.make_url_path($location));

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

