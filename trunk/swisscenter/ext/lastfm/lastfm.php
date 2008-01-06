<?php
  require_once( realpath(dirname(__FILE__).'/../../base/file.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/urls.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/prefs.php')); 
 
  /**
   * Class to tune to a Last.FM radio station.
   *
   */
  
  class lastfm
  {
    var $session_id;
    var $stream_url;

    /**
     * Constructor
     *
     * @param string $username
     * @param string $md5_password
     * @return lastfm
     */
    
    function lastfm( )
    {
    }
    
    /**
     * Utility function to search for a value in a LastFM response
     *
     * @param string $pattern - pattern to search for
     * @param string $text - text to search
     * @return string - text founc
     */
    
    function get_pattern($pattern, $text)
    {
      $matches = array();
      if ( preg_match($pattern,$text,$matches) == 1)
        return $matches[1];
      else 
        return '';    
    }
    
    /**
     * Sends a login request to LastFM and processes the result
     *
     * @return boolean - TRUE if the login succeeded, false otherwise.
     */

    function login($username, $md5_password)
    {
      $login_url = 'http://ws.audioscrobbler.com/radio/handshake.php'.
                   '?version=1.1.1'.
                   '&platform=windows'.
                   '&username='.$username.
                   '&passwordmd5='.$md5_password.
                   '&debug=0&partner=';
                   
      send_to_log(5,"Attempting to login with username '$username' and encrypted password '$md5_password'");
      if ( ($response = file_get_contents($login_url)) === false)
      {
        send_to_log(2,'Failed to access the login URL');
        send_to_log(8,'Response from LastFM',$response);
        return false;
      }
      
      $this->session_id = $this->get_pattern('/session=(.*)\n/i',$response);
      $this->stream_url = $this->get_pattern('/stream_url=(.*)\n/i',$response);
      
      if ($this->session_id == 'FAILED')
      {
        send_to_log(2,'Authentication failed');
        return false;
      }
      
      send_to_log(6,'Successfully authenticated',array("Session"=>$this->session_id, "Stream URL"=>$this->stream_url) );
      return true;
    }
    
    /**
     * Tunes to the specified station.
     *
     * @param string $station
     * @return boolean - true if the station was successfully changed
     */
    
    function tune_to_station($station)
    {
      $station_enc = str_replace(' ','%20',strtolower($station));
      $tune_url = 'http://ws.audioscrobbler.com/radio/adjust.php'.
                  '?session='.$this->session_id.
                  '&url='.$station_enc.
                  '&debug=0'; 
  
      send_to_log(5,'Attempting to change station: '.$station);            
      if ( ($response = file_get_contents($tune_url)) === false)
      {
        send_to_log(2,'Failed to access the station changing URL');
        return false;
      }
      
      if ( strpos($this->get_pattern('/response=(.*)/i',$response),'OK' === false) )
      {
        send_to_log(2,'Failed to change station.');
        return false;
      }
      else 
      {
        send_to_log(6,'Tuned into station: ');
        return true;
      }
    }

    /**
     * Gets information on the currently playing track
     *
     * @return mixed - An array of details when successful, false on error.
     */

    function now_playing()
    {
      $playing_url='http://ws.audioscrobbler.com/radio/np.php'.
                   '?session='.$this->session_id.
                   '&debug=0';
                   
      send_to_log(5,'Attempting to obtain now playing information');            

      for ($i=1; $i<=5; $i++)
      {
          if ( ($response = file_get_contents($playing_url)) === false)
          {
            send_to_log(2,'Failed to access the now playing URL.');
            return false;
          }
          elseif ($this->get_pattern('/streaming=(.*)\n?/i',$response) == "false")
          {
            send_to_log(6,'Attempt '.$i.': LastFM is not streaming (or is unavailable).');
            sleep(3);            
          }
          else 
            break;
      }      
      
      // parse the information      
      $data = array();
      $response = substr($response,3);
      foreach (explode("\n",$response) as $line)
      {
        $values = explode('=',$line);
        if (!empty($values[0]))
          $data[$values[0]] = $values[1];
      }
      
      send_to_log(8,'Now playing information',$data);
      
      if ( $data["streaming"] == "false")
        return false;
      else
        return $data;
    }
    
    /**
     * Function to access the LastFM stream and send it on to the end user.
     *
     * @param integer $duration - the amount of time to stream in seconds (max 24 hours)
     */
    
    function stream( $duration =  86400, $capture_dir = '', $capture_file = '' )
    {
      // headers
      header("Content-type: audio/x-mpeg");
      header('Connection: close');
           
      // Close open sessions, output buffering, etc
      ob_end_flush();      
      ignore_user_abort(FALSE);
      session_write_close();
      set_time_limit($duration+5);
      
      send_to_log(5,'Attempting to stream',$this->stream_url);
      $time_end = time()+$duration;
      
      // Open the stream
      $stream = fopen($this->stream_url,'rb');
      
      // Wait until we're streaming (and exit if we can't get a stream)
      if ( $this->now_playing() === false)
        return false;      
      
      // Are we capturing the stream?
      $capture     = (!empty($capture_dir) && is_writable($capture_dir));
      $capture_fsp = os_path($capture_dir,true).( empty($capture_file) ? date('Y-m-d_H-i-s').'.mp3' : $capture_file);
      if ($capture && $stream) 
        $file = fopen($capture_fsp,'wb');
      
      $fbytessofar = 0; 
      if ($stream)
      {          
        while ( !feof($stream) && time() <= $time_end && (connection_status() == CONNECTION_NORMAL) )
        {
            $fbuf = fread($stream,8*1024);
            $fbytessofar += strlen($fbuf);

            if ( strpos($fbuf,'SYNC') !== false)
              $fbuf=str_replace('SYNC','',$fbuf);              

            if ($capture)
              fwrite($file,$fbuf);
              
            echo $fbuf;
            flush();
        }
          
        fclose($stream);
      }
     
      // Close the file on disk if we were capturing the stream.
      if ($capture) 
        @fclose($file);    
        
      return true;
    }       

    /**
     * Returns an array of URLs pointing to pictures of the given artist. 
     *
     * @param string $artist
     * @param boolean $original - Returns either the original images (true) or thumbnails (false)
     * @return array (of URLs)
     */
    
    function artist_images( $artist, $original = false )
    {
      send_to_log(5,'Looking up artist : '.$artist);
      $pics = array();
      $matches = array();
      
      if (!empty($artist))
      {    
        // Set a timeout on the downloading of artist photos.
        ini_set('default_socket_timeout',3);    
        $html = @file_get_contents('http://www.last.fm/music/'.urlencode($artist).'/+images');
        if ($html === false)
          send_to_log(2,'Failed to access artist details on LastFM (details may not be available).');
        else
        {
          // Find all images of the artist on the page using a regular expression.
          if (preg_match_all('#<a[^>]*href="([^"]*images/[0-]*[^"]*)"[^<]*<img[^>]*src="([^"]*)"#i',$html,$matches) >0 )
          {
            for ($i=0; $i<count($matches[1]); $i++)
            {
              // Original or thumbnail image?
              if ($original)          
                $pics[] = $matches[1][$i];
              else
                $pics[] = $matches[2][$i];
            }
          }
          else 
            send_to_log(5,'No photos found for "'.$artist.'"');
        }
      }

      // Return the array of URLs
      return $pics;
    }

    
  } // End of class   

  /**
   * Class to enable scrobbling of tracks played to the Last.FM website.
   *
   */
  
  class scrobble
  {
    var $session_id;
    var $submission_url;
    var $now_playing_url;
    var $client_id = 'sce'; 
    var $client_version = '0.1';
    
    /**
     * Constructor
     *
     * @return lastfm
     */
    
    function scrobble( )
    {
    }
    
    /**
     * Utility function to search for a value in a LastFM response
     *
     * @param string $pattern - pattern to search for
     * @param string $text - text to search
     * @return string - text founc
     */
    
    function get_pattern($pattern, $text)
    {
      $matches = array();
      if ( preg_match($pattern,$text,$matches) == 1)
        return $matches[1];
      else 
        return '';    
    }
    
    /**
     * Sends a login request to LastFM and processes the result
     *
     * @return boolean - TRUE if the login succeeded, false otherwise.
     */

    function handshake($username, $md5_password)
    {
      // Clear information about the current session (if there is any)
      $this->session_id = '';
      $this->now_playing_url = '';
      $this->submission_url = '';

      // Parameters for the handshake
      $timenow = time();
      $hs_url  = 'http://post.audioscrobbler.com/'.
                 '?hs=true'.
                 '&p=1.2'.
                 '&c='.$this->client_id.
                 '&v='.$this->client_version.
                 '&u='.$username.
                 '&t='.$timenow.
                 '&a='.md5( $md5_password.$timenow);

      // Set a timeout on the handshake
      ini_set('default_socket_timeout',2);    
      
      // Attempt to handshake
      send_to_log(5,"Attempting to login with username '$username' and encrypted password '$md5_password'");      
      if ( ($response = @file_get_contents($hs_url)) === false)
      {
        send_to_log(2,'Failed to access the login URL',$hs_url);
        return false;
      }

      // Split the response by line
      $response = explode("\n",$response);
      
      // Authenticated successfully?
      if ($response[0] != 'OK')
      {
        $this->session_id = '';
        send_to_log(2,'Authentication failed', $response);
        return false;
      }
      
      // Parse the response
      $this->session_id = $response[1];
      $this->now_playing_url = $response[2];
      $this->submission_url = $response[3];      
      send_to_log(6,'Successfully authenticated',array("Session"=>$this->session_id, "Now Playing URL"=>$this->now_playing_url, "Submission URL"=>$this->submission_url) );
      return true;
    }    
     
    /**
     * Function to submit at entry to the LastFM servers.
     *
     * @param integer:timestamp $started_playing
     * @param string $artist
     * @param string $title
     * @param string $album (optional)
     * @param integer $track_no (optional)
     * @param integer $length_s (optional)
     */
    
    function submit( $started_playing, $artist, $track, $album, $length, $track_no )
    {
      // One of the Last.FM rules is that only tracks longer than 30s can be scrobbled.
      if ( $length > 30)
      {
        $data = 's='.$this->session_id.
                '&a[0]='.rawurlencode($artist).
                '&t[0]='.rawurlencode($track).
                '&i[0]='.rawurlencode($started_playing).
                '&o[0]=P'.
                '&r[0]='.
                '&l[0]='.rawurlencode($length).
                '&b[0]='.rawurlencode($album).
                '&n[0]='.rawurlencode($track_no).
                '&m[0]=';
  
        if ( !empty($this->submission_url) )
        {
          send_to_log(6,'Attempting to scrobble song',array("Artist"=>$artist, "Track"=>$track, "Album"=>$album));
          $response = http_post( $this->submission_url, $data, 1);
          
          if (strpos($response,'OK') !== false)
            return true;
          elseif (strpos($response,'BADSESSION') !== false)
            return false;
          elseif (strpos($response,'FAILED') !== false)
            return false;
          else 
            return false;
        }
      }
      
      // Unable to scrobble.
      return false;      
    }
    
    /**
     * Submits the details of a song that has just started playing to the MusicIP server.
     *
     * @param string $artist
     * @param string $track
     * @param string $album
     * @param string $length
     * @param string $track_no
     * @return boolean - true for successful submission, false otherwise.
     */
    
    function playing ( $artist, $track, $album, $length, $track_no )
    {
      $data = 's='.$this->session_id.
              '&a='.rawurlencode($artist).
              '&t='.rawurlencode($track).
              '&b='.rawurlencode($album).
              '&l='.rawurlencode($length).
              '&n='.rawurlencode($track_no).
              '&m=';

      if ( !empty($this->now_playing_url) )
      {
        send_to_log(6,'Attempting to inform Last.FM we are playing a track',array("Artist"=>$artist, "Track"=>$track, "Album"=>$album));
        $response = http_post( $this->now_playing_url, $data, 1);
        
        if (strpos($response,'OK') !== false)
          return true;
        elseif (strpos($response,'BADSESSION') !== false)
          return false;
        else 
          return false;
      }
      else 
        return false;      
    }
    
  }  

  /**
   * Returns whether or not a valid username/password has been specified, and the user
   * wishes to allow connections to Last.FM
   *
   * @return bool
   */
  
  function lastfm_enabled()
  {
    if (!internet_available())
      return false;
    elseif (get_user_pref('LASTFM_USERNAME') == '')
      return false;
    elseif (get_user_pref('LASTFM_PASSWORD') == '')
      return false;
    elseif (get_sys_pref('LASTFM_ENABLED','NO') != 'YES')
      return false;
    else 
      return true;
  }

  /**
   * Returns whether or not the user wishes to scrobble music tracks on the Last.FM website.
   *
   * @return bool
   */
  
  function lastfm_scrobble_enabled()
  {
    return ( lastfm_enabled() && get_user_pref('LASTFM_SCROBBLE','NO') == 'YES');
  }

  /**
   * Notifies the last.FM website that you have started playing a track
   *
   * @param string $artist
   * @param string $track
   * @param string $album
   * @param string $length
   * @param string $track_no
   */
  
  function lastfm_now_playing ( $artist, $track, $album, $length, $track_no )
  { 
    if (lastfm_enabled())
    {
      $obj = new scrobble();
      $obj->handshake( get_user_pref('LASTFM_USERNAME'), get_user_pref('LASTFM_PASSWORD') ); 
      $obj->playing( $artist, $track, $album, $length, $track_no );
    }
  }

  /**
   * Scrobbles the track (notifies the Last.FM website that the track has finished, and should therefore
   * be added to the user's profile.
   * 
   * Note: We have to fudge the "started playing time" because the hardware players caches the file (and so the track won't have
   *      actually finished playing even though it's completed streaming) and there's no notification from the player when the
   *      track is complete.
   *
   * @param string $artist
   * @param string $track
   * @param string $album
   * @param string $length
   * @param string $track_no
   */
  
  function lastfm_scrobble( $artist, $track, $album, $length, $track_no )
  {
    // Only tracks longer than 30s should be scrobbled.
    if (lastfm_scrobble_enabled() && $length > 30)
    {
      $obj = new scrobble();
      $obj->handshake( get_user_pref('LASTFM_USERNAME'), get_user_pref('LASTFM_PASSWORD') ); 
      $obj->submit( gmt_time()-$length, $artist, $track, $album, $length, $track_no );
    }
    
  }
?>
