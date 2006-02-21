<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// If the current page has a session_id parameter, then this will be used to "share" a session. (yet more "cowboy" code!)
if (isset($_REQUEST["session_id"]) && !empty($_REQUEST["session_id"]))
  session_id($_REQUEST["session_id"]);

session_start();
ob_start();

require_once("file.php");
require_once("settings.php");
require_once("stylelib.php");
require_once("menu.php");
require_once("infotab.php");
require_once("utils.php");
require_once("iconbar.php");
require_once("users.php");
require_once("server.php");

function current_session()
{
  if (isset($_COOKIE["PHPSESSID"]))
    return 'session_id='.$_COOKIE["PHPSESSID"];
  else 
    return substr(SID,strpos(SID,'=')+1);
}

//-------------------------------------------------------------------------------------------------
// Procedures to output up/down links
//-------------------------------------------------------------------------------------------------

function up_link( $url)
{
  return '<a href="'.$url.'" TVID="PGUP" ONFOCUSLOAD>'.
         '<img border=0 src="'.style_img("IMG_PGUP").'"></a>';
}

function down_link( $url)
{
  return '<a href="'.$url.'" TVID="PGDN" ONFOCUSLOAD>'.
         '<img border=0 src="'.style_img("IMG_PGDN").'"></a>';
}

//-------------------------------------------------------------------------------------------------
// Procedures to output a multi-column display
//-------------------------------------------------------------------------------------------------

 function multi_col_start ( $col_size = "" )
 {
   echo '<p><table border=0 width="100%"><tr><td '
        .(empty($col_size) ? '' : 'width="'.$col_size)
        .'"valign=top>';
 }

 function multi_col_switch ( $col_size = "", $lborder = false)
 {
   echo '</td><td width="20" '
        .(empty($lborder) ? '' : 'style="border-left: 1 solid #000066 ! important;" ')
        .'><Img src="" height="6" width="20"></td><td '
        .(empty($col_size) ? '' : 'width="'.$col_size.'" ')
        .'valign=top>';
 }

 function multi_col_end ()
 {
   echo '</td></tr></table>';
 }

//-------------------------------------------------------------------------------------------------
// Outputs the initial page layout, body and style settings and prepares the page for output to the
// "main" area.
//-------------------------------------------------------------------------------------------------

// background-attachment:fixed;

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
    $headings               = '<td height="30px" align="center"><b>'.$title.'</b> : '.$tagline.'&nbsp;</td>';
    $background_image       = style_img("NTSC_BACKGROUND");
    $heading_padding_top    = 0;
    $heading_padding_bottom = 14;
  }
  else
  {
    $logo                   = '<td width="160px" height="92px" valign="center" align="center"><img src="/images/logo.gif"></td>';
    $headings               = '<td height="92px" align="center"><h2>'.$title.'&nbsp;</h2>'.$tagline.'&nbsp;</td>';
    $background_image       = style_img("PAL_BACKGROUND");
    $heading_padding_top    = 4;
    $heading_padding_bottom = 14;
  }
  
  if ($focus_colour == '')
    $focus_colour = style_value("PAGE_FOCUS_COLOUR",'#FFFFFF');
  
  echo '<html>
        <head>'.$meta.'
        <meta SYABAS-FULLSCREEN>
        <meta SYABAS-PHOTOTITLE=0>
        <meta SYABAS-BACKGROUND="'.$background_image.'">
        <meta syabas-keyoption="caps"><meta myibox-pip="0,0,0,0,0"><meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
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
  
  if ($margin_top >0)
  {
    echo '<table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <td width="'.SCREEN_WIDTH.'px" height="'.$heading_padding_top.'px"></td>
            </tr>
          </table>';
  }

  echo '<table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
          <tr>'.$logo.
                $headings.'
          </tr>
        </table>
        <table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="'.SCREEN_WIDTH.'px" height="'.$heading_padding_bottom.'px"></td>
          </tr>
        </table>
        <table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="40px" height="335px" ></td>
            <td width="550px" valign="top" align="left">';
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

function img_gen( $filename, $x, $y, $type = false, $bgcol = false, $stretch = false)
{
  $params = 'src='.rawurlencode($filename).'&x='.$x.'&y='.$y;
  
  if ($type !== false)
    $params .='&type='.$type;
  
  if ($bgcol !== false)
    $params .='&bgcol='.$bgcol;

  if ($stretch !== false)
    $params .='&stretch=Y';

  return '<img src="thumb.php?'.$params.'" border=0>';
}

//-------------------------------------------------------------------------------------------------
// Displays a button on the PC browser to perform the same action as on the remote control.
//-------------------------------------------------------------------------------------------------

function pc_nav_button($text, $url)
  {
    return '<td align="center" valign="center" height="23" width="'.(SCREEN_WIDTH/5).'" background="'.style_img('IMG_PC_BUTTON').'" onclick="document.location=\''.$url.'\';">
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
            <td width="35px"></td>
          </tr>
        </table>
        <table width="'.SCREEN_WIDTH.'px" height="30px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="40px"></td>';

  if(!empty($buttons))
  {
    for ($i=0; $i<count($buttons); $i++)
    {
      // Assign to a Remote Control Button
      $button_id = substr('ABC',$i,1);

      if (! empty($buttons[$i]["url"]) )
      {
        $link = $buttons[$i]["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';
          
        $link = '<a '.$link.tvid('KEY_'.$button_id).'>'.$buttons[$i]["text"].'</a>';
      }
      else
        $link = $buttons[$i]["text"];
      
      echo '<td align="center"><img src="'.style_img('IMG_'.$button_id).'">'.$link.'</td>';
    }
  }
  elseif(!empty($iconbar))
  {
    echo '<td align="center">';
    $iconbar->display();
    echo '</td>';
  }
  

  echo '    <td width="35px"></td>
          </tr>
        </table>';
  
  // Test the browser, and if the user is viewing from a browser other than the one on the
  // showcenter then output a "Back" Button (as this would normally be a IR remote button).
  if ( is_pc() )
  {
    echo '<table style="position:absolute; top:'.SCREEN_HEIGHT.'px; left:0px; " width="'.SCREEN_WIDTH.'" cellspacing="10" cellpadding="0"><tr>'.
         pc_nav_button(str('PC_LINK_HOME')   , '/index.php').
         pc_nav_button(str('PC_LINK_CONFIG') , '/config/index.php').
         pc_nav_button(str('PC_LINK_MUSIC')  , '/music.php').
         pc_nav_button(str('PC_LINK_MOVIES') , '/video.php').
         pc_nav_button(str('PC_LINK_PHOTOS') , '/photo.php').
         pc_nav_button(str('PC_LINK_BACK')   , $back).
         '</tr></table>';
  }
  
  echo '<a href="'.$back.'" TVID="backspace"></a>
        <a href="music.php" TVID="music"></a>
        <a href="video.php" TVID="movie"></a>
        <a href="photo.php" TVID="photo"></a>';

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
