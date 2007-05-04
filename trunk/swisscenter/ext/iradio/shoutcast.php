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

 /* $Id$ */

require_once(dirname(__FILE__)."/iradio.php");

/** ShoutCast Specific Parsing
 * @package IRadio
 * @class shoutcast
 */
class shoutcast extends iradio {

  /** Initializing the class
   * @constructor shoutcast
   */
  function shoutcast() {
    $this->iradio();
    $this->set_site("www.shoutcast.com");
  }

  /** Parse shoutcast result page and store stations using add_station()
   * @class shoutcast
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
    $uri = "http://".$this->iradiosite."/$url&numresult=".$this->numresults;
    $this->openpage($uri);
    $stationcount = 0;
    $startpos = strpos($this->page,">Type<"); // seek for start position of block
    $epos = $startpos +1; // prevent endless loop on broken pages
    while ($startpos) {
      $spos = strpos($this->page,"a href=",$startpos); // playlist
      $epos = strpos($this->page,"\">",$spos);
      $playlist = substr($this->page,$spos+8,$epos - $spos -8);
      $spos = strpos($this->page,"<b>[",$epos); // genre
      $epos = strpos($this->page,"]",$spos);
      $genre = substr($this->page,$spos+4,$epos - $spos -4);
      $spos = strpos($this->page,"href=",$epos); // website
      $epos = strpos($this->page,"\">",$spos);
      $website = substr($this->page,$spos+6,$epos - $spos -6);
      $spos = $epos +2; // station name
      $epos = strpos($this->page,"</a>",$spos);
      $name = substr($this->page,$spos,$epos - $spos);
      $spos = strpos($this->page,"Now Playing:</font>",$epos); // now playing
      $epos = strpos($this->page,"</font>",$spos +19);
      $nowplaying = substr($this->page,$spos +19,$epos - $spos -19);
      $spos = strpos($this->page,"FFF\">",$epos); // listeners
      $epos = strpos($this->page,"/",$spos);
      $listeners = substr($this->page,$spos+5,$epos - $spos -5);
      $spos = $epos +1; // maxlisteners
      $epos = strpos($this->page,"</font>",$spos);
      $maxlisteners = substr($this->page,$spos,$epos - $spos);
      $spos = strpos($this->page,"FFF\">",$epos); // bitrate
      $epos = strpos($this->page,"</font>",$spos);
      $bitrate = substr($this->page,$spos+5,$epos - $spos -5);
      $spos = strpos($this->page,"FFF\">",$epos); // format
      $epos = strpos($this->page,"</font>",$spos);
      $format = substr($this->page,$spos+5,$epos - $spos -5);
      $this->add_station($name,$playlist,$bitrate,$genre,$format,$listeners,$maxlisteners,$nowplaying,$website);
      ++$stationcount;
      if ($stationcount = $this->numresults) break;
      $startpos = strpos($this->page,"<a href=\"/sbin/shoutcast-playlist.pls",$epos);
    }
    if (!empty($cachename)) $this->write_cache($cachename,$this->station);
    return TRUE;
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
    return $this->parse("?sgenre=$name",$name);
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
    return $this->parse("?s=$name",$name);
  }

  /** Searching by country
   *  Initiates a freetext search at the ShoutCast site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class liveradio
   * @method search_country
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_country($name) {
    return $this->search_station($name);
  }

}

?>