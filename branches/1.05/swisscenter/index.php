<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/playlist.php");
  require_once("base/file.php");
  require_once("messages_db.php");

  page_header( "Homepage");

  echo '<center>Please select an option from the list:</center><p>';

  $menu = new menu();
  $menu->add_item("Watch A Movie",'video.php',true);
  $menu->add_item("Listen to Music",'music.php',true);
  $menu->add_item("View Photographs",'photo.php',true);
  
  if ($_SESSION["internet"])
    $menu->add_item("View Weather Forecasts",'weather_cc.php',true);

  if (pl_enabled())
    $menu->add_item("Manage Playlists",'manage_pl.php',true);

  $menu->add_item("Preferences and Setup",'config.php',true);

  if ($_SESSION["internet"] && $_SESSION["update"]["available"])
  {
    $menu->add_item("Update SwissCenter",'run_update.php',true);  
  }

  $menu->display();

  // Create the iconbar to add to the footer  
  $icons = new iconbar();
  
  $num_new = count_messages_with_status(MESSAGE_STATUS_NEW);
  if(($num_new) > 0)
    $icons->add_icon("MAIL", "messages.php?return=".current_url());
  

  page_footer('', '', $icons);
  
//  debug($GLOBALS);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
