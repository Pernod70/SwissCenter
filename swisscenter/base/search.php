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
  require_once( realpath(dirname(__FILE__).'/filter.php'));

#-------------------------------------------------------------------------------------------------
# Functions for managing the search history.
#-------------------------------------------------------------------------------------------------

function search_hist_init( $url = '', $sql = '')
{
  $_SESSION["picker"]  = array();
  $_SESSION["history"] = array();

  if (!empty($url))
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

function search_hist_most_recent_prev()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return $_SESSION["history"][count($_SESSION["history"])-2];
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

function search_media_page( $heading, $title, $media_type, $joined_tables, $column, $choose_url )
{
  // Make sure that the session variable for "shuffle" matches the user's preference (because it will have been set "on" for quick play).
  $_SESSION["shuffle"] = get_user_pref('shuffle','off');

  // Should we delete the last entry on the history stack?
  if (isset($_REQUEST["del"]) && strtoupper($_REQUEST["del"]) == 'Y')
    search_hist_pop();

  // Get important paramters from the URL
  $this_url       = url_remove_param(current_url(),'del');
  $this_url       = url_set_param($this_url,'p_del','Y');
  $search         = ( isset($_REQUEST["search"]) ? un_magic_quote(rawurldecode($_REQUEST["search"])) : '');
  $prefix         = ( isset($_REQUEST["any"]) ? $_REQUEST["any"] : '');
  $page           = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
  $focus          = ( empty($_REQUEST["last"]) ? '1' : $_REQUEST["last"] );
  $menu           = new menu();
  $data           = array();
  $history        = search_hist_most_recent();
  $articles       = get_sys_pref('IGNORE_ARTICLES');

  // Contents and ordering of search menu
  $display = $column["display"];
  $info    = $column["info"];
  $order   = $column["order"];

  // Variables that form the SQL statement
  $main_table     = get_media_table($media_type);
  $main_table_sql = "$main_table media ";
  $restrict_sql   = "trim_article($display,'$articles') like '$prefix".db_escape_str(str_replace('_','\_',$search))."%' ".$history["sql"];

  $viewed_sql     = "select concat( sum(if(v.total_viewings>0,1,0)),':',count(*) ) view_status
                     from $main_table_sql $joined_tables";

  // Adding necessary paramters to the target URL (for when an item is selected)
  $choose_url = url_set_params($choose_url, array('add'=>'Y', 'type'=>$display));

  // Get the matching records from the database.
  $data       = db_toarray("   select $display display, $info info
                                 from $main_table_sql $joined_tables
                                where $display != '0' and $restrict_sql and ml.media_type=$media_type
                             group by $display
                               having ".viewed_status_predicate( filter_get_name() )."
                             order by $order");
  $num_rows   = count($data);
  $data       = array_slice($data, $page*MAX_PER_PAGE, MAX_PER_PAGE);

  if ( $data === false || $num_rows === false)
    page_error(str('DATABASE_ERROR'));

  if ($prefix == '')
    $valid = strtoupper(join(db_col_to_list(" select distinct upper(substring(trim_article($display,'$articles'),".(strlen($search)+1).",1)) display
                                                from $main_table_sql $joined_tables
                                               where $display !='0' and $restrict_sql and ml.media_type=$media_type
                                            group by $display
                                              having ".viewed_status_predicate( filter_get_name() )."
                                            order by 1")));
  else
    $valid = '';

  // Remove last picker state before adding the new one
  if (isset($_REQUEST["p_del"]) && strtoupper($_REQUEST["p_del"]) == 'Y')
    search_picker_pop();

  // Start outputting the page
  page_header( $heading, $title.' : '.$search, '', $focus, false,'','PAGE_KEYBOARD');
  echo '<table border=0 width="100%"><tr><td width="'.convert_x(300).'" valign="top">';
  show_picker( url_set_param($this_url,'page',0), $search, '', $valid);
  echo '</td><td valign=top>';

  if (empty($data))
  {
    echo str('SEARCH_NO_ITEMS');
  }
  else
  {
    // Display links for previous and next pages
    $last_page = ceil($num_rows/MAX_PER_PAGE)-1;
    if ($num_rows > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param($this_url,'page',($page > 0 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param($this_url,'page',($page < $last_page ? ($page+1) : 0)) );
    }

    foreach ($data as $row)
    {
      $viewed = explode(':',db_value($viewed_sql." where $display = '".db_escape_str($row["DISPLAY"])."' and $restrict_sql group by $display"));
      $menu->add_info_item($row["DISPLAY"],$row["INFO"],url_set_param($choose_url,'name',rawurlencode($row["DISPLAY"])), false, viewed_icon($viewed[0], $viewed[1]) );
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

  page_footer( url_add_param($history["url"],'p_del','Y'), $buttons);
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

  // Add page to history
  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
  {
    $hist_url  = url_remove_params(current_url(), array('add','p_del'));
    search_hist_push( $hist_url, $predicate );
  }

  if (isset($_REQUEST["p_del"]) && strtoupper($_REQUEST["p_del"]) == 'Y')
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
  if ( db_value("select count(distinct media.$column) from $table $predicate") > 1)
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
