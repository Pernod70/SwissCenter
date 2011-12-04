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

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

# ==========================================================[ Config Class ]===
/** Configuration and structure part of the iradio classes
 * @package IRadio
 * @class iradio
 */
class iradio {
  var $iradiosite;
  var $iradiotype;
  var $cache_expire;
  var $cache;

  var $numresults;
  var $mediatype;

  /** Constructor and only method of this base class.
   *  There's no need to call this yourself - you should just place your
   *  configuration data here.
   * @constructor iradio
   */
  function iradio(){
    //whether to use the cache
    $this->cache_enabled = false;
    // automatically delete cached files older than X secs
    $this->cache_expire = 3600; // 3600=1h
    // limit result list
    $this->numresults = 24; // shoutcast: 25/30/50/100
    // parameter to restrict media type
    $this->mediatype = '';
  }

  /** Set the cache expiration
   * @class iradio
   * @method set_cache
   * @param integer seconds Seconds to hold parsed station lists in cache (0 to disable caching)
   */
  function set_cache($seconds) {
    if (empty($seconds)) {
      $this->cache_enabled = FALSE;
    } else {
      $this->cache_enabled = TRUE;
      $this->cache = new cache_api_request($this->service, $seconds);
      send_to_log(6,'IRadio: Service '.$this->service.' - Caching enabled.');
      send_to_log(6,'IRadio: Cache expiration set to '.$seconds.' seconds.');
    }
  }

  /** Set max numbers of stations for result lists
   * @class iradio
   * @method set_max_results
   * @param integer num Max number of stations in result list
   */
  function set_max_results($num) {
    $this->numresults = $num;
    send_to_log(6,'IRadio: Limiting station lists to max '.$num.' entries.');
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
    if (empty($name)) return FALSE;

    if (empty($record)) { // remove cache object
      send_to_log(6,'IRadio: Empty result set. We do not cache this.');
      return TRUE;
    } else {
      $this->cache->cache($name, $record);
      send_to_log(6,'IRadio: Cached '.$name);
      return TRUE;
    }
  }

  /** Read a record set from cache
   * @class iradio
   * @method read_cache
   * @param string name Name of the cache object as given to write_cache before
   * @return mixed Cache object (or FALSE if no such object or cache disabled)
   */
  function read_cache($name) {
    if (empty($name) || !$this->cache_enabled) return FALSE;

    if (($record = $this->cache->getCached($name)) !== false) {
      if (empty($record)) {
        send_to_log(6,'IRadio: Ooops - empty record cached?');
        return FALSE;
      }
      send_to_log(6,'IRadio: Successfully read cache for '.$name);
      return $record;
    } else {
      send_to_log(6,'IRadio: Nothing cached for '.$name);
      return FALSE;
    }
  }

  /** Set the Internet Radio site to parse
   * @class iradio
   * @method set_site
   * @param string url URL without protocoll, e.g. "www.shoutcast.com"
   */
  function set_site($url) {
    $this->iradiosite = $url;
    send_to_log(6,'IRadio: Radio site set to '.$url);
  }

  /** Set the Internet Radio type id
   * @class iradio
   * @method set_type
   * @param integer type
   */
  function set_type($type) {
    $this->iradiotype = $type;
  }

# ------------------------------------------------------------[ Structures ]---
  /** Retrieve the station list
   * @class iradio
   * @method get_stations
   * @return array stations
   */
  function get_stations() {
    if (empty($this->stations)) {
      $stations = db_col_to_list('select station from iradio_stations where iradio_type='.$this->iradiotype.' order by 1');
      if (is_array($stations) && count($stations) > 0)
        foreach ($stations as $station)
          $this->stations[] = array("text" => $station,
                                    "id"   => $station);
      send_to_log(6,'IRadio: '.count($this->stations).' stations read');
    }
    send_to_log(6,'IRadio: Station list from database was requested.');
    return $this->stations;
  }

  /** Retrieve the country list
   * @class iradio
   * @method get_countries
   * @return array countries
   */
  function get_countries() {
    if (empty($this->countries)) {
      $countries = db_col_to_list('select country from iradio_countries order by 1');
      if (is_array($countries) && count($countries) > 0)
        foreach ($countries as $country)
          $this->countries[] = array("text" => $country,
                                     "id"   => $country);
      send_to_log(6,'IRadio: '.count($countries).' countries read');
    }
    send_to_log(6,'IRadio: Country list from database was requested.');
    return $this->countries;
  }

  /** Get the genre list
   * @class iradio
   * @method get_genres
   * @return array genres (genre[main][sub])
   */
  function get_genres() {
    if (empty($this->genre)) {
      $genres = db_toarray('select genre, subgenre from iradio_genres order by 1,2');
      if (is_array($genres) && count($genres) > 0)
        foreach ($genres as $genre)
          $this->genre[$genre['GENRE']][$genre['SUBGENRE']] = array("text" => $genre['SUBGENRE'],
                                                                    "id"   => $genre['SUBGENRE']);
      send_to_log(6,'IRadio: '.count($genres).' genres read');
    }
    send_to_log(6,'IRadio: Genre list from database was requested.');
    return $this->genre;
  }

  /** Retrieve the main genres (array[0..n] of genre)
   * @class iradio
   * @method get_maingenres
   * @return array array[0..n] of genres
   */
  function get_maingenres() {
    send_to_log(6,'IRadio: Maingenres requested.');
    foreach($this->genre as $main=>$name) {
      $list[] = array("text" => $this->genre[$main][$main]["text"],
                      "id"   => $this->genre[$main][$main]["id"]);
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
    send_to_log(6,'IRadio: Subgenres requested.');
    foreach($this->genre[$maingenre] as $sub=>$name) {
      $list[] = array("text" => $this->genre[$maingenre][$sub]["text"],
                      "id"   => $this->genre[$maingenre][$sub]["id"]);
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
  function add_station($name,$playlist,$bitrate,$genre='',$type='MP3',$listeners=0,$maxlisteners=0,$nowplaying='',$website='',$image='') {
    if (empty($name)||empty($playlist)) return FALSE;
    $station = new StdClass();
    $station->source = $this->iradiotype;
    $station->name = $name;
    if ( substr($playlist, 0, 4) == 'http' )
      $station->playlist = $playlist;
    else
      $station->playlist = 'http://'.$this->iradiosite.$playlist;
    $station->bitrate = (int) $bitrate;
    $station->genre = $genre;
    $station->type = strtoupper($type);
    $station->listeners = $listeners;
    $station->maxlisteners = $maxlisteners;
    $station->nowplaying = $nowplaying;
    $station->website = $website;
    $station->image = $image;
    $this->station[] = $station;
    send_to_log(8,'IRadio: Added station "'.$name.'" ('.$station->playlist.')');
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
    send_to_log(6,'IRadio: Station list was requested.');
    return $this->station;
  }

  /** Add a link to the list
   * @class iradio
   * @method add_link
   * @param string name link name
   * @param string id link id
   * @return boolean success
   */
  function add_link($name,$id) {
    if (empty($name)||empty($id)) return FALSE;
    $link = new StdClass();
    $link->name = $name;
    $link->id   = $id;
    $this->link[] = $link;
    send_to_log(8,'IRadio: Added link "'.$name.'" ('.$id.')');
    return TRUE;
  }

  /** Get the link list
   * @class iradio
   * @method get_link
   * @return array link (array of objects)
   */
  function get_link() {
    send_to_log(6,'IRadio: Link list was requested.');
    return $this->link;
  }

  /** Retrieve page content
   * @class iradio
   * @method openpage
   * @param string url
   * @return boolean success
   */
  function openpage($url) {
    send_to_log(6,'IRadio: Retrieving content from radio site ('.$url.')');
    $this->page = @file_get_contents($url);
    return TRUE;
  }

  /** Browse options for this radio type
   * @class iradio
   * @method browse_options
   * @return array options
   */
  function browse_options() {
    $browse_opts = array();
    if (count($this->get_stations()) > 0)
      $browse_opts[] = array('text'=>str('BROWSE_STATION'), 'params'=>array('by_station'=>'1') );
    $browse_opts[] = array('text'=>str('BROWSE_GENRE'),     'params'=>array('by_genre'=>'1') );
    $browse_opts[] = array('text'=>str('BROWSE_COUNTRY'),   'params'=>array('by_country'=>'1') );
    return $browse_opts;
  }

  /** Determines file type from mime-type
   * @class iradio
   * @method mime_type_decode
   * @param $mime_type
   * @return string
   */
  function mime_type_decode($mime_type) {
    switch ( $mime_type )
    {
      case 'audio/aac':
        return 'AAC';
      case 'audio/aacp':
        return 'AAC+';
      case 'audio/mpeg':
        return 'MP3';
      case 'application/ogg':
        return 'Ogg';
      case 'video/nsv':
        return 'NSV';
      default:
        return '?';
    }
  }

}
?>
