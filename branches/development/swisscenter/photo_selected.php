<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/file.php");
  require_once("base/playlist.php");
  require_once("base/rating.php");
  
  // Function that checks to see if the given attribute ($filter) is unique, and if so it
  // populates the information table ($info)

  function distinct_info (&$info, $info_text, $column, $table, $predicate)
  {
    if ( db_value("select count(distinct $column) $table $predicate") == 1)
      $info->add_item($info_text, db_value("select $column $table $predicate limit 0,1"));
  }

  // Checks to see if the supplied column is unique in for all selected rows, and if not it
  // adds a "Refine by" option to the menu.

  function check_filter ( &$menu, $menu_text, $column, $table, $predicate )
  {
    if ( db_value("select count(distinct $column) $table $predicate") > 1)
      $menu->add_item($menu_text, "photo_search.php?sort=".$column,true);
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $sql_table = "from photos media ".get_rating_join()." inner join photo_albums pa on media.dirname like concat(pa.dirname,'%') where 1=1 ";
  
  $menu      = new menu();
  $info      = new infotab();
  $name      = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $type      = un_magic_quote($_REQUEST["type"]);
  $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
  $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
  $delay = 5;

  $predicate = $post_sql." and $type like '".db_escape_str(str_replace('_','\_',$name))."'";

  $count     = db_value("select count($type) $sql_table $predicate");

  if (isset($_REQUEST["shuffle"]))
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];

  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
    $_SESSION["history"][] = array("url"=> str_replace('add=Y','add=N',url_add_param(current_url(),'p_del','Y')), "sql"=>$predicate);

  // Information on the current selection  
  $info->add_item(str('PHOTOS_NO_SELECTED'), $count);
  $info->add_item(str('PHOTOS_TIME_ONE'), $delay.' Seconds');
  $info->add_item(str('PHOTOS_TIME_ALL'), hhmmss($delay * $count));

  // Menu Options
  $menu->add_item(str('START_SLIDESHOW'),pl_link('sql',"select media.* $sql_table $predicate order by title",'photo'));

  // Display Page
  page_header(str('SLIDESHOW'),'','LOGO_PHOTO');
  $info->display();
  $menu->display();
  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
