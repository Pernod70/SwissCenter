<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor

   Parameters that may be passed (on the URL) to this script

       src = The filename of the image to display
         x = The desired X size
         y = The desired Y size
         
      type = [Optional] Image type to output if a cached copy of the image is available. If not
              specified, then the default is PNG format.
   stretch = [Optional] If present on the URL, then the iumage will be stretched to fit the given
             (X,Y) size instead of keeping it's aspect ratio
 
  *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));

  // Parameters to the script. Need to do more extensive checking on them!
  $filename   = un_magic_quote(rawurldecode($_REQUEST["src"]));
  $format     = strtolower(file_ext($filename));
  $x          = $_REQUEST["x"];
  $y          = $_REQUEST["y"];
  $rs_mode    = $_REQUEST["rs_mode"];
  $cache_file = cache_filename($filename, $x, $y, $rs_mode);
  $aspect     = (isset($_REQUEST["stretch"]) ? false : true);
  $use_cache  = get_sys_pref('CACHE_STYLE_DETAILS','YES');

  // Is there a cached version available?
  if ( $cache_file !== false && file_exists($cache_file) && $use_cache == 'YES' )
  {
    send_to_log(6,"Cached file exists for $filename at ($x x $y)");
    output_cached_file($cache_file, $_REQUEST["type"]);
  }
  else
  {
    // Create a new image
    $image = new CImage();
    
    // Load the image from disk
    if (strtolower(file_ext($filename)) == 'sql')
      $image->load_from_database( substr($filename,0,-4) );
    elseif ( file_exists($filename) || is_remote_file($filename) )
      $image->load_from_file($filename); 
    else  
      send_to_log(1,'Unable to process image specified : '.$filename);  
    
    // Resize it to the required size, whilst maintaining the correct aspect ratio
    $image->resize($x, $y, 0, $aspect, $rs_mode);
    
    // Output the image to the browser.
    if (isset($_REQUEST["type"]))
      $image->output($_REQUEST["type"]);
    else
      $image->output('png');
  }


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
