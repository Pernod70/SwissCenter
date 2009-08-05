<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/ext/lastfm/datafeeds.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  // Where to return to?
  $history  = search_hist_pop();
  $back_url = url_add_param($history["url"], 'add','Y');

  // Get artist, album, and track details
  $artist = $_REQUEST["artist"];
  $album  = $_REQUEST["album"];
  $track  = $_REQUEST["track"];

  // Get artist info
  if (!empty($artist) && ($artist_feed = lastfm_artist_getInfo($artist)))
  {
    $image = array_pop($artist_feed["artist"]["image"]);
    while (empty($image["#text"]) && count($artist_feed["artist"]["image"])>0)
      $image = array_pop($artist_feed["artist"]["image"]);
  }

  // Get album info
  if (!empty($artist) && !empty($album))
  {
    $album_feed = lastfm_album_getInfo($artist, $album);
  }

  // Get track info
  if (!empty($artist) && !empty($track))
  {
    $track_feed = lastfm_track_getInfo($artist, $track);
  }

  page_header( $artist );

  echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
          <tr>';

  // Display image
  if (isset($image["#text"]) && !empty($image["#text"]))
  {
    echo '  <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen($image["#text"],280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>';
  }
  echo '    <td valign="top">';

  // Display artist info
  if (isset($artist_feed["artist"]["bio"]["summary"]))
    echo '<p>'.font_tags(30).utf8_decode($artist_feed["artist"]["bio"]["summary"]);

  // Display album info
  if (isset($album_feed["album"]["wiki"]["summary"]))
    echo '<p>'.font_tags(30).utf8_decode($album_feed["album"]["wiki"]["summary"]);

  // Display track info
  if (isset($track_feed["track"]["wiki"]["summary"]))
    echo '<p>'.font_tags(30).utf8_decode($track_feed["track"]["wiki"]["summary"]);

  $menu = new menu();
  $menu->add_item(str('RETURN_TO_SELECTION'), $back_url);
  $menu->display(1, 400);

  echo '    </td>
          </tr>
        </table>';

  page_footer( $back_url );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
