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
  page_header( str('REFRESH_DATABASE'), '', 'LOGO_CONFIG' );

// At some point in the future, this will take you back to the media refresh screen (via a redirect).
// the media refresh screen will then show the status of the current refresh.

  echo str('REFRESH_RUNNING').'<p>';

  $menu->add_item(str('CONTINUE'),'/');
  $menu->display();
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
