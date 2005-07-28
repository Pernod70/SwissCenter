<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/file.php");

  //
  // Main Code
  //

  $menu = new menu();

  run_background('media_search.php');
  page_header( "Refresh All Databases", '', 'LOGO_CONFIG' );

// At some point in the future, this will take you back to the media refresh screen (via a redirect).
// the media refresh screen will then show the status of the current refresh.

  echo "The database refresh has been started in the background and you may continue to use the 
        system as normal. However please be aware that not all media files will be available 
        for playback until the refresh is complete. 
     <p>
        The initial refresh can take approximately 10 minutes for every 5,000 media files
        present on your system. However subsequent refreshes are much faster<p>";

  $menu->add_item("Continue",'/');
  $menu->display();
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
