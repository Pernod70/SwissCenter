<?
  require_once( realpath(dirname(__FILE__).'/../../base/page.php'));

  require_once( realpath(dirname(__FILE__).'/../../base/file.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/prefs.php'));

  class scrobble
  {
    var $session_id;
    var $submission_url;
    var $now_playing_url;
    var $client_id = 'tst'; // Change to ssc for Swisscenter
    var $client_version = '1.0';
    
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
    
    function submit( $started_playing, $artist, $track, $album, $track_no, $length)
    {
      $data = 's='.$this->session_id.
              '&a[0]='.urlencode($artist).
              '&t[0]='.urlencode($track).
              '&i[0]='.urlencode($started_playing).
              '&o[0]=P'.
              '&r[0]='.
              '&l[0]='.urlencode($length).
              '&b[0]='.urlencode($album).
              '&n[0]='.urlencode($track_no).
              '&m=';

      if ( !empty($this->submission_url) )
      {
        send_to_log(8,'Attempting to communicate with the Last.FM server',array('URL'=>$this->submission_url, 'Params'=>$data));
        $response = http_post( $this->submission_url, $data);
        send_to_log(8,'Results',$response);
        
        if (strpos($response,'OK') !== false)
          return true;
        elseif (strpos($response,'BADSESSION') !== false)
          return false;
        elseif (strpos($response,'FAILED') !== false)
          return false;
        else 
          return false;
      }
      else 
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
              '&a='.urlencode($artist).
              '&t='.urlencode($track).
              '&b='.urlencode($album).
              '&l='.urlencode($length).
              '&n='.urlencode($track_no).
              '&m=';

      if ( !empty($this->now_playing_url) )
      {
        send_to_log(8,'Attempting to communicate with the Last.FM server',array('URL'=>$this->now_playing_url, 'Params'=>$data));
        $response = http_post( $this->now_playing_url, $data);
        send_to_log(8,'Results',$response);
        
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

  // testing
  $obj = new scrobble();
  $obj->handshake( get_user_pref('LASTFM_USERNAME'), get_user_pref('LASTFM_PASSWORD') ); 
  
  $obj->submit( gmt_time()-240, 'Madonna','Dear Jessie','Like A Prayer', '', '');

?>