<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/settings.php");
  require_once("base/file.php");


  $startArray = sscanf( $_SERVER[ "HTTP_RANGE" ], "bytes=%d-" );
  $start      = (empty($startArray[0]) ? 0 : $startArray[0]);
  $file       = stripcslashes($_REQUEST["file"]);
  $size       = filesize($file);
  
  // Log the request (in a later version we will store some info in the database to enable us 
  // to "resume playing" whatever the user last chose, in conjunction with a temp playlist file).
  
  send_to_log("File  : ".$file); 
  send_to_log("Range : ".$start."-".$size."/".$size); 

  // Output page header
  header("Pragma: ");
  header("Cache-Control: ");
  header("Content-type: application/octet-stream");
  header("Content-Disposition: inline; filename=".basename($file)); 
  header("Accept-Ranges: bytes"); 
  header("Content-Length: ".(string)$size); 
  header("Content-Range: bytes ".$start."-".$size."/".$size); 

  // Stream the specified file 
  $fp = fopen($file, 'rb');

  if( $start > 0 )
    fseek( $fp, $start );
  
  fpassthru($fp);
  fclose($fp);
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

