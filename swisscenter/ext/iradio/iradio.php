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

if (!function_exists("send_to_log")) require_once ("logging.php");

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
    $this->cache_dir = '';
    //whether to use the cache
    $this->cache_enabled = false;
    // automatically delete cached files older than X secs
    $this->cache_expire = 3600; // 3600=1h
    // limit result list
    $this->numresults = 24; // shoutcast: 25/30/50/100
    // setup genres from ./genres.txt
    $this->read_genres();
    $this->sort_genres();
    // file naming for cache
    if (substr(php_uname(), 0, 7) == "Windows") {
      $this->os_slash = "\\";
    } else {
      $this->os_slash = "/";
    }
  }

  /** Set the cache directory
   * @class iradio
   * @method set_cache
   * @param optional string directory Cache directory (if empty or ommitted, turn cache off)
   * @return boolean success
   */
  function set_cache($dir='') {
    if (empty($dir)) {
      $this->cache_enabled = FALSE;
      return TRUE;
    }
    if (!is_dir($dir)||!is_writable($dir)) {
      $this->cache_enabled = FALSE;
      return FALSE;
    }
    $this->cache_dir = $dir;
    $this->cache_enabled = TRUE;
    send_to_log(6,"IRadio: Cache directory set to $dir - Caching enabled.");
    $this->purge_cache();
    return TRUE;
  }

  /** Set the cache expiration
   * @class iradio
   * @method set_cache_expiration
   * @param integer seconds Seconds to hold parsed station lists in cache (0 to disable caching)
   */
  function set_cache_expiration($seconds) {
    if (empty($seconds)) $this->cache_enabled = FALSE;
    $this->cache_expire = $seconds;
    send_to_log(6,"IRadio: Cache expiration set to $seconds seconds.");
  }

  /** Set max numbers of stations for result lists
   * @class iradio
   * @method set_max_results
   * @param integer num Max number of stations in result list
   */
  function set_max_results($num) {
    $this->numresults = $num;
    send_to_log(6,"IRadio: Limiting station lists to max $num entries.");
  }

  /** Purge cache dir
   * @class iradio
   * @method purge_cache
   */
  function purge_cache() {
    if (!is_dir($this->cache_dir) || !is_writable($this->cache_dir)) return;
    send_to_log(6,"IRadio: Purging cache...");
    $now = time();
    $thisdir = dir($this->cache_dir);
    while ($file=$thisdir->read()) {
      if ($file!="." && $file!="..") {
        $fname = $this->cache_dir . "/$file";
        $mod = filemtime($fname);
        if ($mod && ($now - $mod > $this->cache_expire)) {
          unlink($fname);
          send_to_log(8,"IRadio: Removing outaged file \"$fname\" from cache.");
        }
      }
    }
  }

  /** Write a record set to cache
   * @class iradio
   * @method write_cache
   * @param string name Name of the cache object
   * @param optional mixed record Cache object (ommit to remove from cache)
   * @return boolean success
   */
  function write_cache($name,$record='') {
    if (!$this->cache_enabled) return TRUE;
    $this->purge_cache();
    if (empty($name)) return FALSE;
    $filename = $this->cache_dir .$this->os_slash.$name;
    if (empty($record)) { // remove cache object
      send_to_log(6,"IRadio: Empty result set. We do not cache this.");
      if (file_exists($filename)) return unlink($filename);
      else return TRUE;
    }
    if (!$file = fopen($filename,'w')) {
      send_to_log(3,"IRadio: Unable to open cache file $file for writing! Check file/directory permissions.");
      return FALSE;
    }
    if (fwrite($file,serialize($record))===FALSE) {
      fclose($file);
      send_to_log(3,"IRadio: Could not write record to cache file $file!");
      return FALSE;
    }
    send_to_log(6,"IRadio: Cached $filename");
    return fclose($file);
  }

  /** Read a record set from cache
   * @class iradio
   * @method read_cache
   * @param string name Name of the cache object as given to write_cache before
   * @return mixed Cache object (or FALSE if no such object or cache disabled)
   */
  function read_cache($name) {
    if (!$this->cache_enabled) return FALSE;
    $this->purge_cache();
    if (empty($name)) return FALSE;
    $filename = $this->cache_dir .$this->os_slash.$name;
    if (!file_exists($filename)) {
      send_to_log(6,"IRadio: Nothing cached for $name ($filename does not exist)");
      return FALSE;
    }
    if (!$file = @fopen($filename,'r')) {
      send_to_log(3,"IRadio: Error opening cache file \"$filename\" - wrong permissions!");
      return FALSE;
    }
    $object = fread($file,filesize($filename));
    $record = unserialize($object);
    if (empty($record)) {
      send_to_log(6,"IRadio: Ooops - empty record cached?");
      return FALSE;
    }
    send_to_log(6,"IRadio: Successfully read cache for $name from \"$filename\"");
    return $record;
  }

  /** Set the Internet Radio site to parse
   * @class iradio
   * @method set_site
   * @param string url URL without protocoll, e.g. "www.shoutcast.com"
   */
  function set_site($url) {
    $this->iradiosite = $url;
    send_to_log(6,"IRadio: Radio site set to $url");
  }

# ------------------------------------------------------------[ Structures ]---
  /** Read the list of countries
   * @class iradio
   * @method read_countries
   * @param optional string filename
   * @return boolean success
   */
  function read_countries($filename="") {
    if (empty($filename)) $filename = dirname(__FILE__)."/countries.txt";
    if (!file_exists($filename)) return FALSE;
    send_to_log(6,"IRadio: Reading country list from \"$filename\"");
    $list = file($filename);
    $lc = count($list);
    $errors = 0;
    for ($i=0;$i<$lc;++$i) {
      $line = trim($list[$i]);
      if (!strlen($line)) continue; // skip empty lines
      if (substr($line,0,1)=="#") continue; // skip comments
      $this->countries[] = $line;
    }
    send_to_log(6,"IRadio: ".count($this->countries)." countries read");
  }

  /** Retrieve the country list
   * @class iradio
   * @method get_countries
   * @return array countries
   */
  function get_countries() {
    if (empty($this->countries)) {
      $this->read_countries();
      sort($this->countries);
    }
    send_to_log(6,"IRadio: Country list was requested - sending it.");
    return $this->countries;
  }

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
    send_to_log(8,"IRadio: Adding $sub to main genre $main");
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
    send_to_log(6,"IRadio: Reading genres from \"$filename\"");
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
    send_to_log(6,"IRadio: Finished genre setup. Errors: $errors");
  }

  /** Sorting the genre list
   * @class iradio
   * @method sort_genres
   */
  function sort_genres() {
    send_to_log(6,"IRadio: Sorting genre list");
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
    send_to_log(6,"IRadio: Complete genre list was requested.");
    return $this->genre;
  }

  /** Retrieve the main genres (array[0..n] of genre)
   * @class iradio
   * @method get_maingenres
   * @return array array[0..n] of genres
   */
  function get_maingenres() {
    send_to_log(6,"IRadio: Maingenres requested.");
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
    send_to_log(6,"IRadio: Subgenres requested.");
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
    send_to_log(8,"IRadio: Added station \"$name\" ($station->playlist)");
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
    send_to_log(6,"IRadio: Station list was requested.");
    return $this->station;
  }

  /** Retrieve page content
   * @class iradio
   * @method openpage
   * @param string url
   * @return boolean success
   */
  function openpage($url) {
    send_to_log(6,"IRadio: Retrieving content from radio site ($url)");
    $this->page = file_get_contents($url);
    return TRUE;
  }

}

?>
