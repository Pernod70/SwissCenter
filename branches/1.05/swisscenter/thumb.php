<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // Do not report any errors at all for the thumbnail generator.
  error_reporting(0);

  include_once('base/settings.php');
  include_once('base/utils.php');
  require_once("base/file.php");

  // Parameters to the script. Need to do more extensive checking on them!
  $filename   = un_magic_quote(rawurldecode($_REQUEST["src"]));
  $x          = $_REQUEST["x"];
  $y          = $_REQUEST["y"];
  $cache_file = str_suffix($_SESSION["opts"]["cache_dir"],'/').'SwissC'.md5($filename.'_x'.$x.'y'.$y).'.png';

  if ( !empty($_SESSION["opts"]["cache_dir"]) && file_exists($cache_file) )
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
    // Get the Image size on disk, and check to see if it should be streamed directly,
    // or resized first.
    $imagedata  = getimagesize($filename);
    if ($x == $imagedata[0] && $y == $imagedata[1])
    {
      //  Load the image 
      switch (strtolower(file_ext($filename)))
      {
        case 'jpg':
        case 'jpeg':
          header("Content-type: image/jpeg");
          break;
        case 'png':
          header("Content-type: image/png");
          break;
        case 'gif':
          header("Content-type: image/gif");
          break;
      }
  
      $fp = fopen($filename, 'rb');
      fpassthru($fp);
      fclose($fp);
    }  
    elseif ( file_exists($filename) )
    {
  
      // Work out the actual dimensions of the image to keep it within the specifed res,
      // but still maintain the aspect ratio.
  
      if ($x && ($imagedata[0] < $imagedata[1]))
        $x = floor(($y / $imagedata[1]) * $imagedata[0]);
      else
        $y = floor(($x / $imagedata[0]) * $imagedata[1]);
  
      //  Load the image (pity there's 3 version of the call depending on the file format!)
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
      }
  
      // Create an empty image, then resize the image on the filesystem into the new image.
      $im2 = ImageCreateTrueColor($x,$y);
      imagecopyResampled ($im2, $image, 0, 0, 0, 0, $x, $y, $imagedata[0], $imagedata[1]);
  
      // Output the image to the browser
      header("Content-type: image/png");
      ImagePng($im2);
  
      // If a cache directory has been defined, then store the cached file into it.
      if (!empty($_SESSION["opts"]["cache_dir"]))
        ImagePng($im2, $cache_file);  
    }
    else 
    {
      header("Content-Type: image/gif");
      $fp = fopen($_SESSION["opts"]["sc_location"].'/images/dot.gif', 'rb')  ;
      fpassthru($fp);
      fclose($fp);
    }
  }

  // Check to see if the CACHE_MAXSIZE_MB has been reached, and if so, delete the older files.
  if (file_exists($_SESSION["opts"]["cache_dir"]) && !empty($_SESSION["opts"]["cache_maxsize_mb"]) )
  {
    $dir      = $_SESSION["opts"]["cache_dir"];
    $max_size = $_SESSION["opts"]["cache_maxsize_mb"] * 1048576;
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