<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/musicip.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $mood     = isset($_REQUEST["mood"]) ? $_REQUEST["mood"] : '';
  $playlist = isset($_REQUEST["playlist"]) ? $_REQUEST["playlist"] : '';
  $recipe   = isset($_REQUEST["recipe"]) ? $_REQUEST["recipe"] : '';

  $menu       = new menu();
  $info       = new infotab();
  $sql_table  = 'media_audio media'.get_rating_join().' where 1=1 ';
  $predicate  = page_hist_current('sql');
  $meta       = '<meta SYABAS-PLAYERMODE="music">';

  // Display Information about current selection
  $track_name  = search_distinct_info($info, str('TRACK_NAME') ,'title' ,$sql_table, $predicate);
  $album_name  = search_distinct_info($info, str('ALBUM')      ,'album' ,$sql_table, $predicate);
  $artist_name = search_distinct_info($info, str('ARTIST')     ,'artist',$sql_table, $predicate);
  $genre_name  = search_distinct_info($info, str('GENRE')      ,'genre' ,$sql_table, $predicate);

  // Form API mix parameters
  $params = array();
  if ( !empty($mood) )     $params = array_merge($params, array( 'mood'     => urlencode($mood) ));
  if ( !empty($playlist) ) $params = array_merge($params, array( 'playlist' => urlencode($playlist) ));
  if ( !empty($recipe) )   $params = array_merge($params, array( 'recipe'   => urlencode($recipe) ));

  // Build menu of options
  $menu->add_item(str('MIP_MIX_SONG'), musicip_mix_link( $sql_table, $predicate, $params) );
  $menu->add_item(str('MIP_MOODS').(empty($mood) ? '' : ': '.$mood), 'mip_moods.php' );
  $menu->add_item(str('MIP_PLAYLISTS').(empty($playlist) ? '' : ': '.$playlist), 'mip_playlists.php' );
  $menu->add_item(str('MIP_RECIPES').(empty($recipe) ? '' : ': '.$recipe), 'mip_recipes.php' );

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  // Page headings
  page_header(str('MIP_MIXER'), '', $meta);

  $info->display();

  // Output the appropriate menu based on earlier choices and ensure that the
  // back button on the remote takes you to the page you just came from.

  echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
        <tr><td valign=top width="'.convert_x(290).'" align="center">
            <table width="100%"><tr><td height="'.convert_y(10).'"></td></tr><tr><td valign=top>
              <center>'.img_gen(style_img('MUSICIP',true,false),250,300).'</center>
            </td></tr></table></td>
            <td valign="top">';
            $menu->display(1, 480);
  echo '    </td>
        <tr><td colspan=2>'.str('MIP_MOODS_INFO').'<br><br>'.str('MIP_RECIPES_INFO').'</td></tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
