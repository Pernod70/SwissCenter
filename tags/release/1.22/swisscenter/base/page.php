<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// If the current page has a session_id parameter, then this will be used to "share" a session.
if (isset($_REQUEST["session_id"]) && !empty($_REQUEST["session_id"]))
  session_id($_REQUEST["session_id"]);

@session_start();
ini_set("session.gc_maxlifetime", "86400"); // Set session timeout to 1 day
ob_start();

require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/stylelib.php'));
require_once( realpath(dirname(__FILE__).'/menu.php'));
require_once( realpath(dirname(__FILE__).'/infotab.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/iconbar.php'));
require_once( realpath(dirname(__FILE__).'/users.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/server.php'));

function current_session()
{
  if (isset($_COOKIE["PHPSESSID"]))
    return 'session_id='.$_COOKIE["PHPSESSID"];
  else
    return 'session_id='.substr(SID,strpos(SID,'=')+1);
}

//-------------------------------------------------------------------------------------------------
// Procedures to output up/down links
//-------------------------------------------------------------------------------------------------

function up_link( $url, $focusload = true )
{
  if (!empty($url))
    return '<a href="'.$url.'" '.tvid("PGUP").' '.($focusload ? 'ONFOCUSLOAD' : '').'>'.img_gen(SC_LOCATION.style_img("PAGE_UP"),40,20,false,false,'RESIZE').'</a>';
  else
    return '';
}

function down_link( $url, $focusload = true )
{
  if (!empty($url))
    return '<a href="'.$url.'" '.tvid("PGDN").' '.($focusload ? 'ONFOCUSLOAD' : '').'>'.img_gen(SC_LOCATION.style_img("PAGE_DOWN"),40,20,false,false,'RESIZE').'</a>';
  else
    return '';
}

function charset()
{
  return 'charset='.get_sys_pref('PLAYER_PAGE_CHARSET','Windows-1252');
}

//-------------------------------------------------------------------------------------------------
// Outputs the initial page layout, body and style settings and prepares the page for output to the
// "main" area.
//-------------------------------------------------------------------------------------------------

function page_header( $title, $tagline = "",  $meta = "", $focus="1", $skip_auth = false, $focus_colour = '', $background = -1, $banner = false )
{
  // Check if the user has been selected and prompt for logon if needed
  if(!$skip_auth && !is_user_selected())
  {
    ob_clean();
    header('Location: '.server_address().'change_user.php');
    exit;
  }

  // Display headings, only if there is no banner
  if (is_screen_ntsc())
  {
    if ($banner)
      $headings = '<td height="'.convert_y(60).'" align="center">&nbsp;</td>';
    else
      $headings = '<td height="'.convert_y(60).'" align="center"><b>'.$title.'</b> : '.font_tags(32).$tagline.'&nbsp;</td>';
  }
  else
  {
    if ($banner)
      $headings = '<td height="'.convert_y(170).'" align="center"><h2>&nbsp;</h2>&nbsp;</td>';
    else
      $headings = '<td height="'.convert_y(170).'" align="center"><h2>'.$title.'&nbsp;</h2>'.font_tags(32).$tagline.'&nbsp;</td>';
  }

  // The default background is specified by PAGE_BACKGROUND
  $page_background = style_img("PAGE_BACKGROUND");

  // Test to see if this page has a special background, and if so if this has been set in the style.
  if (is_string($background))
  {
    if (style_img_exists($background))
      $page_background = style_img($background);
  }
  elseif (is_numeric($background))
  {
    switch ($background)
    {
      case MEDIA_TYPE_MUSIC       : if (style_img_exists("PAGE_MUSIC"))       $page_background = style_img("PAGE_MUSIC"); break;
      case MEDIA_TYPE_PHOTO       : if (style_img_exists("PAGE_PHOTO"))       $page_background = style_img("PAGE_PHOTO"); break;
      case MEDIA_TYPE_VIDEO       : if (style_img_exists("PAGE_VIDEO"))       $page_background = style_img("PAGE_VIDEO"); break;
      case MEDIA_TYPE_RADIO       : if (style_img_exists("PAGE_RADIO"))       $page_background = style_img("PAGE_RADIO"); break;
      case MEDIA_TYPE_TV          : if (style_img_exists("PAGE_TV"))          $page_background = style_img("PAGE_TV"); break;
      case MEDIA_TYPE_WEB         : if (style_img_exists("PAGE_WEB"))         $page_background = style_img("PAGE_WEB"); break;
      case MEDIA_TYPE_INTERNET_TV : if (style_img_exists("PAGE_INTERNET_TV")) $page_background = style_img("PAGE_INTERNET_TV"); break;
    }
  }
  if ($banner)
  {
    if (is_screen_ntsc())
      $background_image = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000, BROWSER_SCREEN_COORDS).'&y='.convert_y(1000, BROWSER_SCREEN_COORDS).'&src='.rawurlencode('.'.$page_background).
                          '&overlay='.rawurlencode($banner).'&ox='.(convert_x(500, BROWSER_SCREEN_COORDS)-convert_y(50, BROWSER_SCREEN_COORDS)*5.4).'&oy='.convert_y(40, BROWSER_SCREEN_COORDS).
                          '&ow='.(convert_y(100, BROWSER_SCREEN_COORDS)*5.4).'&oh='.convert_y(100, BROWSER_SCREEN_COORDS);
    else
      $background_image = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000, SCREEN_COORDS).'&y='.convert_y(1000, SCREEN_COORDS).'&src='.rawurlencode('.'.$page_background).
                          '&overlay='.rawurlencode($banner).'&ox='.(convert_x(500, SCREEN_COORDS)-convert_y(65, SCREEN_COORDS)*5.4).'&oy='.convert_y(40, SCREEN_COORDS).
                          '&ow='.(convert_y(130, SCREEN_COORDS)*5.4).'&oh='.convert_y(130, SCREEN_COORDS);
  }
  else
    $background_image = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000, BROWSER_SCREEN_COORDS).'&y='.convert_y(1000, BROWSER_SCREEN_COORDS).'&src='.rawurlencode('.'.$page_background);

  // Check length of background image URL, some players don't like it being too long
  if ( strlen($background_image)>256 )
    send_to_log(2,'WARNING: Background image URL exceeds 256 chars.',$background_image);

  if ($focus_colour == '')
    $focus_colour = style_value("PAGE_FOCUS_COLOUR",'#FFFFFF');

  header('Content-type: text/html; '.charset());
  echo '<html>
        <head>'.$meta.'
        <meta SYABAS-FULLSCREEN>
        <meta SYABAS-PHOTOTITLE=0>
        <meta SYABAS-BACKGROUND="'.$background_image.'">
        <meta syabas-keyoption="caps"><meta myibox-pip="0,0,0,0,0"><meta http-equiv="content-type" content="text/html;charset=Windows-1252">
        <meta name="generator" content="lyra-box UI">
        <meta http-equiv="Content-Type" content="text/html; '.charset().'">
        <title>'.$title.'</title>
        <style>
          body {font-family: arial; font-size: 14px; background-repeat: no-repeat; color: '.style_value("PAGE_TEXT_COLOUR",'#FFFFFF').';}
          td { color: '.style_value("PAGE_TEXT_COLOUR",'#FFFFFF').';}
          a {color:'.style_value("PAGE_LINKS_COLOUR",'#FFFFFF').'; text-decoration: none;}
        </style>
        </head>
        <body  onLoadSet="'.$focus.'"
               background="'.  $background_image .'"
               FOCUSCOLOR="'.  $focus_colour.'"
               FOCUSTEXT="'.   style_value("PAGE_FOCUS_TEXT",'#FFFFFF').'"
               text="'.        style_value("PAGE_TEXT_COLOUR",'#FFFFFF').'"
               vlink="'.       style_value("PAGE_LINKS_COLOUR",'#FFFFFF').'"
               bgcolor="'.     style_value("PAGE_BACKGROUND_COLOUR",'#FFFFFF').'"
               TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">';

  echo '<table width="'.convert_x(1000).'" border="0" cellpadding="0" cellspacing="0">
        <tr>'.$headings.'</tr>
        </table>

        <table width="'.convert_x(1000).'" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="'.convert_x(50).'" height="'.convert_y(670).'" ></td>
            <td width="'.convert_x(900).'" valign="top" align="left">';
}

//-------------------------------------------------------------------------------------------------
// Displays a blank page with the details on an error.
//-------------------------------------------------------------------------------------------------

function page_error($message)
{
  ob_clean();
  page_header( "Error", "", "", "1", true );
  echo "<center>".font_tags(32).$message."</center><p>";
  $menu = new menu();
  $menu->add_item(str('RETURN_MAIN_MENU'),'/index.php',true);
  $menu->display();
  page_footer('');
  exit;
}

//-------------------------------------------------------------------------------------------------
// Outputs an IMG tag which uses the thumbnail generator/caching engine
//-------------------------------------------------------------------------------------------------

function img_gen( $filename, $x, $y, $type = false, $stretch = false, $rs_mode = false, $html_params = array())
{
  // Build a string containing the name/value pairs of the extra html_params specified
  $html = '';
  foreach ($html_params as $n => $v)
    $html .= $n.'="'.$v.'" ';

  // Build the paramters for the thumb.php script. Also set the onFocusSrc attribute if an image is provided.
  if (is_array($filename))
  {
    $img_params = '/thumb.php?src='.rawurlencode($filename[0]).'&x='.convert_x($x).'&y='.convert_y($y);
    if (!empty($filename[1]))
      $focus_attr = 'onfocussrc="/thumb.php?src='.rawurlencode($filename[1]).'&x='.convert_x($x).'&y='.convert_y($y).'"';
    else
      $focus_attr = '';
  }
  else
  {
    $img_params = '/thumb.php?src='.rawurlencode($filename).'&x='.convert_x($x).'&y='.convert_y($y);
    $focus_attr = '';
  }

  if ($type !== false)
    $img_params .='&type='.$type;

  if ($stretch !== false)
    $img_params .='&stretch=Y';

  if ($rs_mode !== false)
    $img_params .='&rs_mode='.$rs_mode;


  $browser = $_SERVER['HTTP_USER_AGENT'];
  if ( strpos($browser,'MSIE ') !== false && preg_replace('/^.*MSIE (.*);.*$/Ui','\1',$browser) < 7)
  {
    $filter = "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$img_params',sizingMethod='crop');";
    return '<img '.$html.' style="'.$filter.'" width="'.convert_x($x).'" height="'.convert_y($y).'" src="/images/dot.gif" border=0>';
  }
  else
    return '<img '.$html.' width="'.convert_x($x).'" height="'.convert_y($y).'" src="'.$img_params.'" '.$focus_attr.' border=0>';
}

//-------------------------------------------------------------------------------------------------
// Displays a button on the PC browser to perform the same action as on the remote control.
//
// NOTE: The height of the button is fixed at 23 pixels. This does not change with the resolution
//       of the resolution of the screen as this is a PC browser and it is therefore unneccessary.
//-------------------------------------------------------------------------------------------------

function pc_nav_button($text, $url)
  {
    return '<td style="cursor: pointer" align="center" valign="center" height="23" width="'.(convert_x(1000)/5).'" '.
                style_background('PC_BUTTON_BACKGROUND').' onclick="document.location=\''.$url.'\';">
            '.font_colour_tags('PC_BUTTON_TEXT_COLOUR',$text).'
            </td>';
  }

//-------------------------------------------------------------------------------------------------
// Finishes the page layout, including the formatting of any ABC buttons that have been defined.
// Adds an iconbar if there is one but only if there are no buttons
//-------------------------------------------------------------------------------------------------

function page_footer( $back, $buttons= '', $iconbar = 0, $links=true )
{
  echo '    </td>
            <td width="'.convert_x(50).'"></td>
          </tr>
        </table>
        <table width="'.convert_x(1000).'" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="'.convert_x(50).'"></td>';

  if(!empty($buttons))
  {
    for ($i=0; $i<count($buttons); $i++)
    {
      if (! empty($buttons[$i]["url"]) )
      {
        $link = $buttons[$i]["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';

        $link = '<a '.$link.tvid('KEY_'.substr('ABC',$i,1)).'name="'.tvid_code('KEY_'.substr('ABC',$i,1)).'">'.
                img_gen(SC_LOCATION.style_img(quick_access_img($i)),45,54,false,false,false,array("align" => "absmiddle")).
                font_tags(32).$buttons[$i]["text"].'</a>';
      }
      else
        $link = img_gen(SC_LOCATION.style_img(quick_access_img($i)),45,54,false,false,false,array("align" => "absmiddle")).
                font_tags(32).$buttons[$i]["text"];

      echo '<td align="center">'.$link.'</td>';
    }
  }
  elseif(!empty($iconbar))
  {
    echo '<td align="center">';
    $iconbar->display();
    echo '</td>';
  }


  echo '    <td width="'.convert_x(50).'"></td>
          </tr>
        </table>';

  // Test the browser, and if the user is viewing from a browser other than the one on the
  // showcenter then output a "Back" Button (as this would normally be a IR remote button).
  if ( is_pc() )
  {
    echo '<table style="position:absolute; top:'.convert_y(1000).'; left:0; " width="'.convert_x(1000).'" cellspacing="10" cellpadding="0"><tr>'.
         pc_nav_button(str('PC_LINK_HOME')   , '/index.php').
         pc_nav_button(str('PC_LINK_CONFIG') , '/config/index.php').
         pc_nav_button(str('PC_LINK_MUSIC')  , '/music.php').
         pc_nav_button(str('PC_LINK_MOVIES') , '/video.php').
         pc_nav_button(str('PC_LINK_TV')     , '/tv.php').
         pc_nav_button(str('PC_LINK_INTERNET'),'/index.php?submenu=internet').
         pc_nav_button(str('PC_LINK_PHOTOS') , '/photo.php').
         pc_nav_button(str('PC_LINK_BACK')   , $back).
         '</tr></table>';
  }

  if ( $links )
    echo '<a href="'.$back.'" '.tvid('BACKSPACE').'></a>
          <a href="index.php" '.tvid('HOME').'></a>
          <a href="music.php" '.tvid('MUSIC').'></a>
          <a href="video.php" '.tvid('MOVIE').'></a>
          <a href="photo.php" '.tvid('PHOTO').'></a>';

  echo '</body>
        </html>';
}

//-------------------------------------------------------------------------------------------------
// Displays the given message in the center of the screen for the amount of seconds given and then
// redirects the user to the next page.
//-------------------------------------------------------------------------------------------------

function page_inform( $seconds, $url, $title, $text)
{
  send_to_log(8,"Displaying message",array("message"=>$text, "time"=>$seconds, "url"=>$url));
  page_header($title,"",'<meta http-equiv="refresh" content="'.$seconds.';URL='.$url.'">');
  echo "<p>&nbsp;<p>&nbsp;<p><center>".font_tags(32).$text."</center>";
  page_footer('/');
}

//-------------------------------------------------------------------------------------------------
// Simple routine to set preformatted text and recursively output the contents or a variable or
// array for debugging purposed
//-------------------------------------------------------------------------------------------------

function dump()
{
  for ($i=0;$i<@func_num_args();$i++)
  {
    echo "<pre>";
    print_r(@func_get_arg($i));
    echo "</pre>";
  }
}

//-------------------------------------------------------------------------------------------------
// Actions that should be taken at the start of every page
//-------------------------------------------------------------------------------------------------

// Log details of the page request
send_to_log(1,"------------------------------------------------------------------------------");
send_to_log(1,"Page Requested : ".current_url()." by client (".client_ip().")");

// If in design mode, then we want to force loading of styles and/or language strings.
if ( get_sys_pref('CACHE_STYLE_DETAILS','YES') == 'NO' )
  load_style();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
