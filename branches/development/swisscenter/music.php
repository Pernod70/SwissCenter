<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/categories.php");


  function display_music_menu($cat_id)
  {
    if(empty($cat_id))
      $_SESSION["history"] = array(array("url"=>"music.php"));
    else
      $_SESSION["history"] = array(array("url"=>"music.php?cat=$cat_id", "sql"=>category_select_sql($cat_id, 1)));


    echo '<center>Please select an option from the list:</center><p>';

    $menu = new menu();

    $menu->add_item("Browse Music by Artist Name","music_search.php?sort=artist",true);
    $menu->add_item("Browse Music by Album Name","music_search.php?sort=album",true);
    $menu->add_item("Browse Music by Track Name","music_search.php?sort=title",true);
    $menu->add_item("Browse Music by Genre","music_search.php?sort=genre",true);
    $menu->add_item("Browse Music by Year","music_search.php?sort=year",true);
    $menu->add_item("Browse Filesystem","music_browse.php",true);
    $menu->display();
    
    
    if(!empty($cat_id))
      page_footer('music.php');
    else
      page_footer('index.php');
  }
  

/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header("Listen to Music", '', 'LOGO_MUSIC');

  $cat_id = $_REQUEST["cat"];
  if(empty($cat_id))
    $number_of_cats = categories_count(1);
  
  if(($number_of_cats == 1) || !empty($cat_id))
    display_music_menu($cat_id);
  else
    display_categories('music.php', 1);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
