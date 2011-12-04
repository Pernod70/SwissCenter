<?php
/**
 * Thrown when an API call returns an exception.
 *
 */
class MusicXMatchApiException extends Exception{

  public function __construct($code) {

    $msg = '';

    if ($code != 200 && isset(MusicXMatch::$STATUS_CODES[$code]))
    {
      $msg = MusicXMatch::$STATUS_CODES[$code];
    }
    else if ($code != 200)
    {
      $msg = 'Unknown error';
    }

    parent::__construct($msg, $code);
  }
}


/**
 * Handles API requests.
 * For further information, go to
 *  https://developer.musixmatch.com/documentation/
 *
 */
class MusicXMatch
{

  public static $STATUS_CODES = array(
        200=>'The request was successful',
        400=>'The request had bad syntax or was inherently impossible to be satisfied',
        401=>'authentication failed, probably because of a bad API key',
        402=>'a limit was reached, either you exceeded per hour requests limits or your balance is insufficient.',
        403=>'You are not authorized to perform this operation / the api version youâ€™re trying to use has been shut down.',
        404=>'requested resource was not found',
        405=>'requested method was not found'
    );


  protected $_apikey   = 'd5a3c7d60d590eb3309cc70d901f5025';

  protected $_base_url = 'api.musixmatch.com/ws/1.1/';
  protected $_result   = '';
  protected $_use_ssl  = '';
  protected $_method   = '';
  protected $_format   = 'json';

  protected $_query_parameters = array();


  public function __construct($use_ssl = false)
  {
      $this->_use_ssl = $use_ssl;
  }

  /**
   * Resets parameters, except for the apikey
   * @chainable
   *
   */
  private function reset_params()
  {
      $this->_query_parameters = array();
      $this->_query_parameters['apikey'] = $this->_apikey;
      $this->_query_parameters['format'] = $this->_format;
      return $this;
  }

  /**
   * Search for a track in our database.
   *
   * @param $q                    - a string that will be searched in every data field (q_track, q_artist, q_lyrics)
   * @param $q_track              - words to be searched among track titles
   * @param $q_artist             - words to be searched among artist names
   * @param $q_track_artist       - words to be searched among track titles or artist names
   * @param $q_lyrics             - words to be searched into the lyrics
   * @param $f_has_lyrics         - exclude tracks without an available lyrics (automatic if q_lyrics is set)
   * @param $f_artist_id          - filter the results by the artist_id
   * @param $f_artist_mbid        - filter the results by the artist_mbid
   * @param $s_track_rating       - sort the results by our popularity index, possible values are ASC | DESC
   * @param $s_track_release_date - sort the results by track release date, possible values are ASC | DESC
   * @param $quorum_factor        - only works together with q and q_track_artist parameter.
   *                                Possible values goes from 0.1 to 0.9
   *                                A value of 0.9 means: “match at least 90% of the given words”.
   * @return array
   */
  public function track_search($q = null, $q_track = null, $q_artist = null, $q_track_artist = null, $q_lyrics = null, $f_has_lyrics = null, $f_has_subtitle = null, $f_artist_id = null, $f_artist_mbid = null, $s_track_rating = null, $s_track_release_date = null, $quorum_factor = null)
  {
    $this->reset_params();
    if ( !empty($q) )                    $this->_query_parameters['q']                    = $q;
    if ( !empty($q_track) )              $this->_query_parameters['q_track']              = $q_track;
    if ( !empty($q_artist) )             $this->_query_parameters['q_artist']             = $q_artist;
    if ( !empty($q_track_artist) )       $this->_query_parameters['q_artist']             = $q_track_artist;
    if ( !empty($q_lyrics) )             $this->_query_parameters['q_lyrics']             = $$q_lyrics;
    if ( !empty($f_has_lyrics) )         $this->_query_parameters['f_has_lyrics']         = $f_has_lyrics;
    if ( !empty($f_has_subtitle) )       $this->_query_parameters['f_has_subtitle']       = $f_has_subtitle;
    if ( !empty($f_artist_id) )          $this->_query_parameters['f_artist_id']          = $f_artist_id;
    if ( !empty($f_artist_mbid) )        $this->_query_parameters['f_artist_mbid']        = $f_artist_mbid;
    if ( !empty($s_track_rating) )       $this->_query_parameters['s_track_rating']       = $s_track_rating;
    if ( !empty($s_track_release_date) ) $this->_query_parameters['s_track_release_date'] = $s_track_release_date;
    if ( !empty($quorum_factor) )        $this->_query_parameters['quorum_factor']        = $quorum_factor;
    return $this->execute_request('track.search');
  }

  /**
   * Get a track info from our database: title, artist, instrumental flag and cover art.
   *
   * @param $track_id       - the track identifier expressed as a musiXmatch ID or musicbrainz ID
   * @param $track_mbid     - the track identifier expressed as a musiXmatch ID or musicbrainz ID
   * @return array
   */
  public function track_get($track_id = null, $track_mbid = null)
  {
    $this->reset_params();
    if ( !empty($track_id) )        $this->_query_parameters['track_id']      = $track_id;
    if ( !empty($track_mbid) )      $this->_query_parameters['track_mbid']    = $track_mbid;
    return $this->execute_request('track.get');
  }

  /**
   * This api provides you the list of the top tracks of the supported countries.
   *
   * @param $country        - the country code of the desired country chart
   * @param $f_has_lyrics   - exclude tracks without an available lyrics
   * @return array
   */
  public function chart_tracks_get($country, $f_has_lyrics = null)
  {
    $this->reset_params();
    if ( !empty($country) )         $this->_query_parameters['country']       = $country;
    if ( !empty($f_has_lyrics) )    $this->_query_parameters['f_has_lyrics']  = $f_has_lyrics;
    return $this->execute_request('chart.tracks.get');
  }

  /**
   * Retreive the subtitle of a track
   *
   * @param $track_id        - the track identifier expressed as a musiXmatch ID or musicbrainz ID
   * @param $track_mbid      - the track identifier expressed as a musiXmatch ID or musicbrainz ID
   * @param $subtitle_format - the format of the subtitle (lrc,dfxp,stledu). Default to lrc
   * @return array
   */
  public function track_subtitle_get($track_id = null, $track_mbid = null, $subtitle_format = 'lrc')
  {
    $this->reset_params();
    if ( !empty($track_id) )        $this->_query_parameters['track_id']        = $track_id;
    if ( !empty($track_mbid) )      $this->_query_parameters['track_mbid']      = $track_mbid;
    if ( !empty($subtitle_format) ) $this->_query_parameters['subtitle_format'] = $subtitle_format;
    return $this->execute_request('track.subtitle.get');
  }

  /**
   * Retreive the lyrics of a track.
   *
   * @param $track_id       - the track identifier expressed as a musiXmatch ID or musicbrainz ID
   * @param $track_mbid     - the track identifier expressed as a musiXmatch ID or musicbrainz ID
   * @return array
   */
  public function track_lyrics_get($track_id = null, $track_mbid = null)
  {
    $this->reset_params();
    if ( !empty($track_id) )        $this->_query_parameters['track_id']      = $track_id;
    if ( !empty($track_mbid) )      $this->_query_parameters['track_mbid']    = $track_mbid;
    return $this->execute_request('track.lyrics.get');
  }

  /**
   * Submit a lyrics to our database.
   *
   * @param $track_id       - a musixmatch track id
   * @param $lyrics_body    - the lyrics
   * @return array
   */
  public function track_lyrics_post($track_id, $lyrics_body)
  {
    $this->reset_params();
    if ( !empty($track_id) )        $this->_query_parameters['track_id']      = $track_id;
    if ( !empty($lyrics_body) )     $this->_query_parameters['lyrics_body']   = $lyrics_body;
    return $this->execute_request('track.lyrics.post');
  }

  /**
   * This API method provides you the opportunity to help us improving our catalogue.
   *
   * @param $lyrics_id      - the lyrics identifier
   * @param $track_id       - the track identifier
   * @param $feedback       - one of the support feedback type
   * @return array
   */
  public function track_lyrics_feedback_post($lyrics_id, $track_id, $feedback)
  {
    $this->reset_params();
    if ( !empty($lyrics_id) )       $this->_query_parameters['q_lyrics']      = $lyrics_id;
    if ( !empty($track_id) )        $this->_query_parameters['track_id']      = $track_id;
    if ( !empty($feedback) )        $this->_query_parameters['feedback']      = $feedback;
    return $this->execute_request('track.lyrics.feedback.post');
  }

  /**
   * Match your track against our database.
   *
   * @param $q_track        - words to be searched among track titles
   * @param $q_artist       - words to be searched among artist names
   * @param $q_duration     - search for a track duration
   * @param $q_album        - the name of the album
   * @return array
   */
  public function matcher_track_get($q_track = null, $q_artist = null, $q_duration = null, $q_album = null)
  {
    $this->reset_params();
    if ( !empty($q_track) )         $this->_query_parameters['q_track']       = $q_track;
    if ( !empty($q_artist) )        $this->_query_parameters['q_artist']      = $q_artist;
    if ( !empty($q_duration) )      $this->_query_parameters['q_duration']    = $q_duration;
    if ( !empty($q_album) )         $this->_query_parameters['q_album']       = $q_album;
    return $this->execute_request('matcher.track.get');
  }

  /**
   * Get the artist data from our database.
   *
   * @param $artist_id      - the artist identifier expressed as a musiXmatch ID or musicbrainz ID
   * @param $artist_mbid    - the artist identifier expressed as a musiXmatch ID or musicbrainz ID
   * @return array
   */
  public function artist_get($artist_id = null, $artist_mbid = null)
  {
    $this->reset_params();
    if ( !empty($artist_id) )       $this->_query_parameters['artist_id']     = $artist_id;
    if ( !empty($artist_mbid) )     $this->_query_parameters['artist_mbid']   = $artist_mbid;
    return $this->execute_request('artist.get');
  }

  /**
   * Search for artists in our database.
   *
   * @param $q              - a string that will be searched in every data field (q_track, q_artist, q_lyrics)
   * @param $q_track        - words to be searched among tracks titles
   * @param $q_artist       - words to be searched among artists names
   * @param $q_lyrics       - words to be searched into the lyrics
   * @param $f_has_lyrics   - exclude artists without any available lyrics (automatic if q_lyrics is set)
   * @param $f_artist_id    - filter the results by the artist_id
   * @param $f_artist_mbid  - filter the results by the artist_mbid
   * @return array
   */
  public function artist_search($q = null, $q_track = null, $q_artist = null, $q_lyrics = null, $f_has_lyrics = null, $f_artist_id = null, $f_artist_mbid = null)
  {
    $this->reset_params();
    if ( !empty($q) )               $this->_query_parameters['q']             = $q;
    if ( !empty($q_track) )         $this->_query_parameters['q_track']       = $q_track;
    if ( !empty($q_artist) )        $this->_query_parameters['q_artist']      = $q_artist;
    if ( !empty($q_lyrics) )        $this->_query_parameters['q_lyrics']      = $q_lyrics;
    if ( !empty($f_has_lyrics) )    $this->_query_parameters['f_has_lyrics']  = $f_has_lyrics;
    if ( !empty($f_artist_id) )     $this->_query_parameters['f_artist_id']   = $f_artist_id;
    if ( !empty($f_artist_mbid) )   $this->_query_parameters['f_artist_mbid'] = $f_artist_mbid;
    return $this->execute_request('artist.search');
  }

  /**
   * Get the discography of an artist.
   *
   * @param $artist_id      - the album identifier expressed as a musiXmatch ID
   * @param $g_album_name   - group albums by name to avoid duplicates
   * @param $s_release_date - sort by release date (desc/asc)
   * @return array
   */
  public function artist_albums_get($artist_id, $g_album_name = null, $s_release_date = null)
  {
    $this->reset_params();
    if ( !empty($artist_id) )       $this->_query_parameters['artist_id']     = $artist_id;
    if ( !empty($g_album_name) )    $this->_query_parameters['g_album_name']  = $g_album_name;
    if ( !empty($s_release_date) )  $this->_query_parameters['s_release_date']= $s_release_date;
    return $this->execute_request('artist.albums.get');
  }

/**
   * Get the discography of an artist.
   *
   * @param $artist_id      - the album identifier expressed as a musiXmatch ID
   * @param $artist_mbid    - the musicbrainz artist id
   * @return array
   */
  public function artist_related_get($artist_id, $artist_mbid = null)
  {
    $this->reset_params();
    if ( !empty($artist_id) )       $this->_query_parameters['artist_id']     = $artist_id;
    if ( !empty($artist_mbid) )     $this->_query_parameters['artist_mbid']   = $artist_mbid;
    return $this->execute_request('artist.related.get');
  }

  /**
   * This api provides you the list of the top artists of a given country.
   *
   * @param $country        - the country code of the desired country chart
   * @return array
   */
  public function chart_artists_get($country)
  {
    $this->reset_params();
    if ( !empty($country) )         $this->_query_parameters['country']       = $country;
    return $this->execute_request('chart.artists.get');
  }

  /**
   * Get an album from our database: name, release_date, release_type, cover art.
   *
   * @param $album_id       - the album identifier expressed as a musiXmatch ID
   * @return array
   */
  public function album_get($album_id)
  {
    $this->reset_params();
    if ( !empty($album_id) )        $this->_query_parameters['album_id']      = $album_id;
    return $this->execute_request('album.get');
  }

  /**
   * Get the list of all the tracks of an album.
   *
   * @param $album_id       - the album identifier expressed as a musiXmatch ID
   * @return array
   */
  public function album_tracks_get($album_id)
  {
    $this->reset_params();
    if ( !empty($album_id) )        $this->_query_parameters['album_id']      = $album_id;
    return $this->execute_request('album.tracks.get');
  }

  /**
   * With this api you’ll be able to get the base url for the tracking script you need to insert in your page to legalize your existent lyrics library.
   *
   * @param $domain - The domain of your site
   * @return array
   */
  public function tracking_url_get($domain)
  {
    $this->reset_params();
    if ( !empty($domain) )          $this->_query_parameters['domain']        = $domain;
    return $this->execute_request('tracking.url.get');
  }

  /**
   * Executes the request and returns the result
   *
   */
  private function execute_request($method)
  {
    $this->_method = $method;

    $url = $this->build_query_url();

    $query_result = file_get_contents($url);

    $full_result = json_decode($query_result, true);

    //any error has occured
    if (($code = $full_result['message']['header']['status_code']) != 200)
    {
      throw new MusicXMatchApiException($code);
    }

    return $this->_result = $full_result['message']['body'];
  }


  public function result()
  {
    return $this->result;
  }


  /**
   * Uses the parameters and other stuff to build the query string
   * @return string the url to be fetched
   */
  private function build_query_url()
  {

    //protocol
    $url =  $this->_use_ssl ? 'https://'  : 'http://';

    //base url
    $url .= $this->_base_url;

    //method - testing
    $url .= $this->_method;

    $url .= '?';

    foreach ($this->_query_parameters as $key=>$value)
    {
        $url.=$key.'='.rawurlencode($value).'&';
    }

    return $url;
  }
}
?>
