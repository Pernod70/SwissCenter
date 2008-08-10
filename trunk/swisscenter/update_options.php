<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));

  if (get_sys_pref("SVN_REVISION") == "")
    $version = swisscenter_version();
  else
    $version = 'SVN Revision ['.get_sys_pref("SVN_REVISION").']';

  page_header( str('SETUP_UPDATE_SC'), str('CURRENT_VERSION').' '.$version,'',1,false,'','PAGE_CONFIG');

  echo '<p>';
  $menu = new menu();

  $menu->add_item(str('SETUP_UPDATE_SC_RELEASE'),'run_update.php');
  $menu->add_item(str('SETUP_UPDATE_SC_SVN'),'run_svn_update.php');

  $menu->display(1, style_value("MENU_CONFIG_WIDTH"), style_value("MENU_CONFIG_ALIGN"));
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
