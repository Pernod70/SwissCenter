<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/../ext/exif/exif_reader.php'));

if (!function_exists('imagerotate')) 
{
  // $bgcolour is a dummy value to ensure this function maps to the built-in one.
  // It is not needed here as we are only rotating by 90 or 270 degrees, and therefore
  // no background will be become visible.
  function ImageRotate( $imgSrc, $angle, $bgcolour)
  {
    // ensuring we got really RightAngle (if not we choose the closest one)
    $angle = 360 - min( ( (int)(($angle+45) / 90) * 90), 270 );

    // no need to rotate
    if( $angle == 0 )
      return( $imgSrc );

    $srcX = imagesx( $imgSrc );
    $srcY = imagesy( $imgSrc );

    if ($angle == 90) 
    {
      $imgDest = imagecreatetruecolor( $srcY, $srcX );
      for( $x=0; $x<$srcX; $x++ )
          for( $y=0; $y<$srcY; $y++ )
              imagecopy($imgDest, $imgSrc, $srcY-$y-1, $x, $x, $y, 1, 1);
    }
    elseif ($angle == 270)
    {
      $imgDest = imagecreatetruecolor( $srcY, $srcX );
      for( $x=0; $x<$srcX; $x++ )
          for( $y=0; $y<$srcY; $y++ )
              imagecopy($imgDest, $imgSrc, $y, $srcX-$x-1, $x, $y, 1, 1);
    }

    return( $imgDest );
  }
}

// Do we have the "gd" extension loaded? can we load it dynamically?
if (!extension_loaded('gd'))
{
  if (! dl('gd.so'))
    send_to_log(1,"Unable to perform image functions - PHP compiled without 'gd' support.");
}

// -------------------------------------------------------------------------------------------------
// Modifies the ($x,$y) dimesnsions given for an image after it has been scaled to fit within the
// specified bounding box ($box_x,$box_y)
// -------------------------------------------------------------------------------------------------

function image_get_scaled_xy(&$x,&$y,$box_x,$box_y)
{ 
  if ($x >0 && $y >0 && $box_x>0 && $box_y>0)
  {
    if ( $box_x/$x*$y >= $box_y )
    {
      $newx = floor($box_y / $y * $x);
      $newy = $box_y;
    }
    elseif ( $box_y/$y*$x >= $box_x )
    {
      $newx = $box_x;
      $newy = floor($box_x / $x * $y);
    }

    $x = $newx;
    $y = $newy;
  }
}

// -------------------------------------------------------------------------------------------------
// Resizes the image using the user's preferred option (resample or resize) from the config page.
// -------------------------------------------------------------------------------------------------

function preferred_resize( &$dimg, &$simg, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh, $rs_mode )
{
  if ($rs_mode == '')
    $rs_mode = get_sys_pref('IMAGE_RESIZING','RESAMPLE');

  // The showcenter seems to display images that have been resampled with messed up transparency, so we
  // only use the resample method if the image is greater than 150x150 pixels. This should give us a
  // reasonable balance between not resampling icons and losing quality on images,

  if ( $rs_mode == 'RESAMPLE' && ($dw > 150) && ($dh > 150) )
    imagecopyresampled( $dimg,  $simg , $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh );
  else
    ImageCopyResized( $dimg,  $simg , $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh );
}

/**
 * Downloads an image from the internet and caches it locally (for one hour).
 *
 * @param string $url
 * @return string - local filename
 */

function download_and_cache_image( $url )
{
  $filename = get_sys_pref('cache_dir').'/SwissCenter_download_'.md5($url).'.'.file_ext($url);
//  $filename = get_sys_pref('cache_dir').'/SwissCenter_download_'.md5($url).'_'.date('YmdH').'.'.file_ext($url);

  return (file_download_and_save($url, $filename) ? $filename : false);
}

// -------------------------------------------------------------------------------------------------
// Loads and resizes the given image and creates a cached copy of it.
// -------------------------------------------------------------------------------------------------

function precache( $filename, $x, $y, $overwrite = true )
{
  if ( extension_loaded('gd') )
  {
    // Create a new image
    $image = new CImage();

    // Load the image from disk
    if (strtolower(file_ext($filename)) == 'sql')
      $image->load_from_database( substr($filename,0,-4) );
    elseif ( file_exists($filename) )
      $image->load_from_file($filename);
    else
      send_to_log(1,'Unable to process image specified : '.$filename);

    $image->resize($x, $y);
    $image->cache($overwrite);
  }
  else
    send_to_log(1,'Unable to pre-cache image : '.$filename);
}

// -------------------------------------------------------------------------------------------------
// Returns the filename for caching the image locally (this is based on the filename used to load
// the file from disk or from the database and the image's current x/y size).
// -------------------------------------------------------------------------------------------------

function cache_filename( $filename, $x, $y, $rs_mode = '' )
{
  // If in design mode, we don't want to cache files, or use existing cached fules.
  if ( defined('STYLE_MODE') && STYLE_MODE == 'DESIGN' )
    return false;
  
  $cache_dir = get_sys_pref('cache_dir');
  if ($rs_mode == '')
    $rs_mode = get_sys_pref('IMAGE_RESIZING','RESAMPLE');
    
  if (file_ext($filename) != 'sql')
    $filetime = @filemtime($filename);
    
  if ($cache_dir != '')
    return $cache_dir.'/SwissCenter_'.sha1($filename.$filetime).'_x'.$x.'y'.$y.'_'.strtolower($rs_mode).'.png';
  else
    return false;
}

#-------------------------------------------------------------------------------------------------
# Function to check if the cache has exceeded the allowed size, and if so it deletes as many
# files as is necessary to reduce the cache down to 90% of the allowed size.
#-------------------------------------------------------------------------------------------------

function reduce_cache()
{
  $dir = get_sys_pref("cache_dir");
  $max_size = get_sys_pref("cache_maxsize_mb") * 1048576;
  $target_size = $max_size * 0.90; # 90%

  if (file_exists($dir) && $max_size != '' && $max_size > 0 )
  {
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

    // If the sum of images is greater than the cache size, remove as many files as necessary.
    if ($dir_size > $max_size)
    {
      ksort($files);
      foreach( $files as $k => $v)
        if ($dir_size > $target_size)
        {
          unlink($v);
          $details = split('_',$k);
          $dir_size -= $details[1];
        }

    }
  }
}

#-------------------------------------------------------------------------------------------------
# Outputs a cached file directly to the browser
#-------------------------------------------------------------------------------------------------

function output_cached_file( $filename , $type = '')
{
  if ( file_exists($filename) )
  {
    if ($type != '')
    {
      $image = new CImage();
      $image->load_from_file($filename);
      $image->output($type,false);
      send_to_log(6,'Outputting cached file as '.strtoupper($type));
    }
    else
    {
      session_write_close();
      header("Content-type: image/png");
      $fp = fopen($filename,'rb');
      fpassthru($fp);
      fclose($fp);
    }
  }
}

#-------------------------------------------------------------------------------------------------
# Image class
#-------------------------------------------------------------------------------------------------

class CImage
{

  var $image          = false;
  var $width          = 0;
  var $height         = 0;
  var $src_fsp        = false;
  var $cache_filename = false;
  var $exif_data      = false;

  // -------------------------------------------------------------------------------------------------
  // Creates a blank image
  // -------------------------------------------------------------------------------------------------

  function CImage($x = 100, $y = 100)
  {
    $this->image          = imagecreatetruecolor($x,$y);
    $this->width          = $x;
    $this->height         = $y;
    $this->src_fsp        = false;
    $this->cache_filename = false;
  }

  // -------------------------------------------------------------------------------------------------
  // Return image attributes
  // -------------------------------------------------------------------------------------------------

  function get_height()
  { return $this->height; }

  function get_width()
  { return $this->width; }

  function get_image_ref()
  { return $this->image; }

  // -------------------------------------------------------------------------------------------------
  // Allocate a colour to be used in the image (especially useful for alpha blending).
  // -------------------------------------------------------------------------------------------------

  function allocate_colour( $r, $g, $b, $alpha = 0)
  {
    if ($this->image !== false)
      return imagecolorallocatealpha($this->image, $r, $g, $b, $alpha);
    else
      return false;
  }

  // -------------------------------------------------------------------------------------------------
  // Updates the known width and height of the image
  // -------------------------------------------------------------------------------------------------

  function update_sizes()
  {
    if ($this->image !== false)
    {
      $this->width  = imagesx($this->image);
      $this->height = imagesy($this->image);
    }
    else
    {
      $this->width = 0;
      $this->height = 0;
    }
  }

  // -------------------------------------------------------------------------------------------------
  // Loads an image from the database
  // -------------------------------------------------------------------------------------------------

  function load_from_database($sql)
  {
    if ($this->image !== false)
    {
      imagedestroy($this->image);
      $this->exif_data = false;
      $this->image = false;
    }

    $this->image = ImageCreateFromString( db_value($sql) );
    if ($this->image !== false)
    {
      $this->update_sizes();
      $this->src_fsp  = $sql.'.sql';
      $this->cache_filename = cache_filename($this->src_fsp,$this->width,$this->height);
    }
  }

  // -------------------------------------------------------------------------------------------------
  // Loads an image from a file
  // -------------------------------------------------------------------------------------------------

  function load_from_file($filename)
  {
    if ($this->image !== false)
    {
      imagedestroy($this->image);
      $this->image = false;
    }
    
    if ( is_file($filename))
    {      
      switch (strtolower(file_ext($filename)))
      {
        case 'jpg':
        case 'jpeg':
          $this->image = ImageCreateFromJpeg($filename);
          break;
        case 'png':
          $this->image = ImageCreateFromPng($filename);
          imageAlphaBlending($this->image, false);
          imageSaveAlpha($this->image, true);
          break;
        case 'gif':
          $this->image = ImageCreateFromGif($filename);
          break;
        default :
          $this->image = false;
          break;
      }
      
      // If the image created successfully, then update the other information.
      if ($this->image !== false)
      {
        $this->update_sizes();
        $this->src_fsp  = $filename;
        $this->cache_filename = cache_filename($this->src_fsp,$this->width,$this->height);
        $this->exif_data = exif($this->src_fsp);
      }
      else
        send_to_log(2,"ERROR: Image failed to load in function load_image_from_file",$filename);
    }
    
    return $this->image;
  }

  // -------------------------------------------------------------------------------------------------
  // Outputs some text onto the image (using truetype fonts).
  // NOTE: The font-size given should be specified in pixels.
  // -------------------------------------------------------------------------------------------------

  function text ($text, $x = 0, $y = 0, $colour = 0, $size = 14, $font = '', $angle = 0 )
  {
    // Exit value of this function.
    $result = false;
    
    // GD version 2 takes the font-size argument in points, whereas this function takes the 
    // text size in pixels. We therefore need to convert the given value before passing to GD.
    if (gd_version() >=3)
      $size *= 0.8;
    
    // Determine the font to use if not specified.
    if (empty($font))
      $font = get_sys_pref('TTF_FONT');

    // Write the text to the image
    if ($this->image !== false)
    {
      $result = @imagettftext ($this->image,$size,$angle,$x,$y,$colour,$font,$text);
      if ( $result === false )
        send_to_log(5,"Unable to use Truetype Font '$font' to display '$text'");
      
      $this->src_fsp  = false;
    }
    
    return $result;
  }
  
  /**
   * Returns the width (in pixels) of the text when rendered
   *
   * @param string $text
   * @param integer $size
   * @param string $font
   * @param integer $angle
   * @return integer
   */
  
  function get_text_width( $text, $size = 14, $font = '', $angle = 0)
  {
    if (gd_version() >=3)
      $size *= 0.8;
    
    if (empty($font))
      $font = get_sys_pref('TTF_FONT');
      
    $box = @imagettfbbox($size, $angle, $font, $text);
    return ($box[2] - $box[6]);
  }
  
  /**
   * Returns the height (in pixels) of the text when rendered
   *
   * @param string $text
   * @param integer $size
   * @param string $font
   * @param integer $angle
   * @return integer
   */

  function get_text_height( $text, $size = 14, $font = '', $angle = 0)
  {
    if (gd_version() >=3)
      $size *= 0.8;
    
    if (empty($font))
      $font = get_sys_pref('TTF_FONT');
      
    $box = @imagettfbbox($size, $angle, $font, $text);
    return ($box[3] - $box[7]);
  }
  
  // -------------------------------------------------------------------------------------------------
  // Copies a section of the given image onto the current image
  // -------------------------------------------------------------------------------------------------

  function copy(&$src_image, $dest_x, $dest_y)
  {
    if ($this->image !== false)
    {
      send_to_log(8,"Copying image to ($dest_x,$dest_y)");
      ImageAlphaBlending( $this->image, true);
      ImageCopy ( $this->image,  $src_image->get_image_ref() , $dest_x, $dest_y, 0,0, $src_image->get_width(), $src_image->get_height());
      $this->src_fsp  = false;
    }
  }
 
  // -------------------------------------------------------------------------------------------------
  // Resizes an image to fit a particular width or height.
  // -------------------------------------------------------------------------------------------------

  function resize_to_height($y, $rs_mode = '', $border_colour = false)
  {
    $x = floor($this->get_width() * ($y/$this->get_height()));
    send_to_log(8,"Calculating width of image to fit a height of $y. New image size is ($x,$y)");
    $this->resize($x,$y,0,true,$rs_mode,$border_colour);    
  }
  
  function resize_to_width($x, $rs_mode = '', $border_colour = false)
  {
    $y = floor($this->get_height() * ($x/$this->get_width()));
    send_to_log(8,"Calculating width of image to fit a width of $x. New image size is ($x,$y)");
    $this->resize($x,$y,0,true,$rs_mode,$border_colour);    
  }

  // -------------------------------------------------------------------------------------------------
  // Resizes the current image to the given X,Y dimensions. If $keep_aspect is true, then the image
  // will be scaled to the given X,Y size, but the aspect ratio will be maintained.
  // -------------------------------------------------------------------------------------------------

  function resize($x, $y, $bgcolour=0, $keep_aspect = true, $rs_mode = '', $border_colour = false)
  {
    if ($this->image !== false && $x > 0 && $y > 0 && ($x != $this->width || $y != $this->height))
    {
      // Work out new image sizes
      if ($keep_aspect)
      {
        $newx = $this->get_width();
        $newy = $this->get_height();
        image_get_scaled_xy($newx,$newy,$x,$y);
        send_to_log(8,"Resizing $this->src_fsp ($this->width,$this->height) to fit ($x,$y). New size is ($newx,$newy)");
      }
      else
      {
        $newx = $x;
        $newy = $y;
        send_to_log(8,"Stretching $this->src_fsp ($this->width,$this->height) to ($x,$y)");
      }

      $old = $this->image;
      $this->image = ImageCreateTrueColor($x,$y);
      ImageAlphaBlending( $this->image, false);
      ImageSaveAlpha($this->image, true);
      $bgcolour = $this->allocate_colour(0,0,0,127);
      imagefilledrectangle($this->image, 0, 0, $x, $y, $bgcolour);
      send_to_log(8,"Built temporary image");
      preferred_resize($this->image, $old, ($x-$newx)/2, ($y-$newy)/2, 0, 0, $newx, $newy, $this->width, $this->height, $rs_mode);
      send_to_log(8,"Resized/Stretched image");

      if ( $border_colour !== false)
        $this->rectangle( ($x-$newx)/2, ($y-$newy)/2, $newx-1, $newy-1,  $border_colour, false);
      
      imagedestroy($old);
      $this->update_sizes();
      $this->cache_filename = cache_filename($this->src_fsp,$x, $y);
    }
  }

  // -------------------------------------------------------------------------------------------------
  // Rotates the current image by the specified angle, leaving the background the specified
  // colour (default black)
  // -------------------------------------------------------------------------------------------------

  function rotate($angle, $bgcolour = 0)
  {
    if ($this->image !== false && $angle != 0)
    {
      $old = $this->image;
      $this->image = ImageRotate($old, 360-$angle, $bgcolour);
      imagedestroy($old);
      $this->update_sizes();
      $this->src_fsp  = false;
    }
  }
  
  function flip_horizontal()
  {
    if ($this->image !== false)
    {
      $w = imagesx($this->image);
      $h = imagesy($this->image);
      $old = $this->image;
      $this->image = ImageCreateTrueColor($w,$h);

      for ($x = 0; $x < $w; $x++)
       imagecopy($this->image, $old, $x, 0, $w - $x - 1, 0, 1, $h);
        
      imagedestroy($old);
      $this->src_fsp = false;
    }
  }

  function flip_vertical()
  {
    if ($this->image !== false)
    {
      $w = imagesx($this->image);
      $h = imagesy($this->image);
      $old = $this->image;
      $this->image = ImageCreateTrueColor($w,$h);

      for ($y = 0; $y < $h; $y++)
        imagecopy($this->image, $old, 0, $y, 0, $h - $y - 1, $w, 1);
        
      imagedestroy($old);
      $this->src_fsp = false;
    }
  }

  /**
   * Rotates/flips an image to display it in the correct orientation as recorded
   * by the EXIF data held within the original file.
   * 
   * NOTE: This function does nothing if the original file was loaded from the
   * database.
   *
   */
  
  function rotate_by_exif()
  {
    if ( $this->exif_data !== false)
    {
      $orientation = $this->exif_data['Orientation'];
      
      if ( $orientation == 5 || $orientation == 6 || $orientation == 7)
        $this->rotate(90);          
      elseif ( $orientation == 8 )
        $this->rotate(270);
    
      if ( $orientation == 2 || $orientation == 5 || $orientation == 3 )
        $this->flip_horizontal();
    
      if ( $orientation == 4 || $orientation == 7 || $orientation == 3 )
        $this->flip_vertical();    
    }
  }
  
  /**
   * Returns true if the image dimensions will be swapped over due to a rotation
   * according to the EXIF orientation data.
   *
   * @return boolean
   */
  
  function rotate_by_exif_changes_aspect()
  {
    if ( $this->exif_data !== false)
      return ( $this->exif_data['Orientation'] >=5 && $this->exif_data['Orientation']<=8);
    else 
      return false;
  }

  // -------------------------------------------------------------------------------------------------
  // Draws a filled rectangle of the given colour on the image
  // -------------------------------------------------------------------------------------------------

  function rectangle ( $x, $y, $width, $height, $colour, $filled = true )
  {
    if ($filled)
      imagefilledrectangle($this->image, $x, $y, $x+$width, $y+$height, $colour);
    else 
      imagerectangle($this->image, $x, $y, $x+$width, $y+$height, $colour);    
    
    $this->src_fsp  = false;
  }

  // -------------------------------------------------------------------------------------------------
  // Draws a line of the given colour on the image
  // -------------------------------------------------------------------------------------------------

  function line ( $x, $y, $x2, $y2, $colour )
  {
    imageline($this->image, $x, $y, $x2, $y2, $colour);    
    $this->src_fsp  = false;
  }

  // -------------------------------------------------------------------------------------------------
  // Outputs the current image in the specified type
  // at the given location.
  // -------------------------------------------------------------------------------------------------

  function output ($type, $cache = true)
  {
    if ($this->image !== false)
    {
      session_write_close();
      switch (strtolower($type))
      {
        case 'jpg':
        case 'jpeg':
          // Create a copy to ensure transparency is converted to black.
          $copy = ImageCreateTrueColor($this->width,$this->height);
          $bgcolour = imagecolorallocate($copy, 0, 0, 0);
          imagefilledrectangle($copy, 0, 0, $this->width, $this->height, $bgcolour);          
          imagecopy($copy, $this->image, 0,0 ,0,0, $this->width,$this->height);
                   
          // Output the image
          header("Content-type: image/jpeg");
          send_to_log(8,"Outputting JPEG image");
          ob_clean();
          imagejpeg($copy);
          break;
        case 'png':
          header("Content-type: image/png");
          send_to_log(8,"Outputting PNG image");
          ob_clean();
          imagepng($this->image);
          break;
        case 'gif':
          header("Content-type: image/gif");
          send_to_log(8,"Outputting GIF image");
          ob_clean();
          imagegif($this->image);
          break;
      }
    }

    if ($cache)
      $this->cache();
  }

  // -------------------------------------------------------------------------------------------------
  // Caches the file locally.
  // -------------------------------------------------------------------------------------------------

  function cache($overwrite = false)
  {
    if ($this->src_fsp !== false )
    {
      $fsp = $this->cache_filename;
      if ($fsp !== false )
      {
      	if ($overwrite || !file_exists($fsp))
      	{
          ImagePng($this->image, $fsp);
          reduce_cache();
      	}
      	else
      	  touch($fsp);
      }
    }
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
