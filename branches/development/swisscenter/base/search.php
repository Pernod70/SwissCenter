<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/mysql.php");
  require_once("base/az_picker.php");
  require_once("base/rating.php");

  function  search_media_page( $heading, $title, $main_table, $joined_tables, $column,  $choose_url )
  {
    // Should we delete the last entry on the history stack?
    if (strtoupper($_REQUEST["del"]) == 'Y')
      array_pop($_SESSION["history"]);

    // Get important paramters from the URL
    $this_url       = current_url();
    $search         = un_magic_quote(rawurldecode($_REQUEST["search"]));
    $prefix         = $_REQUEST["any"];
    $page           = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
    $focus          = (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] );
    $menu           = new menu();
    $data           = array();
    $back_url       = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
    $post_sql       = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
    $main_table_sql = "$main_table media ".get_rating_join();

    // Adding necessary paramters to the target URL (for when an item is selected)
    $choose_url = url_set_param($choose_url,'add','Y');
    $choose_url = url_set_param($choose_url,'type',$column);

    // Get the matching records from the database.
    $data     = db_toarray("   select distinct $column display 
                                 from $main_table_sql $joined_tables
                                where $column like '".$prefix.db_escape_str(str_replace('_','\_',$search))."%' ".$post_sql." 
                             order by 1 limit ".(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE);
    
    $num_rows = db_value("     select count(distinct $column) 
                                 from $main_table_sql $joined_tables
                                where $column like '".$prefix.db_escape_str(str_replace('_','\_',$search))."%' ".$post_sql);

    if ( $data === false || $num_rows === false)
      page_error(str('DATABASE_ERROR'));

    // Start outputting the page
    page_header( $heading, $title.' : '.$search, '', '', $focus );
    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( $this_url, $search);
    echo '</td><td valign=top>';

    if (empty($data))
    {
      echo str('SEARCH_NO_ITEMS');
    }
    else
    {
      // We are not on the first page, so output a link to go "up" a page of entries.
      if ($page > 0)
        $menu->add_up( url_add_param($this_url,'page',$page-1));

      // We are not on the last page, so output a link to go "down" a page of entries.
      if (($page+1)*MAX_PER_PAGE < $num_rows)
      {
        $menu->add_down( url_add_param($this_url,'page',$page+1));
      }

      foreach ($data as $row)
        $menu->add_item($row["DISPLAY"],url_set_param($choose_url,'name',rawurlencode($row["DISPLAY"])));

      $menu->display( "300" );
    }

    echo '</td></tr></table>';

    // Output ABC buttons
    if (empty($prefix))
      $buttons[] = array('text'=>str('SEARCH_ANYWHERE'), 'url'=>url_add_param($this_url,'any','%') );
    else
      $buttons[] = array('text'=>str('SEARCH_START'), 'url'=>url_add_param($this_url,'any','') );

    $buttons[] = array('text'=>str('SEARCH_CLEAR'), 'url'=>url_add_param($this_url,'search','') );
    $buttons[] = array('text'=>str('SELECT_ALL'),   'url'=>url_set_param($choose_url,'name',rawurlencode($search.'%')) );

    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
