<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("mysql.php");
require_once("prefs.php");

// Do we have the "gd" extension loaded? can we load it dynamically?
if (!extension_loaded('gd'))
  if (! dl('gd.so'))
    send_to_log("Unable to perform image functions - PHP compiled without 'gd' support.");

// -------------------------------------------------------------------------------------------------
// Returns the colour number ofr a 24-bit colour
// -------------------------------------------------------------------------------------------------

function colour ( $r, $g, $b)
{
  return ($r << 16) | ($g << 8) | $b;
}

// -------------------------------------------------------------------------------------------------
// Modifies the ($x,$y) dimesnsions given for an image after it has been scaled to fit within the 
// specified bounding box ($box_x,$box_y)
// -------------------------------------------------------------------------------------------------

function image_get_scaled_xy(&$x,&$y,$box_x,$box_y)
{
  if ($x >0 && $y >0 && $box_x>0 && $box_y>0)
  {
    if ($x <= $y || ($box_x/$x*$y > $box_y) )
    {
      $newx = floor($box_y / $y * $x);
      $newy = $box_y;
    }
    
    if ($x >= $y || ($box_y/$y*$x > $box_x) )    
    {
      $newx = $box_x;
      $newy = floor($box_x / $x * $y);
    }
          
    $x = $newx;
    $y = $newy;
  }
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
    elseif ( file_exists($filename) || substr($filename,0,4) == 'http' )
      $image->load_from_file($filename); 
    else  
      send_to_log('Unable to process image specified : '.$filename);  
  
    $image->resize($x, $y);
    $image->cache($overwrite);
  }
  else 
    send_to_log('Unable to pre-cache image : '.$filename);
}

// -------------------------------------------------------------------------------------------------
// Returns the filename for caching the image locally (this is based on the filename used to load
// the file from disk or from the database and the image's current x/y size).
// -------------------------------------------------------------------------------------------------

function cache_filename( $filename, $x, $y )
{
  $cache_dir = get_sys_pref('cache_dir');
  if ($cache_dir != '')
    return $cache_dir.'/SwissCenter_'.sha1($filename).'_x'.$x.'y'.$y.'.png';
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
      send_to_log('Outputting as '.strtoupper($type).' for '.$filename);
    }
    else 
    {
      header("Content-type: image/png");
      $fp = fopen($filename,'rb');
      fpassthru($fp);
      send_to_log('Using passthru() for '.$filename);
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
  

  // -------------------------------------------------------------------------------------------------
  // Creates a blank image
  // -------------------------------------------------------------------------------------------------

  function CImage($x = 100, $y = 100)
  {
    $this->image          = imagecreate($x,$y);
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
      $this->image = false;
    }

    $this->image = ImageCreateFromString( db_value($sql) );
    $this->update_sizes();
    $this->src_fsp  = $sql.'.sql';
    $this->cache_filename = cache_filename($this->src_fsp,$this->width,$this->height);
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
          
    if ( is_file($filename) )
    {  
      switch (strtolower(file_ext($filename)))
      {
        case 'jpg':
        case 'jpeg':
          $this->image = ImageCreateFromJpeg($filename);
          break;
        case 'png':
          $this->image = ImageCreateFromPng($filename);
          break;
        case 'gif':
          $this->image = ImageCreateFromGif($filename);
          break;
        default :
          $this->image = false;
          break;
      }
      $this->update_sizes();
      $this->src_fsp  = $filename;
      $this->cache_filename = cache_filename($this->src_fsp,$this->width,$this->height);
    }
  }

  // -------------------------------------------------------------------------------------------------
  // Outputs some text onto the image (using truetype fonts).
  // -------------------------------------------------------------------------------------------------
  
  function text ($text, $x = 0, $y = 0, $colour, $size = 14, $font = '', $angle = 0 )
  {
    if (empty($font))
    {
      if (is_windows())
        $font = 'Arial';
      else
        $font = 'luxisr';
    }
    
    if ($this->image !== false)
    {
      @imagettftext ($this->image,$size,$angle,$x,$y,$colour,$font,$text);  
      $this->src_fsp  = false;
    }
  }
  
  // -------------------------------------------------------------------------------------------------
  // Copies a section of the given image onto the current image
  // -------------------------------------------------------------------------------------------------

  function copy(&$src_image, $dest_x, $dest_y, $dest_w = 0, $dest_h = 0)
  {
    if ($this->image !== false)
    {
      ImageAlphaBlending( $this->image, true);
      
      if ( ($dest_w == $src_image->get_width() && $dest_h == $src_image->get_height()) || ($dest_w == 0 && $dest_h == 0) )
        ImageCopy ( $this->image,  $src_image->get_image_ref() , $dest_x, $dest_y, 0,0, $src_image->get_width(), $src_image->get_height());
      else 
        ImageCopyResized( $this->image,  $src_image->get_image_ref() , $dest_x, $dest_y, 0, 0, $dest_w, $dest_h, $src_image->get_width(), $src_image->get_height() );

      $this->src_fsp  = false;
    }
  }

  // -------------------------------------------------------------------------------------------------
  // Resizes the current image to the given X,Y dimensions. If $keep_aspect is true, then the image
  // will be scaled to the given X,Y size, but the aspect ratio will be maintained.
  // -------------------------------------------------------------------------------------------------

  function resize($x, $y, $bgcolour=0, $keep_aspect = true)
  {    
    if ($this->image !== false && $x > 0 && $y > 0 && ($x != $this->width || $y != $this->height))
    {      
      // Work out new image sizes
      if ($keep_aspect) 
      {
        $newx = $this->get_width();
        $newy = $this->get_height();
        image_get_scaled_xy($newx,$newy,$x,$y);
      } 
      else 
      {
        $newx = $this->$x;
        $newy = $this->$y;
      }

      $old = $this->image;
      if ($bgcolour === false)
      {
        $this->image = ImageCreateTrueColor($newx,$newy);
        imagecopyResampled( $this->image, $old, 0,0,0,0, $newx, $newy, $this->width, $this->height);
      }
      else 
      {
        $this->image = ImageCreateTrueColor($x,$y);
        imagefill($this->image,0,0,$bgcolour);
        imagecopyResampled ($this->image, $old, ($x-$newx)/2, ($y-$newy)/2, 0, 0, $newx, $newy, $this->width, $this->height);
      }
      
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
      $this->image = ImageRotate($old, $angle, $bgcolour);
      imagedestroy($old);
      $this->update_sizes();
      $this->src_fsp  = false;
    }
  } 
  
  // -------------------------------------------------------------------------------------------------
  // Draws a filled rectangle of the given colour on the image
  // -------------------------------------------------------------------------------------------------
  
  function rectangle ( $x, $y, $width, $height, $colour )
  {
    imagefilledrectangle($this->image, $x, $y, $x+$width, $y+$height, $colour);
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
      switch (strtolower($type))
      {
        case 'jpg':
        case 'jpeg':
          header("Content-type: image/jpeg");
          imagejpeg($this->image,null,100);
          break;
        case 'png':
          header("Content-type: image/png");
          imagepng($this->image);
          break;
        case 'gif':
          header("Content-type: image/gif");
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
