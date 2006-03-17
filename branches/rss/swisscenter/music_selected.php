<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  
  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************
  
  $menu       = new menu();
  $info       = new infotab();
  $sql_table  = 'mp3s media'.get_rating_join().' where 1=1 ';
  $predicate  = search_process_passed_params();
  $playtime   = db_value("select sum(length) from $sql_table $predicate");
  $num_rows   = db_value("select count(*) from $sql_table $predicate");
  $refine_url = 'music_search.php';
  $this_url   = url_set_param(current_url(),'add','N');

  // Output Title
  if ($num_rows ==1)
    page_header( str('ONE_TRACK'), '');
  else
    page_header( str('MANY_TRACKS',$num_rows), '');

  // Display Information about current selection  
  search_distinct_info($info, str('TRACK_NAME') ,'title' ,$sql_table, $predicate);
  $album_name  = search_distinct_info($info, str('ALBUM')      ,'album' ,$sql_table, $predicate);
  $artist_name = search_distinct_info($info, str('ARTIST')     ,'artist',$sql_table, $predicate);
  search_distinct_info($info, str('GENRE')      ,'genre' ,$sql_table, $predicate);
  search_distinct_info($info, str('YEAR')       ,'year'  ,$sql_table, $predicate);
  $info->add_item( str('MUSIC_PLAY_TIME'),  hhmmss($playtime));
  
  // Build menu of options
  $menu->add_item(str('PLAY_NOW'),   play_sql_list(MEDIA_TYPE_MUSIC,"select * from $sql_table $predicate order by album,lpad(track,10,'0'),title") );
  $menu->add_item(str('ADD_PLAYLIST'),'add_playlist.php?sql='.rawurlencode("select * from $sql_table $predicate order by album,lpad(track,10,'0'),title"),true);

  // If only one track is selected, the user might want to expand their selection to the whole album
  if ($num_rows ==1)
    $menu->add_item( str('SELECT_ENTIRE_ALBUM'),'music_select_album.php?name='.rawurlencode(db_value("select album from $sql_table $predicate")));

  search_check_filter( $menu, str('REFINE_ARTIST'), 'artist', $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_ALBUM'),  'album',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_TITLE'),  'title',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_GENRE'),  'genre',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_YEAR'),   'year',   $sql_table, $predicate, $refine_url );

  if ( !empty($artist_name) )
    $menu->add_item( str("SEARCH_WIKIPEDIA"), lang_wikipedia_search( strip_title($artist_name)), true);
  elseif ( !empty($album_name) )
    $menu->add_item( str("SEARCH_WIKIPEDIA"), lang_wikipedia_search( strip_title($album_name)), true);
  
  // Is there a picture for us to display?
  $folder_img = file_albumart( db_value("select concat(dirname,filename) from $sql_table $predicate limit 0,1") );

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  if (! empty($folder_img) )
  {
    $info->display();
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(290).'" align="center">
              <table width="100%"><tr><td height="'.convert_y(10).'"></td></tr><tr><td valign=top>
                <center>'.img_gen($folder_img,250,300).'</center>
              </td></tr></table></td>
              <td valign="top">';
              $menu->display(480);
    echo '    </td></td></table>';
  }
  else
  {
    $info->display();
    $menu->display();
  }

  // Display ABC buttons
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_param($this_url,'shuffle','on') );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_param($this_url,'shuffle','off') );

  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
