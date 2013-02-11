<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

define('APPLE_TRAILERS_URL','http://trailers.apple.com');

class AppleTrailers {
  private $service = 'apple_trailers';
  private $cache_expire = 3600;
  private $cache;

  function AppleTrailers ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  /**
   * Return requested feed.
   *
   * @param string $feed
   * @return array
   */
  function getFeed ($feed)
  {
    // Form the request URL
    $request = APPLE_TRAILERS_URL.'/trailers/home/feeds/'.$feed.'.json';

    //Sends a request to Apple
    send_to_log(6,'Apple feed request', $request);

    if (!($body = $this->cache->getCached($request))) {
      if (($body = file_get_contents($request)) !== false) {
        // Remove Callback from response body
        if ( strpos($body, 'searchCallback') !== false )
          $body = preg_get('/"results":(.*)}/', $body);
        // Decode response
        $body = unicode_decode($body);
        $this->cache->cache($request, $body);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return json_decode($body, true);
  }

  /**
   * Return results of query.
   *
   * @param string $query
   * @return array
   */
  function quickFind ($query)
  {
    // Remove anything within brackets from query
    $query = preg_replace('/\(.*?\)/', '', $query);

    // Form the request URL
    $request = APPLE_TRAILERS_URL.'/trailers/home/scripts/quickfind.php?callback=searchCallback&q='.rawurlencode($query);

    //Sends a request to Apple
    send_to_log(6,'Apple feed request', $request);

    if (!($body = $this->cache->getCached($request))) {
      if (($body = file_get_contents($request)) !== false) {
        // Remove Callback from response body
        if ( strpos($body, 'searchCallback') !== false )
          $body = preg_get('/"results":(.*)}/', $body);
        // Decode response
        $body = unicode_decode($body);
        $this->cache->cache($request, $body);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }

    // Fix poster url's
    $response = json_decode($body, true);
    for ($i=0; $i<count($response); $i++)
      if (strpos($response[$i]["poster"], 'http') !== 0)
        $response[$i]["poster"] = APPLE_TRAILERS_URL.$response[$i]["poster"];
    return $response;
  }
}

/**
 * Return array of available genres.
 *
 * @return array
 */
function get_apple_trailers_genres()
{
  $apple = new AppleTrailers();
  $trailers = $apple->getFeed('genres');

  $genres = array();
  foreach ($trailers as $trailer)
  {
    $genre = $trailer['genre'][0];
    if ( count($genres) == 0 || $genres[count($genres)-1]["title"] !== $genre )
      $genres[] = array("title"=>$genre, "url"=>'apple_trailer_browse.php?genre='.rawurlencode($genre));
  }
  return $genres;
}

/**
 * Return array of trailers for selected genre.
 *
 * @param string $genre
 * @return array
 */
function get_apple_trailers_by_genre($genre)
{
  $apple = new AppleTrailers();
  $trailers = $apple->getFeed('genres');

  foreach ($trailers as $id=>$trailer)
  {
    if ( $trailer['genre'][0] !== $genre )
      unset($trailers[$id]);
  }
  return $trailers;
}

/**
 * Return array of available studios.
 *
 * @return array
 */
function get_apple_trailers_studios()
{
  $apple = new AppleTrailers();
  $trailers = $apple->getFeed('studios');

  $studios = array();
  foreach ($trailers as $trailer)
  {
    $studio = $trailer['studio'];
    if ( count($studios) == 0 || $studios[count($studios)-1]["title"] !== $studio )
      $studios[] = array("title"=>$studio, "url"=>'apple_trailer_browse.php?studio='.rawurlencode($studio));
  }
  return $studios;
}

/**
 * Return array of trailers for selected studio.
 *
 * @param string $studio
 * @return array
 */
function get_apple_trailers_by_studio($studio)
{
  $apple = new AppleTrailers();
  $trailers = $apple->getFeed('studios');

  foreach ($trailers as $id=>$trailer)
  {
    if ( $trailer['studio'] !== $studio )
      unset($trailers[$id]);
  }
  return $trailers;
}

/**
 * Return description of movie from either itunes xml or trailer page meta tags.
 *
 * @param array $trailer
 * @return string
 */
function get_trailer_description($trailer)
{
  // Remove incorrect encoding of ` from 'location'
  $trailer["location"] = str_replace('u2019','',$trailer["location"]);

  $urltype = isset($trailer["urltype"]) ? $trailer["urltype"] : 'html';
  switch ( $urltype )
  {
    case 'html':
      // Get meta tags from trailer page
      send_to_log(6,'Retrieving trailer description from meta tags',$trailer["location"]);
      $meta = get_meta_tags(APPLE_TRAILERS_URL.$trailer["location"]);
      $description = $meta['description'];
      break;

    case 'itunes':
      // Get the iTunes index.xml containing trailer details
      send_to_log(6,'Retrieving trailer description from index.xml',$trailer["location"]);
      $itunes_url = 'http'.substr_between_strings($trailer["location"], 'url=itms', '.xml').'.xml';
      $xml = file_get_contents($itunes_url);
      $description = substr_between_strings($xml, 'DESCRIPTION', '</TextView>');
      break;
  }
  return $description;
}

/**
 * Return array of trailer titles from an iTunes index.xml.
 *
 * @param array $trailer
 * @return array
 */
function get_trailer_index($trailer)
{
  // Remove incorrect encoding of ` from 'location'
  $trailer["location"] = str_replace('u2019','',$trailer["location"]);

  // Form URL of iTunes XML
  $urltype = isset($trailer["urltype"]) ? $trailer["urltype"] : 'html';
  switch ( $urltype )
  {
    case 'html':
      // The location of http://www.apple.com/moviesxml/s/../../index.xml is not known so
      // need to check various possible locations.

      // Check most likely location
      $index_url = APPLE_TRAILERS_URL.'/moviesxml/s'.str_replace('/trailers','',$trailer["location"]).'index.xml';
      if (url_exists($index_url)) { break; }

      // Some trailers (eg. Halloween 2) have incorrect path in 'location' so recreate it from 'poster'
      $trailer_id = substr(basename($trailer["poster"]),0,strpos(basename($trailer["poster"]),'_'));
      $trailer["location"] = preg_replace('/'.basename($trailer["location"]).'/', $trailer_id, $trailer["location"]);
      $index_url = APPLE_TRAILERS_URL.'/moviesxml/s'.str_replace('/trailers','',$trailer["location"]).'index.xml';
      if (url_exists($index_url)) { break; }

      // Scan html at 'location' for possible xml links
      $html = file_get_contents(APPLE_TRAILERS_URL.$trailer["location"]);
      $trailer_id = preg_get('/moviesxml\/s\/(.*?\/.*?)\/.*?\.xml/',$html);
      $index_url = APPLE_TRAILERS_URL.'/moviesxml/s/'.$trailer_id.'/index.xml';
      break;

    case 'itunes':
      $index_url = 'http'.substr_between_strings($trailer["location"], 'url=itms', '.xml').'.xml';
      break;
  }

  // Get the iTunes index.xml containing trailer details
  if (($index_xml = @file_get_contents($index_url)) !== false)
  {
    // Parse the iTunes XML
    send_to_log(6,'Parsing iTunes index.xml',$index_url);
    preg_match_all('/<GotoURL target="main" url="(.*?.xml)".*?draggingName="(.*?)">/', $index_xml, $trailer_xmls);

    // Remove duplicates and sort
    $trailer_xmls[1] = array_unique($trailer_xmls[1]);
    $trailer_xmls[2] = array_unique($trailer_xmls[2]);
    array_multisort($trailer_xmls[2], $trailer_xmls[1]);
    send_to_log(8,'Found the following trailers',$trailer_xmls[2]);
  }
  else
  {
    send_to_log(2,'Failed to read iTunes index.xml',$index_url);
    $trailer_xmls = array();
  }

  return $trailer_xmls;
}

/**
 * Return array of trailer locations from an iTunes trailer.xml.
 *
 * @param array $trailer_xml
 * @return array
 */
function get_trailer_urls($trailer_xml)
{
  // Get the iTunes XML containing trailer details
  if (($xml = @file_get_contents(APPLE_TRAILERS_URL.$trailer_xml)) !== false)
  {
    // Parse the iTunes XML
    send_to_log(6,'Parsing iTunes trailer.xml',$trailer_xml);
    preg_match_all('/<key>songName<\/key><string>(.*?)<\/string>.*?<key>previewURL<\/key><string>(.*?)<\/string>/', $xml, $trailer_urls);
    send_to_log(8,'Found the following trailers',$trailer_urls);
  }
  else
  {
    send_to_log(2,'Failed to read iTunes trailer.xml',$trailer_xml);
    $trailer_urls = array();
  }

  return $trailer_urls;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
