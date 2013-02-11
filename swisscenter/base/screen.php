<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

define('BROWSER_COORDS',1);
define('SCREEN_COORDS',2);
define('BROWSER_SCREEN_COORDS',3);

define('FONTSIZE_HEADER',1);
define('FONTSIZE_SUBHEADER',2);
define('FONTSIZE_HEADER_NTSC',3);
define('FONTSIZE_BODY',4);
define('FONTSIZE_MENUTEXT',5);
define('FONTSIZE_THUMBTEXT',6);
define('FONTSIZE_KEYPAD',7);
define('FONTSIZE_ICONBAR',8);
define('FONTSIZE_FOOTER',9);

define('SD',1);
define('HD',2);

function store_browser_size( $res )
{
  // This is really crappy, but the hardware sends the wrong browser resolution for HDTV screens
  // so we have to explicitly check for it here and then override it.

  if (!is_pc() && $res == '1280x720')
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

function store_browser_scr_size( $res )
{
  // Some HD hardware use different screen resolutions for browser and viewing media.

  if (!empty($res))
  {
    list ($x, $y) = explode('x',$res);
    $_SESSION["device"]["browser_scr_x_res"] = $x;
    $_SESSION["device"]["browser_scr_y_res"] = $y;
  }
  else
  {
    list ($x, $y) = explode('x',$res);
    $_SESSION["device"]["browser_scr_x_res"] = $_SESSION["device"]["screen_x_res"];
    $_SESSION["device"]["browser_scr_y_res"] = $_SESSION["device"]["screen_y_res"];
  }
}

function store_screen_size( $res = '')
{
  // The NMT players return incorrect resolution for 1080p, replace 1920x1280 with 1920x1080
  // Syabas/01-17-081024-15-POP-403-091/15-POP Firefox/0.8.0+ (gaya1 TV Res1920x1280; Browser Res1100x656-32bits; Res1280x720;)

  if (!is_pc() && $res == '1920x1280')
  {
    $_SESSION["device"]["screen_x_res"] = 1920;
    $_SESSION["device"]["screen_y_res"] = 1080;
  }
  elseif (!empty($res))
  {
    list ($x, $y) = explode('x',$res);
    $_SESSION["device"]["screen_x_res"] = $x;
    $_SESSION["device"]["screen_y_res"] = $y;
  }
  else
  {
    // We have not been provided with the actual screen size, so deduce it from the browser sizes.
    if ($_SESSION["device"]["browser_x_res"] != 624 )
      store_screen_size('1280x720'); // HDTV
    elseif ($_SESSION["device"]["browser_y_res"] == 496 )
      store_screen_size('720x576'); // PAL
    else
      store_screen_size('720x480'); // NTSC
  }
}

function get_browser_size()
{
  return $_SESSION["device"]["browser_x_res"].'x'.$_SESSION["device"]["browser_y_res"];
}

function get_browser_scr_size()
{
  return $_SESSION["device"]["browser_scr_x_res"].'x'.$_SESSION["device"]["browser_scr_y_res"];
}

function get_screen_size()
{
  return $_SESSION["device"]["screen_x_res"].'x'.$_SESSION["device"]["screen_y_res"];
}

/**
 * This function determines the type of display that the user is using to view the SwissCenter on
 * and therefore how the interface should be adjusted to allow for different capabilities
 *
 * EG: HDTV is always widescreen, whilst PAL and NTSC are more 4:3.
 *     PAL has more lines than NTSC and therefore a portion of the interface is "lost".
 *
 * NOTES:
 *
 * If there is no HTTP_USER_AGENT string, then it is likely that the Showcenter media firmware is
 * trying to access SwissCenter (the firmware doesn't identify itself at all). Therefore, we need
 * to determine the screen attributes by re-loading the cached agent string for this IP address.
 */
function get_screen_type()
{
  if (!isset($_SESSION["device"]["screen_type"]))
  {
    // Retrieve the AGENT_STRING from the database if it's not provided by the client
    if ( empty($_SESSION["device"]["agent_string"]) )
      $_SESSION["device"]["agent_string"] = db_value("select agent_string from clients where ip_address='".str_replace('\\','/',$_SERVER["REMOTE_ADDR"])."'");

    // Determine the resolution based on the client type
    if ( is_pc() )
    {
      $pc_screen_size = get_sys_pref('PC_SCREEN_SIZE','800x450');
      store_browser_size($pc_screen_size);
      store_browser_scr_size($pc_screen_size);
      store_screen_size($pc_screen_size);
    }
    elseif ( get_player_model() >= 400 ) // NMT player
    {
      store_screen_size( preg_get( '/TV Res([0-9]+x[0-9]+)/i', $_SESSION["device"]["agent_string"]) );
      store_browser_size( preg_get( '/Browser Res([0-9]+x[0-9]+)/i', $_SESSION["device"]["agent_string"]) );
      store_browser_scr_size( preg_get( '/;\s*Res([0-9]+x[0-9]+)/i', $_SESSION["device"]["agent_string"]) );
    }
    else
    {
      preg_match_all("/[0-9]*x[0-9]*/",$_SESSION["device"]["agent_string"],$matches);
      store_browser_size($matches[0][0]);
      store_screen_size($matches[0][1]);
      store_browser_scr_size($matches[0][1]);
    }

    // What type of screen is it... Widescreen (16:9) or normal (4:3)?
    $_SESSION["device"]["aspect"] = ($_SESSION["device"]["browser_scr_x_res"]/16*9 == $_SESSION["device"]["browser_scr_y_res"] ? '16:9' : '4:3');

    // How do we classify this screen... PAL, NTSC or HDTV?
    if ( $_SESSION["device"]["browser_x_res"] == 624)
      $_SESSION["device"]["screen_type"] = ($_SESSION["device"]["browser_y_res"] == 416 ? 'NTSC' : 'PAL');
    else
      $_SESSION["device"]["screen_type"] = 'HDTV';

    // Record some debugging information
    send_to_log(6,'Device: ',$_SESSION["device"]);
  }

  // Return the screen type
  return $_SESSION["device"]["screen_type"];
}

function is_screen_pal()
{ return ( get_screen_type() == 'PAL' ? true : false ); }

function is_screen_ntsc()
{ return ( get_screen_type() == 'NTSC' ? true : false ); }

function is_screen_hdtv()
{ return ( get_screen_type() == 'HDTV' ? true : false ); }

/**
 * Routines to take X and Y (or width and height) values which are specified as a percentage and
 * return them as actual pixel values in the current screen type (values may be specified with
 * a decimal component).
 *
 */

function convert_x( $x, $coords = BROWSER_COORDS )
{
  get_screen_type();
  if ( $coords == SCREEN_COORDS )
    return ceil($_SESSION["device"]["screen_x_res"] * $x / 1000);
  elseif ( $coords == BROWSER_SCREEN_COORDS )
    return ceil($_SESSION["device"]["browser_scr_x_res"] * $x / 1000);
  else
    return ceil($_SESSION["device"]["browser_x_res"] * $x / 1000);
}

function convert_y( $y, $coords = BROWSER_COORDS )
{
  get_screen_type();
  if ( $coords == SCREEN_COORDS )
    return ceil($_SESSION["device"]["screen_y_res"] * $y / 1000);
  elseif ( $coords == BROWSER_SCREEN_COORDS )
    return ceil($_SESSION["device"]["browser_scr_y_res"] * $y / 1000);
  else
    return ceil($_SESSION["device"]["browser_y_res"] * $y / 1000);
}

function convert_tolog_x( $x, $coords = BROWSER_COORDS )
{
  get_screen_type();
  if ( $coords == SCREEN_COORDS )
    return ceil(1000 * $x / $_SESSION["device"]["screen_x_res"]);
  elseif ( $coords == BROWSER_SCREEN_COORDS )
    return ceil(1000 * $x / $_SESSION["device"]["browser_scr_x_res"]);
  else
    return ceil(1000 * $x / $_SESSION["device"]["browser_x_res"]);
}

function convert_tolog_y( $y, $coords = BROWSER_COORDS )
{
  get_screen_type();
  if ( $coords == SCREEN_COORDS )
    return ceil(1000 * $y / $_SESSION["device"]["screen_y_res"]);
  elseif ( $coords == BROWSER_SCREEN_COORDS )
    return ceil(1000 * $y / $_SESSION["device"]["browser_scr_y_res"]);
  else
    return ceil(1000 * $y / $_SESSION["device"]["browser_y_res"]);
}

/**
 * Returns the size (in pixels) given a size in logical coordinates.
 *
 * @param $desired_size
 * @param $coords
 * @return unknown_type
 */
function font_size( $desired_size, $coords = BROWSER_COORDS )
{
  return convert_y( $desired_size, $coords );
}

/**
 * Returns the font to be used dependant on hardware player and whether HD output is used.
 *
 * @param $font
 * @param $player
 * @param $def
 * @return integer
 */
function get_font( $font, $player = 0, $def = HD )
{
  $fonts = array(
  // PC browser.
   0  => array( FONTSIZE_HEADER      => 40,
                FONTSIZE_SUBHEADER   => 32,
                FONTSIZE_HEADER_NTSC => 32,
                FONTSIZE_BODY        => 32,
                FONTSIZE_MENUTEXT    => 28,
                FONTSIZE_THUMBTEXT   => 20,
                FONTSIZE_KEYPAD      => 30,
                FONTSIZE_ICONBAR     => 32,
                FONTSIZE_FOOTER      => 32 ),
  // 8550 based players. ie. ShowCenter 1000
  100 => array( FONTSIZE_HEADER      => array( SD => 5, HD => 5 ),
                FONTSIZE_SUBHEADER   => array( SD => 3, HD => 3 ),
                FONTSIZE_HEADER_NTSC => array( SD => 3, HD => 3 ),
                FONTSIZE_BODY        => array( SD => 3, HD => 3 ),
                FONTSIZE_MENUTEXT    => array( SD => 3, HD => 3 ),
                FONTSIZE_THUMBTEXT   => array( SD => 1, HD => 1 ),
                FONTSIZE_KEYPAD      => array( SD => 3, HD => 3 ),
                FONTSIZE_ICONBAR     => array( SD => 3, HD => 3 ),
                FONTSIZE_FOOTER      => array( SD => 3, HD => 3 ) ),
  // 8620 based players. ie. ShowCenter 200, EVA700, etc.
  200 => array( FONTSIZE_HEADER      => array( SD => 4, HD => 4 ),
                FONTSIZE_SUBHEADER   => array( SD => 3, HD => 3 ),
                FONTSIZE_HEADER_NTSC => array( SD => 3, HD => 3 ),
                FONTSIZE_BODY        => array( SD => 3, HD => 3 ),
                FONTSIZE_MENUTEXT    => array( SD => 2, HD => 2 ),
                FONTSIZE_THUMBTEXT   => array( SD => 1, HD => 1 ),
                FONTSIZE_KEYPAD      => array( SD => 3, HD => 3 ),
                FONTSIZE_ICONBAR     => array( SD => 3, HD => 3 ),
                FONTSIZE_FOOTER      => array( SD => 3, HD => 3 ) ),
  // 8635/8643 based players. ie. PCH A100/C200, etc.
  400 => array( FONTSIZE_HEADER      => array( SD => 6, HD => 6 ),
                FONTSIZE_SUBHEADER   => array( SD => 4, HD => 5 ),
                FONTSIZE_HEADER_NTSC => array( SD => 4, HD => 5 ),
                FONTSIZE_BODY        => array( SD => 4, HD => 5 ),
                FONTSIZE_MENUTEXT    => array( SD => 3, HD => 4 ),
                FONTSIZE_THUMBTEXT   => array( SD => 2, HD => 2 ),
                FONTSIZE_KEYPAD      => array( SD => 4, HD => 5 ),
                FONTSIZE_ICONBAR     => array( SD => 5, HD => 5 ),
                FONTSIZE_FOOTER      => array( SD => 3, HD => 4 ) ) );

  if ( $player == 0 )
    return $fonts[$player][$font];
  else
    return $fonts[$player][$font][$def];
}

/**
 * Given a desired font size (in logical coordinates), return the nearest size HTML font that the
 * hardware player can display.
 *
 * @param $size
 * @param $colour
 * @return string
 */
function font_tags( $size = false, $colour = false)
{
  // Size
  if ( $size === false)
    $size_param = '';
  else
  {
    if ( is_pc() )
    {
      // Convert logical coordinates font-size (1-1000) to actual pixels
      $size = convert_y(get_font($size));
      $size_param = 'style="font-size : '.$size.'px;"';
    }
    else
    {
      $player_type = floor(get_player_model()/100)*100;
      $size_param = 'size="'.get_font($size, $player_type, is_screen_hdtv() ? HD : SD).'"';
    }
  }

  // Colour
  if ($colour === false)
    $colour_param = '';
  elseif ($colour[0] == '#')
    $colour_param = 'color="'.$colour.'"';
  else
    $colour_param = 'color="'.style_value($colour,'#FFFFFF').'"';

  return "<font $size_param $colour_param>";
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
