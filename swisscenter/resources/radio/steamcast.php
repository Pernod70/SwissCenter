<?php
 #############################################################################
 # IRadioPHP                                             (c) Itzchak Rehberg #
 # Internet Radio Site parsing utility by Itzchak Rehberg & IzzySoft         #
 # http://www.qumran.org/homes/izzy/                                         #
 # ------------------------------------------------------------------------- #
 # This is the Icecast parser                                                #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

require_once( realpath(dirname(__FILE__)."/iradio.php"));
require_once( realpath(dirname(__FILE__).'/../../ext/xml/xmlparser.php'));

/** Steamcast Specific Parsing
 * @package IRadio
 * @class steamcast
 */
class steamcast extends iradio {

  /** Initializing the class
   * @constructor steamcast
   */
  function steamcast() {
    $this->iradio();
    $this->set_site('www.steamcast.com');
    $this->set_type(IRADIO_STEAMCAST);
    $this->search_baseparams = '?t=a';
  }

  /** Parse Steamcast result page and store stations using add_station()
   * @class steamcast
   * @method parse
   * @param string url pagename and params to add to the main url
   * @param string cachename Name of the cache object to use
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function parse($url,$cachename) {
    if (!empty($cachename)) { // try getting the data from cache
      $stations = $this->read_cache($cachename);
      if ($stations !== FALSE) {
        $this->station = $stations;
        return TRUE;
      }
    }
    if (empty($url)) return FALSE;
    $uri = 'http://'.$this->iradiosite.'/sbin/rss_feed.rss'.$this->search_baseparams.$url;
    $this->openpage($uri);

    $xml = new XmlParser($this->page, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $rss = $xml->GetData();
    if ( isset($rss['RSS']['CHANNEL']['ITEM']) )
    {
      if ( !isset($rss['RSS']['CHANNEL']['ITEM'][0]) )
        $rss['RSS']['CHANNEL']['ITEM'] = array($rss['RSS']['CHANNEL']['ITEM']);
      $stationcount = 0;
      foreach ($rss['RSS']['CHANNEL']['ITEM'] as $item)
      {
        $playlist     = $item['LINK']['VALUE'];
        $name         = utf8_decode(html_entity_decode($item['TITLE']['VALUE']));
        $name         = trim(preg_replace('/\[.*\] /','',$name));
        $description  = utf8_decode(html_entity_decode($item['DESCRIPTION']['VALUE']));
        $genre        = utf8_decode(html_entity_decode($item['CATEGORY']['VALUE']));
        $bitrate      = preg_get('/Bitrate: (\d+)/', $description);
        $format       = $this->mime_type_decode($item['ENCLOSURE']['TYPE']);

        // following information is not available here, so place dummies
        $nowplaying = "?";
        $listeners = "";
        $maxlisteners = "";
        $website = "";
        $this->add_station($name,$playlist,$bitrate,$genre,$format,$listeners,$maxlisteners,$nowplaying,$website);
        ++$stationcount;
        if ($stationcount == $this->numresults) break;
      }
    }
    else
      send_to_log(1,'Unable to read playlist file or format not recognised/supported.');

    send_to_log(6,'IRadio: Read '.$stationcount.' stations.');
    if (!empty($cachename)) $this->write_cache($cachename,$this->station);
    return TRUE;
  }

  /** Get the genre list
   * @class iradio
   * @method get_genres
   * @return array genres (genre[main][sub])
   */
  function get_genres() {
    send_to_log(6,'IRadio: Complete genre list was requested.');
    if (empty($this->genre)) {
      $uri = 'http://'.$this->iradiosite;
      $this->openpage($uri);
      # Genres
      $spos = strpos($this->page,'<select name="g"');
      $epos = strpos($this->page,'</select>',$spos);
      $options = substr($this->page,$spos,$epos - $spos);
      $epos = 1;
      while ($spos>0) {
        $spos = strpos($options,'<option',$epos);
        $epos = strpos($options,'">',$spos);
        $g_id = urldecode(substr($options,$spos +15,$epos - $spos -15));
        $spos = $epos +2;
        $epos = strpos($options,'<',$spos);
        $genre = html_entity_decode(strtolower(substr($options,$spos,$epos - $spos)));
        $spos = strpos($options,'<option',$epos) -1;
        send_to_log(8,'IRadio: Got genre '.$genre.' with value '.$g_id);
        if (empty($g_id)) $g_id = 'All';
        $this->genre[$g_id][$g_id] = array("text" => $genre, "id"   => $g_id);
      }
    }
    return $this->genre;
  }

  /** Searching for a genre
   *  Initiates a genre search at the Steamcast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class steamcast
   * @method search_genre
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_genre($name) {
    send_to_log(6,'IRadio: Initialize genre search for "'.$name.'"');
    if ($name == 'All') $name = '';
    return $this->parse('&g='.str_replace(' ','+',$name),str_replace(' ','_',$name));
  }

  /** Searching for a station
   *  Initiates a freetext search at the Steamcast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class steamcast
   * @method search_station
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_station($name) {
    send_to_log(6,'IRadio: Initialize station search for "'.$name.'"');
    return $this->parse('&s='.str_replace('+','_',$name),str_replace(' ','_',$name));
  }

  /** Searching by country
   *  Initiates a freetext search at the Steamcast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class steamcast
   * @method search_country
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_country($name) {
    send_to_log(6,'IRadio: Country search not supported - faking it:');
    return $this->search_station($name);
  }

  /** Test parser functionality
   *  This method makes a simple request for maximum 2 stations of the genre
   *  "pop" (there are always plenty of stations available), and then checks
   *  the second station returned whether it has at least a name and a valid
   *  playlist URL. If so, it returns TRUE - otherwise FALSE.
   * @class steamcast
   * @method test
   * @return boolean OK
   */
  function test() {
    send_to_log(6,'IRadio: Testing Steamcast interface');
    $this->set_cache('');       // disable cache
    $this->set_max_results(5);  // only 2 results needed
    $this->search_genre('pop'); // init search
    if (empty($this->station[1]->name)) {
      send_to_log(3,'IRadio: Steamcast parser returned empty station name');
      return FALSE;
    }
    $url = parse_url($this->station[1]->playlist);
    if (empty($url["host"])) {
      send_to_log(3,'IRadio: Steamcast parser returned invalid playlist: No hostname given');
      return FALSE;
    } elseif (empty($url["path"]) && empty($url["query"])) {
      send_to_log(3,'IRadio: Steamcast parser returned invalid playlist: No filename given');
      return FALSE;
    }
    send_to_log(8,'IRadio: Steamcast parser looks OK');
    return TRUE;
  }

}

?>