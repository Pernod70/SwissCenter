<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));

class lastfmapi {
  var $GET = 'http://ws.audioscrobbler.com/2.0/?format=json&';
  var $service = 'lastfm';
  var $api_key = 'df621f9291ce837cf27b27e4172e0698';

  var $req;
  var $response;
  var $response_code;
  var $cache_table = null;
  var $cache_expire = null;

  /*
   * When your database cache table hits this many rows, a cleanup
   * will occur to get rid of all of the old rows and cleanup the
   * garbage in the table.  For most personal apps, 1000 rows should
   * be more than enough.  If your site gets hit by a lot of traffic
   * or you have a lot of disk space to spare, bump this number up.
   * You should try to set it high enough that the cleanup only
   * happens every once in a while, so this will depend on the growth
   * of your table.
   */
  var $max_cache_rows = 500;

  function lastfmapi ()
  {
    $this->enableCache(3600);
  }

  /**
   * Enable caching to the database
   *
   * @param unknown_type $cache_expire
   * @param unknown_type $table
   */
  function enableCache($cache_expire = 600, $table = 'cache_api_request')
  {
    if (db_value("SELECT COUNT(*) FROM $table WHERE service = '".$this->service."'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $table WHERE service = '".$this->service."' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
    $this->cache_table = $table;
    $this->cache_expire = $cache_expire;
  }

  function getCached ($request)
  {
    //Checks the database for a cached result to the request.
    //If there is no cache result, it returns a value of false. If it finds one,
    //it returns the unparsed XML.
    $reqhash = md5(serialize($request));

    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result)) {
      return json_decode($result, true);
    }
    return false;
  }

  function cache ($request, $response)
  {
    //Caches the unparsed XML of a request.
    $reqhash = md5(serialize($request));

    if (db_value("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'")) {
      db_sqlcommand( "UPDATE ".$this->cache_table." SET response = '".db_escape_str($response)."', expiration = '".strftime("%Y-%m-%d %H:%M:%S")."' WHERE request = '$reqhash'");
    } else {
      db_sqlcommand( "INSERT INTO ".$this->cache_table." (request, service, response, expiration) VALUES ('$reqhash', '$this->service', '".db_escape_str($response)."', '".strftime("%Y-%m-%d %H:%M:%S")."')");
    }

    return false;
  }

  function request ($command, $args = array())
  {
    //Sends a request to LastFM
    $url = url_add_params($this->GET.$command, $args);
    $url = url_add_param($url, 'api_key', $this->api_key);
    send_to_log(6,'LastFM API request',$url);

    if (!($this->response = $this->getCached($url)) ) {
      //Send Requests
      if ($response = file_get_contents($url)) {
        $this->response = json_decode($response, true);
        if (isset($this->response["error"])) {
          $this->response_code = $this->response["error"];
          send_to_log(2,'Error returned by LastFM API', $this->response["message"]);
          return false;
        }
        else {
          $this->cache($url, $response);
        }
      } else {
        send_to_log(2,'There has been a problem sending your command to the server.');
        return false;
      }
    }
    return true;
  }

  /**
   * Return requested feed.
   *
   * @param string $feed
   * @return array
   */
  function getFeed ($feed)
  {
    if ($this->request($feed))
      return $this->response;
    else
      return false;
  }
}

function tagcloud( $tags, $linkto_url, $colours, $sizes )
{
  if (!is_array($colours))
    $colours = explode(',',$colours);
  if (!is_array($sizes))
    $sizes = explode(',',$sizes);

  // get the largest and smallest array values
  $max_qty = max(array_values($tags));
  $min_qty = min(array_values($tags));

  // find the range of values (detect for divide by zero)
  $spread = $max_qty - $min_qty;
  if ($spread == 0)
    $spread = 1;

  // determine the font-size increment
  $col_step = (count($colours)-1)/($spread);
  $size_step = (count($sizes)-1)/($spread);

  // loop through our tag array
  foreach ($tags as $tag => $value)
  {
    $col_idx = (int)(($value - $min_qty) * $col_step);
    $size_idx = (int)(($value - $min_qty) * $size_step);

    echo '<a href="'.str_replace('###',urlencode($tag),$linkto_url).'">'.
         '<font size="'.$sizes[$size_idx].'" color="'.$colours[$col_idx].'">'.
        $tag.
        '</font></a> &nbsp; ';
  }
}

function tagcloud_test()
{
  $lastfm = new lastfmapi();
  $lastfm->cache_expire = 86400;
  $tags = $lastfm->getFeed('method=tag.getTopTags');

  // Overall TopTags
  $cloud = array();
  foreach ($tags["toptags"]["tag"] as $tag)
    $cloud[$tag["name"]] = $tag["count"];

  // Generate a tag cloud from the values in the XML document for the top 50 tags.
  $toptags = array_slice($cloud,0,50);

  $colour_list = '#bbbbbb,#999999,#666666,#333333,#000000';
  $size_list   = '3,4,5,6';

  ksort($toptags);
  tagcloud( $toptags, 'http://www.last.fm/tag/###', $colour_list, $size_list);
}

/**
 * Fetches the top global tags on Last.fm, sorted by popularity (number of times used)
 *
 * @return array
 */
function lastfm_toptags ()
{
  $lastfm = new lastfmapi();
  $lastfm->cache_expire = 86400;

  // Download the latest "Top Tags" from audioscrobbler.com every 24 hours
  if ( $tags = $lastfm->getFeed('method=tag.getTopTags') )
  {
    db_sqlcommand("delete from lastfm_tags");
    foreach ($tags["toptags"]["tag"] as $tag)
      db_insert_row('lastfm_tags', array( 'tag'   => $tag["name"]
                                        , 'count' => $tag["count"]
                                        , 'url'   => $tag["url"]
                                        ) );
  }
  return $tags;
}

/**
 * Get the metadata for an album on Last.fm using the album name or a musicbrainz id. See playlist.fetch on how to get the album playlist.
 *
 * @param string $artist
 * @param string $album
 * @return array
 */
function lastfm_album_getInfo($artist, $album)
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=album.getInfo&artist='.urlencode(utf8_encode($artist)).'&album='.urlencode(utf8_encode($album)));
}

/**
 * Get Images for this artist in a variety of sizes.
 *
 * @param string $artist
 * @param integer $page
 * @param integer $limit
 * @return array
 */
function lastfm_artist_getImages($artist, $page=1, $limit=50)
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=artist.getImages&artist='.urlencode(utf8_encode($artist)).'&page='.$page.'&limit='.$limit);
}

/**
 * Get the metadata for an artist on Last.fm. Includes biography.
 *
 * @param string $artist
 * @return array
 */
function lastfm_artist_getInfo($artist)
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=artist.getInfo&artist='.urlencode(utf8_encode($artist)));
}

/**
 * Get the metadata for a track on Last.fm using the artist/track name or a musicbrainz id.
 *
 * @param string $artist
 * @param string $track
 * @return array
 */
function lastfm_track_getInfo($artist, $track)
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=track.getInfo&artist='.urlencode(utf8_encode($artist)).'&track='.urlencode(utf8_encode($track)));
}

/**
 * Used to add a track-play to a user's profile.
 *
 * @param string $artist
 * @param string $album
 * @param string $track
 * @param integer $duration
 * @param integer $trackNumber
 * @param integer $timestamp
 * @return array
 */
function lastfm_track_scrobble($artist, $album, $track, $duration, $trackNumber, $timestamp)
{
  $lastfm = new lastfmapi();
  return $lastfm->postFeed('method=track.scrobble&track[0]='.urlencode(utf8_encode($track)).
                                            '&timestamp[0]='.$timestamp.
                                               '&artist[0]='.urlencode(utf8_encode($artist)).
                                                '&album[0]='.urlencode(utf8_encode($album)).
                                          '&trackNumber[0]='.$trackNumber.
                                             '&duration[0]='.$duration);
}

/**
 * Used to notify Last.fm that a user has started listening to a track.
 *
 * @param string $artist
 * @param string $album
 * @param string $track
 * @param integer $duration
 * @param integer $trackNumber
 * @return array
 */
function lastfm_track_updateNowPlaying($artist, $album, $track, $duration, $trackNumber)
{
  $lastfm = new lastfmapi();
  return $lastfm->postFeed('method=track.updateNowPlaying&track='.urlencode(utf8_encode($track)).
                                                       '&artist='.urlencode(utf8_encode($artist)).
                                                        '&album='.urlencode(utf8_encode($album)).
                                                  '&trackNumber='.$trackNumber.
                                                     '&duration='.$duration);
}

/**
 * Fetch new radio content periodically in an XSPF format.
 *
 * @return array
 */
function lastfm_radio_getPlaylist()
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=radio.getPlaylist');
}

/**
 * Tune in to a Last.fm radio station.
 *
 * @param string $station
 * @return array
 */
function lastfm_radio_tune($station)
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=radio.tune&station='.rawurlencode(utf8_encode($station)));
}

/**
 * Get a list of the recent tracks listened to by this user.
 *
 * @param string $user
 * @param integer $page
 * @param integer $limit
 * @return array
 */
function lastfm_user_getRecentTracks($user, $page=1, $limit=50)
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=user.getRecentTracks&user='.urlencode(utf8_encode($user)).'&page='.$page.'&limit='.$limit);
}

/**
 * Get the top albums listened to by a user.
 *
 * @param string $user
 * @param string $period - overall | 7day | 3month | 6month | 12month - The time period over which to retrieve top tracks for.
 * @return array
 */
function lastfm_user_getTopAlbums($user, $period='overall')
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=user.getTopAlbums&user='.urlencode(utf8_encode($user)).'&period='.$period);
}

/**
 * Get the top artists listened to by a user.
 *
 * @param string $user
 * @param string $period - overall | 7day | 3month | 6month | 12month - The time period over which to retrieve top tracks for.
 * @return array
 */
function lastfm_user_getTopArtists($user, $period='overall')
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=user.getTopArtists&user='.urlencode(utf8_encode($user)).'&period='.$period);
}

/**
 * Get the top tracks listened to by a user.
 *
 * @param string $user
 * @param string $period - overall | 7day | 3month | 6month | 12month - The time period over which to retrieve top tracks for.
 * @return array
 */
function lastfm_user_getTopTracks($user, $period='overall')
{
  $lastfm = new lastfmapi();
  return $lastfm->getFeed('method=user.getTopTracks&user='.urlencode(utf8_encode($user)).'&period='.$period);
}
?>
