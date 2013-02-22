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
  require_once( realpath(dirname(__FILE__).'/resources/audio/theaudiodb.php'));

  /**
   * Displays the artist/album/track details.
   *
   * @param string $artist
   * @param string $album
   * @param string $track
   * @param integer $num_menu_items
   */
  function music_details($artist, $album, $track, $num_menu_items)
  {
    // Get data from TheAudioDB
    if (!empty($artist))
    {
      $data   = tadb_artist_getInfo($artist);
      $image  = isset($data['strArtistThumb'])  ? $data['strArtistThumb']  : null;
      $logo   = isset($data['strArtistLogo'])   ? $data['strArtistLogo']   : null;
      $fanart = isset($data['strArtistFanart']) ? $data['strArtistFanart'] : null;
      $text   = isset($data['strBiographyEN'])  ? $data['strBiographyEN']  : null;
    }
    if (!empty($track) && !empty($album) && !empty($artist))
    {
      $data   = tadb_track_getInfo($data['strArtist'], $album, $track);
      $image  = isset($data['strTrackThumb'])    ? $data['strTrackThumb']    : null;
      $text   = isset($data['strDescriptionEN']) ? $data['strDescriptionEN'] : null;
    }
    elseif (!empty($artist) && !empty($album))
    {
      $data   = tadb_album_getInfo($data['strArtist'], $album);
      $image  = isset($data['strAlbumThumb'])    ? $data['strAlbumThumb']    : null;
      $text   = isset($data['strDescriptionEN']) ? $data['strDescriptionEN'] : null;
    }
    if (empty($text))
      $text = str('TADB_NO_DETAILS');

    // Display Details
    $maxlen = $_SESSION["device"]["browser_x_res"] * 0.625 * max(9-$num_menu_items,1);
    $short = shorten($text,$maxlen,1,FONTSIZE_BODY);
    if (mb_strlen($short) != mb_strlen($text))
      $short = $short.' <a href="/music_info.php?artist='.rawurldecode($artist).'&album='.rawurldecode($album).'&track='.rawurldecode($track).'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('MORE')).'</a>';

    echo font_tags(FONTSIZE_BODY).$short.'</font>';
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $menu       = new menu();
  $info       = new infotab();
  $type       = un_magic_quote($_REQUEST["type"]);
  $name       = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $sql_table  = 'mp3s media'.get_rating_join().viewed_join(MEDIA_TYPE_MUSIC).' where 1=1 ';
  $predicate  = search_process_passed_params();
  $playtime   = db_value("select sum(length) from $sql_table $predicate");
  $num_rows   = db_value("select count(*) from $sql_table $predicate");
  $refine_url = 'music_search.php';
  $this_url   = url_remove_params(current_url(), array('shuffle'));
  $meta       = '<meta SYABAS-PLAYERMODE="music">';

  // Clean the current url in the history
  page_hist_current_update($this_url, $predicate);

  // Display Information about current selection
  $track_name  = search_distinct_info($info, str('TRACK_NAME') ,'title' ,$sql_table, $predicate);
  $album_name  = search_distinct_info($info, str('ALBUM')      ,'album' ,$sql_table, $predicate);
  $artist_name = search_distinct_info($info, str('ARTIST')     ,'artist',$sql_table, $predicate);
  if (empty($artist_name))
    $artist_name = search_distinct_info($info, str('ARTIST')   ,'band',$sql_table, $predicate);
  search_distinct_info($info, str('GENRE')      ,'genre' ,$sql_table, $predicate);
  search_distinct_info($info, str('YEAR')       ,'year'  ,$sql_table, $predicate);
  $info->add_item( str('MUSIC_PLAY_TIME'),  hhmmss($playtime));

  // Get images from TheAudioDB
  $data = tadb_artist_getInfo($artist_name);
  $image  = isset($data['strArtistThumb'])  ? $data['strArtistThumb']  : null;
  $logo   = isset($data['strArtistLogo'])   ? $data['strArtistLogo']   : null;
  $fanart = isset($data['strArtistFanart']) ? $data['strArtistFanart'] : null;

  // Check for videos at TheAudioDB
  $videos = tadb_artist_videos($artist_name, $album_name, $track_name);

  // Output Title
  if ($num_rows == 1)
    page_header( str('ONE_TRACK'), '', $meta, 1, false, '', $fanart, $logo, 'PAGE_TEXT_BACKGROUND' );
  else
    page_header( str('MANY_TRACKS',$num_rows), '', $meta, 1, false, '', $fanart, $logo, 'PAGE_TEXT_BACKGROUND' );

  // Build menu of options
  $menu->add_item(str('PLAY_NOW').' ('.str('MANY_TRACKS',$num_rows).')', play_sql_list(MEDIA_TYPE_MUSIC,"select * from $sql_table $predicate order by album,lpad(disc,10,'0'),lpad(track,10,'0'),title") );

  // If MusicIP support is enabled then add an extra option
  if ( musicip_available() && musicip_status($sql_table, $predicate) )
    $menu->add_item( str('MIP_MIXER'), 'mip_mixer.php' );
  // Or adds these tracks to their current playlist...
  $menu->add_item(str('ADD_PLAYLIST'),'add_playlist.php?sql='.rawurlencode("select * from $sql_table $predicate order by album,lpad(disc,10,'0'),lpad(track,10,'0'),title"),true);

  // If only one track is selected, the user might want to expand their selection to the whole album
  if ($num_rows ==1 && !empty($album_name))
    $menu->add_item( str('SELECT_ENTIRE_ALBUM'),'music_select_album.php?name='.rawurlencode($album_name));

  // Or refine the tracks further
  search_check_filter( $menu, str('REFINE_ARTIST'), 'sort_artist', $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_COMPOSER'), 'composer',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_ALBUM'),  'sort_album',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_TITLE'),  'sort_title',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_GENRE'),  'genre',  $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_MOOD'),   'mood',   $sql_table, $predicate, $refine_url );
  search_check_filter( $menu, str('REFINE_YEAR'),   'year',   $sql_table, $predicate, $refine_url );

  // Add a menu option to lookup the artist/album in Wikipedia
  if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES')
  {
    if ( !empty($artist_name) )
      $menu->add_item( str("SEARCH_WIKIPEDIA"), lang_wikipedia_search( strip_title($artist_name), url_add_param($this_url, 'hist', PAGE_HISTORY_DELETE) ), true);
    elseif ( !empty($album_name) )
      $menu->add_item( str("SEARCH_WIKIPEDIA"), lang_wikipedia_search( strip_title($album_name), url_add_param($this_url, 'hist', PAGE_HISTORY_DELETE) ), true);
  }

  // Browse by genre, so check for appropriate image to display
  if ( $type == 'genre' )
  {
    $genre_img = SC_LOCATION.'images/genres/'.$name.'.png';
    if ( file_exists($genre_img) )
      $image = $genre_img;
  }
  // Is there a picture for us to display? (only if selected media is in a single folder)
  elseif ( db_value("select count(distinct dirname) from $sql_table $predicate")==1 )
  {
    $image = file_albumart( db_value("select concat(dirname,filename) from $sql_table $predicate limit 0,1") );
  }

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  // There may be 5 options, which is a real squeeeze on the page, so set the padding to a small value
//  $menu->set_vertical_margins(6);

  echo '<table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>';

  // Is there a picture for us to display?
  if ( !empty($image) )
  {
    // Column 1: Image
    echo '    <td width="'.convert_x(280).'" valign="middle">
                <table '.style_background('PAGE_TEXT_BACKGROUND').' cellpadding="10" cellspacing="0" border="0">
                  <tr>
                    <td>'.img_gen($image,280,450,false,false,false,array(),false).'</td>
                  </tr>
                </table>
              </td>';
    // Column 2: Gap
    echo '    <td width="'.convert_x(10).'"></td>';
  }

  // Is a single artist selected?
  if (!empty($artist_name))
  {
    // Column 3: Details and menu
    echo '    <td valign="top">
                <table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0">
                  <tr>
                    <td>';
                    // Music details
                    music_details($artist_name, $album_name, $track_name, $menu->num_items());
    echo '          </td>
                  </tr>
                </table>';
                $menu->display(1, 480);
    echo '    </td>';
  }
  else
  {
    echo '    <td>';
              $info->display();
              $menu->display(1, 480);
    echo '    </td>';
  }

  echo '  </tr>
        </table>';

  // Display ABC buttons
  $buttons = array();
  if ($num_rows > 1)
  {
    if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
      $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_params($this_url, array('shuffle'=>'on', 'hist'=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_params($this_url, array('shuffle'=>'off', 'hist'=>PAGE_HISTORY_REPLACE)) );
  }

  if (internet_available())
  {
    if (!empty($artist_name))
      $buttons[] = array('text'=>str('MUSIC_INFO'), 'url'=> url_add_params('music_info.php', array('artist'=>urlencode($artist_name), 'album'=>urlencode($album_name), 'track'=>urlencode($track_name))) );
    if (!empty($track_name))
      $buttons[] = array('text'=>str('LYRICS'), 'url'=> url_add_params('music_lyrics.php', array('artist'=>urlencode($artist_name), 'track'=>urlencode($track_name))) );
    if (!empty($videos) && count($videos) !== 0)
      $buttons[3] = array('text'=>str('MUSIC_VIDEOS'), 'url'=> url_add_params('music_videos.php', array('artist'=>urlencode($artist_name), 'album'=>urlencode($album_name), 'track'=>urlencode($track_name))) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer(page_hist_previous(), $buttons, 0, true, 'PAGE_TEXT_BACKGROUND');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
