<?php
 #############################################################################
 # IRadioPHP                                             (c) Itzchak Rehberg #
 # Internet Radio Site parsing utility by Itzchak Rehberg & IzzySoft         #
 # http://www.qumran.org/homes/izzy/                                         #
 # ------------------------------------------------------------------------- #
 # This is the TuneIn Radio parser                                           #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

require_once( realpath(dirname(__FILE__)."/iradio.php"));
require_once( realpath(dirname(__FILE__)."/../../base/server.php"));

/** TuneIn Radio Specific Parsing
 * @package IRadio
 * @class tunein
 */
class tunein extends iradio {
  protected $service = 'tunein';

  private $partner_id = 'g74oe2T8';
  private $serial;
  private $username;

  /** Initializing the class
   * @constructor tunein
   */
  function tunein() {
    $this->iradio();
    $this->set_site('opml.radiotime.com');
    $this->set_type(IRADIO_TUNEIN);
    $this->serial = str_replace(':','',$_SESSION["device"]["mac_addr"]);
    $this->username = get_user_pref('RADIOTIME_USERNAME');
    $this->search_baseparams = '?partnerId='.$this->partner_id.'&serial='.$this->serial.'&username='.$this->username.'&render=json&filter=s&locale='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
  }

  /** Parse TuneIn Radio result page and store stations using add_station()
   * @class tunein
   * @method parse
   * @param string method API method used to query TuneIn Radio
   * @param string query parameters used with API method
   * @param string cachename Name of the cache object to use
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function parse($method,$param,$cachename) {
    if (!empty($cachename)) { // try getting the data from cache
      $cache = $this->read_cache($cachename);
      if ($cache !== FALSE) {
        $this->station = $cache["stations"];
        $this->link = $cache["links"];
        $this->title = $cache["title"];
        return TRUE;
      }
    }

    // Get stations from TuneIn Radio
    $uri = 'http://'.$this->iradiosite.'/'.$method.'.ashx'.$this->search_baseparams.'&formats='.$this->mediatype.'&'.$param;
    $this->openpage($uri);
    $results = json_decode($this->page, true);

    // Ensure valid response was returned.
    $head = $results["head"];
    if ($head["status"] !== '200') return FALSE;

    // Title of returned results
    $this->title = $head->title;
    $cache["title"] = $this->title;

    // Parse the results into an array
    $results = $this->parse_feed($results["body"]);

    switch ( $method ) {
      case 'Describe':
        // Extract text from results
        $this->link = array();
        if (isset($results["text"]))
        {
          foreach ($results["text"] as $item)
          {
            if (isset($item["guide_id"]))
            {
              $id   = $item["guide_id"];
              $name = utf8_decode($item["text"]);

              $this->add_link($name,$id);
            }
          }
        }
        $cache["links"] = $this->links;
        send_to_log(6,'IRadio: Read '.count($this->links).' texts.');
        break;

      case 'Browse':
      case 'Search':
        // Extract stations from results
        $stationcount = 0;
        $genres = $this->get_genres();
        if (isset($results["audio"]))
        {
          foreach ($results["audio"] as $item)
          {
            if ((isset($item["item"]) && $item["item"] == 'station') || $param == 'query=aa2')
            {
              $name       = utf8_decode($item["text"]);
              $format     = $item["formats"];
              $playlist   = $item["URL"];
              $bitrate    = $item["bitrate"];
              $genre      = isset($genres[$item["genre_id"]]) ? $genres[$item["genre_id"]]["text"] : '';
              $nowplaying = '';
              $website    = '';
              $image      = $item["image"];

              $this->add_station($name,$playlist,$bitrate,$genre,$format,0,0,$nowplaying,$website,$image);
              ++$stationcount;
              if ($stationcount == $this->numresults) break;
            }
          }
        }
        $cache["stations"] = $this->get_station();
        send_to_log(6,'IRadio: Read '.$stationcount.' stations.',$cache["stations"]);

        // Extract links from results
        $this->link = array();
        if (isset($results["link"]))
        {
          foreach ($results["link"] as $item)
          {
            if (!isset($item["item"]) && isset($item["guide_id"]))
            {
              $id   = $item["guide_id"];
              $name = utf8_decode($item["text"]);

              $this->add_link($name,$id);
            }
          }
        }
        $cache["links"] = $this->get_link();
        send_to_log(6,'IRadio: Read '.count($cache["links"]).' links.',$cache["links"]);
        break;
    }
    if (!empty($cachename)) $this->write_cache($cachename,$cache);
    return TRUE;
  }

  /** Parse TuneIn Radio feed results into an array
   * @class tunein
   * @method parse_feed
   * @param object feed results
   * @return array feed elements grouped in arrays by type
   */
  function parse_feed($results) {
    $item_types = array();
    foreach ($results as $object)
    {
      if (isset($object["children"]) && !empty($object["children"]))
      {
        $item_type = array();
        $item_type = $this->parse_feed($object["children"]);
        foreach ($item_type as $type=>$items)
          foreach ($items as $item)
            $item_types[$type][] = $item;
      }
      else
        $item_types[$object["type"]][] = $object;
    }
    return $item_types;
  }

  /** Restrict search to media type
   *  Not all (hardware) players support all formats offered by TuneIn Radio,
   *  so one may need to restrict it to e.g. "mp3".
   * @class tunein
   * @method restrict_mediatype
   * @param optional string mt MediaType to restrict the search to
   *        (call w/o param to disable restriction)
   */
  function restrict_mediatype($mtype='mp3') {
    send_to_log(6,'IRadio: Restricting to stations in '.$mtype.' format');
    $this->mediatype = $mtype;
  }

  /** Get the genre list
   * @class tunein
   * @method get_genres
   * @return array genres (genre[main][sub])
   */
  function get_genres() {
    send_to_log(6,'IRadio: Complete genre list was requested.');
    return $this->parse('Describe','c=genres','genres');
  }

  /** Searching for a genre
   *  Initiates a genre search at the TuneIn Radio site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class tunein
   * @method search_genre
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_genre($name) {
    send_to_log(6,'IRadio: Initialize genre search for "'.$name.'"');
    return $this->search_station($name);
  }

  /** Searching for a station
   *  Initiates a freetext search at the TuneIn Radio site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class tunein
   * @method search_station
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_station($name) {
    send_to_log(6,'IRadio: Initialize station search for "'.$name.'"');
    return $this->parse('Search','query='.str_replace(' ','+',$name),'Search_'.str_replace(' ','_',$name));
  }

  /** Retrieve the country list
   * @class tunein
   * @method get_countries
   * @return array countries
   */
  function get_countries() {
    send_to_log(6,'IRadio: Country list was requested.');
    return $this->parse('Describe','c=countries','countries');
  }

  /** Searching by country
   *  Initiates a freetext search at the TuneIn Radio site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class tunein
   * @method search_country
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_country($name) {
    send_to_log(6,'IRadio: Initialize country search for "'.$name.'"');
    return $this->search_station($name);
  }

  /** Test parser functionality
   *  This method makes a simple request for maximum 2 stations of the genre
   *  "pop" (there are always plenty of stations available), and then checks
   *  the second station returned whether it has at least a name and a valid
   *  playlist URL. If so, it returns TRUE - otherwise FALSE.
   * @class tunein
   * @method test
   * @return boolean OK
   */
  function test($iteration=1) {
    send_to_log(6,'IRadio: Testing TuneIn Radio interface');
    $this->set_cache('');       // disable cache
    $this->set_max_results(5);  // only 5 results needed (smallest number accepted by ShoutCast website)
    $this->search_genre('pop'); // init search
    if (count($this->station)==0) { // work around shoutcast claiming to find nothing
      if ($iteration <3) {
        send_to_log(6,'IRadio: TuneIn Radio claims to find nothing - retry #'.$iteration);
        ++$iteration;
        return $this->test($iteration);
      } else {
        send_to_log(3,'IRadio: TuneIn Radio still claims to have no stations listed - giving up after '.$iteration.' tries.');
        return FALSE;
      }
    }
    if (empty($this->station[1]->name)) {
      send_to_log(3,'IRadio: TuneIn Radio parser returned empty station name');
      return FALSE;
    }
    $url = parse_url($this->station[1]->playlist);
    if (empty($url["host"])) {
      send_to_log(3,'IRadio: TuneIn Radio parser returned invalid playlist: No hostname given');
      return FALSE;
    } elseif (empty($url["path"]) && empty($url["query"])) {
      send_to_log(3,'IRadio: TuneIn Radio parser returned invalid playlist: No filename given');
      return FALSE;
    }
    send_to_log(8,'IRadio: TuneIn Radio parser looks OK');
    return TRUE;
  }

}
?>