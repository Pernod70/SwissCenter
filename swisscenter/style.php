<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/thumblist.php'));

  // ----------------------------------------------------------------------------------
  // Gets the list of styles from the SwissCenter website and returns them.  It also caches
  // them in the session to reduce the bandwidth on the website.
  // ----------------------------------------------------------------------------------

  function online_list()
  {
    if (!isset($_SESSION["online_styles"]))
    {
      $contents = file('http://update.swisscenter.co.uk/styles/index.txt');
      if (!empty($contents))
        foreach ($contents as $name)
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

    $dh = Fsw::opendir($dir);
    if ($dh !== false)
    {
      while (($file = readdir($dh)) !== false)
      {
        if (isdir($dir.$file) && Fsw::file_exists($dir.$file.'/style.ini') )
        {
          $style_rating = style_rating($file);
          if ( ($style_rating == '') || (get_current_user_rank() >= get_rank_from_name($style_rating)) )
            $style_list[] = $file;
        }
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
      $online_list = online_list();
      if ( count($online_list)>0)
        $styles = array_values(array_diff($online_list,$style_list));
      else
        $styles = array();

      sort($styles);
      return $styles;
    }
  }

  // ----------------------------------------------------------------------------------
  // Gets the rating of the specified style
  // ----------------------------------------------------------------------------------

  function style_rating($style)
  {
    $details = Fsw::parse_ini_file(SC_LOCATION.'styles/'.$style.'/style.ini');
    return (isset($details["STYLE_RATING"]) ? $details["STYLE_RATING"] : '');
  }

  // ----------------------------------------------------------------------------------
  // Main Code
  // ----------------------------------------------------------------------------------

  $styles        = styles_list();
  $page          = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
  $n_per_page    = 8;
  $start         = $page * ($n_per_page);
  $end           = min( count($styles), $start+$n_per_page);
  $tlist         = new thumb_list();
  $online        = isset($_REQUEST["online"]) ? $_REQUEST["online"] : 'N';

  if ( $online == 'Y' )
    page_header( str('STYLE_DOWNLOAD'), '');
  else
    page_header( str('STYLE_CHOOSE'), '');

  if (count($styles) == 0)
  {
    echo '<center>'.str('STYLE_NONE_AVAILABLE').'</center>';
  }
  else
  {
     // Populate an array with the details that will be displayed
    for ($i=$start; $i<$end; $i++)
    {
      if ( $online == 'Y' )
        $tlist->add_item( 'http://update.swisscenter.co.uk/styles/'.$styles[$i].'.jpg' , $styles[$i] , 'href="run_style_download.php?name='.rawurlencode($styles[$i]).'"');
      else
        $tlist->add_item( 'styles/'.$styles[$i].'/folder.jpg' , $styles[$i] , 'href="set_style.php?style='.rawurlencode($styles[$i]).'"');
    }

   // Display a link to the previous page
    if ( $page > 0)
      $tlist->set_up( 'style.php?online='.$online.'&page='.($page-1) );

    // Display a link to the next page
    if ( $end < count($styles) )
    {
      $tlist->set_down( 'style.php?online='.$online.'&page='.($page+1) );
    }

    $tlist->set_num_cols(4);
    $tlist->set_thumbnail_size(192,202);
    $tlist->display();
  }

  // Link to the online resources only if the ZIP extension is enabled and we are
  // not already viewing the online styles.
  if ( internet_available() && $online != 'Y' && (extension_loaded('zip') || is_unix()))
    $buttons[] = array('text'=>str('STYLE_CHECK_ONLINE','SwissCenter.co.uk'), 'url'=>'style.php?online=Y');
  elseif ( $online == 'Y')
    $buttons[] = array('text'=>str('STYLE_SHOW_INSTALLED'), 'url'=>'style.php');

  page_footer( 'config.php', $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
