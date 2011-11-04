<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));

define('METROLYRICS_URL','http://www.metrolyrics.com');

class MetroLyrics {
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

  function MetroLyrics ()
  {
    $this->service = 'metrolyrics';
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
      return html_entity_decode($result, true);
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

  private function request ($request, $nocache = false)
  {
    //Sends a request to MetroLyrics
    send_to_log(6,'MetroLyrics request:', $request);
    if (!($this->response = $this->getCached($request)) || $nocache) {
      if ($body = file_get_contents($request)) {
        $lyrics = preg_get('/<p id="lyrics">(.*)<\/p>/U', $body);
        $this->response = html_entity_decode($lyrics);
        $this->cache($request, $lyrics);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return true;
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
    if ( $this->request(METROLYRICS_URL.'/widgets/winamp/winamp.php?artist='.rawurlencode($artist).'&title='.rawurlencode($title)) )
      return $this->response;
    else
      return false;
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
