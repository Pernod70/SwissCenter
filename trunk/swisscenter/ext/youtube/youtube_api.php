<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));
require_once( realpath(dirname(__FILE__).'/../json/json.php'));

class phpYouTube {
  private $GET = 'http://gdata.youtube.com/';

  // Not used - required for API requests that need authentication
  private $developer_key = 'AI39si4es6-gXx07JdA7stI80G_1cics8gnt76TkH8qiODFg4NKflrvGlr1YQQCOn1zU6Y2jBH7qMw8kEMmRmNKEuvoG8oZG2g';

  private $service;
  private $response;
  private $cache_table = null;
  private $cache_expire = null;

  private $api_version = 2;
  private $start_index = 1;
  private $max_results = 50;

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
  private $max_cache_rows = 1000;

  function phpYouTube ()
  {
    $this->service = 'youtube';
    $this->enableCache(3600);
  }

  /**
   * Enable caching to the database
   *
   * @param unknown_type $cache_expire
   * @param unknown_type $table
   */
  private function enableCache($cache_expire = 600, $table = 'cache_api_request')
  {
    if (db_value("SELECT COUNT(*) FROM $table WHERE service = '".$this->service."'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $table WHERE service = '".$this->service."' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
    $this->cache_table = $table;
    $this->cache_expire = $cache_expire;
  }

  private function getCached ($request)
  {
    //Checks the database for a cached result to the request.
    //If there is no cache result, it returns a value of false. If it finds one,
    //it returns the unparsed XML.
    $reqhash = md5(serialize($request));

    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result)) {
      return object_to_array(json_decode($result));;
    }
    return false;
  }

  private function cache ($request, $response)
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

  private function request($request, $args = array(), $nocache = false)
  {
    // Select JSON output and set the API version to use
    $request = url_add_params($this->GET.$request, array_merge(array("alt" => "json", "v" => $this->api_version), $args));

    //Sends a request to YouTube
    send_to_log(6,'YouTube API request', $request);
    if (!($this->response = $this->getCached($request)) || $nocache) {
      if ($body = file_get_contents($request)) {
        $this->response = object_to_array(json_decode($body));
        $this->cache($request, $body);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return true;
  }

  /**
   * Returns an array of regions that can be used to retrieve region-specific standard feeds.
   *
   * @return array
   */
  function getRegions()
  {
    $regions = array( array('COUNTRY' => 'Australia',      'REGION_ID' => 'AU'),
                      array('COUNTRY' => 'Brazil',         'REGION_ID' => 'BR'),
                      array('COUNTRY' => 'Canada',         'REGION_ID' => 'CA'),
                      array('COUNTRY' => 'Czech Republic', 'REGION_ID' => 'CZ'),
                      array('COUNTRY' => 'France',         'REGION_ID' => 'FR'),
                      array('COUNTRY' => 'Germany',        'REGION_ID' => 'DE'),
                      array('COUNTRY' => 'Great Britain',  'REGION_ID' => 'GB'),
                      array('COUNTRY' => 'Holland',        'REGION_ID' => 'NL'),
                      array('COUNTRY' => 'Hong Kong',      'REGION_ID' => 'HK'),
                      array('COUNTRY' => 'India',          'REGION_ID' => 'IN'),
                      array('COUNTRY' => 'Ireland',        'REGION_ID' => 'IE'),
                      array('COUNTRY' => 'Israel',         'REGION_ID' => 'IL'),
                      array('COUNTRY' => 'Italy',          'REGION_ID' => 'IT'),
                      array('COUNTRY' => 'Japan',          'REGION_ID' => 'JP'),
                      array('COUNTRY' => 'Mexico' ,        'REGION_ID' => 'MX'),
                      array('COUNTRY' => 'New Zealand',    'REGION_ID' => 'NZ'),
                      array('COUNTRY' => 'Poland',         'REGION_ID' => 'PL'),
                      array('COUNTRY' => 'Russia',         'REGION_ID' => 'RU'),
                      array('COUNTRY' => 'South Korea',    'REGION_ID' => 'KR'),
                      array('COUNTRY' => 'Spain',          'REGION_ID' => 'ES'),
                      array('COUNTRY' => 'Sweden',         'REGION_ID' => 'SE'),
                      array('COUNTRY' => 'Taiwan',         'REGION_ID' => 'TW'),
                      array('COUNTRY' => 'United States',  'REGION_ID' => 'US') );
    return $regions;
  }

  /*
    These functions are the direct implementations of youtube calls.
    For method documentation, including arguments, visit
    http://code.google.com/apis/youtube/2.0/developers_guide_protocol_audience.html
  */

  /**
   * Return a users profile.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_profiles.html
   *
   * @param string $username
   * @return array
   */
  function entryUserProfile ($username = NULL)
  {
    $this->request('feeds/api/users/'.$username);
    return $this->response;
  }

  /**
   * Return details for a single video.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_video_entries.html
   *
   * @param string $video_id
   * @return array
   */
  function videoEntry ($video_id)
  {
    $this->request('feeds/api/videos/'.$video_id);
    return $this->response;
  }

  /**
   * Search for videos.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_api_query_parameters.html
   *
   * @param string $query
   * @param string $order
   * @return array
   */
  function videoSearch ($query = NULL, $order = 'relevance')
  {
    $this->request('feeds/api/videos', array("q"=>urlencode($query), "orderby"=>$order, "start-index"=>$this->start_index, "max-results"=>$this->max_results));
    return $this->response;
  }

  /**
   * Search for playlists.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_playlist_search.html
   *
   * @param string $query
   * @return array
   */
  function playlistSearch ($query = NULL)
  {
    $this->request('feeds/api/playlists/snippets', array("q"=>urlencode($query), "start-index"=>$this->start_index, "max-results"=>$this->max_results));
    return $this->response;
  }

  /**
   * Search for channels.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_channel_search.html
   *
   * @param string $query
   * @return array
   */
  function channelSearch ($query = NULL)
  {
    $this->request('feeds/api/channels', array("q"=>urlencode($query), "start-index"=>$this->start_index, "max-results"=>$this->max_results));
    return $this->response;
  }

  /**
   * Return a user feed.
   *
   * @param string $username
   * @param string $type
   * @return array
   */
  function usersFeed ($username, $type)
  {
    $this->request('feeds/api/users/'.$username.'/'.$type, array("start-index"=>$this->start_index, "max-results"=>$this->max_results));
    return $this->response;
  }

  /**
   * Return a playlist feed.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_playlists.html#Retrieving_a_playlist
   *
   * @param string $playlist_id
   * @return array
   */
  function playlistFeed ($playlist_id)
  {
    $this->request('feeds/api/playlists/'.$playlist_id, array("start-index"=>$this->start_index, "max-results"=>$this->max_results));
    return $this->response;
  }

  /**
   * Return a standard video feed.
   *
   * http://code.google.com/apis/youtube/2.0/developers_guide_protocol_video_feeds.html#Standard_feeds
   *
   * @param string $type - feed id
   * @param string $time - time query parameter, can be today, this_week, this_month, or all_time
   * @param string $category - defined in http://gdata.youtube.com/schemas/2007/categories.cat
   * @param string $region - defined in getRegions
   * @return array
   */
  function standardFeed ($type, $time = 'all_time', $category = NULL, $region = NULL)
  {
    $args = array();

    // Only the following standard feeds support the time parameter
    $support_time = array( 'top_rated', 'top_favorites', 'most_viewed', 'most_discussed', 'most_linked', 'most_responded' );
    if ( in_array($type, $support_time) ) { $args['time'] = $time; }

    $args['start-index'] = $this->start_index;
    $args['max-results'] = $this->max_results;

    // Add the category and region to the feed url
    $type   = !empty($category) ? $type.'_'.$category.'/' : $type;
    $region = !empty($region)   ? $region.'/' : '';
    $this->request('feeds/api/standardfeeds/'.$region.$type, $args);
    return $this->response;
  }

}
?>
