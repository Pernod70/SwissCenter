<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

  page_header("Listen to Music");

  $_SESSION["history"] = array(array("url"=>"music.php"));

  echo '<center>Please select an option from the list:</center><p>';

  $menu = new menu();
  
  if ($_SESSION["internet"])
    $menu->add_item("Listen to Internet Radio","music_radio.php",true);
    
  $menu->add_item("Browse Music by Artist Name","music_search.php?sort=artist",true);
  $menu->add_item("Browse Music by Album Name","music_search.php?sort=album",true);
  $menu->add_item("Browse Music by Track Name","music_search.php?sort=title",true);
  $menu->add_item("Browse Music by Genre","music_search.php?sort=genre",true);
  $menu->add_item("Browse Music by Year","music_search.php?sort=year",true);
  $menu->add_item("Browse Filesystem","music_browse.php",true);
  $menu->display();
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
