<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/install_checks.php");

  require_once("base/page.php");
  require_once("base/playlist.php");
  require_once("base/users.php");
  require_once("base/file.php");
  require_once("messages_db.php");

  page_header( str('MAIN_MENU'));

  $menu = new menu();
  $icons = new iconbar(400);

  // Menu Items

  $menu->add_item(str('WATCH_MOVIE'),'video.php',true);
  $menu->add_item(str('LISTEN_MUSIC'),'music.php',true);

  if (internet_available() && get_sys_pref('radio_enabled','YES') == 'YES')
    $menu->add_item( str('LISTEN_RADIO'),"music_radio.php",true);

  $menu->add_item( str('VIEW_PHOTO'),'photo.php',true);

  if (internet_available() && get_sys_pref('weather_enabled','YES') == 'YES')
    $menu->add_item( str('VIEW_WEATHER') ,'weather_cc.php',true);

  if (pl_enabled())
    $menu->add_item( str('MANAGE_PLAYLISTS'),'manage_pl.php',true);

  // Icons

  if (get_num_users() > 1)
    $icons->add_icon("ICON_USER",get_current_user_name(),'change_user.php');

  if (internet_available() && $_SESSION["update"]["available"])
    $icons->add_icon("ICON_UPDATE",str('UPDATE'),'run_update.php');

  $num_new = count_messages_with_status(MESSAGE_STATUS_NEW);
  if(($num_new) > 0)
    $icons->add_icon("ICON_MAIL",str('NEW'),"messages.php?return=".current_url());

  $icons->add_icon("ICON_SETUP",str('SETUP'),"config.php");

  // Display the page content

  echo '<center>'.str('SELECT_OPTION').'</center><p>';
  $menu->display();
  page_footer('', '', $icons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
