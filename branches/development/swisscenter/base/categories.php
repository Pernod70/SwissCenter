<?
  require_once('menu.php');
  require_once('mysql.php');
  
  define('CAT_ALL', -1);
  define('CAT_NEW', -2);

  function display_categories($next_page, $media_type)
  {
    echo '<center>Please select a category from the list:</center><p>';
    
    $cats = db_toarray("select distinct c.cat_id,c.cat_name from categories c,media_locations ml where c.cat_id=ml.cat_id and ml.media_type=$media_type order by c.cat_name ASC"); 

    $menu = new menu();
    
    $menu->add_item("All Categories", $next_page."?cat=".CAT_ALL, true);
    
    foreach($cats as $cat)
      $menu->add_item($cat["CAT_NAME"], $next_page."?cat=".$cat["CAT_ID"], true);
    
    $menu->display();
    
    page_footer( 'index.php' );
  }
  
  function category_select_sql($cat_id, $media_type)
  {
    // > 0 indicates normal categories
    if($cat_id > 0)
    {
      $locations = db_col_to_list("select location_id from media_locations where cat_id=$cat_id and media_type=$media_type");
      if(!empty($locations))
        $sql = " and location_id in (".implode($locations,",").")";
    }
    elseif($cat_id == CAT_NEW)
    {
      // TODO: New media SQL
    }
    // No sql needed for CAT_ALL
    
    return $sql;
  }
  
  function categories_count($media_type)
  {
    return db_value("select count(distinct cat_id) from media_locations where media_type=$media_type");
  }

?>
