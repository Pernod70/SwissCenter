<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/playlist.php");
  require_once("base/file.php");

  page_header( "Homepage");

  echo '<center>Please select an option from the list:</center><p>';

  $menu = new menu();
  $menu->add_item("Watch A Movie",'video.php',true);
  $menu->add_item("Listen to Music",'music.php',true);
//  $menu->add_item("View Photographs",'',true);
  
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
  page_footer('');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
