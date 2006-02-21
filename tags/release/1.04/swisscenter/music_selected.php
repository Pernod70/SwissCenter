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

  function distinct_info ($name, $filter, $newsql, &$info)
  {
    if (db_value("select count(distinct $filter) from mp3s where ".substr($newsql,5)) == 1) 
    {
      $text = db_value("select $filter from mp3s where ".substr($newsql,5). ' limit 1,1');
      $info->add_item($name, $text);
    }    
  }

  // Function that checks to see if the supplied SQL contains a filter for the specified type.
  // If it doesn't, then we can output a menu item which allows the user to filter on that type.

  function check_filter ( $filter, $newsql, &$menu )
  {
    $num_rows  = db_value("select count(distinct $filter) from mp3s where ".substr($newsql,5));
    if ($num_rows>1)
    {
      switch ($filter)
      {
        case 'artist': $menu->add_item("Refine by Artist Name","music_search.php?sort=artist",true); break;
        case 'album' : $menu->add_item("Refine by Album Name","music_search.php?sort=album",true);   break;
        case 'year'  : $menu->add_item("Refine by Year","music_search.php?sort=year",true);          break;
        case 'title' : $menu->add_item("Refine by Track Name","music_search.php?sort=title",true);   break;
        case 'genre' : $menu->add_item("Refine by Genre Name","music_search.php?sort=genre",true);   break;
      }
    }
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.

  $name      = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $type      = un_magic_quote($_REQUEST["type"]);
  $title     = un_magic_quote($name);
  $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
  $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
  $newsql    = $post_sql." and $type like '".db_escape_str(str_replace('_','\_',$name))."'";
  $playtime  = db_value("select sum(length) from mp3s where ".substr($newsql,5));
  $num_rows  = db_value("select count($type) from mp3s where ".substr($newsql,5));
  
  if (isset($_REQUEST["shuffle"]))
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];

  $title = shorten($title,40);

  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
    $_SESSION["history"][] = array("url"=> str_replace('add=Y','add=N',url_add_param(current_url(),'p_del','Y')), "sql"=>$newsql);

  if ($num_rows == 1)
  {
    page_header('1 Track Found');
    // Single match, so get the details from the database and display them
    if ( ($data = db_toarray("select * from mp3s where ".substr($newsql,5))) === false)
      page_error('A database error occurred');

    $info->add_item('Track Name', $data[0]["TITLE"]);
    $info->add_item('Album', $data[0]["ALBUM"]);
    $info->add_item('Artist', $data[0]["ARTIST"]);
    $info->add_item('Genre', $data[0]["GENRE"]);
    $info->add_item('Year', $data[0]["YEAR"]);
    $info->add_item('Play Time', hhmmss($playtime));
    $menu->add_item("Play now",pl_link('file',$data[0]["DIRNAME"].$data[0]["FILENAME"]));

    $folder_img = ifile_in_dir($data[0]["DIRNAME"],$_SESSION["opts"]["art_files"]);

    if (pl_enabled())
      $menu->add_item("Add to your playlist",'add_playlist.php?sql='.rawurlencode('select * from mp3s where file_id='.$data[0]["FILE_ID"]),true);

    $menu->add_item("Select Entire Album",'music_select_album.php?name='.rawurlencode($data[0]["ALBUM"]));
  }
  else
  {
    // More than one track matches, so output filter details and menu options to add new filters
    if ($num_rows ==1)
      page_header($num_rows.' Track Found');
    else
      page_header($num_rows.' Tracks Found');

    if ( ($data = db_toarray("select dirname from mp3s where ".substr($newsql,5)." group by dirname")) === false )
      page_error('A database error occurred');

    if ( count($data)==1)
      $folder_img = ifile_in_dir($data[0]["DIRNAME"],$_SESSION["opts"]["art_files"]);

    distinct_info('Track Name' ,'title' ,$newsql, $info);
    distinct_info('Album'      ,'album' ,$newsql, $info);
    distinct_info('Artist'     ,'artist',$newsql, $info);
    distinct_info('Genre'      ,'genre' ,$newsql, $info);
    distinct_info('Year'       ,'year'  ,$newsql, $info);
    $info->add_item('Play Time',  hhmmss($playtime));
    $menu->add_item('Play now',   pl_link('sql','select * from mp3s where '.substr($newsql,5)." order by album,track,title"));

    if (pl_enabled())
      $menu->add_item('Add to your playlist','add_playlist.php?sql='.rawurlencode('select * from mp3s where '.substr($newsql,5)." order by album,track,title"),true);

    check_filter( 'artist', $newsql, $menu);
    check_filter( 'album', $newsql, $menu);
    check_filter( 'title', $newsql, $menu);
    check_filter( 'genre', $newsql, $menu);
    check_filter( 'year', $newsql, $menu);
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
              $menu->display(320,28);
    echo '    </td></td></table>';
  }
  else
  {
    $info->display();
    $menu->display();
  }

  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>'Turn Shuffle On', 'url'=>'music_selected.php?shuffle=on&name='.$name.'&type='.$type );
  else
    $buttons[] = array('text'=>'Turn Shuffle Off', 'url'=>'music_selected.php?shuffle=off&name='.$name.'&type='.$type );

  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
