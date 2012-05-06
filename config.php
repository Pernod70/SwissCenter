<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/svn.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));

  page_header( str('SETUP_TITLE'), str('CURRENT_VERSION').' '.swisscenter_version().' : '.str('SVN_REVISION').' ['.svn_current_revision().']','',1,false,'','PAGE_CONFIG');

  echo '<p>';
  $menu = new menu();

  // Are there any new messages to display to the user?
  $num_read = count_messages_with_status(MESSAGE_STATUS_READ);
  $num_new = count_messages_with_status(MESSAGE_STATUS_NEW);
  if (($num_read + $num_new) > 0)
  {
    $menu->add_item(str('MESSAGES_VIEW')
                   ." (".$num_new." ".str('MESSAGE_STATUS_NEW').", ".$num_read." ".str('MESSAGE_STATUS_READ').")"
                   ,'messages.php',true);
  }

  $menu->add_item(str('STATISTICS'),'statistics.php',true);
  $menu->add_item(str('LANG_CHANGE'),'change_lang.php',true);
  $menu->add_item(str('SETUP_CHANGE_UI'),'style.php',true);
  $menu->add_item(str('SETUP_REMOTE'),'remote_tvid.php',true);
  $menu->add_item(str('PIN_CHANGE'), 'change_pin.php');
  $menu->add_item(str('SETUP_SEARCH_NEW_MEDIA'),'do_refresh.php', true);

  // Does the User have internet connectivity?
  if (internet_available())
    $menu->add_item(str('SETUP_UPDATE_SC'),'update_options.php',true);

  $menu->display(1, style_value("MENU_CONFIG_WIDTH"), style_value("MENU_CONFIG_ALIGN"));
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
