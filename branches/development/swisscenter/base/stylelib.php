<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("users.php");

//-------------------------------------------------------------------------------------------------
// This procedure stores the STYLE information in the current session. 
//-------------------------------------------------------------------------------------------------

function load_style()
{
/*  if(is_user_selected())
    $style = db_value("select value from user_prefs where user_id=".get_current_user_id()." and name='STYLE'");

  if (!empty($style) && file_exists(SC_LOCATION.'styles/'.$style.'/style.ini'))
  {
    $details = parse_ini_file(SC_LOCATION.'styles/'.$style.'/style.ini');
    $details["location"] = '/styles/'.$style.'/';
    $details["name"]     = $style;
  }
  else 
*/  {
    $details = parse_ini_file(SC_LOCATION.'images/style.ini');
    $details["location"] = '/images/';
    $details["name"]     = 'Default';
  }
  
  // Ensure the display for audio will be in the correct place...
  if (get_screen_type() == 'PAL')
    $dummy = @file_get_contents('http://'.client_ip().':2020/pod_audio_info.cgi?x=210&y=464');  
  else
    $dummy = @file_get_contents('http://'.client_ip().':2020/pod_audio_info.cgi?x=210&y=369');  
         
  // Cache the style parameters in the session
  $_SESSION["style"] = $details;
}
 
//-------------------------------------------------------------------------------------------------
// Returns a given image paramter from the Style settings.
//-------------------------------------------------------------------------------------------------

function style_img ($name, $ext_url = false)
{
  if ( !isset($_SESSION["style"]))
    load_style();
    
  $path   = substr(SC_LOCATION,0,-1);
  $file   = $_SESSION["style"]["location"].$_SESSION["style"][strtoupper($name)];

  if ( isset( $_SESSION["style"][strtoupper($name)]) && file_exists($path.$file) )
    return ($ext_url ? $path : '').$file;
  else 
    return ($ext_url ? $path : '').'/images/dot.gif';
}

function style_img_exists ($name)
{
  if ( !isset($_SESSION["style"]))
    load_style();

  $path   = substr(SC_LOCATION,0,-1);
  $file   = $_SESSION["style"]["location"].$_SESSION["style"][strtoupper($name)];

  if ( isset( $_SESSION["style"][strtoupper($name)]) && file_exists($path.$file) )
    return true;
  else 
    return false;
}

function style_value ( $name, $default = '')
{
  if ( !isset($_SESSION["style"]))
    load_style();

  if ( isset( $_SESSION["style"][strtoupper($name)]) && !empty($_SESSION["style"][strtoupper($name)]) )
    return $_SESSION["style"][strtoupper($name)];
  else
    return $default;
}

   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
