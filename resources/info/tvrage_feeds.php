<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));
require_once( realpath(dirname(__FILE__).'/../../ext/xml/xmlparser.php'));

define('TVRAGE_API_KEY', 'lpw4cCJ1NTc6XExnIynD');
define('TVRAGE_FEED_URL','http://services.tvrage.com/myfeeds/');

class TVRage {
  private $service = 'tvrage';
  private $cache_expire = 43200;
  private $cache;

  function TVRage ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  private function request($request)
  {
    // Sends a request to TVRage
    send_to_log(6,'TVRage feed request', $request);
    if (!($this->response = $this->cache->getCached($request))) {
      if (($this->response = file_get_contents($request)) !== false) {
        $this->cache->cache($request, $this->response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return true;
  }

  /**
   * Return requested feed.
   *
   * @param string $feed
   * @return array
   */
  function Search($show)
  {
    if ( $this->request(TVRAGE_FEED_URL.'search.php?key='.TVRAGE_API_KEY.'&show='.$show) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      if ( !isset($tvrage['RESULTS']['SHOW'][0]) )
        $tvrage['RESULTS']['SHOW'] = array($tvrage['RESULTS']['SHOW']);
      return $tvrage;
    } else {
      return false;
    }
  }

  function Detailed_Search($show)
  {
    if ( $this->request(TVRAGE_FEED_URL.'full_search.php?key='.TVRAGE_API_KEY.'&show='.$show) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      if ( !isset($tvrage['RESULTS']['SHOW'][0]) )
        $tvrage['RESULTS']['SHOW'] = array($tvrage['RESULTS']['SHOW']);
      return $tvrage;
    } else {
      return false;
    }
  }

  function Show_Info($sid)
  {
    if ( $this->request(TVRAGE_FEED_URL.'showinfo.php?key='.TVRAGE_API_KEY.'&sid='.$sid) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      return $tvrage;
    } else {
      return false;
    }
  }

  function Episode_List($sid)
  {
    if ( $this->request(TVRAGE_FEED_URL.'episode_list.php?key='.TVRAGE_API_KEY.'&sid='.$sid) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      if ( !isset($tvrage['SHOW']['EPISODELIST'][0]) )
        $tvrage['SHOW']['EPISODELIST'] = array($tvrage['SHOW']['EPISODELIST']);
      return $tvrage;
    } else {
      return false;
    }
  }

  function Episode_Info($sid, $ep)
  {
    if ( $this->request(TVRAGE_FEED_URL.'episodeinfo.php?key='.TVRAGE_API_KEY.'&sid='.$sid.'&ep='.$ep) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      return $tvrage;
    } else {
      return false;
    }
  }

  function Full_Show_Info($sid)
  {
    if ( $this->request(TVRAGE_FEED_URL.'full_show_info.php?key='.TVRAGE_API_KEY.'&sid='.$sid) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      return $tvrage;
    } else {
      return false;
    }
  }

  function Full_Schedule($country, $format=1)
  {
    if ( $this->request(TVRAGE_FEED_URL.'fullschedule.php?key='.TVRAGE_API_KEY.'&country='.$country.'&24_format='.$format) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      return $tvrage;
    } else {
      return false;
    }
  }

  function Countdown()
  {
    if ( $this->request(TVRAGE_FEED_URL.'countdown.php?key='.TVRAGE_API_KEY) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      return $tvrage;
    } else {
      return false;
    }
  }

  function Current_Shows()
  {
    if ( $this->request(TVRAGE_FEED_URL.'currentshows.php?key='.TVRAGE_API_KEY) ) {
      $xml = new XmlParser($this->response, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
      $tvrage = $xml->GetData();
      return $tvrage;
    } else {
      return false;
    }
  }

  /**
   * Returns an array of show/episode details.
   *
   * @param string $show - name of show
   * @param integer $exact - if set to 1 then $show must be an exact match
   * @param string $episode - episode id in the form 2x04
   * @return array
   */
  function Quickinfo($show, $exact="", $episode="")
  {
    if ( !$show ) { return FALSE; }

    $ret = array();
    if ( $this->request("http://services.tvrage.com/tools/quickinfo.php?show=".urlencode($show)."&ep=".urlencode($episode)."&exact=".urlencode($exact)) )
    {
      // Remove <pre> tag from response
      $this->response = str_replace('<pre>', '', $this->response);
      $lines = explode("\n", $this->response);

      foreach ($lines as $line)
      {
        list ($key,$val) = explode('@',$line,2);
        $key = str_replace(' ', '_', $key);
        switch ( $key )
        {
          case "Genres":
            $ret[$key] = explode(' | ', $val);
            break;

          case "Latest_Episode":
          case "Next_Episode":
          case "Episode Info":
            list ($ep,$title,$airdate) = explode('^', $val);
            $ret[5] = array('Episode' => $ep, 'Title' => $title, 'AirDate' => $airdate);
            break;

          default:
            $ret[$key] = $val;
        }
      }
    }

    if ( isset($ret['Show_ID']) ) {
      return $ret;
    } else {
      return FALSE;
    }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
