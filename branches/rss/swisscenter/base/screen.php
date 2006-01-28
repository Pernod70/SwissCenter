<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

#-------------------------------------------------------------------------------------------------
# This function determines the type of display that the user is using to view the SwissCenter on
# and therefore how the interface should be adjusted to allow for different capabilities
#
# EG: HDTV is always widescreen, whilst PAL and NTSC are more 4:3.
#     PAL has more lines than NTSC and therefore a portion of the interface is "lost".
#-------------------------------------------------------------------------------------------------

function get_screen_type()
{
// NTSC - 624,416 (from agent string)
// PAL  - 624,496 (from agent string)
// HDTV - 1280,720 (from agent string)
// HDTV - 1920,1080 

  $_SESSION["screen"]["width"]  = 624;
  $_SESSION["screen"]["height"] = 496;
  $_SESSION["screen"]["type"]   = 'HDTV';
  
  return $_SESSION["screen"]["type"];
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
  return ceil($_SESSION["screen"]["width"] * $x / 100);
}

function convert_y( $y)
{
  get_screen_type(); 
  return ceil($_SESSION["screen"]["height"] * $y / 100);  
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
