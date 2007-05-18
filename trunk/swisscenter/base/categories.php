<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/menu.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/rating.php'));
  
  define('CAT_ALL', -1);
  define('CAT_NEW', -2);

  // -------------------------------------------------------------------------------------------------
  // Displays a menu of categories and allows you to choose one.
  // -------------------------------------------------------------------------------------------------

  function display_categories($next_page, $media_type)
  {
    echo '<center>'.str('SELECT_CATEGORY').'</center><p>';
    
    $special    = array( array("CAT_NAME"=>str('CAT_LIST_ALL'),"CAT_ID"=>CAT_ALL)
                       , array("CAT_NAME"=>str('CAT_RECENTLY_ADDED'),"CAT_ID"=>CAT_NEW) );

    $media_table = db_value("select media_table from media_types where media_id=$media_type");

    $categories = db_toarray("select distinct c.cat_id,c.cat_name from
                                categories c inner join media_locations ml on c.cat_id=ml.cat_id
                                inner join $media_table media on media.location_id=ml.location_id
                                left outer join certificates media_cert on media_cert.cert_id=media.certificate
                                inner join certificates unrated_cert on unrated_cert.cert_id=ml.unrated
                              where ml.media_type=$media_type".get_rating_filter()." order by c.cat_name ASC");

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
        $sql = " and media.location_id in (".implode($locations,",").")";
    }
    elseif($cat_id == CAT_NEW)
    {
      $sql = " and media.discovered > ('".db_datestr()."' - interval 7 day)";
    }
    // No sql needed for CAT_ALL
    
    return $sql;
  }
  
  // -------------------------------------------------------------------------------------------------
  // Returns the SQL needed to populate a drop-down list of categories.
  // -------------------------------------------------------------------------------------------------

  function category_list_sql()
  {
    return 'select cat_id,cat_name from categories order by cat_name';
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
