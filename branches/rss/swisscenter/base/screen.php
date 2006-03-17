<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

function store_browser_size( $res )
{
  // This is really crappy, but the hardware sends the wrong browser resolution for HDTV screens
  // so we have to explicitly check for it here and then override it.
  
  if ($res == '1280x720')
  {
    $_SESSION["device"]["browser_x_res"] = 1080;
    $_SESSION["device"]["browser_y_res"] =  640;      
  }
  else 
  {
    list ($x, $y) = explode('x',$res);  
    $_SESSION["device"]["browser_x_res"] = $x;
    $_SESSION["device"]["browser_y_res"] = $y;      
  }
}

function store_screen_size( $res = '')
{
  if (!empty($res))
  {
    list ($x, $y) = explode('x',$res);  
    $_SESSION["device"]["screen_x_res"] = $x;
    $_SESSION["device"]["screen_y_res"] = $y;      
  }
  else 
  {
    // We have not been provided with the actual scrren size, so deduce it from the browser sizes.
    if ($_SESSION["device"]["browser_x_res"] != 624 )
      store_screen_size('1280x720'); // HDTV
    elseif ($_SESSION["device"]["browser_y_res"] == 496 )
      store_screen_size('720x576'); // PAL
    else 
      store_screen_size('720x480'); // NTSC
  }
}

#-------------------------------------------------------------------------------------------------
# This function determines the type of display that the user is using to view the SwissCenter on
# and therefore how the interface should be adjusted to allow for different capabilities
#
# EG: HDTV is always widescreen, whilst PAL and NTSC are more 4:3.
#     PAL has more lines than NTSC and therefore a portion of the interface is "lost".
#-------------------------------------------------------------------------------------------------

function get_screen_type()
{
  if ( is_pc() )
  {
    store_browser_size('800x450');
    store_screen_size('800x450');
  }
  else 
  {
    preg_match_all("/[0-9]*x[0-9]*/",$_SESSION["device"]["agent_string"],$matches);  
    store_browser_size($matches[0][0]);
    store_screen_size($matches[0][1]);
  }
  
  // What type of screen is it... Widescreen (16:9) or normal (4:3)?
  $_SESSION["device"]["aspect"] = ($_SESSION["device"]["browser_x_res"]/16*9 == $_SESSION["device"]["browser_y_res"] ? '16:9' : '4:3');

  // How do we clasify this screen... PAL, NTSC or HDTV?
  if ( $_SESSION["device"]["browser_x_res"] == 624)
    $_SESSION["device"]["screen_type"] = ($_SESSION["device"]["browser_y_res"] == 416 ? 'NTSC' : 'PAL');
  else
    $_SESSION["device"]["screen_type"] = 'HDTV';
      
  // Return the screen type
  return $_SESSION["device"]["screen_type"];
}

function is_screen_pal()
{ return ( get_screen_type() == 'PAL' ? true : false ); }

function is_screen_ntsc()
{ return ( get_screen_type() == 'NTSC' ? true : false ); }

function is_screen_hdtv()
{ return ( get_screen_type() == 'HDTV' ? true : false ); }

#-------------------------------------------------------------------------------------------------
# Routines to take X and Y (or width and height) values which are specified as a percentage and 
# return them as actual pixel values in the current screen type (values may be specified with 
# a decimal component).
#-------------------------------------------------------------------------------------------------

function convert_x( $x )
{
  get_screen_type();  
  return ceil($_SESSION["device"]["browser_x_res"] * $x / 1000);
}

function convert_y( $y)
{
  get_screen_type(); 
  return ceil($_SESSION["device"]["browser_y_res"] * $y / 1000);  
}

function font_nearest( $desired_size )
{
  if ( is_pc() )
    $size = $desired_size;
  else
  {
    // Convert font size to pixels for this display device.
    $desired_size = convert_y($desired_size);

    // The hardware players only have a small number of font sizes available, so try to work out which
    // font size will give the closest number of pixels to that desired and then return the font size
    // in logical co-ordinates (0-1000).
    
    if     ($desired_size <= 12) 
      $size = 10.4;
    elseif ($desired_size <= 15) 
      $size = 20.8;
    elseif ($desired_size <= 17) 
      $size = 24;
    elseif ($desired_size <= 20) 
      $size = 25.6;
    elseif ($desired_size <= 26) 
      $size = 28.8;
    else
      $size = 38.5;
  }
  
  return $size;
}

function font_tags( $size = false, $colour = false)
{
  // Size
  if ( $size === false)
    $size_param = '';
  else
  {
    // Convert font size to pixels for this display device.
    $size = convert_y($size);
  
    if ( is_pc() )
      $size_param = 'style="font-size : '.$size.'px;"';
    else
    {
      // The hardware players only have a small number of font sizes available... so try to pick the best one
      if     ($size <= 12) 
        $size_param = 'size="1"';
      elseif ($size <= 15) 
        $size_param = 'size="2"';
      elseif ($size <= 17) 
        $size_param = 'size="3"';
      elseif ($size <= 20) 
        $size_param = 'size="4"';
      elseif ($size <= 26) 
        $size_param = 'size="5"';
      else
        $size_param = 'size="6"';    
    }
  }

  // Colour
  if ($colour === false)    
    $colour_param = '';
  elseif ($colour[0] == '#')
    $colour_param = 'color="'.$colour.'"';
  else
    $colour_param = 'color="'.style_value($name,'#FFFFFF').'"';
  
  return "<font $size_param $colour_param>";
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
