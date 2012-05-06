<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

class SHOUTcastTV {
  private $GET = 'http://yp.shoutcast.com/sbin/newtvlister.phtml';

  private $service = 'shoutcast_tv';
  private $cache_expire = 3600;
  private $cache;
  private $response;

  function SHOUTcastTV ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  private function request()
  {
    //Sends a request to SHOUTcast TV
    $url = $this->GET;
    send_to_log(6,'SHOUTcast TV request',$url);

    if (!($this->response = $this->cache->getCached($url)) ) {
      //Send Requests
      if (($this->response = file_get_contents($url)) !== false) {
        $this->cache->cache($url, $this->response);
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
  function getDirectory ($sort = '', $genre = '')
  {
    if ($this->request())
    {
      preg_match_all('/station name="(.*)" id="(.*)" br="(.*)" rt="(.*)" ct="(.*)" load="(.*)" genre="(.*)" lc="(.*)"/U', $this->response, $matches);

      // Organise directory into usable array
      $directory = array();
      for ($i=0; $i<=count($matches[0])-1; $i++)
      {
        if (empty($genre) || $genre == ucwords(trim($matches[7][$i])))
          $directory[] = array("name"    => trim($matches[1][$i]),
                               "url"     => 'http://yp.shoutcast.com/sbin/tunein-tvstation.pls?id='.$matches[2][$i],
                               "bitrate" => $matches[3][$i],
                               "rating"  => trim($matches[4][$i]),
                               "current" => trim($matches[5][$i]),
                               "load"    => $matches[6][$i],
                               "genre"   => ucwords(trim($matches[7][$i])),
                               "viewers" => $matches[8][$i]);
      }

      // Sort the directory
      foreach ($directory as $key => $row)
        $order[$key] = $row[$sort];
      array_multisort($order, ($sort == 'viewers' ? SORT_DESC : SORT_ASC), $directory);

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
