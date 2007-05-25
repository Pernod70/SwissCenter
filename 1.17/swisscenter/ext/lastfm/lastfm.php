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
                   
      send_to_log(1,"Attempting to login with username '$username' and encrypted password '$md5_password'");
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
      $station_enc = str_replace(' ','%20',strtolower($station));
      $tune_url = 'http://ws.audioscrobbler.com/radio/adjust.php'.
                  '?session='.$this->session_id.
                  '&url='.$station_enc.
                  '&debug=0'; 
  
      send_to_log(1,'Attempting to change station: '.$station);            
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
        send_to_log(1,'Tuned into station: ');
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
      $time = time();
      $playing_url='http://ws.audioscrobbler.com/radio/np.php'.
                   '?session='.$this->session_id.
                   '&debug=0';
                   
      send_to_log(1,'Attempting to obtain now playing information');            

      for ($i=1; $i<=5; $i++)
      {
          if ( ($response = file_get_contents($playing_url)) === false)
          {
            send_to_log(1,'Failed to access the now playing URL.');
            return false;
          }
          elseif ($this->get_pattern('/streaming=(.*)\n?/i',$response) == "false")
          {
            send_to_log(5,'Attempt '.$i.': LastFM is not streaming (or is unavailable).');
            sleep(2);            
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
      
      send_to_log(1,'Now playing information',$data);
      
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
      
      send_to_log(1,'Attempting to stream',$this->stream_url);
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
            $fbuf = fread($stream,32*1024);
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
    }       
  }    
  
?>