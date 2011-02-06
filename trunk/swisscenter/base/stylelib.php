<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/users.php'));
require_once( realpath(dirname(__FILE__).'/capabilities.php'));

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

  // Cache the style parameters in the session
  $_SESSION["style"] = $details;

  // Ensure the display for audio will be in the correct place (only applicable to hardware players)
  if (is_hardware_player())
  {
    list ($x,$y) = explode(',',style_value('NOW_PROGRESS_BAR','300,820'));
    set_progress_bar_location( $x,$y );
    // Enable Dolby downmix option.
    // $dummy = @file_get_contents('http://'.client_ip().':2020/set_amx=ON');
  }
}

//-------------------------------------------------------------------------------------------------
// Returns the value for the appropriate tag int he style definition
//-------------------------------------------------------------------------------------------------

function style_value ( $name, $default = '')
{
  if ( !isset($_SESSION["style"]))
    load_style();

  if ( isset( $_SESSION["style"][strtoupper($name)]) && !empty($_SESSION["style"][strtoupper($name)]) )
    return $_SESSION["style"][strtoupper($name)];
  else
    return $default;
}

//-------------------------------------------------------------------------------------------------
// Functions to test the values specified in the style definition
//-------------------------------------------------------------------------------------------------

function style_img_exists ($name)
{
  $img  = style_value($name);
  $path = substr(SC_LOCATION,0,-1).$_SESSION["style"]["LOCATION"];

  if ($img == '')
    return false;
  elseif (file_exists($path.$img))
    return true;
  elseif  (file_exists(SC_LOCATION.'images/'.$img))
    return true;
  else
    return false;
}

function style_is_image( $name )
{
  $img = style_value($name);

  if ( preg_match('/\.(jpeg|jpg|gif|png)$/i',$img) == 0)
    return false;
  elseif ( ! style_img_exists($name) )
    return false;
  else
    return true;
}

function style_is_colour( $name )
{
  $col = ltrim(style_value($name),'#');

  if ( preg_match('/[0-9a-f]{6}/i',$col) == 0 )
    return false;
  else
    return true;
}

//-------------------------------------------------------------------------------------------------
// Returns an image filename, given the name of the style parameter. If the image does not exist
// within the current style, then the image from the default style is used. If *that* doesn't
// exist, then a transparent image is returned.
//-------------------------------------------------------------------------------------------------

function style_img ($name, $full_path = false, $placeholder = true)
{
  $path = substr(SC_LOCATION,0,-1);
  $val  = style_value($name);

  if ( file_exists(substr(SC_LOCATION,0,-1).$_SESSION["style"]["LOCATION"].$val) )
    $file   = $_SESSION["style"]["LOCATION"].$val;
  else
    $file   = '/images/'.$val;

  if ( $val != '' && file_exists($path.$file) )
    return ($full_path ? $path : '').$file;
  elseif ($placeholder)
    return ($full_path ? $path : '').'/images/dot.gif';
  else
    return '';
}

//-------------------------------------------------------------------------------------------------
// Wraps the given text with a FONT tag containing the appropriate colour from the style file. If
// the value in the file is not a valid colour (ie: it might be an image) then the text remains
// unaltered.
//-------------------------------------------------------------------------------------------------

function font_colour_tags( $name, $text )
{
  if ( style_is_colour($name) )
    return '<font color="'.style_value($name,'#FFFFFF').'">'.$text.'</font>';
  else
    return $text;
}

// ----------------------------------------------------------------------------------
// Truncates a string to the given width (in pixels), and adds an ellipse to
// indicate it has been shortened
// ----------------------------------------------------------------------------------

function shorten( $text, $width, $lines = 1, $font_size = FONTSIZE_BODY, $dots = true, $word_trunc = true )
{
  if(empty($text))
    return $text;

  global $char_widths;
  $short_string = "";
  $len          = 0;
  $text         = (string)$text;

  // Use either actual font size in pixels or get player dependant font.
  if (strpos($font_size,'px') !== false)
    $size = $font_size;
  else
    $size = get_font($font_size);

  // The character sizes specified in the array are for a fixed size font. therefore, we
  // need to calculate the max_len for the font size we were given.
  $max_len = 32/$size * $width * 10 * player_fontsize_multiplier();

  if ($lines > 1)
  {
    // Multiple lines
    for ($lineno = 0; $lineno < $lines; $lineno++)
    {
      $line = shorten($text, $width, 1, $size, false);
      $text = substr($text,strlen($line));
      $short_string .= $line;
    }
  }
  else
  {
    // Single line
    for ($index = 0; $index < strlen($text); $index++)
    {
      $current_char = $text[$index];

      if(!array_key_exists($current_char, $char_widths))
        $char_len = 176;
      else
        $char_len = $char_widths[$current_char];

      if(($len + $char_len) < $max_len)
      {
        // Not reached the end of the space yet
        $len += $char_len;
        $short_string .= $current_char;
      }
      else
      {
        // Trims the string back to the last whitespace (max 8 chars will be trimmed)
        if ( $word_trunc && (strlen($short_string) - strrpos($short_string,' ')) <10)
          $short_string = substr($short_string,0,strrpos($short_string,' ')+1);

        break;
      }
    }
  }

  if ($dots && strlen($short_string) < strlen($text))
    $short_string .= "...";

  return $short_string;
}

//-------------------------------------------------------------------------------------------------
// Returns the appropriate HTML to set the background image/colour for an element (TABLE, TD, etc)
// provided that the style parameter given specifies a valid image/colour.
//-------------------------------------------------------------------------------------------------

function style_background ( $name )
{
  if ( style_is_colour($name))
    return ' bgcolor="'.style_value($name).'" ';
  elseif ( style_is_image($name))
  {
    return ' style="background-repeat: repeat;" background="/thumb.php?src='.rawurlencode(style_img($name,true)).'" ';
  }
  else
    return '';
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
