<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/categories.php");

  function display_video_menu($cat_id)
  {
    if(empty($cat_id))
      $_SESSION["history"] = array(array("url"=>"video.php"));
    else
      $_SESSION["history"] = array(array("url"=>"video.php?cat=$cat_id", "sql"=>category_select_sql($cat_id, 3)));

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
    
    if(!empty($cat_id))
      page_footer('video.php');
    else
      page_footer('index.php');
  }
  
  
/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header("Watch A Movie",'','LOGO_MOVIE');

  $cat_id = $_REQUEST["cat"];
  if(empty($cat_id))
    $number_of_cats = categories_count(3);
  
  
  if(($number_of_cats == 1) || !empty($cat_id))
    display_video_menu($cat_id);
  else
    display_categories('video.php', 3);


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
