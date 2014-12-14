<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

define('YOUPORN_URL','http://www.youporn.com');

class YouPorn {
  private $service = 'youporn';
  private $cache_expire = 3600;
  private $cache;
  private $response;

  function YouPorn ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  /**
   * Send the request to get the HTML page contents.
   *
   * @param $url
   * @return string
   */
  private function request( $url )
  {
    //Send the age_check cookie with the request
    $streamcontext_options = array( 'http'=> array(
                                    'method'=> 'GET',
                                    'header'=> 'Cookie: age_check=1'.'\r\n' ) );
    $streamcontext = stream_context_create($streamcontext_options);
    $body = file_get_contents($url, false, $streamcontext);
    return $body;
  }

  /**
   * Get a page and parse individual items.
   *
   * @param string $feed
   * @return array
   */
  function getItems ($url)
  {
    $request = YOUPORN_URL.$url;

    //Sends a request to YouPorn
    send_to_log(6,'YouPorn request', $request);
    if (!($items = $this->cache->getCached($request))) {
      if (($body = $this->request($request)) !== false) {
        preg_match_all('/<a href="(\/watch\/.*)">.*data-thumbnail="(.*jpg).*".*<p class="videoTitle" title="(.*)">(.*)<\/p>/Ums',$body,$items);
        $items = json_encode($items);
        $this->cache->cache($request, $items);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return json_decode($items, true);
  }

  /**
   * Get and parse the categories page.
   *
   * @return array
   */
  function getCategories ()
  {
    $request = YOUPORN_URL.'/categories';

    //Sends a request to YouPorn
    send_to_log(6,'YouPorn request', $request);
    if (!($categories = $this->cache->getCached($request))) {
      if (($body = $this->request($request)) !== false) {
        preg_match_all('/<a href="\/(category\/.*)\/">(.*)<\/a>/U',$body,$categories);
        $categories = json_encode($categories);
        $this->cache->cache($request, $categories);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return json_decode($categories, true);
  }

  /**
   * Get a video selected page and return the parsed details.
   *
   * @param $url
   * @return array
   */
  function getDetails ( $url )
  {
    $request = YOUPORN_URL.$url;

    //Sends a request to YouPorn
    send_to_log(6,'YouPorn request', $request);
    if (!($details = $this->cache->getCached($request))) {
      if (($body = $this->request($request)) !== false) {
        $details = array('title'     => preg_get('/<title>(.*) - Free/U', $body),
                         'rating'    => preg_get('/<div class="rating-percentage">(.*)%.*<\/div>/Usm', $body),
                         'viewed'    => preg_get("/<div id='stats-views'><i class='watch-icon icon-views'><\/i>(.*)<\/div>/U", $body),
                         'date'      => preg_get("/<div id='stats-date'><i class='watch-icon icon-date'><\/i>(.*)<\/div>/U", $body),
                         'mpg_url'   => html_entity_decode(preg_get('/<a href="(.*.download.youporn.*)">\sMPG/U', $body)),
                         'mp4_url'   => html_entity_decode(preg_get('/<a href="(.*.download.youporn.*)">\sMP4/U', $body)));
        $details = json_encode($details);
        $this->cache->cache($request, $details);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return json_decode($details, true);
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
