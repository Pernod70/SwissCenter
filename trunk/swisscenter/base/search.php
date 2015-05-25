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
# Function to output a "search" page for any media type.
#-------------------------------------------------------------------------------------------------

function search_media_page( $heading, $title, $media_type, $joined_tables, $column, $choose_url )
{
  // Make sure that the session variable for "shuffle" matches the user's preference (because it will have been set "on" for quick play).
  $_SESSION["shuffle"] = get_user_pref('shuffle','off');

  // Get important paramters from the URL
  $this_url  = current_url();
  $search    = ( isset($_REQUEST["search"]) ? rawurldecode($_REQUEST["search"]) : '');
  $prefix    = ( isset($_REQUEST["any"]) ? $_REQUEST["any"] : '');
  $page      = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
  $focus     = ( empty($_REQUEST["last"]) ? '1' : $_REQUEST["last"] );
  $menu      = new menu();
  $data      = array();
  $predicate = page_hist_current('sql');

  // Contents and ordering of search menu
  $display = $column["display"];
  $info    = $column["info"];
  $order   = $column["order"];

  // Variables that form the SQL statement
  $main_table     = get_media_table($media_type);
  $main_table_sql = "$main_table media ";
  $restrict_sql   = "$display like '$prefix".db_escape_str(str_replace('_','\_',$search))."%' ".$predicate;

  $viewed_sql     = "select concat( sum(if(v.total_viewings>0,1,0)),':',count(*) ) view_status
                     from $main_table_sql $joined_tables";

  // Adding necessary paramters to the target URL (for when an item is selected)
  $choose_url = url_set_params($choose_url, array('type'=>$display));

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
    $valid = mb_strtoupper(join(db_col_to_list(" select distinct upper(substring($display,".(mb_strlen($search)+1).",1)) display
                                                from $main_table_sql $joined_tables
                                               where $display !='0' and $restrict_sql and ml.media_type=$media_type
                                            group by $display
                                              having ".viewed_status_predicate( filter_get_name() )."
                                            order by 1")));
  else
    $valid = '';

  // Start outputting the page
  page_header( $heading, $title.' : '.$search, '', $focus, false,'','PAGE_KEYBOARD');
  echo '<table border=0 width="100%"><tr><td width="'.convert_x(300).'" valign="top">';
  show_picker( url_set_param($this_url, 'page', 0), $search, '', $valid);
  echo '</td><td valign=top>';

  if (empty($data))
  {
    echo font_tags(FONTSIZE_BODY).str('SEARCH_NO_ITEMS');
  }
  else
  {
    // Display links for previous and next pages
    $last_page = ceil($num_rows/MAX_PER_PAGE)-1;
    if ($num_rows > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_params($this_url, array('last'=>($page > 0 ? MAX_PER_PAGE : ($num_rows % MAX_PER_PAGE)), 'page'=>($page > 0 ? ($page-1) : $last_page))) );
      $menu->add_down( url_add_params($this_url, array('last'=>1, 'page'=>($page < $last_page ? ($page+1) : 0))) );
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
    $buttons[] = array('text'=>str('SEARCH_ANYWHERE'), 'url'=>url_add_params($this_url, array('any'=>'%', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array('text'=>str('SEARCH_CLEAR'),    'url'=>url_add_params($this_url, array('search'=>'', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array('text'=>str('SELECT_ALL'),      'url'=>url_set_params($choose_url, array('name'=>rawurlencode($search.'%'))) );
  }
  else
  {
    $buttons[] = array('text'=>str('SEARCH_START'), 'url'=>url_add_params($this_url, array('any'=>'', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array('text'=>str('SEARCH_CLEAR'), 'url'=>url_add_params($this_url, array('search'=>'', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array('text'=>str('SELECT_ALL'),   'url'=>url_set_params($choose_url, array('name'=>rawurlencode('%'.$search.'%'))) );
  }

  page_footer(page_hist_previous(), $buttons);
}

#-------------------------------------------------------------------------------------------------
# Function to process the paramters passed by the function above when an item has been selected.
#-------------------------------------------------------------------------------------------------

function search_process_passed_params()
{
  $name      = rawurldecode($_REQUEST["name"]);
  $type      = $_REQUEST["type"];
  $predicate = page_hist_current('sql');

  // If no $type is specified, then $name contains a pure SQL predicate to add.
  if (empty($type))
    $predicate .= " and $name";
  else
  {
    // Remove any existing predicate before appending the new one.
    $predicate = preg_replace(array("/ and $type like '.*'/", "/ and sort_$type like '.*'/"), '', $predicate);
    $predicate .= " and $type like '".db_escape_str(str_replace('_','\_',$name))."'";
  }

  if (isset($_REQUEST["shuffle"]))
  {
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];
    set_user_pref('shuffle',$_REQUEST["shuffle"]);
  }

  // Add new SQL predicate to history
  page_hist_current_update(page_hist_current('url'), $predicate);

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
