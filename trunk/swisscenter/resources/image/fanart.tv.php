<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

// API key registered to SwissCenter project
define('FANARTTV_API_KEY', '9a4e6e2cdfb55e97523adfe92a0a6ba2');

class Fanarttv {
  private $cache_expire = 3600;
  private $cache;
  private $response;
  private $site;

  function Fanarttv ($service = 'fanarttv')
  {
    $this->service = $service;
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
    $this->site = 'http://webservice.fanart.tv/v3';
  }

  /**
   * Send the API request.
   *
   * @param $url
   * @return string
   */
  private function request($url)
  {
    // Sends a request to fanart.tv
    send_to_log(6,'fanart.tv API request',$url);
    if (!($this->response = $this->cache->getCached($url))) {
      if (($this->response = file_get_contents($url)) !== false) {
        $this->cache->cache($url, $this->response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $url);
        return false;
      }
    }
    return true;
  }


  /**
   * Get images for Movie
   *
   * @param string $id - Numeric tmdb_id or imdb_id of the movie.
   */
  public function getMovies($id) {
    $url = $this->site.'/movies/'.$id.'?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Latest Movies
   *
   */
  public function latestMovies() {
    $url = $this->site.'/movies/latest/?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Get images for artist
   *
   * @param string $id - Musicbrainz id for the artist.
   */
  public function getArtist($id) {
    $url = $this->site.'/music/'.$id.'?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Get Album
   *
   * @param string $id - Albums musicbrainz release-group id
   */
  public function getAlbum($id) {
    $url = $this->site.'/music/albums/'.$id.'?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Get Label
   *
   * @param string $id - Labels musicbrainz id
   */
  public function getLabel($id) {
    $url = $this->site.'/music/labels/'.$id.'?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Latest Artists
   *
   */
  public function latestArtists() {
    $url = $this->site.'/music/latest?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Get images for show
   *
   * @param string $id - thetvdb id for the show.
   */
  public function getShow($id) {
    $url = $this->site.'/tv/'.$id.'?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Latest Shows
   *
   */
  public function latestShows() {
    $url = $this->site.'/tv/latest?api_key='.FANARTTV_API_KEY;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

}
?>