<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");

  $_SESSION["history"] = array(array("url"=>"video.php"));

  page_header("Watch A Movie");
  echo '<center>Please select an option from the list:</center><p>';

  $menu = new menu();
  $menu->add_item("Browse by Title","video_search.php?sort=title",true);
  $menu->add_item("Browse by Actor","video_search.php?sort=actor",true);
  $menu->add_item("Browse by Director","video_search.php?sort=director",true);
  $menu->add_item("Browse by Genre","video_search.php?sort=genre",true);
  $menu->add_item("Browse by Year","video_search.php?sort=year",true);
  $menu->add_item("Browse by Certificate","video_search.php?sort=certificate",true);
  $menu->add_item("Browse Filesystem","video_browse.php",true);
  $menu->display();
  page_footer( 'index.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
