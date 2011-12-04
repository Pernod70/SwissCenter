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

require_once(dirname(__FILE__)."/iradio.php");

/** IceCast Specific Parsing
 * @package IRadio
 * @class icecast
 */
class icecast extends iradio {
  private $service = 'icecast';

  /** Initializing the class
   * @constructor icecast
   */
  function icecast() {
    $this->iradio();
    $this->set_site('dir.xiph.org');
    $this->set_type(IRADIO_ICECAST);
    $this->search_baseparams = '';
  }

  /** Parse Icecast result page and store stations using add_station()
   * @class icecast
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
    $uri = 'http://'.$this->iradiosite.'/yp.xml';
    $this->openpage($uri);
    $stationcount = 0;
    $spos = strpos($this->page,'<entry>'); // seek for start position of block
    $epos = $spos +1; // prevent endless loop on broken pages
    while ($spos) {
      $epos  = strpos($this->page,'</entry>',$spos);
      $block = substr($this->page,$spos,$epos - $spos);
      $playlist = preg_get('/<listen_url>(.*)<\/listen_url>/U', $block);
      $name     = utf8_decode(xmlspecialchars_decode(preg_get('/<server_name>(.*)<\/server_name>/U', $block)));
      $bitrate  = preg_get('/<bitrate>(.*)<\/bitrate>/U', $block);
      $format   = $this->mime_type_decode(preg_get('/<server_type>(.*)<\/server_type>/U', $block));
      $genre    = utf8_decode(xmlspecialchars_decode(preg_get('/<genre>(.*)<\/genre>/U', $block)));

      // following information is not available here, so place dummies
      $nowplaying = '?';
      $listeners = '';
      $maxlisteners = '';
      $website = '';

      // Filter the results by genre or keyword
      if ( strpos($url,'genre=') !== false )
      {
        $search = preg_get('/genre=(.*)/',$url);
        if ( stripos($genre,$search) !== false )
        {
          $this->add_station($name,$playlist,$bitrate,$genre,$format,$listeners,$maxlisteners,$nowplaying,$website);
          ++$stationcount;
        }
      }
      elseif ( strpos($url,'search=') !== false )
      {
        $search = preg_get('/search=(.*)/',$url);
        if ( stripos($genre.$name,$search) !== false )
        {
          $this->add_station($name,$playlist,$bitrate,$genre,$format,$listeners,$maxlisteners,$nowplaying,$website);
          ++$stationcount;
        }
      }
      if ($stationcount == $this->numresults) break;
      $spos = strpos($this->page,'<entry>',$epos);
    }
    send_to_log(6,'IRadio: Read '.$stationcount.' stations.');
    if (!empty($cachename)) $this->write_cache($cachename,$this->station);
    return TRUE;
  }

  /** Searching for a genre
   *  Initiates a genre search at the IceCast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class icecast
   * @method search_genre
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_genre($name) {
    send_to_log(6,'IRadio: Initialize genre search for "'.$name.'"');
    return $this->parse('genre='.$name,str_replace(' ','_',$name));
  }

  /** Searching for a station
   *  Initiates a freetext search at the IceCast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class icecast
   * @method search_station
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_station($name) {
    send_to_log(6,'IRadio: Initialize station search for "'.$name.'"');
    return $this->parse('search='.$name,str_replace(' ','_',$name));
  }

  /** Searching by country
   *  Initiates a freetext search at the IceCast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class icecast
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
   * @class icecast
   * @method test
   * @return boolean OK
   */
  function test() {
    send_to_log(6,'IRadio: Testing Icecast interface');
    $this->set_cache('');       // disable cache
    $this->set_max_results(5);  // only 2 results needed
    $this->search_genre('pop'); // init search
    if (empty($this->station[1]->name)) {
      send_to_log(3,'IRadio: Icecast parser returned empty station name');
      return FALSE;
    }
    $url = parse_url($this->station[1]->playlist);
    if (empty($url["host"])) {
      send_to_log(3,'IRadio: Icecast parser returned invalid playlist: No hostname given');
      return FALSE;
    } elseif (empty($url["path"]) && empty($url["query"])) {
      send_to_log(3,'IRadio: Icecast parser returned invalid playlist: No filename given');
      return FALSE;
    }
    send_to_log(8,'IRadio: Icecast parser looks OK');
    return TRUE;
  }

}

?>