<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

session_start();
ob_start();

require_once("settings.php");
require_once("menu.php");
require_once("infotab.php");
require_once("utils.php");

//-------------------------------------------------------------------------------------------------
// Returns a given image paramter from the Style settings.
//-------------------------------------------------------------------------------------------------

function style_img ($name)
{
  $file = $_SESSION["opts"]["style"]["location"].$_SESSION["opts"]["style"][strtoupper($name)];
  if ( file_exists($_SESSION["opts"]["sc_location"].$file) )
    return $file;
  else 
    return '/images/dot.gif';
}

function style_col ($name)
{
  $col = $_SESSION["opts"]["style"][strtoupper($name)];
  if ( !isset($col) || empty($col) )
    return '#000000';
  else 
    return $col;
}

//-------------------------------------------------------------------------------------------------
// Procedures to ouput up/down links
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
// Procedures to ouput a multi-column display
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

function page_header( $title, $tagline = "", $focus="1", $meta = "")
{
  echo '<html>
        <head>'.$meta.'
        <meta SYABAS-FULLSCREEN>
        <meta SYABAS-PHOTOTITLE=0>
        <meta syabas-keyoption="caps"><meta myibox-pip="0,0,0,0,0"><meta http-equiv="content-type" content="text/html;charset=iso-8859-1">
        <meta name="generator" content="lyra-box UI">
        <title>'.$title.'</title>
        <style>
          body {font-family: arial; font-size: 14px; background-repeat: no-repeat; }
          a {color:'.style_col("PAGE_LINK").'; text-decoration: none;}
        </style>
        </head>
        <body  onLoadSet="'.$focus.'"
               background="'.  style_img("PAGE_BACKGROUND").'"
               FOCUSCOLOR="'.  style_col("PAGE_FOCUS_COLOUR").'"
               FOCUSTEXT="'.   style_col("PAGE_FOCUS_TEXT").'"
               text="'.        style_col("PAGE_TEXT").'"
               vlink="'.       style_col("PAGE_VLINK").'"
               bgcolor="'.     style_col("PAGE_BGCOLOUR").'"
               TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">

        <table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="'.SCREEN_WIDTH.'px" height="4px"></td>
          </tr>
        </table>
        <table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="160px"  height="92px" ><img src="/images/logo.gif" width="160" height="92">
            </td>
            <td align="center">
              <h2>'.$title.'&nbsp;</h2>
            '.$tagline.'&nbsp;
            </td>
          </tr>
        </table>
        <table width="'.SCREEN_WIDTH.'px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="'.SCREEN_WIDTH.'px" height="14px"></td>
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
  page_header( "Error" );
  echo "<center>$message</center><p>";
  $menu = new menu();
  $menu->add_item("Return to the Homepage",'/index.php',true);
  $menu->display();
  page_footer('');
  exit;
}

//-------------------------------------------------------------------------------------------------
// Outputs an IMG tag which uses the thumbnail generator/caching engine
//-------------------------------------------------------------------------------------------------

function img_gen( $filename, $x, $y, $name = '')
{
  return '<img src="thumb.php?src='.rawurlencode($filename).'&x='.$x.'&y='.$y.'" name="'.$name.'" border=0>';
}

//-------------------------------------------------------------------------------------------------
// Finishes the page layout, including the formatting of any ABC buttons that have been defined.
//-------------------------------------------------------------------------------------------------

function page_footer( $back, $buttons= array() )
{
  echo '    </td>
            <td width="35px"></td>
          </tr>
        </table>
        <table width="'.SCREEN_WIDTH.'px" height="30px" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="40px"></td>';

  for ($i=0; $i<count($buttons); $i++)
  {
    // Assign to a Remote Control Button
    $button_id = substr('ABC',$i,1);

    if (! empty($buttons[$i]["url"]) )
    {
      $link = $buttons[$i]["url"];
      if (substr($link,0,5) != 'href=')
        $link = 'href="'.$link.'"';

      // Output the link slightly different for the showcenter browser.
      if ( is_showcenter() )
        $link = $buttons[$i]["text"].'<a '.$link.' TVID="key_'.strtolower($button_id).'"></a>';
      else
        $link = '<a '.$link.' TVID="key_'.strtolower($button_id).'">'.$buttons[$i]["text"].'</a>';
    }
    else
      $link = $buttons[$i]["text"];

      echo '<td align="center"><img src="'.style_img("IMG_".$button_id).'">'.$link.'</td>';
  }

  echo '    <td width="35px"></td>
          </tr>
        </table>';
  
  // Test the browser, and if the user is viewing from a browser other than the one on the
  // showcenter then output a "Back" Button (as this would normally be a IR remote button).
  if (strpos($_ENV["HTTP_USER_AGENT"],'Syabas') === false)
    echo '<a href="'.$back.'"><img src="/images/dot.gif" width="'.SCREEN_WIDTH.'" height="30" border=0></a>';
  
  echo '<a href="'.$back.'" TVID="backspace"></a>
        <a href="music.php" TVID="music"></a>
        <a href="video.php" TVID="movie"></a>
        <a href="photo.php" TVID="photo"></a>';

  echo '</body>
        </html>';
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

if (!empty($_ENV["SCRIPT_NAME"]))
  send_to_log("Page Requested : ".$_ENV["SCRIPT_NAME"]);

if (!empty($_ENV["QUERY_STRING"]))
  send_to_log("Parameters : ".$_ENV["QUERY_STRING"]);


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
