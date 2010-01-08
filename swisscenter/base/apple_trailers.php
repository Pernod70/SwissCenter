<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/../ext/json/json.php'));

// Decides which include path delimiter to use.  Windows should be using a semi-colon
// and everything else should be using a colon.  If this isn't working on your system,
// comment out this if statement and manually set the correct value into $path_delimiter.
if (strpos(__FILE__, ':') !== false) {
  $path_delimiter = ';';
} else {
  $path_delimiter = ':';
}

// This will add the packaged PEAR files into the include path for PHP, allowing you
// to use them transparently.  This will prefer officially installed PEAR files if you
// have them.  If you want to prefer the packaged files (there shouldn't be any reason
// to), swap the two elements around the $path_delimiter variable.  If you don't have
// the PEAR packages installed, you can leave this like it is and move on.

ini_set('include_path', ini_get('include_path') . $path_delimiter . dirname(__FILE__) . '/../ext/PEAR');

class AppleTrailers {
  var $GET = 'http://www.apple.com/trailers/home/';
  var $service = 'apple_trailers';

  var $req;
  var $response;
  var $response_code;
  var $cache_table = null;
  var $cache_expire = null;

  /*
   * When your database cache table hits this many rows, a cleanup
   * will occur to get rid of all of the old rows and cleanup the
   * garbage in the table.  For most personal apps, 1000 rows should
   * be more than enough.  If your site gets hit by a lot of traffic
   * or you have a lot of disk space to spare, bump this number up.
   * You should try to set it high enough that the cleanup only
   * happens every once in a while, so this will depend on the growth
   * of your table.
   */
  var $max_cache_rows = 1000;

  function AppleTrailers ()
  {
    // All calls to the API are done via the GET method using the PEAR::HTTP_Request package.
    require_once 'HTTP/Request.php';
    $this->req =& new HTTP_Request();
    $this->req->setMethod(HTTP_REQUEST_METHOD_GET);
    $this->enableCache(3600);
  }

  /**
   * Enable caching to the database
   *
   * @param unknown_type $cache_expire
   * @param unknown_type $table
   */
  function enableCache($cache_expire = 600, $table = 'cache_api_request')
  {
    if (db_value("SELECT COUNT(*) FROM $table WHERE service = '".$this->service."'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $table WHERE service = '".$this->service."' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
    $this->cache_table = $table;
    $this->cache_expire = $cache_expire;
  }

  function getCached ($request)
  {
    //Checks the database for a cached result to the request.
    //If there is no cache result, it returns a value of false. If it finds one,
    //it returns the unparsed XML.
    $reqhash = md5(serialize($request));

    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result)) {
      return object_to_array(json_decode($result));
    }
    return false;
  }

  function cache ($request, $response)
  {
    //Caches the unparsed XML of a request.
    $reqhash = md5(serialize($request));

    if (db_value("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'")) {
      db_sqlcommand( "UPDATE ".$this->cache_table." SET response = '".db_escape_str($response)."', expiration = '".strftime("%Y-%m-%d %H:%M:%S")."' WHERE request = '$reqhash'");
    } else {
      db_sqlcommand( "INSERT INTO ".$this->cache_table." (request, service, response, expiration) VALUES ('$reqhash', '$this->service', '".db_escape_str($response)."', '".strftime("%Y-%m-%d %H:%M:%S")."')");
    }

    return false;
  }

  function request ($command, $args = array())
  {
    //Sends a request to Apple
    $url = url_add_params($this->GET.$command, $args);
    $this->req->setURL($url);
    send_to_log(6,'Apple feed request',$url);

    if (!($this->response = $this->getCached($url)) ) {
      $this->req->addHeader("Connection", "Keep-Alive");

      //Send Requests
      if ($this->req->sendRequest()) {
        // Clean response body
        $response = substr($this->req->getResponseBody(), strpos($this->req->getResponseBody(),'['), strrpos($this->req->getResponseBody(),']') - strpos($this->req->getResponseBody(),'[') + 1);
        // Decode response
        $response = unicode_decode($response);
        $this->response = object_to_array(json_decode($response));
        $this->response_code = $this->req->getResponseCode();
        $this->cache($url, $response);
      } else {
        die("There has been a problem sending your command to the server.");
        return false;
      }
    }
    return true;
  }

  /**
   * Return requested feed.
   *
   * @param string $feed
   * @return array
   */
  function getFeed ($feed)
  {
    $this->request('feeds/'.$feed.'.json');
    return $this->response;
  }

  /**
   * Return results of query.
   *
   * @param string $query
   * @return array
   */
  function quickFind ($query)
  {
    $this->request('scripts/quickfind.php?callback=searchCallback&q='.rawurlencode($query));
    return $this->response;
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
 * Retrieve and parse a section of the Apple trailers page for list of movies.
 *
 * @param string $section
 * @return array
 */
function get_apple_trailers_page_section($section)
{
  $match_section = array("weekendboxoffice" => '/Weekend Box Office.*<ul>(.*)<\/ul>/siU',
                         "openingthisweek"  => '/Opening this week.*<ul>(.*)<\/ul>/siU');
  // Get Apple trailers main page
  $html = file_get_contents('http://www.apple.com/trailers/');

  // Parse page for Weekend Box Office or Opening trailers
  $html = preg_get($match_section[$section], $html);

  $trailer_urls = array();
  foreach (explode('<li>', $html) as $item)
  {
    if (strpos($item, 'title='))
      preg_match('/<a class="title" href="(.*)".*title="(.*)".*<\/a>/sU', $item, $trailer);
    elseif (strpos($item, 'title'))
      preg_match('/<a class="title" href="(.*)">(.*)<\/a>/sU', $item, $trailer);

    if (!empty($trailer[2]))
    {
      // Remove any preceding number
      $trailer[2] = preg_replace('/(\d+\. )/','',$trailer[2]);
      $trailers[] = array("title"=>$trailer[2], "url"=>'apple_trailer_selected.php?query='.rawurlencode($trailer[2]));
    }
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
      $meta = get_meta_tags('http://www.apple.com/'.$trailer["location"]);
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
      $index_url = 'http://www.apple.com/moviesxml/s'.str_replace('/trailers','',$trailer["location"]).'index.xml';
      if (url_exists($index_url)) { break; }

      // Some trailers (eg. Halloween 2) have incorrect path in 'location' so recreate it from 'poster'
      $trailer_id = substr(basename($trailer["poster"]),0,strpos(basename($trailer["poster"]),'_'));
      $trailer["location"] = preg_replace('/'.basename($trailer["location"]).'/', $trailer_id, $trailer["location"]);
      $index_url = 'http://www.apple.com/moviesxml/s'.str_replace('/trailers','',$trailer["location"]).'index.xml';
      if (url_exists($index_url)) { break; }

      // Scan html at 'location' for possible xml links
      $html = file_get_contents('http://www.apple.com/'.$trailer["location"]);
      $trailer_id = preg_get('/moviesxml\/s\/(.*?\/.*?)\/.*?\.xml/',$html);
      $index_url = 'http://www.apple.com/moviesxml/s/'.$trailer_id.'/index.xml';
      break;

    case 'itunes':
      $index_url = 'http'.substr_between_strings($trailer["location"], 'url=itms', '.xml').'.xml';
      break;
  }

  // Get the iTunes index.xml containing trailer details
  if ($index_xml = @file_get_contents($index_url))
  {
    // Parse the iTunes XML
    send_to_log(6,'Parsing iTunes index.xml',$index_url);
    preg_match_all('/<GotoURL target="main" url="(.*?.xml)".*?draggingName="(.*?)">/', $index_xml, $trailer_xmls);

    // Remove duplicates and sort
    $trailer_xmls[1] = array_unique($trailer_xmls[1]);
    $trailer_xmls[2] = array_unique($trailer_xmls[2]);
    array_multisort($trailer_xmls[2], $trailer_xmls[1]);
    send_to_log(8,'Found the following trailers',$trailer_xmls);
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
  if ($xml = @file_get_contents('http://www.apple.com'.$trailer_xml))
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

/**
 * Functions for managing the trailer navigation history.
 */
function apple_trailer_hist_init( $url )
{
  $_SESSION["history"] = array();
  $_SESSION["history"][] = $url;
}

function apple_trailer_hist_push( $url )
{
  $_SESSION["history"][] = $url;
}

function apple_trailer_hist_pop()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return array_pop($_SESSION["history"]);
}

function apple_trailer_hist_most_recent()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return $_SESSION["history"][count($_SESSION["history"])-1];
}

function apple_trailer_page_params()
{
  // Remove pages from history
  if (isset($_REQUEST["del"]))
    for ($i=0; $i<$_REQUEST["del"]; $i++)
      apple_trailer_hist_pop();

  $this_url = url_remove_param(current_url(), 'del');
  $back_url = url_add_param(apple_trailer_hist_most_recent(), 'del', 2);

  // Add page to history
  apple_trailer_hist_push($this_url);

  return $back_url;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
