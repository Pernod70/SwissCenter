<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/patching.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));

  /**
   * Check for and apply any oustanding database patches.
   */
  
  apply_database_patches();
  
  /**
   * Menu Items
   */
  
  $menu = new menu();
  $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);
  $menu->add_item(str('WATCH_MOVIE'),'video.php',true);
  $menu->add_item(str('WATCH_TV'),'tv.php',true);
  $menu->add_item(str('LISTEN_MUSIC'),'music.php',true);

  if (internet_available() && get_sys_pref('radio_enabled','YES') == 'YES')
    $menu->add_item( str('LISTEN_RADIO'),"music_radio.php",true);

  $menu->add_item( str('VIEW_PHOTO'),'photo.php',true);
  
  if ( (internet_available() && get_sys_pref('web_enabled','YES') == 'YES') || get_sys_pref('OVERRIDE_ENABLE_WEBLINKS','NO') == 'YES')
  	$menu->add_item(str('BROWSE_WEB'),'web_urls.php',true);

  if (internet_available() && get_sys_pref('weather_enabled','YES') == 'YES')
    $menu->add_item( str('VIEW_WEATHER') ,'weather_cc.php',true);

  if (pl_enabled())
    $menu->add_item( str('MANAGE_PLAYLISTS'),'manage_pl.php',true);

  /**
   * Icons
   */
  
  $icons = new iconbar();

  if (get_num_users() > 1)
    $icons->add_icon("ICON_USER",get_current_user_name(),'change_user.php');

  if (internet_available() && update_available() )
    $icons->add_icon("ICON_UPDATE",str('UPDATE'),'run_update.php');

  if ( count_messages_with_status(MESSAGE_STATUS_NEW) > 0)
    $icons->add_icon("ICON_MAIL",str('NEW'),"messages.php?return=".current_url());

  $icons->add_icon("ICON_SETUP",str('SETUP'),"config.php");

  /**
   * Display the page content
   */

  page_header( str('MAIN_MENU'));
  echo '<center>'.str('SELECT_OPTION').'</center><p>';
  $menu->display_page($page);  
  page_footer('', '', $icons);
  
  // Clear any active filters
  filter_set();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
