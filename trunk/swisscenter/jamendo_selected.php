<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/resources/audio/jamendo.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $jamendo = new Jamendo();
  if (isset($_REQUEST["track"]))
  {
    $unit     = 'track';
    $fields   = 'track_id+track_name+track_image+track_duration+track_dates+album_id+album_name+album_image+album_duration+album_dates+artist_id+artist_name';
    $params   = '?track_id='.$_REQUEST["track"].'&album_imagesize=400';
    $items    = $jamendo->getQuery($fields, $unit, $params);
    $title    = $items[0]['track_name'].' - '.$items[0]['artist_name'];
    $tagline  = str('ONE_TRACK');
    $image    = (empty($items[0]['track_image']) ? $items[0]['album_image'] : $items[0]['track_image']);
    $duration = $items[0]['track_duration'];
  }
  elseif (isset($_REQUEST["album"]))
  {
    $unit     = 'track';
    $fields   = 'track_id+track_name+track_image+track_duration+track_dates+album_id+album_name+album_image+album_duration+album_dates+artist_id+artist_name';
    $params   = '?album_id='.$_REQUEST["album"].'&album_imagesize=400';
    $items    = $jamendo->getQuery($fields, $unit, $params);
    $title    = $items[0]['album_name'].' - '.$items[0]['artist_name'];
    $tagline  = str('MANY_TRACKS', count($items));
    $image    = $items[0]['album_image'];
    $duration = $items[0]['album_duration'];
  }
  elseif (isset($_REQUEST["artist"]))
  {
    $unit     = 'track';
    $fields   = 'track_id+track_name+track_image+track_duration+track_dates+album_id+album_name+album_image+album_duration+album_dates+artist_id+artist_name+artist_image';
    $params   = '?artist_id='.$_REQUEST["artist"].'&artist_imagesize=400';
    $items    = $jamendo->getQuery($fields, $unit, $params);
    $title    = $items[0]['artist_name'];
    $tagline  = str('MANY_TRACKS', count($items));
    $image    = $items[0]['artist_image'];
  }
  $num_rows   = count($items);
  $this_url   = url_remove_params(current_url(), array('shuffle'));
  $meta       = '<meta SYABAS-PLAYERMODE="music">';

send_to_log(2,'items',$items);

  $menu = new menu();
  $info = new infotab();

  // Display Information about current selection
  if ($num_rows == 1) $info->add_item( str('TRACK_NAME'), $items[0]['track_name']);
  $info->add_item( str('ALBUM'), $items[0]['album_name']);
  $info->add_item( str('ARTIST'), $items[0]['artist_name']);
  $info->add_item( str('GENRE'), $items[0]['album_genre']);
  $info->add_item( str('YEAR'), $items[0]['album_dates']['release']);
  $info->add_item( str('MUSIC_PLAY_TIME'), hhmmss($duration));

  // Output Title
  page_header( $title, $tagline, $meta );

  // Build menu of options
  $menu->add_item(str('PLAY_NOW').' ('.str('MANY_TRACKS',$num_rows).')', play_sql_list(MEDIA_TYPE_MUSIC,"select * from $sql_table $predicate order by album,lpad(disc,10,'0'),lpad(track,10,'0'),title") );

  // If only one track is selected, the user might want to expand their selection to the whole album
  if ($num_rows == 1)
    $menu->add_item( str('SELECT_ENTIRE_ALBUM'), url_set_params('jamendo_selected.php', array('album'=>$items[0]['album_id'], 'hist'=>PAGE_HISTORY_REPLACE)) );

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  echo '<table width="100%" cellpadding="0" cellspacing="0" border="0">
          <tr>';

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

  // Column 3: Details and menu
  echo '    <td>';
            $info->display();
            $menu->display(1, 480);
  echo '    </td>
          </tr>
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

  // Make sure the "back" button goes to the correct page:
  page_footer(page_hist_previous(), $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
