<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/musicip.php'));

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
  $meta       = '<meta SYABAS-PLAYERMODE="music">';

  // Output Title
  if ($num_rows ==1)
    page_header( str('ONE_TRACK'), '', $meta );
  else
    page_header( str('MANY_TRACKS',$num_rows), '', $meta );

  // Display Information about current selection
  $track_name  = search_distinct_info($info, str('TRACK_NAME') ,'title' ,$sql_table, $predicate);
  $album_name  = search_distinct_info($info, str('ALBUM')      ,'album' ,$sql_table, $predicate);
  $artist_name = search_distinct_info($info, str('ARTIST')     ,'artist',$sql_table, $predicate);
  search_distinct_info($info, str('COMPOSER')   ,'composer',$sql_table, $predicate);
  search_distinct_info($info, str('GENRE')      ,'genre' ,$sql_table, $predicate);
  search_distinct_info($info, str('YEAR')       ,'year'  ,$sql_table, $predicate);
  $info->add_item( str('MUSIC_PLAY_TIME'),  hhmmss($playtime));

  // Build menu of options
  $menu->add_item(str('PLAY_NOW'), play_sql_list(MEDIA_TYPE_MUSIC,"select * from $sql_table $predicate order by album,lpad(disc,10,'0'),lpad(track,10,'0'),title") );

  // If MusicIP support is enabled then add an extra option
  if ( musicip_available() && musicip_status($sql_table, $predicate) )
    $menu->add_item( str('MIP_MIXER'), url_add_params('mip_mixer.php', array('add'  => 'Y',
                                                                             'type' => $_REQUEST["type"],
                                                                             'name' => $_REQUEST["name"])) );
  // Or adds these tracks to their current playlist...
  $menu->add_item(str('ADD_PLAYLIST'),'add_playlist.php?sql='.rawurlencode("select * from $sql_table $predicate order by album,lpad(disc,10,'0'),lpad(track,10,'0'),title"),true);

  // If only one track is selected, the user might want to expand their selection to the whole album
  if ($num_rows ==1 && !empty($album_name))
    $menu->add_item( str('SELECT_ENTIRE_ALBUM'),'music_select_album.php?name='.rawurlencode($album_name));

  // Or refine the tracks further
  search_check_filter( $menu, str('REFINE_ARTIST'), 'artist', $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_COMPOSER'), 'composer', $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_ALBUM'),  'album',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_TITLE'),  'title',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_GENRE'),  'genre',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_YEAR'),   'year',   $sql_table, $predicate, $refine_url );

  // Add a menu option to lookup the artist/album in Wikipedia
  if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES')
  {
    $back_url = url_remove_params(current_url(), array('add','p_del'));
    if ( !empty($artist_name) )
      $menu->add_item( str("SEARCH_WIKIPEDIA"), lang_wikipedia_search( strip_title($artist_name), $back_url ), true);
    elseif ( !empty($album_name) )
      $menu->add_item( str("SEARCH_WIKIPEDIA"), lang_wikipedia_search( strip_title($album_name), $back_url ), true);
  }

  // Is there a picture for us to display? (only if selected media is in a single folder)
  if ( db_value("select count(distinct dirname) from $sql_table $predicate")==1 )
    $folder_img = file_albumart( db_value("select concat(dirname,filename) from $sql_table $predicate limit 0,1") );

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  // There may be 5 options, which is a real squeeeze on the page, so set the padding to a small value
  $menu->set_vertical_margins(6);

  if (! empty($folder_img) )
  {
    $info->display();
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(290).'" align="center">
              <table width="100%"><tr><td height="'.convert_y(10).'"></td></tr><tr><td valign=top>
                <center>'.img_gen($folder_img,250,300).'</center>
              </td></tr></table></td>
              <td valign="top">';
              $menu->display(1, 480);
    echo '    </td></td></table>';
  }
  else
  {
    $info->display();
    $menu->display();
  }

  // Display ABC buttons
  $buttons = array();
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_param($this_url,'shuffle','on') );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_param($this_url,'shuffle','off') );
  if (internet_available() && !empty($artist_name))
    $buttons[] = array('text'=>str('ARTIST_INFO'), 'url'=> url_add_params('music_info.php', array('artist'=>urlencode($artist_name),
                                                                                                  'album'=>urlencode($album_name),
                                                                                                  'track'=>urlencode($track_name))) );

  page_footer( url_add_params( search_picker_most_recent(), array("p_del"=>"y","del"=>"y") ), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
