<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/mysql.php");
  require_once("base/az_picker.php");
  require_once("base/users.php");

  function search_page( $title, $column, $sort, $this_url, $prefix, $search, $page)
  {
    page_header('Video', $title.' : '.$search,'LOGO_MOVIE','', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );
    $menu      = new menu();
    $data      = array();
    $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
    $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];// . " and IFNULL(media_cert.rank,unrated_cert.rank) <= ".get_current_user_rank();
    $sql       = "from movies media
                  inner join media_locations ml on media.location_id = ml.location_id
                  left outer join certificates media_cert on media.certificate = media_cert.cert_id
                  inner join certificates unrated_cert on ml.unrated = unrated_cert.cert_id
                  left outer join directors_of_movie dom on media.file_id = dom.movie_id
                  left outer join genres_of_movie gom on media.file_id = gom.movie_id
                  left outer join actors_in_movie aim on media.file_id = aim.movie_id
                  left outer join actors a on aim.actor_id = a.actor_id
                  left outer join directors d on dom.director_id = d.director_id
                  left outer join genres g on gom.genre_id = g.genre_id where ";
    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( $this_url.'?sort='.$sort.'&any='.$prefix.'&search=', $search);
    echo '</td><td valign=top>';

    // Get the matching records from the database.
    $data = db_toarray("select distinct $column display $sql $column !='' and $column like '".$prefix.db_escape_str(str_replace('_','\_',$search))."%' ".$post_sql." order by 1 limit ".(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE);
    $num_rows = db_value("select count(distinct $column) $sql $column !='' and $column like '".$prefix.db_escape_str(str_replace('_','\_',$search))."%' ".$post_sql);

    if ( $data === false || $num_rows === false)
      page_error('A database error occurred');

    if (empty($data))
    {
      echo 'There are no items that match your search.';
    }
    else
    {

      // We are not on the first page, so output a link to go "up" a page of entries.
      if ($page > 0)
        $menu->add_up( $this_url.'?sort='.$sort.'&search='.rawurlencode($search).'&any='.$prefix.'&page='.($page-1));

      // We are not on the last page, so output a link to go "down" a page of entries.
      if (($page+1)*MAX_PER_PAGE < $num_rows)
        $menu->add_down( $this_url.'?sort='.$sort.'&search='.rawurlencode($search).'&any='.$prefix.'&page='.($page+1));

      foreach ($data as $row)
      {
        $menu->add_item($row["DISPLAY"],'video_selected.php?add=Y&type='.$sort.'&name='.rawurlencode($row["DISPLAY"]),true);
      }
      $menu->display( "300" );
    }

    echo '</td></tr></table>';

    // Output ABC buttons if appropriate

    if (empty($prefix))
      $buttons[] = array('text'=>'Anywhere in Name', 'url'=>$this_url.'?sort='.$sort.'&search='.rawurlencode($search).'&any=%' );
    else
      $buttons[] = array('text'=>'Start of Name', 'url'=>$this_url.'?sort='.$sort.'&search='.rawurlencode($search).'&any=' );

    $buttons[] = array('text'=>'Clear Search', 'url'=>$this_url.'?sort='.$sort.'&any='.$prefix);
    $buttons[] = array('text'=>'Select All', 'url'=>'video_selected.php?add=Y&type='.$sort.'&name='.rawurlencode($search.'%'));

    page_footer($back_url, $buttons);
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Should we delete the last entry on the history stack?
  if (strtoupper($_REQUEST["del"]) == 'Y')
    array_pop($_SESSION["history"]);

    // Check page parameters, and if not set then assign default values.
  $sort    = $_REQUEST["sort"];
  $search  = un_magic_quote(rawurldecode($_REQUEST["search"]));
  $prefix  = $_REQUEST["any"];
  $page    = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
  
  switch ($sort)
  {
    case "title":
    case "year":
      $column = $sort;
      break;
    case "genre":
    case "actor":
    case "director":
      $column = $sort."_name";
      break;
    case "certificate":
      $column = "IFNULL(media_cert.name,unrated_cert.name)";
      break;
  }

  search_page( 'Browse by '.ucfirst($sort), $column, $sort, 'video_search.php', $prefix, $search, $page)


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
