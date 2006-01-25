<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));

  //
  // Main Code
  //

  $menu = new menu();

  media_refresh_now();
  page_header( str('REFRESH_DATABASE'), '');

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
