<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));

class SHOUTcastTV {
  var $GET = 'http://yp.shoutcast.com/sbin/newtvlister.phtml';
  var $service = 'shoutcast_tv';

  var $req;
  var $response;
  var $cache_table = null;
  var $cache_expire = null;

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
  var $max_cache_rows = 500;

  function SHOUTcastTV ()
  {
    $this->enableCache(3600);
  }

  /**
   * Enable caching to the database
   *
   * @param unknown_type $cache_expire
   * @param unknown_type $table
   */
  function enableCache($cache_expire = 600, $table = 'cache_api_request')
  {
    if (db_value("SELECT COUNT(*) FROM $table WHERE service = '".$this->service."'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $table WHERE service = '".$this->service."' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
    $this->cache_table = $table;
    $this->cache_expire = $cache_expire;
  }

  function getCached ($request)
  {
    //Checks the database for a cached result to the request.
    //If there is no cache result, it returns a value of false. If it finds one,
    //it returns the unparsed XML.
    $reqhash = md5(serialize($request));

    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result)) {
      return $result;
    }
    return false;
  }

  function cache ($request, $response)
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

  function request ()
  {
    //Sends a request to SHOUTcast TV
    $url = $this->GET;
    send_to_log(6,'SHOUTcast TV request',$url);

    if (!($this->response = $this->getCached($url)) ) {
      //Send Requests
      if ($response = file_get_contents($url)) {
        $this->response = $response;
        $this->cache($url, $response);
      } else {
        send_to_log(2,'There has been a problem sending your command to the server.');
        return false;
      }
    }
    return true;
  }

  /**
   * Return SHOUTcast TV directory.
   *
   * @return array
   */
  function getDirectory ()
  {
    if ($this->request())
    {
      preg_match_all('/station name="(.*)" id="(.*)" br="(.*)" rt="(.*)" ct="(.*)" load="(.*)" genre="(.*)" lc="(.*)"/U', $this->response, $matches);

      // Organise directory into usable array
      $directory = array();
      for ($i=0; $i<=count($matches[0])-1; $i++)
      {
        $directory[] = array("name"    => trim($matches[1][$i]),
                             "url"     => 'http://yp.shoutcast.com/sbin/tunein-tvstation.pls?id='.$matches[2][$i],
                             "bitrate" => $matches[3][$i],
                             "rating"  => trim($matches[4][$i]),
                             "current" => trim($matches[5][$i]),
                             "load"    => $matches[6][$i],
                             "genre"   => ucwords(trim($matches[7][$i])),
                             "viewers" => $matches[8][$i]);
      }
      return $directory;
    }
    else
      return false;
  }

  function getGenres ()
  {
    if ($this->request())
    {
      preg_match_all('/genre="(.*)"/U', $this->response, $matches);

      // Tidy the returned genres by trimming and ucwords
      foreach ($matches[1] as $id=>$genre)
        $matches[1][$id] = ucwords(trim($genre));

      // Sort genres
      $genres = array_count_values($matches[1]);
      ksort($genres);

      return $genres;
    }
    else
      return false;
  }
}
?>
