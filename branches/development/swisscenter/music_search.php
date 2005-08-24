<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/mysql.php");
  require_once("base/az_picker.php");
  require_once("base/rating.php");

  function search_page( $title, $column, $this_url, $prefix, $search, $page)
  {
    page_header(str('LISTEN_MUSIC'), $title.' : '.$search, 'LOGO_MUSIC', '', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );

    $menu      = new menu();
    $data      = array();
    $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
    $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
    $sql = "from mp3s media".get_rating_join()." where";

    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( $this_url.'?sort='.$column.'&any='.$prefix.'&search=', $search);
    echo '</td><td valign=top>';

    // Get the matching records from the database.
    $data = db_toarray("select distinct $column display $sql $column like '".$prefix.db_escape_str(str_replace('_','\_',$search))."%' ".$post_sql." order by 1 limit ".(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE);
    $num_rows = db_value("select count(distinct $column) $sql $column like '".$prefix.db_escape_str(str_replace('_','\_',$search))."%' ".$post_sql);

    if ( $data === false || $num_rows === false)
      page_error(str('DATABASE_ERROR'));

    if (empty($data))
    {
      echo str('SEARCH_NO_ITEMS');
    }
    else
    {

      // We are not on the first page, so output a link to go "up" a page of entries.
      if ($page > 0)
        $menu->add_up( $this_url.'?sort='.$column.'&search='.rawurlencode($search).'&any='.$prefix.'&page='.($page-1));

      // We are not on the last page, so output a link to go "down" a page of entries.
      if (($page+1)*MAX_PER_PAGE < $num_rows)
        $menu->add_down( $this_url.'?sort='.$column.'&search='.rawurlencode($search).'&any='.$prefix.'&page='.($page+1));

      foreach ($data as $row)
      {
        $menu->add_item($row["DISPLAY"],'music_selected.php?add=Y&type='.$column.'&name='.rawurlencode($row["DISPLAY"]),true);
      }
      $menu->display( "300" );
    }

    echo '</td></tr></table>';

    // Output ABC buttons if appropriate

    if (empty($prefix))
      $buttons[] = array('text'=>str('SEARCH_ANYWHERE'), 'url'=>$this_url.'?sort='.$column.'&search='.rawurlencode($search).'&any=%' );
    else
      $buttons[] = array('text'=>str('SEARCH_START'), 'url'=>$this_url.'?sort='.$column.'&search='.rawurlencode($search).'&any=' );

    $buttons[] = array('text'=>str('SEARCH_CLEAR'), 'url'=>$this_url.'?sort='.$column.'&any='.$prefix);
    $buttons[] = array('text'=>str('SELECT_ALL'), 'url'=>'music_selected.php?add=Y&type='.$column.'&name='.rawurlencode($search.'%'));

    page_footer($back_url, $buttons);
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Should we delete the last entry on the history stack?
  if (strtoupper($_REQUEST["del"]) == 'Y')
    array_pop($_SESSION["history"]);

  // Check page parameters, and if not set then assign default values.
  $column    = $_REQUEST["sort"];
  $search  = un_magic_quote(rawurldecode($_REQUEST["search"]));
  $prefix  = $_REQUEST["any"];
  $page    = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);

  switch ($column)
  {
    case "album":
    case "artist":
    case "genre":
    case "year":
      $title = str(strtoupper(($column)));
      break;
    case "title":
      $title = str('TRACK_NAME');
      break;
  }

  search_page( $title, $column, 'music_search.php', $prefix, $search, $page)


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
