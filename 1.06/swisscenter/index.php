<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/page.php");
  require_once("base/playlist.php");
  require_once("base/file.php");
  require_once("messages_db.php");

  $menu = new menu();
  $icons = new iconbar(400);

  // Menu Items
  
  $menu->add_item("Watch A Movie",'video.php',true);
  $menu->add_item("Listen to Music",'music.php',true);

  if (internet_available() && get_sys_pref('radio_enabled','YES') == 'YES')
    $menu->add_item("Listen to Internet Radio","music_radio.php",true);

  $menu->add_item("View Photographs",'photo.php',true);
  
  if (internet_available() && get_sys_pref('weather_enabled','YES') == 'YES')
    $menu->add_item("View Weather Forecasts",'weather_cc.php',true);

  if (pl_enabled())
    $menu->add_item("Manage Playlists",'manage_pl.php',true);

  // Icons
  
  if (internet_available() && $_SESSION["update"]["available"])
    $icons->add_icon("ICON_UPDATE","Update",'run_update.php');  

  $num_new = count_messages_with_status(MESSAGE_STATUS_NEW);
  if(($num_new) > 0)
    $icons->add_icon("ICON_MAIL","New","messages.php?return=".current_url());
    
  $icons->add_icon("ICON_SETUP","Setup","config.php");

  // Display the page content
    
  page_header( "Main Menu", '','LOGO_HOME');
  echo '<center>Please select an option from the list:</center><p>';
  $menu->display();
  page_footer('', '', $icons);  

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
