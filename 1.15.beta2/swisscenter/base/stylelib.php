<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/users.php'));

//-------------------------------------------------------------------------------------------------
// This procedure stores the STYLE information in the current session. 
//-------------------------------------------------------------------------------------------------

function load_style( $user_id = '')
{
  $style = get_user_pref('STYLE','x',$user_id);

  if (!empty($style) && file_exists(SC_LOCATION.'styles/'.$style.'/style.ini'))
  {
    $details = parse_ini_file(SC_LOCATION.'images/style.ini');
    $details = array_merge($details,parse_ini_file(SC_LOCATION.'styles/'.$style.'/style.ini'));
    $details["LOCATION"] = '/styles/'.$style.'/';
    $details["NAME"]     = $style;
  }
  else 
  {
    $details = parse_ini_file(SC_LOCATION.'images/style.ini');
    $details["LOCATION"] = '/images/';
    $details["NAME"]     = 'Default';
  }

  // Ensure the display for audio will be in the correct place (only applicable to hardware players)
  if (is_hardware_player())
  {
    if (get_screen_type() == 'PAL')
      $dummy = @file_get_contents('http://'.client_ip().':2020/pod_audio_info.cgi?x=210&y=464');  
    else
      $dummy = @file_get_contents('http://'.client_ip().':2020/pod_audio_info.cgi?x=210&y=369');  
  }
         
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
  
  if (style_img_exists($name))
    $file   = $_SESSION["style"]["LOCATION"].$_SESSION["style"][strtoupper($name)];
  else 
    $file   = '/images/'.$_SESSION["style"][strtoupper($name)];

  if ( isset( $_SESSION["style"][strtoupper($name)]) && file_exists($path.$file) )
    return ($ext_url ? $path : '').$file;
  else 
    return ($ext_url ? $path : '').'/images/dot.gif';
}

function style_img_exists ($name)
{
  if ( !isset($_SESSION["style"]))
    load_style();

  $path   = substr(SC_LOCATION,0,-1).$_SESSION["style"]["LOCATION"];
  
  if ( isset( $_SESSION["style"][strtoupper($name)]) && file_exists($path.$_SESSION["style"][strtoupper($name)]) )
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

function font_colour_tags( $name, $text )
{
  return '<font color="'.style_value($name,'#FFFFFF').'">'.$text.'</font>';
}

   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
