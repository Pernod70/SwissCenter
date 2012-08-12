<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

class Jamendo {
  private $cache_expire = 3600;
  private $cache;
  private $response;
  private $site;

  function Jamendo ($service = 'jamendo')
  {
    $this->service = $service;
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
    $this->site = 'http://api.'.$service.'.com';
  }

  /**
   * Send the request to get the HTML page contents.
   *
   * @param $url
   * @return string
   */
  private function request($url)
  {
    // Sends a request to Jamendo
    send_to_log(6,'Jamendo API request',$url);
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
   * Get a page and parse individual items.
   *
   * @param string $feed
   * @return array
   */
  function getQuery ($fields, $unit, $params = '')
  {
    $join = '';
    if ($unit == 'track') $join = 'track_album+album_artist/';
    $url = $this->site.'/get2/'.$fields.'/'.$unit.'/json/'.$join.$params;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }
}
?>