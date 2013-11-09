<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/cache_api_request.php'));
require_once( realpath(dirname(__FILE__).'/../ext/lastfm/datafeeds.php'));
require_once( realpath(dirname(__FILE__).'/../resources/audio/discogs.php'));

/**
 * Searches for and downloads an artist image from Google Images.
 *
 * @param string $artist
 * @return string - path to downloaded image, or false if failed.
 */
function get_google_artist_image( $artist )
{
  // Return if no artist provided
  if (empty($artist))
    return false;

  $url   = 'http://ajax.googleapis.com/ajax/services/search/images'
            .'?v=1.0'
            .'&q='.str_replace('%20','+',urlencode($artist))
            .'&rsz=large'
            .'&start='.mt_rand(0,3)*8
            .'&safe=moderate'
            .'&imgsz=xxlarge'
            .'&as_filetype=jpg';

  send_to_log(6,'Querying Google Images for artist: '.$artist);

  // Use a cached response if available
  $cache = new cache_api_request('google', 3600);
  if ( !($response = $cache->getCached($url)) )
  {
    $response = json_decode( file_get_contents($url) );
    if ( $response->responseStatus == 200 )
    {
      // Cache the response
      $cache->cache($url, $response);
    }
  }
  else
  {
    send_to_log(6,'- Using cached response');
  }

  if ( $response->responseStatus == 200 )
  {
    // Create folder for artist images
    $oldumask = umask(0);
    $local_folder = SC_LOCATION.'fanart/artists';
    if (!file_exists($local_folder)) { @mkdir($local_folder,0777); }
    $local_folder = SC_LOCATION.'fanart/artists/'.filename_safe(mb_strtolower($artist));
    if (!file_exists($local_folder)) { @mkdir($local_folder,0777); }

    // Collect image URL's from results object
    $image_urls = array();
    foreach ($response->responseData->results as $result)
      $image_urls[] = $result->unescapedUrl;

    // Select random image from those returned
    $image_url = $image_urls[mt_rand(0,count($image_urls)-1)];

    // Download image
    if ( file_download_and_save( $image_url, $local_folder.'/'.basename($image_url) ) )
      return $local_folder.'/'.basename($image_url);
    else
      return false;
  }
  else
  {
    send_to_log(2,'Failed to get response from Google Images',$response->responseDetails);
    return false;
  }
}

/**
 * Searches for and downloads a random artist image from Discogs.
 *
 * @param string $artist
 * @return string - path to downloaded image, or false if failed.
 */
function get_discogs_artist_image( $artist )
{
  // Return if no artist provided
  if (empty($artist))
    return false;

  // Create folder for artist images
  $oldumask = umask(0);
  $local_folder = SC_LOCATION.'fanart/artists';
  if (!file_exists($local_folder)) { @mkdir($local_folder,0777); }
  $local_folder = SC_LOCATION.'fanart/artists/'.filename_safe(mb_strtolower($artist));
  if (!file_exists($local_folder)) { @mkdir($local_folder,0777); }
  umask($oldumask);

  // Filter original images that meet our minimum size requirements
  $image_urls = get_discogs_artist_images( $artist, 'original', get_sys_pref('NOW_PLAYING_FANART_QUALITY',0) );

  if ( !empty($image_urls) )
  {
    // Select random image from those returned
    $image = $image_urls[mt_rand(0,count($image_urls)-1)];

    // Download image
    if ( file_download_and_save( $image["remote"], $local_folder.'/'.$image["local"] ) )
      return $local_folder.'/'.$image["local"];
    else
      return false;
  }
  else
  {
    return false;
  }
}

/**
 * Returns an array of artist images from Discogs.
 *
 * @param string $artist
 * @param string $size - original, thumb
 * @param integer $min_size - minimum size limit
 * @return array - image URL's.
 */
function get_discogs_artist_images( $artist, $size = 'original', $min_size = 0 )
{
  // Return if no artist provided
  if (empty($artist))
    return false;

  send_to_log(6,'Querying Discogs Images for artist: '.$artist);

  $discogs = new Discogs();
  $results = $discogs->search($artist, 'artist');

  // Search results for best match
  $titles = array ();
  foreach ($results['results'] as $index => $result)
    $titles[$index] = $result['title'];
  $index = best_match($artist, $titles, $accuracy);

  if ($index !== false)
  {
    $artist = $discogs->artist($results['results'][$index]['id']);

    // Number of images available
    if (count($artist['images']) == 0)
    {
      send_to_log(2,'No artist images available at Discogs');
      return false;
    }

    // Filter original images that meet our minimum size requirements
    $image_urls = array();
    foreach ($artist['images'] as $image)
    {
      if ($size == 'thumb' || ($image['width'] >= $min_size && $image['height'] >= $min_size))
      {
        if ($size == 'original')
          $image_urls[] = array( "remote" => $image['uri'],
                                 "local"  => basename($image['uri']) );
        else
          $image_urls[] = array( "remote" => $image['uri150'],
                                 "local"  => basename($image['uri']) );
      }
    }

    if ( empty($image_urls) )
    {
      send_to_log(2,'No artist images met minimum size requirements');
      return false;
    }
    else
    {
      return $image_urls;
    }
  }
  else
  {
    send_to_log(2,'Failed to find artist at Discogs');
    return false;
  }
}
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>