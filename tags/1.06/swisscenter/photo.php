<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/categories.php");

  function display_photo_menu($cat_id)
  {
    if(empty($cat_id))
      $_SESSION["history"] = array(array("url"=>"photo.php"));
    else
      $_SESSION["history"] = array(array("url"=>"photo.php?cat=$cat_id", "sql"=>category_select_sql($cat_id, 1)));

    echo '<center>Please select an option from the list:</center><p>';

    $menu = new menu();
    $menu->add_item("Browse Filesystem","photo_browse.php",true);
    $menu->display();
    
    if(!empty($cat_id))
      page_footer('photo.php');
    else
      page_footer('index.php');
  }

/**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header("View Photographs",'', 'LOGO_PHOTO');
  $cat_id = $_REQUEST["cat"];
  
  if( !empty($cat_id) )
    display_photo_menu($cat_id);
  else
    display_categories('photo.php', 2);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
