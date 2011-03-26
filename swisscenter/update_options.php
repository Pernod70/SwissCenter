<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/svn.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));

  // Get array of all releases
  $releases = svn_release_tags();
  ksort($releases);

  // Installed stable release
  $current_version = swisscenter_version();

  // Installed SVN revision
  $current_svn = svn_current_revision();

  // Available stable release
  end($releases);
  $latest_version = key($releases);

  // Available SVN revision
  $latest_svn = svn_latest_revision();

  page_header( str('SETUP_UPDATE_SC'),'','',1,false,'','PAGE_CONFIG');

  echo '<center>'.font_tags(FONTSIZE_BODY).str('CURRENT_VERSION').' '.$current_version.' : SVN Revision ['.$current_svn.']<center>';
  echo '<p>';
  echo '<center>'.font_tags(FONTSIZE_BODY).str('AVAILABLE_VERSION').' '.$latest_version.' : SVN Revision ['.$latest_svn.']<center>';
  echo '<p>';
  $menu = new menu();

  if ( $latest_version >= $current_version )
    $menu->add_item(str('SETUP_UPDATE_SC_RELEASE'),'run_update.php');
  elseif ( $latest_svn >= $current_svn )
    $menu->add_item(str('SETUP_UPDATE_SC_SVN'),'run_svn_update.php');

  $menu->display(1, style_value("MENU_CONFIG_WIDTH"), style_value("MENU_CONFIG_ALIGN"));
  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
