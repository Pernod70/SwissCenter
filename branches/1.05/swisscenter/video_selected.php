<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/file.php");
  require_once("base/playlist.php");

  $menu = new menu();
  $info = new infotab();
  
  // Function that checks to see if the given attribute ($filter) is unique, and if so it
  // populates the information table.

  function distinct_info ($filter, $sql_table, $newsql)
  {
    if (db_value("select count(distinct $filter) from $sql_table where ".substr($newsql,5)) == 1) 
      return db_value("select $filter from $sql_table where ".substr($newsql,5). ' limit 1,1');
    else
      return '';
  }

  // Function that checks to see if the supplied SQL contains a filter for the specified type.
  // If it doesn't, then we can output a menu item which allows the user to filter on that type.

  function check_filters ($filter_list, $sql_table, $newsql, &$menu )
  {
    foreach ($filter_list as $filter)
    {
      $num_rows  = db_value("select count(distinct $filter) from $sql_table where ".substr($newsql,5));
      if ($num_rows>1)
      {
        switch ($filter)
        {
          case 'title'         : $menu->add_item("Refine by Title","video_search.php?sort=title",true); break;
          case 'year'          : $menu->add_item("Refine by Year","video_search.php?sort=year",true);   break;
          case 'rating'        : $menu->add_item("Refine by Rating","video_search.php?sort=certificate",true);  break;
          case 'genre_name'    : $menu->add_item("Refine by Genre","video_search.php?sort=genre",true);  break;
          case 'actor_name'    : $menu->add_item("Refine by Actor","video_search.php?sort=actor",true);  break;        
          case 'director_name' : $menu->add_item("Refine by Director","video_search.php?sort=director",true);  break;
        }
      }
    }
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.

  $type      = un_magic_quote($_REQUEST["type"]);
  
  switch ($type)
  {
    case "title":
    case "year":
      $column = $type;
      break;
    case "genre":
    case "actor":
    case "director":
      $column = $type."_name";
      break;
    case "certificate":
      $column = "rating";
      break;
  }

  $sql_table  = "movies m 
                left outer join directors_of_movie dom on m.file_id = dom.movie_id
                left outer join genres_of_movie gom on m.file_id = gom.movie_id
                left outer join actors_in_movie aim on m.file_id = aim.movie_id
                left outer join actors a on aim.actor_id = a.actor_id
                left outer join directors d on dom.director_id = d.director_id
                left outer join genres g on gom.genre_id = g.genre_id";
  
  $name      = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
  $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
  $newsql    = $post_sql." and $column like '".db_escape_str(str_replace('_','\_',$name))."'";  
  $playtime  = db_value("select sum(length) from $sql_table where ".substr($newsql,5));
  $num_rows  = db_value("select count($column) from $sql_table where ".substr($newsql,5));

  if (isset($_REQUEST["shuffle"]))
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];

  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
    $_SESSION["history"][] = array("url"=> str_replace('add=Y','add=N',url_add_param(current_url(),'p_del','Y')), "sql"=>$newsql);

  //
  // A single movie has been matched/selected by the user, so display as much information as possible
  // on the screen, along with commands to "Play Now" or "Add to Playlist".
  //
    
  if ($num_rows == 1)
  {
    page_header('1 Track Found');
    // Single match, so get the details from the database and display them
    if ( ($data = db_toarray("select m.*, a.actor_name, d.director_name, g.genre_name from $sql_table where ".substr($newsql,5))) === false)
      page_error('A database error occurred');

    $info->add_item('Title', $data[0]["TITLE"]);
    $info->add_item('Year', $data[0]["YEAR"]);
    $info->add_item('Certificate', $data[0]["RATING"]);
    $info->add_item('Director', $data[0]["DIRECTOR_NAME"]);
    $info->add_item('Starring', $data[0]["ACTOR_NAME"]);
    $info->add_item('Genre', $data[0]["GENRE_NAME"]);
    
    $menu->add_item("Play now",pl_link('file',$data[0]["DIRNAME"].$data[0]["FILENAME"]));
    $folder_img = find_in_dir($data[0]["DIRNAME"],$_SESSION["opts"]["art_files"]);

    if (pl_enabled())
      $menu->add_item("Add to your playlist",'add_playlist.php?sql='.rawurlencode('select * from movies where file_id='.$data[0]["FILE_ID"]),true);
  }

  //
  // There are multiple movies which match the criteria enterede by the user. Therefore, we should
  // display the information that is common to all movies, and provide links to refine the search
  // further.
  //
  else
  {

  // More than one track matches, so output filter details and menu options to add new filters
    if ($num_rows ==1)
      page_header($num_rows.' Track Found');
    else
      page_header($num_rows.' Tracks Found');

    if ( ($data = db_toarray("select dirname from $sql_table where ".substr($newsql,5)." group by dirname")) === false )
      page_error('A database error occurred');

    if ( count($data)==1)
      $folder_img = find_in_dir($data[0]["DIRNAME"],$_SESSION["opts"]["art_files"]);


    $info->add_item('Title', distinct_info('title',$sql_table, $newsql));
    $info->add_item('Year', distinct_info('year',$sql_table, $newsql));
    $info->add_item('Certificate',distinct_info('rating',$sql_table, $newsql));
    $info->add_item('Play Time' ,hhmmss($playtime));
    $menu->add_item('Play now'  , pl_link('sql','select * from $sql_table where '.substr($newsql,5)." order by title"));

    if (pl_enabled())
      $menu->add_item('Add to your playlist','add_playlist.php?sql='.rawurlencode('select * from $sql_table where '.substr($newsql,5)." order by title"),true);

    check_filters( array('title','year','rating','genre_name','actor_name','director_name'), $sql_table, $newsql, $menu);
  }

  // Is there a picture for us to display?
  if (! empty($folder_img) )
  {
    $info->display();
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="170px" align="center">
              <table width="100%"><tr><td height="10px"></td></tr><tr><td valign=top>
                <center>'.img_gen($folder_img,150,200).'</center>
              </td></tr></table></td>
              <td valign="top">';
              $menu->display(320);
    echo '    </td></td></table>';
  }
  else
  {
    $info->display();
    $menu->display();
  }

  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>'Turn Shuffle On', 'url'=>'video_selected.php?shuffle=on&name='.$name.'&type='.$type );
  else
    $buttons[] = array('text'=>'Turn Shuffle Off', 'url'=>'video_selected.php?shuffle=off&name='.$name.'&type='.$type );

  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
