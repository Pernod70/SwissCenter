<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/thumblist.php");

  // ----------------------------------------------------------------------------------
  // Gets the list of styles from the SwissCenter website and returns them.  It also caches
  // them in the session to reduce the bandwidth on the website.
  // ----------------------------------------------------------------------------------
  
  function online_list()
  {
    if (!isset($_SESSION["online_styles"]))
    {
      foreach ( file('http://update.swisscenter.co.uk/styles/index.txt') as $name)
        $_SESSION["online_styles"][] = trim($name);
    }
        
    return $_SESSION["online_styles"];
 }

  // ----------------------------------------------------------------------------------
  // Gets the list of styles currently installed
  // ----------------------------------------------------------------------------------

  function styles_list()
  {
    $dir = 'styles/';
    $style_list = array();

    if ($dh = opendir($dir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if (is_dir($dir.$file) && file_exists($dir.$file.'/style.ini') )
          $style_list[] = $file;
      }
      closedir($dh);
    }

    // If we are viewing online styles, return only the styles that are not already installed.
    if ($_REQUEST["online"] != 'Y')
    {
      sort($style_list);
      return $style_list;
    }
    else
    {
      $styles = array_values(array_diff(online_list(),$style_list));
      sort($styles);
      return $styles;
    }
  }

  // ----------------------------------------------------------------------------------
  // Main Code
  // ----------------------------------------------------------------------------------

  if ($_REQUEST["online"] != 'Y')
    page_header( "Choose Style", '','LOGO_CONFIG' );
  else
    page_header("Download Style", '', 'LOGO_CONFIG');

  $styles        = styles_list();
  $page          = $_REQUEST["page"];
  $n_per_page    = 4;
  $start         = $page * ($n_per_page);
  $end           = min( count($styles), $start+$n_per_page);
  $tlist         = new thumb_list(550);
  
  if (count($styles) == 0)
  {
    echo '<center>There are no styles available</center>';
  }
  else 
  {  
     // Populate an array with the details that will be displayed
    for ($i=$start; $i<$end; $i++)
    {
      if ($_REQUEST["online"] == 'Y')
        $tlist->add_item( 'http://update.swisscenter.co.uk/styles/'.$styles[$i].'.jpg' , $styles[$i] , 'href="download_style.php?name='.rawurlencode($styles[$i]).'"'); 
      else 
        $tlist->add_item( 'styles/'.$styles[$i].'/folder.jpg' , $styles[$i] , 'href="set_style.php?style='.rawurlencode($styles[$i]).'"'); 
    }
  
   // Display a link to the previous page
    if ( $page > 0)   
      $tlist->set_down( 'style.php?online='.$_REQUEST["online"].'&page='.($page-1) ); 
  
    // Display a link to the next page
    if ( $end < count($styles) )
    {
      $tlist->set_down( 'style.php?online='.$_REQUEST["online"].'&page='.($page+1) ); 
    }
  
    $tlist->set_num_cols(2);
    $tlist->set_thumbnail_size(120,100);
    $tlist->display();
  }

  // Link to the online resources only if the ZIP extension is enabled and we are 
  // not already viewing the online styles.
  if ( $_REQUEST["online"] != 'Y' && extension_loaded('zip') )
    $buttons[] = array('text'=>'Download Styles from SwissCenter.co.uk', 'url'=>'style.php?online=Y');
  elseif ( $_REQUEST["online"] == 'Y')
    $buttons[] = array('text'=>'Show Installed Styles', 'url'=>'style.php');

  page_footer( 'config.php', $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
