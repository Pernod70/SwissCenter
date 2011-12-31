<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

define('VIDEOBASH_URL','http://www.videobash.com');

class VideoBash {
  private $service = 'videobash';
  private $cache_expire = 3600;
  private $cache;
  private $response;

  function VideoBash ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  /**
   * Send the request to get the HTML page contents.
   *
   * @param $url
   * @return string
   */
  private function request($url)
  {
    $body = file_get_contents($url);
    return $body;
  }

  /**
   * Get a page and parse individual items.
   *
   * @param string $type - videos/photos
   * @param string $cat  - category
   * @param string $sort - mr/tr/mv
   * @param string $when - d/w/m
   * @return array
   */
  function getItems($type = 'videos', $cat = 'all', $sort = '', $when = '', $search = '')
  {
    if (empty($search))
      $request = VIDEOBASH_URL.'/'.$type.'/'.$cat.'/'.$sort.'/'.$when;
    else
      $request = VIDEOBASH_URL.'/search/'.$sort.'/'.$when.'?search='.$search.'&type='.rtrim($type, 's');

    //Sends a request to VideoBash
    send_to_log(6,'VideoBash request', $request);
    if (!($all_items = $this->cache->getCached($request))) {
      $all_items = array(1=>array(), 2=>array(), 3=>array());
      $num_pages = 1;
      for ($page=1; $page<=$num_pages; $page++) {
        if (($body = $this->request(url_add_params($request, array('page'=>$page, 'search'=>$search)))) !== false) {
          // Determine number of available pages
          preg_match_all('/[\?|&]page=(\d+)/', $body, $matches);
          $max_page  = max($matches[1]);
          // Adjust number of pages to read
          $num_pages = min($max_page, get_sys_pref('VIDEOBASH_PAGES', 5));

          $body = preg_replace('/src=".*" data-thumb=/', 'src=', $body);
          if ($type == 'videos')
            preg_match_all('/<li class="thumbs-video-wrapper.*src="(.*jpg)".*<a href="(.*video_show.*)">(.*)<\/a>/Ums',$body,$items);
          else
            preg_match_all('/<li class="thumbs-video-wrapper.*src="(.*jpg)".*<a href="(.*photo_show.*)">(.*)<\/a>/Ums',$body,$items);
          $all_items[1] = array_merge($all_items[1], $items[1]);
          $all_items[2] = array_merge($all_items[2], $items[2]);
          $all_items[3] = array_merge($all_items[3], $items[3]);
        } else {
          send_to_log(2,"There has been a problem sending your command to the server.", $request);
          return false;
        }
      }
      $all_items = json_encode($all_items);
      $this->cache->cache($request, $all_items);
    }
    return json_decode($all_items, true);
  }

  /**
   * Get and parse the categories page.
   *
   * @param $type - videos/photos
   * @return array
   */
  function getCategories($type = 'videos')
  {
    $request = VIDEOBASH_URL;

    //Sends a request to VideoBash
    send_to_log(6,'VideoBash request', $request);
    if (!($categories = $this->cache->getCached('categories_'.$type))) {
      if (($body = $this->request($request)) !== false) {
        preg_match_all('/<li><a href=".*'.$type.'\/(.*)"><span>(.*)<\/span><\/a><\/li>/U',$body,$categories);
        $categories = array(1=>array_merge(array('all'), $categories[1]),
                            2=>array_merge(array('all'), $categories[2]));
        $categories = json_encode($categories);
        $this->cache->cache('categories_'.$type, $categories);
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
  function getDetails($url)
  {
    //Sends a request to VideoBash
    send_to_log(6,'VideoBash request', $url);
    if (!($details = $this->cache->getCached($url))) {
      if (($body = $this->request($url)) !== false) {
        $details = array('title'       => preg_get('/<title>(.*) - Funny/U', $body),
                         'rating'      => preg_get('/id="thumbs_percent" itemprop="average">(.*)%<\/div>/U', $body),
                         'description' => preg_get('/"description" content="(.*) - Watch Funny Videos, Clips,Jokes and Pranks from around the world. Videobash has a huge selection of free funny pictures,viral videos, and entertainment!/U', $body),
                         'viewed'      => preg_get('/<div class="realviews float-left"><strong>(.*)<\/strong>/U', $body),
                         'date'        => preg_get('/<span class="time-upload">on(.*)<\/span>/U', $body),
                         'image'       => preg_get('/"imageContent".*src="(.*jpg)"/U', $body),
                         'video_url'   => preg_get('/.*false&video_url=.*(.*.mp4)/U', $body));
        $details = json_encode($details);
        $this->cache->cache($url, $details);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $url);
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
