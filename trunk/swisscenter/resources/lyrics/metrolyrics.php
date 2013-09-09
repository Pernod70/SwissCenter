<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

define('METROLYRICS_URL','http://www.metrolyrics.com');

class MetroLyrics {
  private $service = 'metrolyrics';
  private $cache_expire = 3600;
  private $cache;

  function MetroLyrics ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  /**
   * Search for requested lyrics.
   *
   * @param string $artist
   * @param string $title
   * @return string
   */
  function getLyrics ($artist, $title)
  {
    // Form the request URL
    $request = METROLYRICS_URL.'/external/winamp/get/'.rawurlencode($artist).'/'.rawurlencode($title);

    //Sends a request to MetroLyrics
    send_to_log(6,'MetroLyrics request:', $request);

    if (!($lyrics = $this->cache->getCached($request))) {
      if (($body = file_get_contents($request)) !== false) {
        $lyrics = preg_get('/<div id="lyrics-body">(.*)<\/div>/Usm', $body);
        $lyrics = html_entity_decode($lyrics);
        if (!empty($lyrics))
          $this->cache->cache($request, $lyrics);
        else
          $lyrics = false;
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return $lyrics;
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
