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

require_once( realpath(dirname(__FILE__)."/iradio.php"));
require_once( realpath(dirname(__FILE__).'/../xml/xmlparser.php'));

/** Live365 Specific Parsing
 * @package IRadio
 * @class live365
 */
class live365 extends iradio {
  var $username;
  var $password;
  var $signedin;

  var $application_id;
  var $session_id;
  var $device_id;
  var $status;
  var $access;

  /** Initializing the class
   * @constructor live365
   */
  function live365() {
    $this->iradio();
    $this->set_site('www.live365.com');
    $this->set_type(IRADIO_LIVE365);
    $this->session_id = 0;
    $this->access = 'PUBLIC';

    // Login to Live365 with user credentials
    $this->username = get_user_pref('LIVE365_USERNAME');
    $this->password = get_user_pref('LIVE365_PASSWORD');
    $this->signedin = false;
    if (!empty($this->username) && !empty($this->password))
      $this->signedin = $this->login($this->username, $this->password);
  }

  /** Login to Live365 with user credentials
   * @class live365
   * @method login
   */
  function login($username, $password) {
    send_to_log(5,"Attempting to login to Live365 with username '$username'");
    $login = file_get_contents('http://'.$this->iradiosite.'/cgi-bin/api_login.cgi?action=login&remember=Y&org=live365&member_name='.$username.'&password='.$password);
    $xml = new XmlParser($login, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $login = $xml->GetData();

    $code   = $login['LIVE365_API_LOGIN_CGI']['CODE']['VALUE'];
    $reason = $login['LIVE365_API_LOGIN_CGI']['REASON']['VALUE'];

    if ( $reason == 'Success' )
    {
      $this->application_id = $login['LIVE365_API_LOGIN_CGI']['APPLICATION_ID']['VALUE'];
      $this->session_id     = $login['LIVE365_API_LOGIN_CGI']['SESSION_ID']['VALUE'];
      $this->device_id      = $login['LIVE365_API_LOGIN_CGI']['DEVICE_ID']['VALUE'];
      $this->status         = $login['LIVE365_API_LOGIN_CGI']['MEMBER_STATUS']['VALUE'];
      $this->access         = ($this->status == 'REGULAR' ? 'PUBLIC' : 'ALL');
      send_to_log(8,'IRadio: Live365 successful login:', $reason);
      return true;
    }
    else
    {
      send_to_log(8,'IRadio: Live365 failed to login:', $reason);
      return false;
    }
  }

  /** Logout of Live365
   * @class live365
   * @method logout
   */
  function logout($session_id) {
    send_to_log(5,"Attempting to logout of Live365");
    $logout = file_get_contents('http://'.$this->iradiosite.'/cgi-bin/api_login.cgi?action=logout&sessionid='.$session_id.'&org=live365');
    $xml = new XmlParser($logout, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $logout = $xml->GetData();

    $code   = $logout['LIVE365_API_LOGIN_CGI']['CODE']['VALUE'];
    $reason = $logout['LIVE365_API_LOGIN_CGI']['REASON']['VALUE'];

    if ( $reason == 'Success' )
    {
      send_to_log(8,'IRadio: Live365 successful logout:', $reason);
      return true;
    }
    else
    {
      send_to_log(8,'IRadio: Live365 failed to logout:', $reason);
      return false;
    }
  }

  /** Browse options for this radio type
   * @class live365
   * @method browse_options
   * @return array options
   */
  function browse_options() {
    $browse_opts = array();
    if (count($this->get_stations()) > 0)
      $browse_opts[] = array('text'=>str('BROWSE_STATION'),     'params'=>array('by_station'=>'1') );
    $browse_opts[] = array('text'=>str('BROWSE_GENRE'),         'params'=>array('by_genre'=>'1') );
    $browse_opts[] = array('text'=>str('BROWSE_COUNTRY'),       'params'=>array('by_country'=>'1') );
    $browse_opts[] = array('text'=>str('BROWSE_TOP_STATIONS'),  'params'=>array('by_genre'=>'1', 'maingenre'=>'All', 'subgenre'=>'All') );
    $browse_opts[] = array('text'=>str('BROWSE_FREE_STATIONS'), 'params'=>array('by_genre'=>'1', 'maingenre'=>'Free', 'subgenre'=>'Free') );
    $browse_opts[] = array('text'=>str('BROWSE_EDITORS_PICKS'), 'params'=>array('by_genre'=>'1', 'maingenre'=>'ESP', 'subgenre'=>'ESP') );
    $browse_opts[] = array('text'=>str('BROWSE_PRESETS'),       'params'=>array('by_genre'=>'1', 'maingenre'=>'Presets', 'subgenre'=>'Presets') );
    return $browse_opts;
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
      $stations = $this->read_cache($cachename.'_'.$this->username);
      if ($stations !== FALSE) {
        $this->station = $stations;
        return TRUE;
      }
    }
    if (empty($url)) return FALSE;
    $uri = 'http://'.$this->iradiosite.'/'.$url.'&sessionid='.$this->session_id;
    $this->openpage($uri);

    $xml = new XmlParser($this->page, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $directory = $xml->GetData();
    if ( isset($directory['LIVE365_DIRECTORY']['LIVE365_STATION']) )
    {
      if ( !isset($directory['LIVE365_DIRECTORY']['LIVE365_STATION'][0]) )
        $directory['LIVE365_DIRECTORY']['LIVE365_STATION'] = array($directory['LIVE365_DIRECTORY']['LIVE365_STATION']);
      $stationcount = 0;
      foreach ($directory['LIVE365_DIRECTORY']['LIVE365_STATION'] as $station)
      {
        $station_id   = $station['STATION_ID']['VALUE'];
        $title        = utf8_decode(xmlspecialchars_decode($station['STATION_TITLE']['VALUE']));
        $broadcaster  = $station['STATION_BROADCASTER']['VALUE'];
        $bitrate      = $station['STATION_CONNECTION']['VALUE'];
        $codec        = $station['STATION_CODEC']['VALUE'];
        $genre        = $station['STATION_GENRE']['VALUE'];
        $listeners    = $station['STATION_LISTENERS_ACTIVE']['VALUE'];
        $maxlisteners = $station['STATION_LISTENERS_MAX']['VALUE'];
        $status       = $station['STATION_STATUS']['VALUE'];
        $playlist     = '/cgi-bin/play.pls?stationid='.$station_id.'&broadcaster='.$broadcaster.'&sessionid='.$this->session_id.'&ext=.pls';
        $playlist     = '/play/'.$broadcaster.'&sessionid='.$this->session_id;

        if ( $status == 'OK' )
        {
          $this->add_station($title,$playlist,$bitrate,$genre,$codec,$listeners,$maxlisteners);
          ++$stationcount;
        }
        if ($stationcount == $this->numresults) break;
      }
    }
    else
    {
      send_to_log(8,'IRadio: Live365 claims to find nothing (try #'.$iteration.')');
      if ($iteration < 3) return $this->parse($url,$cachename,++$iteration);
      else return FALSE;
    }
    send_to_log(6,'IRadio: Read '.$stationcount.' stations.');
    if (!empty($cachename)) $this->write_cache($cachename.'_'.$this->username,$this->station);
    return TRUE;
  }

  /** Get the genre list
   * @class live365
   * @method get_genres
   * @return array genres (genre[main][sub])
   */
  function get_genres() {
    send_to_log(6,'IRadio: Complete genre list was requested.');
    if (empty($this->genre)) {
      $genres = $this->read_cache('genres');
      if ($genres !== FALSE) {
        $this->genre = $genres;
      } else {
        $uri = 'http://'.$this->iradiosite.'/cgi-bin/api_genres.cgi?action=get&sessionid='.$this->session_id.'&format=xml';
        $this->openpage($uri);
        # Genres
        $xml = new XmlParser($this->page, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
        $directory = $xml->GetData();
        if ( isset($directory['LIVE365_API_GENRES_CGI']['GENRES']['GENRE']) )
        {
          if ( !isset($directory['LIVE365_API_GENRES_CGI']['GENRES']['GENRE'][0]) )
            $directory['LIVE365_API_GENRES_CGI']['GENRES']['GENRE'] = array($directory['LIVE365_API_GENRES_CGI']['GENRES']['GENRE']);
          foreach ($directory['LIVE365_API_GENRES_CGI']['GENRES']['GENRE'] as $genre)
          {
            $name = utf8_decode($genre['DISPLAY_NAME']['VALUE']);
            $g_id  = utf8_decode($genre['NAME']['VALUE']);
            $p_id  = utf8_decode($genre['PARENT_NAME']['VALUE']);
            send_to_log(8,'IRadio: Got genre '.$name.' with value '.$g_id);
            if ($p_id == 'ROOT') $p_id = $g_id;
            $this->genre[$p_id][$g_id] = array("text" => $name, "id" => $g_id);
          }
          $this->write_cache('genres',$this->genre);
        }
      }
    }
    return $this->genre;
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
    send_to_log(6,'IRadio: Initialize genre search for "'.$name.'"');
    return $this->parse('cgi-bin/directory.cgi?genre='.str_replace(' ','%2B',$name).'&access='.$this->access.'&site=xml&rows='.$this->numresults,str_replace(' ','_',$name));
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
    send_to_log(6,'IRadio: Initialize station search for "'.$name.'"');
    return $this->parse('cgi-bin/directory.cgi?s_type=adv&s_match=all&s_stn='.str_replace(' ','%2B',$name).'&access='.$this->access.'&site=xml&rows='.$this->numresults,str_replace(' ','_',$name));
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
    send_to_log(6,'IRadio: Initialize country search for "'.$name.'"');
    return $this->parse('cgi-bin/directory.cgi?s_type=adv&s_match=all&s_loc='.str_replace(' ','%2B',$name).'&access='.$this->access.'&site=xml&rows='.$this->numresults,str_replace(' ','_',$name));
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
    send_to_log(6,'IRadio: Testing Live365 interface');
    $this->set_cache('');       // disable cache
    $this->set_max_results(5);  // only 5 results needed (smallest number accepted by Live365 website)
    $this->search_genre('pop'); // init search
    if (count($this->station)==0) { // work around shoutcast claiming to find nothing
      if ($iteration <3) {
        send_to_log(6,'IRadio: Live365 claims to find nothing - retry #'.$iteration);
        ++$iteration;
        return $this->test($iteration);
      } else {
        send_to_log(3,'IRadio: Live365 still claims to have no stations listed - giving up after '.$iteration.' tries.');
        return FALSE;
      }
    }
    if (empty($this->station[1]->name)) {
      send_to_log(3,'IRadio: Live365 parser returned empty station name');
      return FALSE;
    }
    $url = parse_url($this->station[1]->playlist);
    if (empty($url["host"])) {
      send_to_log(3,'IRadio: Live365 parser returned invalid playlist: No hostname given');
      return FALSE;
    } elseif (empty($url["path"]) && empty($url["query"])) {
      send_to_log(3,'IRadio: Live365 parser returned invalid playlist: No filename given');
      return FALSE;
    }
    send_to_log(8,'IRadio: Live365 parser looks OK');
    return TRUE;
  }

}

?>