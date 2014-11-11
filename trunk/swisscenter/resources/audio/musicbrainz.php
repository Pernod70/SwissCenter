<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));
require_once( realpath(dirname(__FILE__).'/../../base/utils.php'));

class MusicBrainz {
  private $cache_expire = 3600;
  private $cache;
  private $response;
  private $site;
  private $context;

  function MusicBrainz ($service = 'musicbrainz')
  {
    $this->service = $service;
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
    $this->site = 'http://musicbrainz.org/ws/2/';
    $this->context = stream_context_create(array("http"=>array("method" => "GET",
                                                               "header" => "Accept: application/json\r\n" .
                                                                           "User-Agent: SwissCenter/1.24 ( http://www.swisscenter.co.uk )\r\n")));
  }

  /**
   * Send the API request.
   *
   * @param $url
   * @return string
   */
  private function request($url)
  {
    // Sends a request to MusicBrainz
    send_to_log(6,'MusicBrainz API request',$url);
    if (!($this->response = $this->cache->getCached($url))) {
      if (($this->response = file_get_contents($url, false, $this->context)) !== false) {
        $this->cache->cache($url, $this->response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $url);
        return false;
      }
    }
    return true;
  }


  /**
   * Search
   *
   * @param string $type - annotation, area, artist, cdstub, freedb, label, place, recording, release-group, release, tag, work
   * @param string $query
   */
  public function search($index, $query, $field = '') {
    $query = (!empty($field) ? $field.':'.rawurlencode($query) : rawurlencode($query));
    $url = $this->site.$index.'/?query='.$query.'&fmt=json';
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }
}

function mb_artist_search($artist)
{
  $result = false;
  if (internet_available() && !empty($artist))
  {
    $mb = new MusicBrainz();
    $data = $mb->search('artist', $artist);
    if (is_array($data['artists']))
    {
      // Search results for exact or best match
      $results = array();
      foreach ($data['artists'] as $i=>$item)
        $results[$i] = $item['name'];
      $id = best_match($artist, $results, $accuracy);
      if ($id !== false)
        $result = $data['artists'][$id];
    }
  }
  return $result;
}
?>