<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

class phpYouTube {
  private $GET = 'http://gdata.youtube.com/';

  // Not used - required for API requests that need authentication
  private $developer_key = 'AI39si4es6-gXx07JdA7stI80G_1cics8gnt76TkH8qiODFg4NKflrvGlr1YQQCOn1zU6Y2jBH7qMw8kEMmRmNKEuvoG8oZG2g';

  private $service = 'youtube';
  private $cache_expire = 3600;
  private $cache;

  private $api_version = 2;
  private $start_index = 1;
  private $max_results = 50;

  function phpYouTube ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  private function request($request, $args = array())
  {
    // Select JSON output and set the API version to use
    $request = url_add_params($this->GET.$request, array_merge(array("alt" => "json", "v" => $this->api_version), $args));

    //Sends a request to YouTube
    send_to_log(6,'YouTube API request', $request);
    if (!($body = $this->cache->getCached($request))) {
      if (($body = file_get_contents($request)) !== false) {
        $this->cache->cache($request, $body);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return json_decode($body, true);
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
    return $this->request('feeds/api/users/'.$username);
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
    return $this->request('feeds/api/videos/'.$video_id);
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
    return $this->request('feeds/api/videos', array("q"=>urlencode($query), "orderby"=>$order, "start-index"=>$this->start_index, "max-results"=>$this->max_results));
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
    return $this->request('feeds/api/playlists/snippets', array("q"=>urlencode($query), "start-index"=>$this->start_index, "max-results"=>$this->max_results));
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
    return $this->request('feeds/api/channels', array("q"=>urlencode($query), "start-index"=>$this->start_index, "max-results"=>$this->max_results));
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
    return $this->request('feeds/api/users/'.$username.'/'.$type, array("start-index"=>$this->start_index, "max-results"=>$this->max_results));
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
    return $this->request('feeds/api/playlists/'.$playlist_id, array("start-index"=>$this->start_index, "max-results"=>$this->max_results));
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
    return $this->request('feeds/api/standardfeeds/'.$region.$type, $args);
  }

}
?>
