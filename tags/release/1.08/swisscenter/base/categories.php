<?
  require_once('menu.php');
  require_once('mysql.php');
  
  define('CAT_ALL', -1);
  define('CAT_NEW', -2);

  // -------------------------------------------------------------------------------------------------
  // Displays a menu of categories and allows you to choose one.
  // -------------------------------------------------------------------------------------------------

  function display_categories($next_page, $media_type)
  {
    echo '<center>Please select a category from the list:</center><p>';
    
    $special    = array( array("CAT_NAME"=>"All Categories","CAT_ID"=>CAT_ALL)
                       , array("CAT_NAME"=>"Recently Added","CAT_ID"=>CAT_NEW) );
    $categories = db_toarray("select distinct c.cat_id,c.cat_name from categories c,media_locations ml where c.cat_id=ml.cat_id and ml.media_type=$media_type order by c.cat_name ASC");     
    $cats       = array_merge( $special , $categories);
    $page       = (isset($_REQUEST["cat_page"]) ? $_REQUEST["cat_page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE; 
    $end        = min($start+MAX_PER_PAGE,count($cats));

    $menu = new menu();    

    if ($page > 1)
      $menu->add_up( url_add_param(current_url(),'cat_page',($page-1)));

    if ( count($cats) > $end)
      $menu->add_down( url_add_param(current_url(),'cat_page',($page+1)));

    for ($i=$start; $i<$end; $i++)
      $menu->add_item($cats[$i]["CAT_NAME"], $next_page."?cat=".$cats[$i]["CAT_ID"], true);
    
    $menu->display();
    
    page_footer( 'index.php' );
  }
  
  // -------------------------------------------------------------------------------------------------
  // Returns the SQL condition required to filter by category
  // -------------------------------------------------------------------------------------------------

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
      $sql = " and discovered > ('".db_datestr()."' - interval 7 day)";
    }
    // No sql needed for CAT_ALL
    
    return $sql;
  }
  
  // -------------------------------------------------------------------------------------------------
  // Returns the number of categories in use for the given media type.
  // -------------------------------------------------------------------------------------------------

  function categories_count($media_type)
  {
    return db_value("select count(distinct cat_id) from media_locations where media_type=$media_type");
  }

?>
