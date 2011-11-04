<?php

/** Configuration and structure part of the iradio classes
 * @package IRadio
 * @class iradio_playing
 */
class iradio_playing {

  var $playlist_url;

  /** Constructor and only method of this base class.
   *  There's no need to call this yourself - you should just place your
   *  configuration data here.
   * @constructor iradio_playing
   */
  function iradio_playing( $url = '' )
  {
    // the iradio server to use.
    $this->playlist_url = $url;
  }

  /**
   * Returns the track title currently being played by the SHOUTcast server.
   *
   * @param integer $host
   * @param integer $port
   * @return string
   */
  function shoutcast_played( $host, $port )
  {
    $info = "";
    $tracks = array();

    // Retrieve the HTML page detailing the recently played songs from the server

    send_to_log(5,'Connecting to SHOUTcast server '.$host.':'.$port);
    if (($fp = @fsockopen($host, $port, $errno, $errstr, 2)) === FALSE)
    {
      send_to_log(2,'- Connection refused to SHOUTcast server',array("Host"=>$host,"Port"=>$port));
      return false;
    }
    else
    {
      fputs($fp, "GET /played.html HTTP/1.0\r\nUser-Agent: Mozilla\r\n\r\n");
      while (!feof($fp))
        $info .= fgets($fp);
    }

    // Process the results into an array

    $info = explode('<td>',$info);
    if (count($info)<4)
    {
      send_to_log(5,'- The current track is not available');
      return false;
    }
    else
    {
      for ($i=(count($info)-6)/2; $i>0; $i--)
        array_unshift($tracks, strip_tags($info[$i*2+6]));

      array_unshift($tracks,$info[4]);
    }

    foreach ($tracks as $idx=>$track)
    {
      // Extract details from track info
      $track  = preg_replace('/(\[.*?\])/', '', $track);
      $year   = preg_get('/\((\d\d\d\d)\)/', $track);
      $track  = preg_replace('/(\(.*?\))/', '', $track);
      $title  = trim(preg_get('/.* - (.*)/', $track));
      $artist = trim(preg_get('/(.*) - /', $track));
      if (!empty($title))
        $tracks[$idx] = array("TITLE"  => $title,
                              "ARTIST" => $artist,
                              "YEAR"   => $year);
      else
        $tracks[$idx] = array("TITLE"  => $track);
    }

    // Return the results

    send_to_log(8,'Tracks returned from the SHOUTcast server',$tracks);
    return $tracks;
  }

  /**
   * Returns the track title currently being played by the Live365 server.
   *
   * @param string $handle
   * @return string
   */
  function live365_played( $handle )
  {
    $tracks = array();

    // Retrieve the XML page detailing the recently played tracks from the server

    send_to_log(5,'Retrieving playlist from Live365 station '.$handle);
    $xml = file_get_contents("http://www.live365.com/pls/front?handler=playlist&cmd=view&handle=$handle&viewType=xml");

    // Process the results into an array

    $num_entries = preg_match_all('/<PlaylistEntry>(.*)<\/PlaylistEntry>/Um', $xml, $matches);

    foreach ($matches[1] as $entry)
    {
      $title = utf8_decode(xmlspecialchars_decode(preg_get('/<Title>(.*)<\/Title>/', $entry)));
      $artist = utf8_decode(xmlspecialchars_decode(preg_get('/<Artist>(.*)<\/Artist>/', $entry)));
      $album = utf8_decode(xmlspecialchars_decode(preg_get('/<Album>(.*)<\/Album>/', $entry)));
      $image = preg_get('/<visualURL>.*img=(.*)<\/visualURL>/', $entry);
      $length = preg_get('/<Seconds>(.*)<\/Seconds>/', $entry);
      $tracks[] = array("TITLE"    => $title,
                        "ARTIST"   => $artist,
                        "ALBUM"    => $album,
                        "ALBUMART" => $image,
                        "LENGTH"   => $length);
    }

    // Return the results

    send_to_log(8,'Tracks returned from the Live365 server',$tracks);
    return $tracks;
  }

}
?>
