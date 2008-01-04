<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/page.php'));
  require_once( realpath(dirname(__FILE__).'/utils.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/az_picker.php'));
  require_once( realpath(dirname(__FILE__).'/rating.php'));
  require_once( realpath(dirname(__FILE__).'/media.php'));
  
#-------------------------------------------------------------------------------------------------
# Functions for managing the search history.
#-------------------------------------------------------------------------------------------------

function search_hist_init( $url, $sql )
{
  $_SESSION["picker"]  = array();
  $_SESSION["history"] = array();
  $_SESSION["history"][] = array("url"=> $url, "sql"=>$sql);
}

function search_picker_init( $url )
{
  $_SESSION["picker"]  = array($url);
}

function search_picker_push( $url )
{
  $_SESSION["picker"][]  = $url;
}

function search_picker_pop()
{
  if (count($_SESSION["picker"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return array_pop($_SESSION["picker"]);  
}

function search_picker_most_recent()
{
  if (count($_SESSION["picker"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return $_SESSION["picker"][count($_SESSION["picker"])-1]; 
}

function search_hist_push( $url, $sql )
{
  $_SESSION["history"][] = array("url"=> $url, "sql"=>$sql);
}

function search_hist_pop()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return array_pop($_SESSION["history"]);
}

function search_hist_most_recent()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));
  
  return $_SESSION["history"][count($_SESSION["history"])-1];
}

function search_hist_first()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return $_SESSION["history"][0];
}

#-------------------------------------------------------------------------------------------------
# Function to output a "search" page for any media type.
#-------------------------------------------------------------------------------------------------  

function  search_media_page( $heading, $title, $media_type, $joined_tables, $column,  $choose_url )
{
  // Make sure that the session variable for "shuffle" matches the user's preference (because it will have been set "on" for quick play).
  $_SESSION["shuffle"] = get_user_pref('shuffle','off');
  
  // Should we delete the last entry on the history stack?
  if (isset($_REQUEST["del"]) && strtoupper($_REQUEST["del"]) == 'Y')
  {
    search_hist_pop();
    search_picker_pop();
  }

  // Get important paramters from the URL
  $this_url       = url_set_param(current_url(),'del','N');
  $search         = ( isset($_REQUEST["search"]) ? un_magic_quote(rawurldecode($_REQUEST["search"])) : '');
  $prefix         = ( isset($_REQUEST["any"]) ? $_REQUEST["any"] : '');
  $page           = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
  $focus          = ( empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] );
  $menu           = new menu();
  $data           = array();
  $history        = search_hist_most_recent();

  // variables that form the SQL statement
  $main_table     = get_media_table($media_type);
  $main_table_sql = "$main_table media ".get_rating_join();
  $restrict_sql   = "$column like '$prefix".db_escape_str(str_replace('_','\_',$search))."%' $history[sql]";
  
  $viewed_sql     = "select concat( sum(if(viewings.total_viewings>0,1,0)),':',count(*) ) view_status
                     from $main_table_sql $joined_tables
                     left outer join viewings on (media.file_id = viewings.media_id and viewings.media_type= $media_type)";

  // Adding necessary paramters to the target URL (for when an item is selected)
  $choose_url = url_set_param($choose_url,'add','Y');
  $choose_url = url_set_param($choose_url,'type',$column);

  // Get the matching records from the database.
  $data       = db_toarray("   select distinct $column display 
                                 from $main_table_sql $joined_tables
                                where $column != '0' and $restrict_sql 
                             order by 1 limit ".(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE);
  
  $num_rows   = db_value("     select count(distinct $column) 
                                 from $main_table_sql $joined_tables
                                where $column != '0' and $restrict_sql");
  
  if ( $data === false || $num_rows === false)
    page_error(str('DATABASE_ERROR'));
    
  if ($prefix == '')
    $valid = strtoupper(join(db_col_to_list(" select distinct substring($column,".(strlen($search)+1).",1) display from $main_table_sql $joined_tables where $column !='0' and $restrict_sql order by 1")));
  else
    $valid = '';

  // Remove last picker state (del='N' if we have not arrived here from another page) 
  if (isset($_REQUEST["del"]) && strtoupper($_REQUEST["del"]) == 'N')
    search_picker_pop();
    
  // Start outputting the page
  page_header( $heading, $title.' : '.$search, '', $focus );
  echo '<table border=0 width="100%"><tr><td width="'.convert_x(300).'" valign="top">';
  show_picker( url_set_param($this_url,'page',0), $search, '', $valid);
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
    {
      $viewed = explode(':',db_value($viewed_sql." where $column = '".db_escape_str($row["DISPLAY"])."' and $restrict_sql group by $column"));
      $menu->add_item($row["DISPLAY"],url_set_param($choose_url,'name',rawurlencode($row["DISPLAY"])), false, viewed_icon($viewed[0], $viewed[1]) );
    }

    $menu->display(1, 540);
  }

  echo '</td></tr></table>';

  // Output ABC buttons
  if (empty($prefix))
  {
    $buttons[] = array('text'=>str('SEARCH_ANYWHERE'), 'url'=>url_add_param($this_url,'any','%') );
    $buttons[] = array('text'=>str('SEARCH_CLEAR'), 'url'=>url_add_param($this_url,'search','') );
    $buttons[] = array('text'=>str('SELECT_ALL'),   'url'=>url_set_param($choose_url,'name',rawurlencode($search.'%')) );
  }
  else
  {
    $buttons[] = array('text'=>str('SEARCH_START'), 'url'=>url_add_param($this_url,'any','') );
    $buttons[] = array('text'=>str('SEARCH_CLEAR'), 'url'=>url_add_param($this_url,'search','') );
    $buttons[] = array('text'=>str('SELECT_ALL'),   'url'=>url_set_param($choose_url,'name',rawurlencode('%'.$search.'%')) );
  }

  page_footer( $history["url"], $buttons);
}
  
#-------------------------------------------------------------------------------------------------
# Function to process the paramters passed by the function above when an item has been selected.
#-------------------------------------------------------------------------------------------------

function search_process_passed_params()
{
  $name      = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $type      = un_magic_quote($_REQUEST["type"]);
  $history   = search_hist_most_recent();
  
  // If no $type is specified, then $name contains a pure SQL predicate to add.
  if (empty($type))
    $predicate = $history["sql"]." and $name";
  else 
    $predicate = $history["sql"]." and $type like '".db_escape_str(str_replace('_','\_',$name))."'";

  if (isset($_REQUEST["shuffle"]))
  {
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];
    set_user_pref('shuffle',$_REQUEST["shuffle"]);
  }

  // Add page to history, otherwise pop picker
  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
  {
    $hist_url  = url_set_param(current_url(),'add','N');
    search_hist_push( $hist_url , $predicate );
  }
  else
  {
    search_picker_pop();
  }
  
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
  {
    $col = db_value("select $column from $table $predicate limit 1");
    $info->add_item($info_text, $col);
    return $col;
  }
  else 
    return '';
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
