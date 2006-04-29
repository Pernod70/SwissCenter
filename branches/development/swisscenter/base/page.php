<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// If the current page has a session_id parameter, then this will be used to "share" a session.
if (isset($_REQUEST["session_id"]) && !empty($_REQUEST["session_id"]))
  session_id($_REQUEST["session_id"]);

@session_start();
ob_start();

require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/stylelib.php'));
require_once( realpath(dirname(__FILE__).'/menu.php'));
require_once( realpath(dirname(__FILE__).'/infotab.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/iconbar.php'));
require_once( realpath(dirname(__FILE__).'/users.php'));
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

function up_link( $url)
{
  return '<a href="'.$url.'" TVID="PGUP" ONFOCUSLOAD>'.img_gen(SC_LOCATION.style_img("IMG_PGUP"),40,20,false,false,'RESIZE').'</a>';
}

function down_link( $url)
{
  return '<a href="'.$url.'" TVID="PGDN" ONFOCUSLOAD>'.img_gen(SC_LOCATION.style_img("IMG_PGDN"),40,20,false,false,'RESIZE').'</a>';
}

//-------------------------------------------------------------------------------------------------
// Outputs the initial page layout, body and style settings and prepares the page for output to the
// "main" area.
//-------------------------------------------------------------------------------------------------

function page_header( $title, $tagline = "",  $meta = "", $focus="1", $skip_auth = false, $focus_colour = '')
{
  // Check if the user has been selected and prompt for logon if needed
  if(!$skip_auth && !is_user_selected())
  {
    ob_clean();
    header('Location: '.server_address().'change_user.php');
    exit;
  }
  
  if (get_screen_type() == 'NTSC')
  {
    $logo                   = '';
    $headings               = '<td height="'.convert_y(60).'" align="center"><b>'.$title.'</b> : '.$tagline.'&nbsp;</td>';
  }
  else
  {
    $logo                   = '<td width="'.convert_x(250).'" height="'.convert_y(190).'" valign="center" align="center">'.img_gen(SC_LOCATION."/images/logo.gif",200,100,false,false,'RESIZE');
    $headings               = '<td height="'.convert_y(190).'" align="center"><h2>'.$title.'&nbsp;</h2>'.$tagline.'&nbsp;</td>';
  }

  $background_image       = '/thumb.php?type=jpg&stretch=Y&x='.convert_x(1000).'&y='.convert_y(1000).'&src='.rawurlencode(SC_LOCATION.style_img("PAL_BACKGROUND"));
  
  if ($focus_colour == '')
    $focus_colour = style_value("PAGE_FOCUS_COLOUR",'#FFFFFF');
  
  echo '<html>
        <head>'.$meta.'
        <meta SYABAS-FULLSCREEN>
        <meta SYABAS-PHOTOTITLE=0>
        <meta SYABAS-BACKGROUND="'.$background_image.'">
        <meta syabas-keyoption="caps"><meta myibox-pip="0,0,0,0,0"><meta http-equiv="content-type" content="text/html;charset=utf-8">
        <meta name="generator" content="lyra-box UI">
        <title>'.$title.'</title>
        <style>
          body {font-family: arial; font-size: 14px; background-repeat: no-repeat; }
          a {color:'.style_value("PAGE_LINK",'#FFFFFF').'; text-decoration: none;}
        </style>
        </head>
        <body  onLoadSet="'.$focus.'"
               background="'.  $background_image .'"
               FOCUSCOLOR="'.  $focus_colour.'"
               FOCUSTEXT="'.   style_value("PAGE_FOCUS_TEXT",'#FFFFFF').'"
               text="'.        style_value("PAGE_TEXT",'#FFFFFF').'"
               vlink="'.       style_value("PAGE_VLINK",'#FFFFFF').'"
               bgcolor="'.     style_value("PAGE_BGCOLOUR",'#FFFFFF').'"
               TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">';
  
  echo '<table width="'.convert_x(1000).'" border="0" cellpadding="0" cellspacing="0">
        <tr>'.$logo.$headings.'</tr>
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
  echo "<center>$message</center><p>";
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
    
  // Build the paramters for the thumb.php script
  $img_params = '/thumb.php?src='.rawurlencode($filename).'&x='.convert_x($x).'&y='.convert_y($y);
  
  if ($type !== false)
    $img_params .='&type='.$type;
  
  if ($stretch !== false)
    $img_params .='&stretch=Y';
    
  if ($rs_mode !== false)
    $img_params .='&rs_mode='.$rs_mode;

  return '<img '.$html.' width="'.convert_x($x).'" height="'.convert_y($y).'" src="'.$img_params.'" border=0>';
}

//-------------------------------------------------------------------------------------------------
// Displays a button on the PC browser to perform the same action as on the remote control.
//
// NOTE: The height of the button is fixed at 23 pixels. This does not change with the resolution
//       of the resolution of the screen as this is a PC browser and it is therefore unneccessary.
//-------------------------------------------------------------------------------------------------

function pc_nav_button($text, $url)
  {
    return '<td align="center" valign="center" height="23" width="'.(convert_x(1000)/5).'" background="'.style_img('IMG_PC_BUTTON').'" onclick="document.location=\''.$url.'\';">
         <a href="'.$url.'"><font color="'.style_value('COL_PC_BUTTON','#000000').'">'.$text.'</font></a>
         </td>';
  }

//-------------------------------------------------------------------------------------------------
// Finishes the page layout, including the formatting of any ABC buttons that have been defined.
// Adds an iconbar if there is one but only if there are no buttons
//-------------------------------------------------------------------------------------------------

function page_footer( $back, $buttons= '', $iconbar = 0 )
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
          
        $link = '<a '.$link.tvid('KEY_'.substr('ABC',$i,1)).'>'.$buttons[$i]["text"].'</a>';
      }
      else
        $link = $buttons[$i]["text"];

        echo '<td align="center">'.img_gen(SC_LOCATION.style_img(quick_access_img($i)),50,60).$link.'</td>';
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
         pc_nav_button(str('PC_LINK_PHOTOS') , '/photo.php').
         pc_nav_button(str('PC_LINK_BACK')   , $back).
         '</tr></table>';
  }
  
  echo '<a href="'.$back.'" '.tvid('BACKSPACE').'></a>
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
  debug_to_log("Displaying message",array("message"=>$text, "time"=>$seconds, "url"=>$url));
  page_header($title,"",'<meta http-equiv="refresh" content="'.$seconds.';URL='.$url.'">');
  echo "<p>&nbsp;<p>&nbsp;<p><center>".$text."</center>";
  page_footer('/');  
}

//-------------------------------------------------------------------------------------------------
// Simple routine to set preformatted text and recursively output the contents or a variable or
// array for debugging purposed
//-------------------------------------------------------------------------------------------------

function debug( $item )
{
  echo "<pre>";
  print_r($item);
  echo "</pre>";
}

//-------------------------------------------------------------------------------------------------
// Main code for thislibrary
//-------------------------------------------------------------------------------------------------

send_to_log("------------------------------------------------------------------------------");
send_to_log("Page Requested : ".current_url());

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
