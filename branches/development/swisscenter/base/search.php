<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/mysql.php");
  require_once("base/az_picker.php");
  require_once("base/rating.php");
  
#-------------------------------------------------------------------------------------------------
# Function to output a "search" page for any media type.
#-------------------------------------------------------------------------------------------------  

function  search_media_page( $heading, $title, $main_table, $joined_tables, $column,  $choose_url )
{
  // Should we delete the last entry on the history stack?
  if (strtoupper($_REQUEST["del"]) == 'Y')
    array_pop($_SESSION["history"]);

  // Get important paramters from the URL
  $this_url       = url_set_param(current_url(),'del','N');
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
  page_header( $heading, $title.' : '.$search, '', $focus );
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
  
#-------------------------------------------------------------------------------------------------
# Function to process the paramters passed by the function above when an item has been selected.
#-------------------------------------------------------------------------------------------------

function search_process_passed_params()
{
  $name      = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $type      = un_magic_quote($_REQUEST["type"]);
  $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
  $predicate = $post_sql." and $type like '".db_escape_str(str_replace('_','\_',$name))."'";

  if (isset($_REQUEST["shuffle"]))
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];

  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
    $_SESSION["history"][] = array("url"=> str_replace('add=Y','add=N',url_add_param(current_url(),'p_del','Y')), "sql"=>$predicate);

  return $predicate;
}

#-------------------------------------------------------------------------------------------------
# Checks to see if the supplied column is unique in for all selected rows, and if not it then it
# adds a "Refine by" option to the menu.
#-------------------------------------------------------------------------------------------------

function search_check_filter ( &$menu, $menu_text, $column, $table, $predicate, $refine_url )
{
  if ( db_value("select count(distinct $column) from $table $predicate") > 1)
    $menu->add_item($menu_text, $refine_url."?sort=".$column,true);
}

#-------------------------------------------------------------------------------------------------
# Function that checks to see if the given attribute ($filter) is unique, and if so it
# populates the information table ($info)
#-------------------------------------------------------------------------------------------------

function search_distinct_info (&$info, $info_text, $column, $table, $predicate)
{
  if ( db_value("select count(distinct $column) from $table $predicate") == 1)
    $info->add_item($info_text, db_value("select $column from $table $predicate limit 0,1"));
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
