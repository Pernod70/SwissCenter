<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

#-------------------------------------------------------------------------------------------------
# Returns the colour number ofr a 24-bit colour
#-------------------------------------------------------------------------------------------------

function colour ( $r, $g, $b)
{
  return ($r << 16) | ($g << 8) | $b;
}

#-------------------------------------------------------------------------------------------------
# Image class
#-------------------------------------------------------------------------------------------------

class CImage
{

  var $image;
  var $width = 0;
  var $height = 0;
  

  // -------------------------------------------------------------------------------------------------
  // Creates a blank image
  // -------------------------------------------------------------------------------------------------

  function CImage($x = 100, $y = 100)
  {
    $this->image  = imagecreate($x,$y);
    $this->width  = $x;
    $this->height = $y;
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
      @imagettftext ($this->image,$size,$angle,$x,$y,$colour,$font,$text);  
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
    }
  }

  // -------------------------------------------------------------------------------------------------
  // Rotates the current image by the specified angle, leaving the background the specified
  // colour (default black)
  // -------------------------------------------------------------------------------------------------

  function resize($x, $y, $bgcolour=0, $keep_aspect = true)
  {    
    if ($this->image !== false && $x > 0 && $y > 0)
    {
      if ($keep_aspect) 
      {
        if ($x && ($this->width < $this->height))
        {
          $newx = floor(($y / $this->height) * $this->width);
          $newy = $y;
        }
        else
        {
          $newx = $x;
          $newy = floor(($x / $this->width) * $this->height);
        }
      }

      $old = $this->image;
      $this->image = ImageCreateTrueColor($x,$y);
      imagefill($this->image,0,0,$bgcolour);
      imagecopyResampled ($this->image, $old, ($x-$newx)/2, ($y-$newy)/2, 0, 0, $newx, $newy, $this->width, $this->height);
      imagedestroy($old);
      $this->update_sizes();
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
    }
  } 
  
  // -------------------------------------------------------------------------------------------------
  // Draws a filled rectangle of the given colour on the image
  // -------------------------------------------------------------------------------------------------
  
  function rectangle ( $x, $y, $width, $height, $colour )
  {
    imagefilledrectangle($this->image, $x, $y, $x+$width, $y+$height, $colour);
  }

  // -------------------------------------------------------------------------------------------------
  // Outputs the current image in the specified type
  // at the given location.
  // -------------------------------------------------------------------------------------------------

  function output ($type)
  {
    if ($this->image !== false)
    {
      switch (strtolower($type))
      {
        case 'jpg':
        case 'jpeg':
          header("Content-type: image/jpeg");
          imagejpeg($this->image);
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
  }


}

   



/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
