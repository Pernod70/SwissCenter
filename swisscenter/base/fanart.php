<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/cache_api_request.php'));
require_once( realpath(dirname(__FILE__).'/../ext/lastfm/datafeeds.php'));

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
    $local_folder = SC_LOCATION.'fanart/artists';
    if (!file_exists($local_folder)) { @mkdir($local_folder); }
    $local_folder = SC_LOCATION.'fanart/artists/'.strtolower($artist);
    if (!file_exists($local_folder)) { @mkdir($local_folder); }

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
 * Searches for and downloads an artist image from Last.FM.
 *
 * @param string $artist
 * @return string - path to downloaded image, or false if failed.
 */
function get_lastfm_artist_image( $artist )
{
  // Return if no artist provided
  if (empty($artist))
    return false;

  // Create folder for artist images
  $local_folder = SC_LOCATION.'fanart/artists';
  if (!file_exists($local_folder)) { @mkdir($local_folder); }
  $local_folder = SC_LOCATION.'fanart/artists/'.strtolower($artist);
  if (!file_exists($local_folder)) { @mkdir($local_folder); }

  // Filter original images that meet our minimum size requirements
  $image_urls = get_lastfm_artist_images( $artist, 'original', get_sys_pref('NOW_PLAYING_FANART_QUALITY',0) );

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
 * Returns an array of artist images from Last.FM.
 *
 * @param string $artist
 * @param string $size - original, small, medium, large
 * @param integer $min_size - minimum size limit
 * @return array - image URL's.
 */
function get_lastfm_artist_images( $artist, $size = 'original', $min_size = 0 )
{
  // Return if no artist provided
  if (empty($artist))
    return false;

  send_to_log(6,'Querying Last.fm Images for artist: '.$artist);

  $images = lastfm_artist_getImages($artist);
  if ( $images )
  {
    // Number of pages available
    $pages = $images["images"]["@attr"]["totalPages"];
    if ($pages == 0)
    {
      send_to_log(2,'No artist images available at Last.fm');
      return false;
    }

    // Choose random page
    $page = mt_rand(1, $pages);
    if ($page > 1)
      $images = lastfm_artist_getImages($artist, $page);

    // Manage the array of images if only a single image is returned
    if (!array_key_exists(0, $images["images"]["image"]))
      $images["images"]["image"] = array(0=>$images["images"]["image"]);

    // Filter original images that meet our minimum size requirements
    $image_urls = array();
    foreach ($images["images"]["image"] as $image)
    {
      // Find the URL of the original image
      foreach ($image["sizes"]["size"] as $img_size)
      {
        if ($img_size["name"] == $size && $img_size["width"] >= $min_size && $img_size["height"] >= $min_size)
        {
          $image_urls[] = array( "remote" => $img_size["#text"],
                                 "local"  => basename($image["url"]).'.'.file_ext($img_size["#text"]) );
          break;
        }
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
    send_to_log(2,'Failed to get response from Last.fm artist.getImages');
    return false;
  }
}
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>