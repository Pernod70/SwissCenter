<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

define('YOUPORN_URL','http://www.youporn.com');

class YouPorn {
  private $service;
  private $response;
  private $cache_table = null;
  private $cache_expire = null;

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

  function YouPorn ()
  {
    $this->service = 'youporn';
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
      return json_decode($result, true);
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
    if (!($items = $this->getCached($request))) {
      if ($body = $this->request($request)) {
        preg_match_all('/<a href="\/watch\/.*">.*src="(.*jpg)".*<a href="(\/watch\/.*)">(.*)<\/a>/Ums',$body,$items);
        $this->cache($request, $items);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return $items;
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
    if (!($categories = $this->getCached($request))) {
      if ($body = $this->request($request)) {
        preg_match_all('/<a href="(\/category\/.*\/)">(.*)<\/a>/U',$body,$categories);
        $this->cache($request, $categories);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return $categories;
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
    if (!($details = $this->getCached($request))) {
      if ($body = $this->request($request)) {
        $details = array('title'     => preg_get('/<title>(.*) - Free/U', $body),
                         'duration'  => preg_get('/<span>Duration:<\/span>(.*)<\/li>/U', $body),
                         'rating'    => preg_get('/<span>Rating:<\/span>(.*)\/.*<\/li>/U', $body),
                         'viewed'    => preg_get('/<span>Views:<\/span>(.*)/U', $body),
                         'date'      => preg_get('/<span>Date:<\/span>(.*)<\/li>/U', $body),
                         'video_url' => preg_get('/<a href="(.*)">MPG/U', $body));
        $this->cache($request, $details);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return $details;
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
