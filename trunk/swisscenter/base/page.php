<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/session.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/stylelib.php'));
require_once( realpath(dirname(__FILE__).'/menu.php'));
require_once( realpath(dirname(__FILE__).'/infotab.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/iconbar.php'));
require_once( realpath(dirname(__FILE__).'/users.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/image.php'));
require_once( realpath(dirname(__FILE__).'/server.php'));

//-------------------------------------------------------------------------------------------------
// Procedures to output up/down links
//-------------------------------------------------------------------------------------------------

function up_link( $url, $focusload = true )
{
  if (!empty($url))
    return '<a name="up" href="'.$url.'" '.tvid("PGUP").' '.($focusload ? 'ONFOCUSLOAD' : '').'>'.img_gen(SC_LOCATION.style_img("PAGE_UP"),40,20,false,false,'RESIZE').'</a>';
  else
    return '';
}

function down_link( $url, $focusload = true )
{
  if (!empty($url))
    return '<a name="down" href="'.$url.'" '.tvid("PGDN").' '.($focusload ? 'ONFOCUSLOAD' : '').'>'.img_gen(SC_LOCATION.style_img("PAGE_DOWN"),40,20,false,false,'RESIZE').'</a>';
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

function page_header( $title, $tagline = "",  $meta = "", $focus="1", $skip_auth = false, $focus_colour = '', $background = -1, $banner = false, $text_background = '' )
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
      $headings = '<td height="'.convert_y(60).'"><table '.(empty($title) ? '' : style_background($text_background)).'  align="center" border="0" cellpadding="2" cellspacing="0"><tr><td><b>&nbsp;'.$title.'&nbsp;</b> : '.font_tags(FONTSIZE_HEADER_NTSC).$tagline.'&nbsp;</td></tr></table></td>';
  }
  else
  {
    if ($banner)
      $headings = '<td height="'.convert_y(170).'" align="center"><h2>&nbsp;</h2>&nbsp;</td>';
    else
    {
      $headings = '<td height="'.convert_y(100).'"><table '.(empty($title) ? '' : style_background($text_background)).' align="center" border="0" cellpadding="2" cellspacing="0"><tr><td><b>&nbsp;'.font_tags(FONTSIZE_HEADER).$title.'&nbsp;</b></td></tr></table></td>
               <tr><td height="'.convert_y(70).'"><table '.(empty($tagline) ? '' : style_background($text_background)).' align="center" border="0" cellpadding="2" cellspacing="0"><tr><td>&nbsp;'.font_tags(FONTSIZE_SUBHEADER).$tagline.'&nbsp;</td></tr></table></td>';
    }
  }

  // The default background is specified by PAGE_BACKGROUND
  $page_background = '.'.style_img("PAGE_BACKGROUND");

  // Test to see if this page has a special background, and if so if this has been set in the style.
  if (is_string($background))
  {
    if (style_img_exists($background))
      $page_background = '.'.style_img($background);
    elseif ( file_exists($background) || is_remote_file($background) )
      $page_background = $background;
  }
  elseif (is_numeric($background))
  {
    switch ($background)
    {
      case MEDIA_TYPE_MUSIC       : if (style_img_exists("PAGE_MUSIC"))       $page_background = '.'.style_img("PAGE_MUSIC"); break;
      case MEDIA_TYPE_PHOTO       : if (style_img_exists("PAGE_PHOTO"))       $page_background = '.'.style_img("PAGE_PHOTO"); break;
      case MEDIA_TYPE_VIDEO       : if (style_img_exists("PAGE_VIDEO"))       $page_background = '.'.style_img("PAGE_VIDEO"); break;
      case MEDIA_TYPE_RADIO       : if (style_img_exists("PAGE_RADIO"))       $page_background = '.'.style_img("PAGE_RADIO"); break;
      case MEDIA_TYPE_TV          : if (style_img_exists("PAGE_TV"))          $page_background = '.'.style_img("PAGE_TV"); break;
      case MEDIA_TYPE_WEB         : if (style_img_exists("PAGE_WEB"))         $page_background = '.'.style_img("PAGE_WEB"); break;
      case MEDIA_TYPE_INTERNET_TV : if (style_img_exists("PAGE_INTERNET_TV")) $page_background = '.'.style_img("PAGE_INTERNET_TV"); break;
    }
  }

  if ($banner)
  {
    if (is_screen_ntsc())
      $background_image = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000, BROWSER_SCREEN_COORDS).'&y='.convert_y(1000, BROWSER_SCREEN_COORDS).'&src='.rawurlencode($page_background).
                          '&overlay='.rawurlencode($banner).'&ox='.(convert_x(500, BROWSER_SCREEN_COORDS)-convert_y(50, BROWSER_SCREEN_COORDS)*5.4).'&oy='.convert_y(40, BROWSER_SCREEN_COORDS).
                          '&ow='.(convert_y(100, BROWSER_SCREEN_COORDS)*5.4).'&oh='.convert_y(100, BROWSER_SCREEN_COORDS);
    else
      $background_image = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000, BROWSER_SCREEN_COORDS).'&y='.convert_y(1000, BROWSER_SCREEN_COORDS).'&src='.rawurlencode($page_background).
                          '&overlay='.rawurlencode($banner).'&ox='.(convert_x(500, BROWSER_SCREEN_COORDS)-convert_y(65, BROWSER_SCREEN_COORDS)*5.4).'&oy='.convert_y(40, BROWSER_SCREEN_COORDS).
                          '&ow='.(convert_y(130, BROWSER_SCREEN_COORDS)*5.4).'&oh='.convert_y(130, BROWSER_SCREEN_COORDS);
  }
  else
    $background_image = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000, BROWSER_SCREEN_COORDS).'&y='.convert_y(1000, BROWSER_SCREEN_COORDS).'&src='.rawurlencode($page_background);

  // Check length of background image URL, some players don't like it being too long
  if ( strlen($background_image)>256 )
    send_to_log(2,'WARNING: Background image URL exceeds 256 chars.',$background_image);

  if ($focus_colour == '')
    $focus_colour = style_value("PAGE_FOCUS_COLOUR",'#FFFFFF');

  header('Content-type: text/html; '.charset());
  echo '<html>
        <head>'.$meta.'
        <meta SYABAS-COMPACT=OFF>
        <meta SYABAS-FULLSCREEN>
        <meta SYABAS-PHOTOTITLE=0>
        <meta SYABAS-BACKGROUND="'.$background_image.'">
        <meta SYABAS-KEYOPTION="caps">
        <meta myibox-pip="0,0,0,0,0">
        <meta name="generator" content="lyra-box UI">
        <meta http-equiv="Content-Type" content="text/html; '.charset().'">
        <title>'.$title.'</title>
        <style>
          body {font-family: arial; font-size: 14px; background-repeat: no-repeat; color: '.style_value("PAGE_TEXT_COLOUR",'#FFFFFF').';}
          td {color: '.style_value("PAGE_TEXT_COLOUR",'#FFFFFF').';}
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
  echo "<center>".font_tags(FONTSIZE_BODY).$message."</center><p>";
  $menu = new menu();
  $menu->add_item(str('RETURN_MAIN_MENU'),'/index.php',true);
  $menu->display();
  page_footer('');
  exit;
}

//-------------------------------------------------------------------------------------------------
// Outputs an IMG tag which uses the thumbnail generator/caching engine
//-------------------------------------------------------------------------------------------------

function img_gen( $filename, $x, $y, $type = false, $stretch = false, $rs_mode = false, $html_params = array(), $fill_size = true )
{
  // Build a string containing the name/value pairs of the extra html_params specified
  $html = '';
  foreach ($html_params as $n => $v)
    $html .= $n.'="'.$v.'" ';

  if ($fill_size == false)
  {
    // Get size of resized image
    image_resized_xy($filename, $x, $y);
  }

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

  if ($fill_size == false)
  {
    $img_params .='&fill_size=N';
  }

  // If width or height are not specified then do not use to define img size
  $width  = empty($x) ? '' : ' width="'.convert_x($x).'"';
  $height = empty($y) ? '' : ' height="'.convert_y($y).'"';
  $browser = $_SERVER['HTTP_USER_AGENT'];
  if ( strpos($browser,'MSIE ') !== false && preg_replace('/^.*MSIE (.*);.*$/Ui','\1',$browser) < 7)
  {
    // Make IE (<7) use PNG Alpha transparency
    $filter = "filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='$img_params',sizingMethod='crop');";
    return '<img '.$html.' style="'.$filter.'"'.$width.$height.' src="/images/dot.gif" border="0">';
  }
  else
    return '<img '.$html.$width.$height.' src="'.$img_params.'" '.$focus_attr.' border="0">';
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

function page_footer( $back, $buttons = '', $iconbar = 0, $links = true, $text_background = '' )
{
  echo '    </td>
            <td width="'.convert_x(50).'"></td>
          </tr>
        </table>
        <table '.style_background($text_background).' width="'.convert_x(1000).'" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="'.convert_x(50).'"></td>';

  if(!empty($buttons))
  {
    foreach ($buttons as $i=>$button)
    {
      if (! empty($button["url"]) )
      {
        $link = $button["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';

        $link = '<a '.$link.tvid('KEY_'.substr('ABCD',$i,1)).'name="'.tvid_code('KEY_'.substr('ABCD',$i,1)).'">'.
                img_gen(SC_LOCATION.style_img(quick_access_img($i)),25,40,false,false,false,array("align" => "absmiddle")).
                font_tags(FONTSIZE_FOOTER).$button["text"].'</a>';
      }
      else
        $link = img_gen(SC_LOCATION.style_img(quick_access_img($i)),25,40,false,false,false,array("align" => "absmiddle")).
                font_tags(FONTSIZE_FOOTER).$button["text"];

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

  // Log the page history stack
  send_to_log(8, 'Page history:', $_SESSION['history']);

  // Save current state to file
  $save_state = array('history'=>$_SESSION['history']);
  file_put_contents(get_sys_pref('CACHE_DIR').'/'.get_current_user_id().'_save_state', serialize($save_state));
}

//-------------------------------------------------------------------------------------------------
// Displays the given message in the center of the screen for the amount of seconds given and then
// redirects the user to the next page.
//-------------------------------------------------------------------------------------------------

function page_inform( $seconds, $url, $title, $text)
{
  send_to_log(8,"Displaying message",array("message"=>$text, "time"=>$seconds, "url"=>$url));
  page_header($title,"",'<meta http-equiv="refresh" content="'.$seconds.';URL='.$url.'">');
  echo "<p>&nbsp;<p>&nbsp;<p><center>".font_tags(FONTSIZE_BODY).$text."</center>";
  page_footer('/');
}

#-------------------------------------------------------------------------------------------------
# Functions for managing the page history.
#-------------------------------------------------------------------------------------------------

function page_hist_init( $url = '', $sql = '' )
{
  // Initialise the page tracking array.
  $_SESSION["history"] = array();

  if (!empty($url))
    $_SESSION["history"][] = array("url"=>$url, "sql"=>$sql);
}

function page_hist_push( $url, $sql = '' )
{
  $_SESSION["history"][] = array("url"=>$url, "sql"=>$sql);
}

function page_hist_pop()
{
  if (count($_SESSION["history"]) == 0)
  {
    send_to_log(2,'ERROR: Failed to read $_SESSION in page_hist_pop()');
    return array("url"=>'index.php', "sql"=>'');
  }
  else
    return array_pop($_SESSION["history"]);
}

function page_hist_current( $ref = '' )
{
  if (count($_SESSION["history"]) == 0)
  {
    send_to_log(2,'ERROR: Failed to read $_SESSION in page_hist_current()');
    $recent = array("url"=>'index.php', "sql"=>'');
    if ( empty($ref) )
      return $recent;
    else
      return $recent[$ref];
  }
  else
  {
    if ( empty($ref) )
      return $_SESSION["history"][count($_SESSION["history"])-1];
    else
      return $_SESSION["history"][count($_SESSION["history"])-1][$ref];
  }
}

function page_hist_current_update( $url, $sql )
{
  $_SESSION["history"][count($_SESSION["history"])-1] = array("url"=>$url, "sql"=>$sql);
}

function page_hist_previous( $ref = 'url' )
{
  if (count($_SESSION["history"]) == 0)
  {
    send_to_log(2,'ERROR: Failed to read $_SESSION in page_hist_back_url()');
    if ($ref == 'url')
      return 'index.php';
    else
      return '';
  }
  else
  {
    if ($ref == 'url')
      return url_add_param($_SESSION["history"][count($_SESSION["history"])-2]["url"], 'hist', PAGE_HISTORY_DELETE);
    else
      return $_SESSION["history"][count($_SESSION["history"])-2]["sql"];
  }
}

//-------------------------------------------------------------------------------------------------
// Actions that should be taken at the start of every page
//-------------------------------------------------------------------------------------------------

// Page history tracking parameters
define('PAGE_HISTORY_ADD',     'add');
define('PAGE_HISTORY_DELETE',  'delete');
define('PAGE_HISTORY_REPLACE', 'replace');

// Track page history
$current_url = url_remove_param(current_url(), 'hist');

// Initialise the page history tracker at index page
if ( strpos($current_url, '/index.php') !== false )
{
  page_hist_init($current_url);
}
else
{
  // Common page parameters that can be ignored when checking for page history replacement
  $page_params = array('page', 'any', 'last', 'search', 'thumbs');

  // Determine whether to add, replace, or delete page from history
  if (url_remove_params($current_url, $page_params) == url_remove_params(page_hist_current('url'), $page_params))
    $hist_param = PAGE_HISTORY_REPLACE;
  elseif (isset($_REQUEST["hist"]))
    $hist_param = $_REQUEST["hist"];
  else
    $hist_param = PAGE_HISTORY_ADD;

  switch ( $hist_param )
  {
    case PAGE_HISTORY_ADD:
      // Add only if URL is different from previous URL (ie. ignore page refresh)
      if ( $current_url !== page_hist_current('url') )
        page_hist_push($current_url, page_hist_current('sql'));
      break;
    case PAGE_HISTORY_DELETE:
      page_hist_pop();
      break;
    case PAGE_HISTORY_REPLACE:
      page_hist_current_update($current_url, page_hist_current('sql'));
      break;
  }
}

// Log details of the page request
send_to_log(1,"------------------------------------------------------------------------------");
send_to_log(1,"Page Requested : ".$current_url." by client (".client_ip().")");

// If in design mode, then we want to force loading of styles and/or language strings.
if ( get_sys_pref('CACHE_STYLE_DETAILS','YES') == 'NO' )
  load_style();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
