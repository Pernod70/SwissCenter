<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

// API key registered to SwissCenter project
define('AUDIODB_API_KEY', '537769737343656e746572');

class TheAudioDB {
  private $cache_expire = 3600;
  private $cache;
  private $response;
  private $site;

  function TheAudioDB ($service = 'theaudiodb')
  {
    $this->service = $service;
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
    $this->site = 'http://www.'.$service.'.com';
  }

  /**
   * Send the request to get the HTML page contents.
   *
   * @param $url
   * @return string
   */
  private function request($url)
  {
    // Sends a request to TheAudioDB
    send_to_log(6,'TheAudioDB API request',$url);
    if (!($this->response = $this->cache->getCached($url))) {
      if (($this->response = file_get_contents($url)) !== false) {
        $this->cache->cache($url, $this->response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $url);
        return false;
      }
    }
    return true;
  }


  /**
   * Return Artist details from artist name
   *
   * @param string $artist
   */
  public function artistSearchArtist($artist) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/search.php?s='.rawurlencode($artist);
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return all Album details from artist name
   *
   * @param string $artist
   */
  public function artistSearchAlbums($artist) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/searchalbum.php?s='.rawurlencode($artist);
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return single album details from artist/album name
   *
   * @param string $artist
   * @param string $album
   */
  public function artistSearchAlbum($artist, $album) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/searchalbum.php?s='.rawurlencode($artist).'&a='.rawurlencode($album);
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return track details from artist/track name
   *
   * @param string $artist
   * @param string $track
   */
  public function artistSearchTrack($artist, $track) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/searchtrack.php?s='.rawurlencode($artist).'&t='.rawurlencode($track);
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return single Music DVD from artist/mdvd name
   *
   * @param string $artist
   * @param string $dvd
   */
  public function artistSearchDVD($artist, $dvd) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/searchmdvd.php?s='.rawurlencode($artist).'&a='.rawurlencode($dvd);
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual Artist details using known TADB_Artist_ID
   *
   * @param integer $artistId
   */
  public function artistData($artistId) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/artist.php?i='.$artistId;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual Artist info using a known MusicBrainz_Artist_ID
   *
   * @param string $mb_artist_id
   */
  public function mbArtistData($mb_artist_id) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/artist-mb.php?i='.$mb_artist_id;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return All Albums for an Artist using known TADB_Artist_ID
   *
   * @param integer $artistId
   */
  public function artistAlbumData($artistId) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/album.php?i='.$artistId;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual Album info using known TADB_Album_ID
   *
   * @param integer $albumId
   */
  public function albumData($albumId) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/album.php?m='.$albumId;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual Album info using a known MusicBrainz_Release-Group_ID
   *
   * @param string $mb_release_group_id
   */
  public function mbAlbumData($mb_release_group_id) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/album-mb.php?i='.$mb_release_group_id;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return All Tracks for Album from known TADB_Album_ID
   *
   * @param integer $albumId
   */
  public function albumTrackData($albumId) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/track.php?m='.$albumId;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual track info using a known TADB_Track_ID
   *
   * @param integer $trackId
   */
  public function trackData($trackId) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/track.php?i='.$trackId;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual track info using a known MusicBrainz_Recording_ID
   *
   * @param string $mb_recording_id
   */
  public function mbTrackData($mb_recording_id) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/mb-track.php?i='.$mb_recording_id;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }

  /**
   * Return individual track Mvid Names and Youtube Links for a known TADB_Artist_ID
   *
   * @param integer $artistId
   */
  public function musicVideos($artistId) {
    $url = 'http://www.theaudiodb.com/api/v1/json/'.AUDIODB_API_KEY.'/mvid.php?i='.$artistId;
    if ( $this->request($url) ) {
      return json_decode($this->response, true);
    } else {
      return false;
    }
  }
}

function tadb_artist_getInfo($artist)
{
  $result = false;
  if (internet_available() && !empty($artist))
  {
    $tadb = new TheAudioDB();
    $data = $tadb->artistSearchArtist(utf8_encode($artist));
    if (isset($data['artists'][0]['artist']))
      $result = $data['artists'][0]['artist'];
  }
  return $result;
}

function tadb_album_getInfo($artist, $album)
{
  $result = false;
  if (internet_available() && !empty($artist) && !empty($album))
  {
    $tadb = new TheAudioDB();
    // Remove any edition details (in brackets) from the album name, ie. (Deluxe Edition)
    $album = trim(preg_replace('/\(.*?\)/', '', $album, 1));
    $data = $tadb->artistSearchAlbum(utf8_encode($artist), utf8_encode($album));
    if (isset($data['album'][0]['album']))
      $result = $data['album'][0]['album'];
  }
  return $result;
}

function tadb_track_getInfo($artist, $track)
{
  $result = false;
  if (internet_available() && !empty($artist) && !empty($track))
  {
    $tadb = new TheAudioDB();
    $data = $tadb->artistSearchTrack(utf8_encode($artist), utf8_encode($track));
    if (isset($data['track'][0]['track']))
      $result = $data['track'][0]['track'];
  }
  return $result;
}

function tadb_artist_videos($artist, $album, $track)
{
  $result = false;
  if (internet_available() && !empty($artist))
  {
    $tadb = new TheAudioDB();
    $data = tadb_artist_getInfo($artist);
    if (isset($data['idArtist']))
    {
      $artistId = $data['idArtist'];
      $mvids = $tadb->musicVideos($artistId);
      if (isset($mvids['mvids']))
      {
        // Filter videos for requested album
        $data = tadb_album_getInfo($artist, $album);
        if (isset($data['idAlbum']))
        {
          $albumId = $data['idAlbum'];
          foreach ($mvids['mvids'] as $id=>$mvid)
          {
            if ($mvid['track']['idAlbum'] !== $albumId)
              unset($mvids['mvids'][$id]);
          }
        }
        // Filter videos for requested track
        if (!empty($track))
        {
          foreach ($mvids['mvids'] as $id=>$mvid)
          {
            if ($mvid['track']['strTrack'] !== $track)
              unset($mvids['mvids'][$id]);
          }
        }
        $result = $mvids['mvids'];
      }
    }
  }
  return $result;
}
?>