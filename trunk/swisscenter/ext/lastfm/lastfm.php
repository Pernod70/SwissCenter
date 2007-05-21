<?
  require_once( realpath(dirname(__FILE__).'/../../base/file.php'));

 
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
                   
      send_to_log(1,"Attempting to login with username '$username' and encrypted password '$md5_password'",$login_url);
      if ( ($response = file_get_contents($login_url)) === false)
      {
        send_to_log(1,'Failed to access the login URL');
        return false;
      }
      
      $this->session_id = $this->get_pattern('/session=(.*)\n/i',$response);
      $this->stream_url = $this->get_pattern('/stream_url=(.*)\n/i',$response);
      
      if ($this->session_id == 'FAILED')
      {
        send_to_log(1,'Authentication failed');
        return false;
      }
      
      send_to_log(1,'Successfully authenticated',array("Session"=>$this->session_id, "Stream URL"=>$this->stream_url) );
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
      $station_enc = $station;
      $tune_url = 'http://ws.audioscrobbler.com/radio/adjust.php'.
                  '?session='.$this->session_id.
                  '&url='.$station_enc.
                  '&debug=0'; 
  
      send_to_log(1,'Attempting to change station',$tune_url);            
      if ( ($response = file_get_contents($tune_url)) === false)
      {
        send_to_log(1,'Failed to access the station changing URL');
        return false;
      }
      
      if ( strpos($this->get_pattern('/response=(.*)/i',$response),'OK' === false) )
      {
        send_to_log(1,'Failed to change station.');
        return false;
      }
      else 
      {
        send_to_log(1,'Tuned into station: ',$station);
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
                   
      send_to_log(1,'Attempting to obtain now playing information',$playing_url);            
      if ( ($response = file_get_contents($playing_url)) === false)
      {
        send_to_log(1,'Failed to access the now playing URL.');
        return false;
      }
      
      send_to_log(1,'Now playing information',explode(newline(),$response));
      return explode(newline(),$response);
    }
  
    /**
     * Function to access the LastFM stream and send it on to the end user.
     *
     */
    
    function stream()
    {
      ob_end_flush();      
      send_to_log(1,'Attempting to stream',$this->stream_url);
      
      $stream = fopen($this->stream_url,'rb');
      $fbytessofar = 0; 
      if ($stream)
      {
        $file   = fopen('test.mp3','wb');
        while ( !feof($stream) && $fbytessofar < 409600)
        {
            $fbuf = fread($stream,1024);
            $fbytessofar += strlen($fbuf);

            if (strpos($fbuf,'SYNC') === false)
              fwrite($file,$fbuf);
            else 
              $track = $this->now_playing();

            echo '. ';
            flush();
        }
          
        fclose($file);
        fclose($stream);
      }
    
    }   
    
  }    
  
  
  //
  // Main Code
  //
  $time_limit = 300;
  $time_end = time()+$time_limit;
  
  set_time_limit($time_limit);
  
  $lastfm = new lastfm();
  $lastfm->login('rztaylor', md5('password') );
  $lastfm->tune_to_station( 'lastfm://globaltags/pop' );
  $lastfm->stream();
        
?>