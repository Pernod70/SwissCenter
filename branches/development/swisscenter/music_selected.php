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
      $menu->add_item($menu_text, "music_search.php?sort=".$column,true);
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $sql_table = 'from mp3s media'.get_rating_join().' where 1=1 ';
  
  $menu      = new menu();
  $info      = new infotab();
  $name      = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $type      = un_magic_quote($_REQUEST["type"]);
  $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
  $post_sql  = $_SESSION["history"][count($_SESSION["history"])-1]["sql"];
  $predicate = $post_sql." and $type like '".db_escape_str(str_replace('_','\_',$name))."'";
  $playtime  = db_value("select sum(length) $sql_table $predicate");
  $num_rows  = db_value("select count($type) $sql_table $predicate");

  if (isset($_REQUEST["shuffle"]))
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];

  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
    $_SESSION["history"][] = array("url"=> str_replace('add=Y','add=N',url_add_param(current_url(),'p_del','Y')), "sql"=>$predicate);

  // Title
  if ($num_rows ==1)
    page_header( str('ONE_TRACK'), '', 'LOGO_MUSIC');
  else
    page_header( str('MANY_TRACKS',$num_rows), '', 'LOGO_MUSIC');

  // Display Information about current selection  
  distinct_info($info, str('TRACK_NAME') ,'title' ,$sql_table, $predicate);
  distinct_info($info, str('ALBUM')      ,'album' ,$sql_table, $predicate);
  distinct_info($info, str('ARTIST')     ,'artist',$sql_table, $predicate);
  distinct_info($info, str('GENRE')      ,'genre' ,$sql_table, $predicate);
  distinct_info($info, str('YEAR')       ,'year'  ,$sql_table, $predicate);
  $info->add_item( str('MUSIC_PLAY_TIME'),  hhmmss($playtime));
  
  // Build menu of options
  $menu->add_item(str('PLAY_NOW'),   pl_link('sql',"select * $sql_table $predicate order by album,lpad(track,10,'0'),title",'audio'));
  $menu->add_item(str('ADD_PLAYLIST'),'add_playlist.php?sql='.rawurlencode("select * $sql_table $predicate order by album,lpad(track,10,'0'),title"),true);

  // If only one track is selected, the user might want to expand their selection to the whole album
  if ($num_rows ==1)
    $menu->add_item( str('SELECT_ENTIRE_ALBUM'),'music_select_album.php?name='.rawurlencode(db_value("select album $sql_table $predicate")));

  check_filter( $menu, str('REFINE_ARTIST'), 'artist', $sql_table, $predicate );
  check_filter( $menu, str('REFINE_ALBUM'), 'album',  $sql_table, $predicate );
  check_filter( $menu, str('REFINE_TITLE'), 'title',  $sql_table, $predicate );
  check_filter( $menu, str('REFINE_GENRE'), 'genre',  $sql_table, $predicate );
  check_filter( $menu, str('REFINE_YEAR'), 'year',   $sql_table, $predicate );

  // Is there a picture for us to display?
  $folder_img = file_albumart( db_value("select concat(dirname,filename) $sql_table $predicate limit 0,1") );

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  if (! empty($folder_img) )
  {
    $info->display();
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="170px" align="center">
              <table width="100%"><tr><td height="10px"></td></tr><tr><td valign=top>
                <center>'.img_gen($folder_img,150,150).'</center>
              </td></tr></table></td>
              <td valign="top">';
              $menu->display(300);
    echo '    </td></td></table>';
  }
  else
  {
    $info->display();
    $menu->display();
  }

  // Display ABC buttons
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON', 'url'=>'music_selected.php?shuffle=on&name='.$name.'&type='.$type );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=>'music_selected.php?shuffle=off&name='.$name.'&type='.$type );

  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
