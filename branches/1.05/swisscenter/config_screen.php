<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once('base/prefs.php');

  if (isset($_REQUEST["SCREEN"]))
  {
    set_sys_pref('screen', $_REQUEST["SCREEN"]);
    $_SESSION["opts"]["screen"] = $_REQUEST["SCREEN"];
    header('Location: /index.php');
  }
  else 
  {
    $menu = new menu();
    page_header('Configure Screen');
  	$menu->add_item("PAL","config_screen.php?SCREEN=Pal",true);
  	$menu->add_item("NTSC","config_screen.php?SCREEN=NTSC",true);
    $menu->display(320,28);
    page_footer( 'config.php', $buttons );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
