<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/cache_api_request.php'));
require_once( realpath(dirname(__FILE__).'/../ext/json/json.php'));

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

  $query = utf8_encode($artist);
  $url   = 'http://ajax.googleapis.com/ajax/services/search/images'
            .'?v=1.0'
            .'&q='.str_replace('%20','+',urlencode($query))
            .'&rsz=large'
            .'&start='.rand(0,3)*8
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
    $local_folder = SC_LOCATION.'fanart/artists/'.strtolower($artist);
    if (!file_exists($local_folder)) { @mkdir($local_folder); }

    // Collect image URL's from results object
    $image_urls = array();
    foreach ($response->responseData->results as $result)
      $image_urls[] = $result->unescapedUrl;

    // Select random image from those returned
    $image_url = $image_urls[rand(0,count($image_urls)-1)];

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

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>