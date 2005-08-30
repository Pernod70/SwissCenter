<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // Do not report any errors at all for the thumbnail generator.
//  error_reporting(0);

  include_once('base/settings.php');
  include_once('base/utils.php');
  require_once("base/file.php");
  require_once("base/image.php");

  // Parameters to the script. Need to do more extensive checking on them!
  $filename   = un_magic_quote(rawurldecode($_REQUEST["src"]));
  $format     = strtolower(file_ext($filename));
  $x          = $_REQUEST["x"];
  $y          = $_REQUEST["y"];
  $cache_file = cache_filename($filename, $x, $y);

  // Is there a cached version available?
  if ( $cache_file !== false && file_exists($cache_file) )
  {
    output_cached_file($cache_file);
  }
  else
  {
    // Create a new image
    $image = new CImage();
    
    // Load the image from disk
    if (strtolower(file_ext($filename)) == 'sql')
      $image->load_from_database( substr($filename,0,-4) );
    elseif ( file_exists($filename) || substr($filename,0,4) == 'http' )
      $image->load_from_file($filename); 
    else  
      send_to_log('Unable to process image specified : '.$filename);  
    
    // Resize it to the required size, whilst maintaining the correct aspect ratio
    $image->resize($x, $y, false);
    
    // Output the image to the browser.
    $image->output($format);
  }


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>