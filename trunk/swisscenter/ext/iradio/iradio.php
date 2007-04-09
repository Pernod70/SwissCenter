<?php
 #############################################################################
 # IRadioPHP                                             (c) Itzchak Rehberg #
 # Internet Radio Site parsing utility by Itzchak Rehberg & IzzySoft         #
 # http://www.qumran.org/homes/izzy/                                         #
 # ------------------------------------------------------------------------- #
 # Base Class holding config and structures                                  #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

 /* $Id$ */

// the proxy to use for connections to imdb.
// leave it empty for no proxy.
// this is only supported with PEAR. 
define ('PROXY', "");
define ('PROXY_PORT', "");

// set to false to use the old browseremulator.
$PEAR = false;

require_once(dirname(__FILE__)."/iradio_request.php");

# ==========================================================[ Config Class ]===
/** Configuration and structure part of the iradio classes
 * @package IRadio
 * @class iradio_config
 */
class iradio {
  var $iradiosite;
  var $cachedir;
  var $usecache;
  var $storecache;
  var $cache_expire;

  /** Constructor and only method of this base class.
   *  There's no need to call this yourself - you should just place your
   *  configuration data here.
   * @constructor imdb_config
   */
  function iradio(){
    // the iradio server to use.
    $this->set_site("www.shoutcast.com");
    // cachedir should be writable by the webserver. This doesn't need to be
    // under documentroot.
    $this->cachedir = './imdb/cache/';
    //whether to use a cached page to retrieve the information if available.
    $this->usecache = false;
    //whether to store the pages retrieved for later use.
    $this->storecache = false;
    // automatically delete cached files older than X secs
    $this->cache_expire = 600;
    // limit result list
    $this->numresults = 12; // shoutcast: 25/30/50/100
    // setup genres from ./genres.txt
    $this->read_genres();
    $this->sort_genres();
  }

  /** Set the Internet Radio site to parse
   * @class iradio
   * @method set_site
   * @param string url URL without protocoll, e.g. "www.shoutcast.com"
   */
  function set_site($url) {
    $this->iradiosite = $url;
  }

# ------------------------------------------------------------[ Structures ]---
  /** Add a genre to the genre list
   * @class iradio
   * @method add_genre
   * @param string main Main genre
   * @param string sub Sub-genre
   * @param optional string comment
   * @return boolean success
   */
  function add_genre ($main,$sub,$comment="") {
    $this->genre[$main][$sub] = $comment;
    return TRUE;
  }

  /** Read genres from file
   * @class iradio
   * @method read_genres
   * @param optional string filename
   * @return boolean success
   */
  function read_genres ($filename="") {
    if (empty($filename)) $filename = dirname(__FILE__)."/genres.txt";
    if (!file_exists($filename)) return FALSE;
    $list = file($filename);
    $lc = count($list);
    $errors = 0;
    for ($i=0;$i<$lc;++$i) {
      $line = trim($list[$i]);
      if (!strlen($line)) continue; // skip empty lines
      if (substr($line,0,1)=="#") continue; // skip comments
      $item = explode(':',$line);
      if (!$this->add_genre($item[0],$item[1],$item[2])) ++$errors;
    }
#    echo "Set up genres from '$filename'. Errors: $errors<br>";
  }

  /** Sorting the genre list
   * @class iradio
   * @method sort_genres
   */
  function sort_genres() {
    ksort($this->genre);
    foreach ($this->genre as $main=>$sub) {
      ksort($this->genre[$main]);
    }
  }

  /** Get the genre list
   * @class iradio
   * @method get_genres
   * @return array genres (genre[main][sub])
   */
  function get_genres() {
    return $this->genre;
  }

  /** Retrieve the main genres (array[0..n] of genre)
   * @class iradio
   * @method get_maingenres
   * @return array array[0..n] of genres
   */
  function get_maingenres() {
    foreach($this->genre as $main=>$name) {
      $list[] = $main;
    }
    return $list;
  }

  /** Retrieve the sub genres (array[0..n] of genre)
   * @class iradio
   * @method get_subgenres
   * @param string maingenre main genre to retrieve the sub genres for
   * @return array array[0..n] of genres
   */
  function get_subgenres($maingenre) {
    foreach($this->genre[$maingenre] as $sub=>$name) {
      $list[] = $sub;
    }
    return $list;
  }

  /** Add a station to the list
   * @class iradio
   * @method add_station
   * @param string name station name
   * @param string playlist playlist URL
   * @param integer bitrate bitrate in kBPS
   * @param optional string genre exact genre name given by station, default empty
   * @param optional string type default: MP3
   * @param optional integer listeners default 0
   * @param optional integer maxlisteners default 0
   * @param optional string nowplaying what they are playing right now
   * @param optional string website website url
   * @return boolean success
   */
  function add_station($name,$playlist,$bitrate,$genre="",$type="MP3",$listeners=0,$maxlisteners=0,$nowplaying="",$website="") {
    if (empty($name)||empty($playlist)) return FALSE;
    $station->name = $name;
    $station->playlist = "http://".$this->iradiosite.$playlist;
    $station->bitrate = (int) $bitrate;
    $station->type = strtoupper($type);
    $station->listeners = $listeners;
    $station->maxlisteners = $maxlisteners;
    $station->nowplaying = $nowplaying;
    $station->website = $website;
    $this->station[] = $station;
    return TRUE;
  }

  /** Get the station list
   * @class iradio
   * @method get_station
   * @return array station (array of objects)
   * @brief structure of the returned objects:
   *  <UL><LI>name: Station Name</LI><LI>playlist: PlayList URL</LI>
   *      <LI>type: Audio Type (e.g. MP3)</LI><LI>bitrate: Bitrate (kBPS)</LI>
   *      <LI>listeners/maxlisteners: current/max listeners</LI>
   *      <LI>nowplaying: Currently played song</LI><LI>website: Stations WebSite</LI></UL>
   */
  function get_station() {
    return $this->station;
  }

  /** Retrieve page content
   * @class iradio
   * @method openpage
   * @param string url
   * @return boolean success
   */
  function openpage($url) {
    $req = new iradio_Request("");
    $req->setURL($url);
    $req->sendRequest();
    $this->page = $req->getResponseBody();
    return TRUE;
  }

}

?>
