<?php
 #############################################################################
 # IRadioPHP                                             (c) Itzchak Rehberg #
 # Internet Radio Site parsing utility by Itzchak Rehberg & IzzySoft         #
 # http://www.qumran.org/homes/izzy/                                         #
 # ------------------------------------------------------------------------- #
 # This is the Live365 parser                                              #
 # ------------------------------------------------------------------------- #
 # This program is free software; you can redistribute and/or modify it      #
 # under the terms of the GNU General Public License (see doc/LICENSE)       #
 #############################################################################

require_once(dirname(__FILE__)."/iradio.php");

/** Live365 Specific Parsing
 * @package IRadio
 * @class live365
 */
class live365 extends iradio {
  var $username;
  var $application_id;
  var $session_id;
  var $device_id;
  var $status;
  var $access;

  /** Initializing the class
   * @constructor shoutcast
   */
  function live365() {
    $this->iradio();
    $this->set_site("www.live365.com");
    $this->set_type(IRADIO_LIVE365);
  }

  /** Login to Live365 with user credentials
   * @class live365
   * @method login
   */
  function login() {
    $this->username = get_user_pref('LIVE365_USERNAME');
    $password = get_user_pref('LIVE365_PASSWORD');
    $login    = file_get_contents("http://".$this->iradiosite."/cgi-bin/api_login.cgi?action=login&remember=Y&org=live365&member_name=$this->username&password=$password");
    $code     = preg_get('/<Code>(.*)<\/Code>/U', $login);
    $reason   = preg_get('/<Reason>(.*)<\/Reason>/U', $login);

    if ( $reason == 'Success' )
    {
      $this->application_id = preg_get('/<Application_ID>(.*)<\/Application_ID>/Ui', $login);
      $this->session_id     = preg_get('/<Session_ID>(.*)<\/Session_ID>/Ui', $login);
      $this->device_id      = preg_get('/<Device_ID>(.*)<\/Device_ID>/Ui', $login);
      $this->status         = preg_get('/<Member_Status>(.*)<\/Member_Status>/Ui', $login);
      $this->access         = ($this->status == 'REGULAR' ? 'PUBLIC' : 'ALL');
    }
    else
    {
      send_to_log(8,"IRadio: Live365 failed to login", $reason);
    }
  }

  /** Parse Live365 result page and store stations using add_station()
   * @class live365
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
    $uri = "http://".$this->iradiosite."/$url";
    $this->openpage($uri);
    $stationcount = 0;
    $spos = strpos($this->page,"<LIVE365_STATION>"); // seek for start position of block
    if ($startpos===FALSE) {
      send_to_log(8,"IRadio: Live365 claims to find nothing (try #$iteration)");
      if ($iteration < 3) return $this->parse($url,$cachename,++$iteration);
      else return FALSE;
    }
    $epos = $spos +1; // prevent endless loop on broken pages
    while ($spos) {
      $epos  = strpos($this->page,'</LIVE365_STATION>',$spos);
      $block = substr($this->page,$spos,$epos - $spos);
      $station_id   = preg_get('/<STATION_ID>(.*)<\/STATION_ID>/U', $block);
      $title        = utf8_decode(preg_get('/<STATION_TITLE><!\[CDATA\[(.*)\]\]><\/STATION_TITLE>/U', $block));
      $broadcaster  = preg_get('/<STATION_BROADCASTER>(.*)<\/STATION_BROADCASTER>/U', $block);
      $bitrate      = preg_get('/<STATION_CONNECTION>(.*)<\/STATION_CONNECTION>/U', $block);
      $codec        = preg_get('/<STATION_CODEC>(.*)<\/STATION_CODEC>/U', $block);
      $genre        = preg_get('/<STATION_GENRE><![CDATA[(.*)]]><\/STATION_GENRE>/U', $block);
      $listeners    = preg_get('/<STATION_LISTENERS_ACTIVE>(.*)<\/STATION_LISTENERS_ACTIVE>/U', $block);
      $maxlisteners = preg_get('/<STATION_LISTENERS_MAX>(.*)<\/STATION_LISTENERS_MAX>/U', $block);
      $status       = preg_get('/<STATION_STATUS>(.*)<\/STATION_STATUS>/U', $block);

      $playlist     = "/cgi-bin/play.pls?stationid=".$station_id."&broadcaster=".$broadcaster."&membername=demo_afl&filename.pls";

      if ( $status == 'OK' && $codec !== 'mp3PRO' )
      {
        $this->add_station($title,$playlist,$bitrate,$genre,$codec,$listeners,$maxlisteners);
        ++$stationcount;
      }
      if ($stationcount == $this->numresults) break;
      $spos = strpos($this->page,"<LIVE365_STATION>",$epos);
    }
    send_to_log(6,"IRadio: Read $stationcount stations.");
    if (!empty($cachename)) $this->write_cache($cachename,$this->station);
    return TRUE;
  }

  /** Searching for a genre
   *  Initiates a genre search at the Live365 site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class live365
   * @method search_genre
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_genre($name) {
    send_to_log(6,"IRadio: Initialize genre search for \"$name\"");
    return $this->parse("cgi-bin/directory.cgi?s_type=adv&s_match=all&s_genre=".str_replace(' ','+',$name)."&access=PUBLIC&site=xml&rows=".$this->numresults,str_replace(' ','_',$name));
  }

  /** Searching for a station
   *  Initiates a freetext search at the Live365 site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class live365
   * @method search_station
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_station($name) {
    send_to_log(6,"IRadio: Initialize station search for \"$name\"");
    return $this->parse("cgi-bin/directory.cgi?s_type=adv&s_match=all&s_stn=".str_replace(' ','+',$name)."&access=PUBLIC&site=xml&rows=".$this->numresults,str_replace(' ','_',$name));
  }

  /** Searching by country
   *  Initiates a genre search at the Live365 site and stores all returned
   *  stations using iradio::add_station (use get_station() to retrieve
   *  the results). Returns FALSE on error (or if no stations found), TRUE
   *  otherwise.
   * @class live365
   * @method search_country
   * @param string name
   * @return boolean success FALSE on error or nothing found, TRUE otherwise
   */
  function search_country($name) {
    send_to_log(6,"IRadio: Initialize country search for \"$name\"");
    return $this->parse("cgi-bin/directory.cgi?s_type=adv&s_match=all&s_loc=".str_replace(' ','+',$name)."&access=PUBLIC&site=xml&rows=".$this->numresults,str_replace(' ','_',$name));
  }

  /** Test parser functionality
   *  This method makes a simple request for maximum 2 stations of the genre
   *  "pop" (there are always plenty of stations available), and then checks
   *  the second station returned whether it has at least a name and a valid
   *  playlist URL. If so, it returns TRUE - otherwise FALSE.
   * @class live365
   * @method test
   * @return boolean OK
   */
  function test($iteration=1) {
    send_to_log(6,"IRadio: Testing Live365 interface");
    $this->set_cache("");       // disable cache
    $this->set_max_results(5);  // only 5 results needed (smallest number accepted by Live365 website)
    $this->search_genre("pop"); // init search
    if (count($this->station)==0) { // work around shoutcast claiming to find nothing
      if ($iteration <3) {
        send_to_log(6,"IRadio: Live365 claims to find nothing - retry #$iteration");
        ++$iteration;
        return $this->test($iteration);
      } else {
        send_to_log(3,"IRadio: Live365 still claims to have no stations listed - giving up after $iteration tries.");
        return FALSE;
      }
    }
    if (empty($this->station[1]->name)) {
      send_to_log(3,"IRadio: Live365 parser returned empty station name");
      return FALSE;
    }
    $url = parse_url($this->station[1]->playlist);
    if (empty($url["host"])) {
      send_to_log(3,"IRadio: Live365 parser returned invalid playlist: No hostname given");
      return FALSE;
    } elseif (empty($url["path"]) && empty($url["query"])) {
      send_to_log(3,"IRadio: Live365 parser returned invalid playlist: No filename given");
      return FALSE;
    }
    send_to_log(8,"IRadio: Live365 parser looks OK");
    return TRUE;
  }

}

?>