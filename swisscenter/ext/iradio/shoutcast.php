<?php
 #############################################################################
 # IRadioPHP                                             (c) Itzchak Rehberg #
 # Internet Radio Site parsing utility by Itzchak Rehberg & IzzySoft         #
 # http://www.qumran.org/homes/izzy/                                         #
 # ------------------------------------------------------------------------- #
 # This is the ShoutCast parser                                              #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

require_once( realpath(dirname(__FILE__)."/iradio.php"));

/** ShoutCast Specific Parsing
 * @package IRadio
 * @class shoutcast
 */
class shoutcast extends iradio {
  var $api_key = 'pe1DPxlnBcQpX78Y';

  /** Initializing the class
   * @constructor shoutcast
   */
  function shoutcast() {
    $this->iradio();
    $this->set_site('api.shoutcast.com');
    $this->set_type(IRADIO_SHOUTCAST);
  }

  /** Parse shoutcast result page and store stations using add_station()
   * @class shoutcast
   * @method parse
   * @param string url pagename and params to add to the main url
   * @param string cachename Name of the cache object to use
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function parse($url,$cachename,$iteration=1) {
    if (!empty($cachename)) { // try getting the data from cache
      $stations = $this->read_cache($cachename);
      if ($stations !== FALSE) {
        $this->station = $stations;
        return TRUE;
      }
    }
    if (empty($url)) return FALSE;
    $uri = 'http://'.$this->iradiosite.$url.'&limit='.$this->numresults;
    $this->openpage($uri);

    preg_match_all('/station name="(.*)" mt="(.*)" id="(.*)" br="(.*)" genre="(.*)" ct="(.*)" lc="(.*)"/U', $this->page, $matches);
    $stationcount = count($matches[0]);

    // Organise directory into usable array
    for ($i=0; $i<=$stationcount-1; $i++)
    {
      $name       = trim(xmlspecialchars_decode($matches[1][$i]));
      $name       = str_replace(' - a SHOUTcast.com member station', '', $name);
      $format     = array_search(trim($matches[2][$i]), array('MP3' => 'audio/mpeg', 'AAC+' => 'audio/aacp'));
      $playlist   = 'http://yp.shoutcast.com/sbin/tunein-station.pls?id='.$matches[3][$i];
      $bitrate    = $matches[4][$i];
      $genre      = ucwords(trim(xmlspecialchars_decode($matches[5][$i])));
      $nowplaying = ucwords(trim(xmlspecialchars_decode($matches[6][$i])));
      $listeners  = $matches[7][$i];

      $this->add_station($name,$playlist,$bitrate,$genre,$format,$listeners,0,$nowplaying,'');
    }

    send_to_log(6,'IRadio: Read '.$stationcount.' stations.');
    if (!empty($cachename)) $this->write_cache($cachename,$this->station);
    return TRUE;
  }

  /** Restrict search to media type
   *  Not all (hardware) players support all formats offered by ShoutCast,
   *  so one may need to restrict it to e.g. "mp3".
   * @class shoutcast
   * @method restrict_mediatype
   * @param optional string mt MediaType to restrict the search to
   *        (call w/o param to disable restriction)
   */
  function restrict_mediatype($mtype='audio/mpeg') {
    send_to_log(6,'IRadio: Restricting to stations in '.$mtype.' format');
    $this->search_baseparams = '&mt='.$mtype;
  }

  /** Searching for a genre
   *  Initiates a genre search at the shoutcast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class shoutcast
   * @method search_genre
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_genre($name) {
    send_to_log(6,'IRadio: Initialize genre search for "'.$name.'"');
    return $this->parse('/legacy/genresearch?k='.$this->api_key.'&genre='.str_replace(' ','+',$name),str_replace(' ','_',$name));
  }

  /** Searching for a station
   *  Initiates a freetext search at the shoutcast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class shoutcast
   * @method search_station
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_station($name) {
    send_to_log(6,'IRadio: Initialize station search for "'.$name.'"');
    return $this->parse('/legacy/stationsearch?k='.$this->api_key.'&search='.str_replace(' ','+',$name),str_replace(' ','_',$name));
  }

  /** Searching by country
   *  Initiates a freetext search at the ShoutCast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class shoutcast
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
   * @class shoutcast
   * @method test
   * @return boolean OK
   */
  function test($iteration=1) {
    send_to_log(6,'IRadio: Testing ShoutCast interface');
    $this->set_cache('');       // disable cache
    $this->set_max_results(5);  // only 5 results needed (smallest number accepted by ShoutCast website)
    $this->search_genre('pop'); // init search
    if (count($this->station)==0) { // work around shoutcast claiming to find nothing
      if ($iteration <3) {
        send_to_log(6,'IRadio: ShoutCast claims to find nothing - retry #'.$iteration);
        ++$iteration;
        return $this->test($iteration);
      } else {
        send_to_log(3,'IRadio: ShoutCast still claims to have no stations listed - giving up after '.$iteration.' tries.');
        return FALSE;
      }
    }
    if (empty($this->station[1]->name)) {
      send_to_log(3,'IRadio: ShoutCast parser returned empty station name');
      return FALSE;
    }
    $url = parse_url($this->station[1]->playlist);
    if (empty($url["host"])) {
      send_to_log(3,'IRadio: ShoutCast parser returned invalid playlist: No hostname given');
      return FALSE;
    } elseif (empty($url["path"]) && empty($url["query"])) {
      send_to_log(3,'IRadio: ShoutCast parser returned invalid playlist: No filename given');
      return FALSE;
    }
    send_to_log(8,'IRadio: ShoutCast parser looks OK');
    return TRUE;
  }

}
?>