<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

//-------------------------------------------------------------------------------------------------
// This procedure stores the STYLE information in the current session. 
//-------------------------------------------------------------------------------------------------

function load_style()
{
  $style = db_value("select value from user_prefs where user_id=".CURRENT_USER." and name='STYLE'");
  if (!empty($style) && file_exists(SC_LOCATION.'styles/'.$style.'/style.ini'))
  {
    $details = parse_ini_file(SC_LOCATION.'styles/'.$style.'/style.ini');
    $details["location"] = '/styles/'.$style.'/';
    $details["name"]     = $style;
  }
  else 
  {
    $details = parse_ini_file(SC_LOCATION.'images/style.ini');
    $details["location"] = '/images/';
    $details["name"]     = 'Default';
  }
  return $details;
}
 
//-------------------------------------------------------------------------------------------------
// Returns a given image paramter from the Style settings.
//-------------------------------------------------------------------------------------------------

function style_img ($name, $ext_url = false)
{
  $path   = substr(SC_LOCATION,0,-1);
  $file   = $_SESSION["style"]["location"].$_SESSION["style"][strtoupper($name)];

  if ( file_exists($path.$file) )
    return ($ext_url ? $path : '').$file;
  else 
    return ($ext_url ? $path : '').'/images/dot.gif';
}

function style_value ( $name, $default )
{
  $val = $_SESSION["style"][strtoupper($name)];
  if ( !isset($val) || empty($val) )
    return $default;
  else
    return $val;
}

//-------------------------------------------------------------------------------------------------
// If the current session does not have a cached copy of the current style, then load it.
//-------------------------------------------------------------------------------------------------

 if (! isset($_SESSION["style"]))
   $_SESSION["style"] = load_style();
   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
