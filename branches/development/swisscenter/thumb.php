<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // Do not report any errors at all for the thumbnail generator.
//  error_reporting(0);

  include_once('base/settings.php');
  include_once('base/utils.php');
  require_once("base/file.php");
  require_once("base/prefs.php");

  // Parameters to the script. Need to do more extensive checking on them!
  $filename   = un_magic_quote(rawurldecode($_REQUEST["src"]));
  $format     = strtolower(file_ext($filename));
  $x          = $_REQUEST["x"];
  $y          = $_REQUEST["y"];
  $cache_file = str_suffix(get_sys_pref("cache_dir"),'/').'SwissC'.md5($filename.'_x'.$x.'y'.$y).'.png';

  if ( get_sys_pref("cache_dir") != '' && file_exists($cache_file) )
  {
    // There is a cached version of the image available... so use it!
    header("Content-Type: image/png");
    touch($cache_file);
    $fp = fopen($cache_file, 'rb')  ;
    fpassthru($fp);
    fclose($fp);
  }
  else
  {
    if ( strtolower(file_ext($filename)) == 'sql' || file_exists($filename) )
    {
  
      //  Load the image (pity there's 4 versions of the call depending on the file format!)
      switch (strtolower(file_ext($filename)))
      {
        case 'jpg':
        case 'jpeg':
          $image = ImageCreateFromJpeg($filename);
          break;
        case 'png':
          $image = ImageCreateFromPng($filename);
          break;
        case 'gif':
          $image = ImageCreateFromGif($filename);
          break;
        case 'sql':
          header("Content-type: image");
          echo substr(db_value(substr($filename,0,-4)),0);
          exit;
          $image = ImageCreateFromString( db_value(substr($filename,0,-4)) );
          break;
      }
  
      $imagedata[0] = imagesx($image);
      $imagedata[1] = imagesy($image);
      
      if ($x != $imagedata[0] || $y != $imagedata[1])
      {      
        // Work out the actual dimensions of the image to keep it within the specifed res,
        // but still maintain the aspect ratio.
    
        if ($x && ($imagedata[0] < $imagedata[1]))
          $x = floor(($y / $imagedata[1]) * $imagedata[0]);
        else
          $y = floor(($x / $imagedata[0]) * $imagedata[1]);
  
          // Create an empty image, then resize the image on the filesystem into the new image.
        $im2 = ImageCreateTrueColor($x,$y);
        imagecopyResampled ($im2, $image, 0, 0, 0, 0, $x, $y, $imagedata[0], $imagedata[1]);
    
        // Output the image to the browser
        switch ($format)
        {
          case 'jpg':
          case 'jpeg':
                header("Content-type: image/jpeg");
                ImageJpeg($im2);
                break;
          case 'gif':
                header("Content-type: image/gif");
                ImageGif($im2);
                break;
          case 'png';
                header("Content-type: image/png");
                ImagePng($im2);
                break;
        }
      }
      else 
      {
        // Image does not need resizing, so just output it
        header("Content-type: image/png");
        ImagePng($image);
      }
  
      // If a cache directory has been defined, then store the cached file into it.
      if ( get_sys_pref("cache_dir") != '' )
        ImagePng($im2, $cache_file);  
    }
    else 
    {
      header("Content-Type: image/gif");
      $fp = fopen(SC_LOCATION.'/images/dot.gif', 'rb')  ;
      fpassthru($fp);
      fclose($fp);
    }
  }

  // Check to see if the CACHE_MAXSIZE_MB has been reached, and if so, delete the older files.
  if (file_exists(get_sys_pref("cache_dir")) && get_sys_pref("cache_maxsize_mb") != '' )
  {
    $dir      = get_sys_pref("cache_dir");
    $max_size = get_sys_pref("cache_maxsize_mb") * 1048576;
    $dir_size = 0;
    
    // Calculate sum of images filesizes, and maintain an array of images.
    if ($dirstream = @opendir($dir))
    {
      while (false !== ($filename = readdir($dirstream)))
        if ($filename!="." && $filename!=".." && substr($filename,0,6) == 'SwissC')
          if (is_file($dir."/".$filename))
          {
            $dir_size += filesize($dir."/".$filename);
            $files[filemtime($dir."/".$filename).'_'.filesize($dir.'/'.$filename).'_'.$filename] = $dir.'/'.$filename;
          }
    }
    closedir($dirstream);
    ksort($files);

    // If the sum of images is greater than the cache size, remove as many files as necessary.
    if ($dir_size > $max_size)
    {
      foreach( $files as $k => $v)
        if ($dir_size > $max_size)
        {
          unlink($v);
          $details = split('_',$k);
          $dir_size -= $details[1];
        }

    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>