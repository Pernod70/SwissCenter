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
  $filename   = un_magic_quote(urldecode($_REQUEST["src"]));
  
  // If the file is on the internet, download it into a temporary location first
  if ( is_remote_file($filename) )
  {
    $url = $filename;
    $filename = get_sys_pref('cache_dir').'/SwissCenter_download_'.md5($filename).'_'.date('YmdH').'.'.file_ext($filename);
    file_download_and_save($url, $filename);
  }

  // Other parameters passed in to the script
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
    
    // Optimisation: If a rotate needs to be done, swap the X/Y sizes over
    if (get_sys_pref('IMAGE_ROTATE','YES')!='NO' && $image->rotate_by_exif_swaps_dims())
      list($x,$y) = array($y,$x);

    // Resize it to the required size, whilst maintaining the correct aspect ratio
    $image->resize($x, $y, 0, $aspect, $rs_mode);

    // Rotate/mirror the image as specified in the EXIF data (and enabled)
    if (get_sys_pref('IMAGE_ROTATE','YES')!='NO')
      $image->rotate_by_exif();
    
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
