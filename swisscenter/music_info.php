<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/audio/theaudiodb.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  // Get artist, album, and track details
  $artist  = isset($_REQUEST["artist"]) ? rawurldecode($_REQUEST["artist"]) : false;
  $album   = isset($_REQUEST["album"]) ? rawurldecode($_REQUEST["album"]) : false;
  $track   = isset($_REQUEST["track"]) ? rawurldecode($_REQUEST["track"]) : false;
  $cover   = isset($_REQUEST["cover"]) ? rawurldecode($_REQUEST["cover"]) : 'front';
  $display = isset($_REQUEST["display"]) ? rawurldecode($_REQUEST["display"]) : 'artist';
  $current = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : 'ALL';
  $tabs    = array('ALL', 'ALBUMS', 'SINGLES');

  // Get data from TheAudioDB
  if (!empty($artist))
  {
    $data   = tadb_artist_getInfo($artist);
    $image  = isset($data['strArtistThumb'])  ? $data['strArtistThumb']  : null;
    $logo   = isset($data['strArtistLogo'])   ? $data['strArtistLogo']   : null;
    $fanart = isset($data['strArtistFanart']) ? $data['strArtistFanart'] : null;
    $text   = isset($data['strBiography'])    ? font_tags(FONTSIZE_BODY).$data['strBiography'].'</font>' : null;
  }
  switch ($display)
  {
    case 'track':
      $data  = tadb_track_getInfo($data['strArtist'], $album, $track);
      $image = isset($data['strTrackThumb'])  ? $data['strTrackThumb']  : null;
      $text  = isset($data['strDescription']) ? font_tags(FONTSIZE_BODY).$data['strDescription'].'</font>' : null;
      break;
    case 'album':
      $data  = tadb_album_getInfo($data['strArtist'], $album);
      switch ($cover)
      {
        case 'back':
          $image = $data['strAlbumThumbBack'];
          break;
        case 'cdart':
          $image = $data['strAlbumCDart'];
          break;
        default:
          $image = $data['strAlbumThumb'];
          break;
      }
      $text  = isset($data['strDescription']) ? font_tags(FONTSIZE_BODY).$data['strDescription'].'</font>' : null;
      break;
    case 'review':
      $data  = tadb_album_getInfo($data['strArtist'], $album);
      $image = isset($data['strAlbumThumb']) ? $data['strAlbumThumb']  : null;
      $text  = isset($data['strReview'])     ? font_tags(FONTSIZE_BODY).$data['strReview'].'</font>' : null;
      break;
    case 'discog':
      $data  = tadb_artist_albums($data['idArtist']);
      $text = '<table width="100%">';
      foreach ($data as $item)
      {
        if ($current == 'ALL' || ($current == 'ALBUMS' && $item['strReleaseFormat'] !== 'Single') || ($current == 'SINGLES' && $item['strReleaseFormat'] == 'Single'))
        {
          if (db_value("select 'YES' from mp3s where (artist='".db_escape_str($artist)."' or band='".db_escape_str($artist)."') and album like '".db_escape_str($item['strAlbum'])."' group by album") == 'YES')
            $text .= '<tr><td><a href="music_selected.php?type=album&name='.rawurlencode($item['strAlbum']).'">'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').$item['strAlbum'].'</font></a></td><td align="right">'.font_tags(FONTSIZE_BODY).$item['intYearReleased'].'</font></td></tr>';
          else
            $text .= '<tr><td>'.font_tags(FONTSIZE_BODY).$item['strAlbum'].'</font></td><td align="right">'.font_tags(FONTSIZE_BODY).$item['intYearReleased'].'</font></td></tr>';
        }
      }
      $text .= '</table>';
      $tab_strip = '';
      foreach ($tabs as $key=>$tab)
        $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="'.url_add_params('music_info.php', array('display'=>'discog', 'artist'=>urlencode($artist), 'album'=>urlencode($album), 'track'=>urlencode($track), 'tab'=>$tab, 'hist'=>PAGE_HISTORY_REPLACE)).'">'.
                      ($tab == $current ? font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(FONTSIZE_BODY).str($tab)).'</font></a>';
      break;
  }

  // Set default text and image if no details available
  if (empty($text))
    $text = font_tags(FONTSIZE_BODY).str('TADB_NO_DETAILS').'</font>';
  if (empty($image))
    $image = style_img('NOW_NO_ALBUMART',true);

  // Tabs for cover art
  $tabs = array();
  if (!empty($data['strAlbumThumb'])) array_push($tabs, 'front');
  if (!empty($data['strAlbumThumbBack'])) array_push($tabs, 'back');
  if (!empty($data['strAlbumCDart'])) array_push($tabs, 'cdart');
  $tab_strip = '';
  foreach ($tabs as $key=>$tab)
    $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="'.url_add_params(current_url(), array('cover'=>$tab, 'hist'=>PAGE_HISTORY_REPLACE)).'">'.
                  ($tab == $cover ? font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(FONTSIZE_BODY).str($tab)).'</font></a>';

  page_header( $artist, '', '', 1, false, '', $fanart, $logo, 'PAGE_TEXT_BACKGROUND' );

  if ($display == 'discog')
    echo '<center>'.font_tags(FONTSIZE_BODY).$tab_strip.'</center>';

  // Column 1: Image
  echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
          <tr>';
  echo '    <td width="'.convert_x(280).'" valign="middle">
              <table '.style_background('PAGE_TEXT_BACKGROUND').' cellpadding="10" cellspacing="0" border="0">
                <tr>
                  <td>'.img_gen($image,280,450,false,false,false,array(),false).'<br><center>'.$tab_strip.'</center></td>
                </tr>
              </table>
            </td>';

  // Column 2: Gap
  echo '    <td width="'.convert_x(10).'"></td>';

  // Column 3: Details
  echo '    <td valign="top">';

  if (is_pc())
    echo '<div style="height:'.convert_y(650).'; overflow:scroll;">';

  echo '      <table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0">
                <tr>
                  <td>';

  // Display info
  echo '<p>'.$text;

  echo '          </td>
                </tr>
              </table>';

  if (is_pc())
    echo '</div>';

  echo '    </td>
          </tr>
        </table>';

  // Display ABC buttons
  $buttons = array();
  if (!empty($artist) && $display !== 'discog')
    $buttons[] = array('text'=>str('DISCOGRAPHY'), 'url'=> url_add_params('music_info.php', array('display'=>'discog', 'artist'=>urlencode($artist), 'album'=>urlencode($album), 'track'=>urlencode($track), 'hist'=>PAGE_HISTORY_REPLACE)) );
  if (!empty($artist) && $display !== 'artist')
    $buttons[] = array('text'=>str('ARTIST_INFO'), 'url'=> url_add_params('music_info.php', array('display'=>'artist', 'artist'=>urlencode($artist), 'album'=>urlencode($album), 'track'=>urlencode($track), 'hist'=>PAGE_HISTORY_REPLACE)) );
  if (!empty($album) && $display !== 'album')
    $buttons[] = array('text'=>str('ALBUM_INFO'), 'url'=> url_add_params('music_info.php', array('display'=>'album', 'artist'=>urlencode($artist), 'album'=>urlencode($album), 'track'=>urlencode($track), 'hist'=>PAGE_HISTORY_REPLACE)) );
  if (!empty($track) && $display !== 'track')
    $buttons[] = array('text'=>str('TRACK_INFO'), 'url'=> url_add_params('music_info.php', array('display'=>'track', 'artist'=>urlencode($artist), 'album'=>urlencode($album), 'track'=>urlencode($track), 'hist'=>PAGE_HISTORY_REPLACE)) );
  if (!empty($album) && $display !== 'review')
    $buttons[] = array('text'=>str('ALBUM_REVIEW'), 'url'=> url_add_params('music_info.php', array('display'=>'review', 'artist'=>urlencode($artist), 'album'=>urlencode($album), 'track'=>urlencode($track), 'hist'=>PAGE_HISTORY_REPLACE)) );

  page_footer( page_hist_previous(), $buttons, 0, true, 'PAGE_TEXT_BACKGROUND');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
