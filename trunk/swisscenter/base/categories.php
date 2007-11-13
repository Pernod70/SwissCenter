<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/menu.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/rating.php'));
  
  /**
   * Displays a menu of categories and allows you to choose one.
   *
   * @param URL $next_page - URL of page to go to once a category has been chosen
   * @param integer $media_type - The type of media for which we want to display categories
   * @param integer $parent_id - [Optional] parent ID if we wish to display subcategories
   */

  function display_categories($next_page, $media_type, $parent_id=0)
  {
    echo '<center>'.str('SELECT_CATEGORY').'</center><p>';
    
    $special    = array( array("CAT_NAME"=>str('CAT_LIST_ALL'),"CAT_ID"=>-$parent_id) );

    $media_table = db_value("select media_table from media_types where media_id=$media_type");

    // Array of all end node categories containing media
    $category_ids_media = db_col_to_list("select distinct c.cat_id from
                                categories c inner join media_locations ml on c.cat_id=ml.cat_id
                                inner join $media_table media on media.location_id=ml.location_id
                                left outer join certificates media_cert on media_cert.cert_id=media.certificate
                                inner join certificates unrated_cert on unrated_cert.cert_id=ml.unrated
                              where ml.media_type=$media_type".get_rating_filter());
    
    // Array of all sub-categories leading to media
    $category_ids = category_parents($category_ids_media);

    // Categories at current node
    $categories_node = db_toarray("select distinct cat_id,cat_name from categories
                              where parent_id=$parent_id order by cat_name ASC");
    
    $categories = array();
    foreach ($categories_node as $category_node)
    {
      if (in_array($category_node["CAT_ID"],$category_ids))
        $categories[] = $category_node;
    } 
    
    $cats       = array_merge( $categories, $special);
    $page       = (isset($_REQUEST["cat_page"]) ? $_REQUEST["cat_page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE; 
    $end        = min($start+MAX_PER_PAGE,count($cats));

    $menu = new menu();    

    if ($page > 1)
      $menu->add_up( url_add_param(current_url(),'cat_page',($page-1)));

    if ( count($cats) > $end)
      $menu->add_down( url_add_param(current_url(),'cat_page',($page+1)));

    for ($i=$start; $i<$end; $i++) 
    { 
      // Check for another sub-category or category contains media
      if ( !in_array($cats[$i]["CAT_ID"],$category_ids_media) && $cats[$i]["CAT_ID"]>0)
        $menu->add_item($cats[$i]["CAT_NAME"], $next_page."?subcat=".$cats[$i]["CAT_ID"], true);
      else
        $menu->add_item($cats[$i]["CAT_NAME"], $next_page."?cat=".$cats[$i]["CAT_ID"], true);
    }
    $menu->display();
    
    // Make sure the "back" button goes to the correct page:
    if ($parent_id==0)
      page_footer( 'index.php' );
    else
    {
      $back_id = db_value("select parent_id from categories where cat_id=$parent_id");
      page_footer( $next_page."?subcat=".$back_id );
    }
  }
  
  /**
   * Returns the SQL condition required to filter by category
   *
   * @param integer $cat_id - The ID of the category to filter on
   * @param integer $media_type - The type of media.
   * @return string
   */
  
  function category_select_sql($cat_id, $media_type)
  {
    // > 0 indicates normal categories, < 0 indicates all sub-categories
    if($cat_id > 0)
    {
      $locations = db_col_to_list("select location_id from media_locations where cat_id=$cat_id and media_type=$media_type");
      if(!empty($locations))
        return " and media.location_id in (".implode($locations,",").")";
    }
    elseif ($cat_id < 0)
    {
      $locations = db_col_to_list("select location_id from media_locations where cat_id in (".
                                  implode(",",category_children(-$cat_id)).") and media_type=$media_type");
      if(!empty($locations))
        return " and media.location_id in (".implode($locations,",").")";
    }
    else 
    {
      // If no $cat_id is specified (or it is 0) then the user wants to see all categories and
      // therefore there is no need to return any SQL at all. 
      return '';
    }
  }
  
  /**
   * Returns the SQL needed to populate a drop-down list of categories.
   *
   * @return string
   */
  
  function category_list_sql()
  {
    return 'select cat_id,cat_name from categories order by cat_name';
  }
  
  /**
   * Returns the number of categories
   *
   * @param integer $media_type - The type of media
   * @return integer
   */
  
  function category_count($media_type)
  {
    $media_table = db_value("select media_table from media_types where media_id=$media_type");
    return db_value("select count(distinct c.cat_id) 
                         from categories c inner join media_locations ml on c.cat_id=ml.cat_id
                              inner join $media_table media on media.location_id=ml.location_id
                              left outer join certificates media_cert on media_cert.cert_id=media.certificate
                              inner join certificates unrated_cert on unrated_cert.cert_id=ml.unrated
                        where ml.media_type=$media_type".get_rating_filter()." order by c.cat_name ASC");
  }
  
  /**
   * Returns an array containg all category id's which contain media
   *
   * @param array $category_ids - end node category id's containing media
   * @return array $valid_category_ids - all category id's containing media
   */
  
  function category_parents($category_ids)
  {
    $valid_category_ids = array();
    
    foreach ($category_ids as $category_id)
    {
      $valid_category_ids[] = $category_id;
      $parent_id = db_value("select parent_id from categories where cat_id=$category_id");
      
      while ($parent_id != 0)
      {
        if (!in_array($parent_id,$valid_category_ids))
          $valid_category_ids[] = $parent_id;
          
        $parent_id = db_value("select parent_id from categories where cat_id=$parent_id");
      }
    }
    return $valid_category_ids;
  }
  
  /**
   * Returns an array containg all category id's which contain media
   *
   * @param array $cat_id - category id to list children from
   * @return array $category_ids - all child category id's
   */
  
  function category_children($cat_id)
  {
    $valid_category_ids = array();
    
    $category_ids = db_col_to_list("select cat_id from media_locations");
    
    foreach ($category_ids as $category_id)
    {
      $parent_id = db_value("select parent_id from categories where cat_id=$category_id");
      
      while ($parent_id != 0)
      {
        if ($parent_id == $cat_id && !in_array($parent_id,$valid_category_ids))
          $valid_category_ids[] = $category_id;
          
        $parent_id = db_value("select parent_id from categories where cat_id=$parent_id");
      }
    }
    return $valid_category_ids;
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
