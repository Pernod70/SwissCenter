<?php
 #############################################################################
 # IRadioPHP                                             (c) Itzchak Rehberg #
 # Internet Radio Site parsing utility by Itzchak Rehberg & IzzySoft         #
 # http://www.qumran.org/homes/izzy/                                         #
 # ------------------------------------------------------------------------- #
 # This is the Live-Radio parser                                              #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

 /* $Id$ */

require_once(dirname(__FILE__)."/iradio.php");

/** Live-Radio Specific Parsing
 * @package IRadio
 * @class liveradio
 */
class liveradio extends iradio {

  /** Initializing the class
   * @constructor liveradio
   */
  function liveradio() {
    $this->iradio();
    $this->set_site("www.live-radio.net");
    $this->search_baseparams = "?OSt=Li&OCnt=Li&OSta=Li&Sta=&OCit=Li&Cit=&OGen=Li&$url";
  }

  /** Parse Live-Radio result page and store stations using add_station()
   * @class liveradio
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
    $uri = "http://".$this->iradiosite."/SearchResults.php3".$this->search_baseparams."&OPag=".$this->numresults;
#die($uri);
    $this->openpage($uri);
    $stationcount = 0;
    $startpos = strpos($this->page,'HREF="redirstation'); // seek for start position of block
    $epos = $startpos +1; // prevent endless loop on broken pages
    while ($startpos) {
      $spos = strpos($this->page,'HREF="',$startpos); // website
      $epos = strpos($this->page,"\">",$spos);
      $website = "http://".$this->iradiosite."/".substr($this->page,$spos+6,$epos - $spos -6);
      $spos = $epos+2; // name
      $epos = strpos($this->page,"</A>",$spos);
      $name = trim(substr($this->page,$spos,$epos - $spos));
      $spos = strpos($this->page,"(",$epos); // location
      $epos = strpos($this->page,")&nbsp;",$spos);
      $location = substr($this->page,$spos+1,$epos - $spos -1);
      $spos = strpos($this->page,'F00">',$epos); // genre
      $epos = strpos($this->page,'</FONT>',$spos);
      $genre = substr($this->page,$spos+5,$epos - $spos -5);
      $spos = strpos($this->page,'redirfeed',$epos); // playlist
      $epos = strpos($this->page,'">',$spos);
      $playlist = "/".substr($this->page,$spos,$epos - $spos);
      $spos = strpos($this->page,'""></A>',$epos); // format
      $epos = strpos($this->page,'</ul>',$spos);
      if ($epos - $spos > 20) $epos = strpos($this->page,'<LI>',$spos); // multiple feeds
      $format = trim(substr($this->page,$spos+7,$epos - $spos -7));
      // following information is not available here, so place dummies
      $bitrate = "?";
      $nowplaying = "?";
      $listeners = "";
      $maxlisteners = "";
      $this->add_station($name,$playlist."&filetype=.pls",$bitrate,$genre,$format,$listeners,$maxlisteners,$nowplaying,$website);
      ++$stationcount;
      if ($stationcount == $this->numresults) break;
      $startpos = strpos($this->page,'HREF="redirstation',$epos);
    }
    if (!empty($cachename)) $this->write_cache($cachename,$this->station);
    return TRUE;
  }

  /** Obtain parameters from the search page
   *  The Live-Radio.NET site tends to change the valid values for certain
   *  parameters from time to time, so we need to make sure to have the
   *  correct ones.
   *  This method will fill the $this->params array.
   * @class liveradio
   * @method get_siteparams
   */
  function get_siteparams() {
    if (is_array($this->params->mediatype)) return;
    $uri = 'http://'.$this->iradiosite.'/SearchStations.php3';
    $this->openpage($uri);
    # Media Types
    $spos = strpos($this->page,'<select name="OFee"');
    $epos = strpos($this->page,'</Select>',$spos);
    $options = substr($this->page,$spos,$epos - $spos);
    $epos = 1;
    while ($spos>0) {
      $spos = strpos($options,'<option',$epos);
      $epos = strpos($options,'">',$spos);
      $optval = strtolower(substr($options,$spos +15,$epos - $spos -15));
      $spos = $epos +2;
      $epos = strpos($options,'<',$spos);
      $optname = strtolower(substr($options,$spos,$epos - $spos));
      $this->params->mediatype[$optname] = $optval;
      $spos = strpos($options,'<option',$epos) -1;
    }
  }

  /** Restrict search to media type
   *  Not all (hardware) players support all formats offered by Live-Radio.NET,
   *  so one may need to restrict it to e.g. "mp3".
   * @class liveradio
   * @method restrict_mediatype
   * @param optional string mtype MediaType to restrict the search to
   *        (call w/o param to disable restriction)
   */
  function restrict_mediatype($mtype="any") {
    $this->get_siteparams();
    $mtype = strtolower($mtype);
    if (isset($this->params->mediatype[$mtype]))
      $this->search_baseparams .= "&OFee=".$this->params->mediatype[$mtype];
    else
      $this->search_baseparams .= "&OFee=".$this->params->mediatype["any"];
  }

  /** Searching for a genre
   *  Initiates a genre search at the Live-Radio site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class liveradio
   * @method search_genre
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_genre($name) {
    return $this->parse("&Genre=$name&Cnt=&St=",$name);
  }

  /** Searching for a station
   *  Initiates a freetext search at the Live-Radio site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class liveradio
   * @method search_station
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_station($name) {
    return $this->parse("&Genre=&Cnt=&St=$name",$name);
  }

  /** Searching by country
   *  Initiates a freetext search at the Live-Radio site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class liveradio
   * @method search_country
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_country($name) {
    return $this->parse("&Genre=&Cnt=$name&St=",$name);
  }

}

?>