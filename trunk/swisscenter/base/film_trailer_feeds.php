<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

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

define('FILMTRAILER_URL','http://www.services.filmtrailer.com');
define('FILMTRAILER_CHANNEL_USER_ID', '41100914-1'); // Unique ID for SwissCenter
define('FILMTRAILER_FILE_TYPE', 'wmv');              // flv, mp4, mov, wmv
define('FILMTRAILER_QUALITY', 'xxlarge');            // small, medium, large, xlarge, xxlarge

class FilmTrailer {
  var $service  = 'film_trailers';
  var $product_type = 'cinema';
  var $region_code = 'uk';

  private $req;
  private $response;
  private $response_code;
  private $cache_table = null;
  private $cache_expire = null;

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
  private $max_cache_rows = 1000;

  function FilmTrailer ()
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
  private function enableCache($cache_expire = 600, $table = 'cache_api_request')
  {
    if (db_value("SELECT COUNT(*) FROM $table WHERE service = '".$this->service."'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $table WHERE service = '".$this->service."' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
    $this->cache_table = $table;
    $this->cache_expire = $cache_expire;
  }

  private function getCached($request)
  {
    //Checks the database for a cached result to the request.
    //If there is no cache result, it returns a value of false. If it finds one,
    //it returns the unparsed XML.
    $reqhash = md5(serialize($request));

    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result)) {
      return $result;
    }
    return false;
  }

  private function cache($request, $response)
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

  private function request($command, $args = array())
  {
    //Sends a request to FilmTrailer.com
    $url = url_add_params($command, $args);
    $this->req->setURL($url);
    send_to_log(6,'FilmTrailer feed request',$url);

    if (!($this->response = $this->getCached($url)) ) {
      $this->req->addHeader("Connection", "Keep-Alive");

      //Send Requests
      if ($this->req->sendRequest()) {
        $this->response = $this->req->getResponseBody();
        $this->response_code = $this->req->getResponseCode();
        $this->cache($url, $this->req->getResponseBody());
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
  function getFeed($feed)
  {
    $this->request($this->get_feed_url($feed));
    $trailers = parse_filmtrailer_xml($this->response);
    return $trailers;
  }

  /**
   * Return array of trailers for selected genre.
   *
   * @param string $genre
   * @return array
   */
  function getGenreFeed($genre)
  {
    $this->request($this->get_genre_feed_url($genre));
    $trailers = parse_filmtrailer_xml($this->response);
    return $trailers;
  }

  /**
   * Set product type.
   * Valid values are cinema, dvd, bluray or he (he = combination of DVD and Blu-Ray)
   *
   * @param string $type
   */
  function set_product_type($type)
  {
    $this->product_type = $type;
  }

  /**
   * Set region code.
   * Valid values are uk, fr, de, es, it, ch, ch-fr, nl, dk, se, fi.
   *
   * @param string $region
   */
  function set_region_code($region)
  {
    $region_map = array('da'=>'dk', 'de'=>'de', 'en'=>'uk', 'es'=>'es', 'fr'=>'fr', 'it'=>'it', 'nl'=>'nl', no, 'sv'=>'se');
    if (isset($region_map[$region]))
      $this->region_code = $region_map[$region];
    else
      $this->region_code = 'uk';
  }

  function get_feed_url($feed)
  {
    return "http://$this->region_code.feed.previewnetworks.com/v3.1/$this->product_type/$feed/".FILMTRAILER_CHANNEL_USER_ID."/?file_type=".FILMTRAILER_FILE_TYPE;
  }

  function get_genre_feed_url($genre)
  {
    return "http://$this->region_code.feed.playnw.com/?ListType=$genre&channel_user_id=".FILMTRAILER_CHANNEL_USER_ID."&file_type=".FILMTRAILER_FILE_TYPE;
  }

  /**
   * Return results of query.
   *
   * @param string $query
   * @return array
   */
  function quickFind ($query)
  {
    // Get the film ID list
    $this->request('http://uk.listing.playnetworks.net/listing.php');

    // Search the film ID list for matches
    $matches = array();
    $query = (empty($query) ? '.*' : '[\w]*'.$query.'[\w]*');
    preg_match_all('/<tr><td><a href="javascript: get_player\(this,\'([0-9]+)_([0-9]+)_([0-9]+)\'\);">\d+<\/a>[\s\d\(\)]+<\/td><td><a href="javascript: get_player\(this,\'[0-9_]+\'\);">('.$query.')<\/a>/Usi', $this->response, $matches);

    // Remove duplicate movies, caused by having multiple trailers
    $trailers = array();
    for ($i=0; $i<count($matches[0]); $i++)
    {
      $trailers[$matches[2][$i]]["title"]      = $matches[4][$i];
      $trailers[$matches[2][$i]]["film_id"]    = $matches[2][$i];
      $trailers[$matches[2][$i]]["trailers"][] = array('clip_id' => $matches[1][$i], 'type_id' => $matches[3][$i]);
    }
    return $trailers;
  }
}

/**
  * Parse the XML file
  *
  * @param string $filename
  */
  function parse_filmtrailer_xml($xml) {
    global $movietrailer;

    // Create XML parser
    $xmlparser = xml_parser_create();
    $success = true;
    if ($xmlparser !== false) {
      xml_set_element_handler($xmlparser, 'start_tag_movietrailer', 'end_tag_movietrailer');
      xml_set_character_data_handler($xmlparser, 'tag_contents_movietrailer');

      // Process XML file
      $xml = preg_replace("/>\s+/u", ">", $xml);
      $xml = preg_replace("/\s+</u", "<", $xml);
      if (!xml_parse($xmlparser, $xml)) {
        send_to_log(8, 'XML parse error: ' . xml_error_string(xml_get_error_code($xmlparser)) . xml_get_current_line_number($xmlparser));
        $result = false;
      }
      else
      {
        $result = $movietrailer;
      }
    } else {
      send_to_log(5, 'Unable to create an expat XML parser - is the "xml" extension loaded into PHP?');
      $result = false;
    }
    return $result;
  }

  //-------------------------------------------------------------------------------------------------
  // Callback functions to perform parsing of the various XML files.
  //-------------------------------------------------------------------------------------------------

  function start_tag_movietrailer($parser, $name, $attribs)
  {
    global $tag;
    global $movie;
    global $clip;
    global $file;
    global $picture;
    global $product;
    global $id;

    switch ($name)
    {
      case 'MOVIE':
        $movie = array();
        $movie['MOVIE_ID'] = $attribs['MOVIE_ID'];
        $movie['IMDB_ID']  = $attribs['IMDB_ID'];
        break;
      case 'ACTOR':
      case 'WRITER':
      case 'DIRECTOR':
      case 'PRODUCER':
      case 'CATEGORIE':
      case 'COUNTRY':
        $id = $attribs['ID'];
        $tag = $name;
        break;
      case 'IMAGE':
        $movie[strtoupper($attribs['TYPE'])][strtoupper($attribs['SIZE'])][] = $attribs['URL'];
        break;
      case 'CLIP':
        $clip = array();
        $clip['CLIP_ID']      = $attribs['CLIP_ID'];
        $clip['CLIP_TYPE_ID'] = $attribs['CLIP_TYPE_ID'];
        $clip['NAME']         = $attribs['NAME'];
        break;
      case 'FILE':
        $file = array();
        $file['FORMAT']       = $attribs['FORMAT'];
        $file['SIZE']         = $attribs['SIZE'];
        $file['STATUS_ID']    = $attribs['STATUS_ID'];
        break;
      case 'PICTURE':
        $picture = array();
        $picture['ID']        = $attribs['ID'];
        $picture['TYPE_ID']   = $attribs['TYPE_ID'];
        $picture['TYPE_NAME'] = $attribs['TYPE_NAME'];
        $picture['STATUS_ID'] = $attribs['STATUS_ID'];
        break;
      case 'PRODUCT':
        $product = array();
        $product['PRODUCT_ID'] = $attribs['PRODUCT_ID'];
        $product['NAME']       = $attribs['NAME'];
        break;
      default:
        $tag = $name;
    }
  }

  function end_tag_movietrailer($parser, $name)
  {
    global $movie;
    global $clip;
    global $file;
    global $picture;
    global $product;
    global $movietrailer;

    switch ($name)
    {
      case 'MOVIE':
        $movietrailer[] = $movie;
        break;
      case 'CLIP':
        $movie['CLIPS'][$clip['CLIP_TYPE_ID']] = $clip;
        break;
      case 'FILE':
        $clip['FILES'][$file['SIZE']] = $file;
        break;
      case 'PICTURE':
        $movie['PICTURES'][$picture['TYPE_ID']] = $picture;
        break;
      case 'PRODUCT':
        $movie['PRODUCTS'][] = $product;
        break;
      default:
    }
  }

  function tag_contents_movietrailer($parser, $data)
  {
    global $tag;
    global $movie;
    global $clip;
    global $file;
    global $picture;
    global $product;
    global $id;

    $data = utf8_decode($data);

    switch ($tag)
    {
      case 'ORIGINAL_TITLE':   { $movie['ORIGINAL_TITLE'] .= $data; break; }
      case 'MOVIE_DURATION':   { $movie['MOVIE_DURATION'] .= $data; break; }
      case 'PRODUCTION_YEAR':  { $movie['PRODUCTION_YEAR'] .= $data; break; }
      case 'OFFICIAL_WEBSITE': { $movie['OFFICIAL_WEBSITE'] .= $data; break; }
      case 'ACTOR':            { $movie['ACTORS'][$id] .= $data; break; }
      case 'WRITER':           { $movie['WRITERS'][$id] .= $data; break; }
      case 'DIRECTOR':         { $movie['DIRECTORS'][$id] .= $data; break; }
      case 'PRODUCER':         { $movie['PRODUCERS'][$id] .= $data; break; }
      case 'CATEGORIE':        { $movie['CATEGORIES'][$id] .= $data; break; }
      case 'COUNTRY':          { $movie['COUNTRIES'][$id] .= $data; break; }

      case 'URL':
      {
        if (strpos($data, 'image') > 0)
          $picture['URL'] .= $data;
        else
          $file['URL'] .= $data;
        break;
      }

      case 'DURATION':         { $file['DURATION'] .= $data; break; }

      case 'MIME_TYPE':        { $picture['MIME_TYPE'] .= $data; break; }

      case 'PRODUCT_TITLE':    { $product['PRODUCT_TITLE'] .= $data; break; }
      case 'DESCRIPTION':      { $product['DESCRIPTION'] .= $data; break; }
      case 'PREMIERE':         { $product['PREMIERE'] .= $data; break; }
    }
  }

/**
 * Return array of available genres.
 *
 * @return array
 */
function get_film_trailer_genres()
{
  $genres = array(array("title"=>'Action',      "url"=>'CinemaAction'),
                  array("title"=>'Adventure',   "url"=>'CinemaAdventure'),
                  array("title"=>'Animation',   "url"=>'CinemaAnimation'),
                  array("title"=>'Biography',   "url"=>'CinemaBiography'),
                  array("title"=>'Comedy',      "url"=>'CinemaComedy'),
                  array("title"=>'Crime',       "url"=>'CinemaCrime'),
                  array("title"=>'Documentary', "url"=>'CinemaDocumentary'),
                  array("title"=>'Drama',       "url"=>'CinemaDrama'),
                  array("title"=>'Family',      "url"=>'CinemaFamily'),
                  array("title"=>'Fantasy',     "url"=>'CinemaFantasy'),
                  array("title"=>'Film-Noir',   "url"=>'CinemaFilmNoir'),
                  array("title"=>'Game Show',   "url"=>'CinemaGameShow'),
                  array("title"=>'History',     "url"=>'CinemaHistory'),
                  array("title"=>'Horror',      "url"=>'CinemaHorror'),
                  array("title"=>'Music',       "url"=>'CinemaMusic'),
                  array("title"=>'Musical',     "url"=>'CinemaMusical'),
                  array("title"=>'Mystery',     "url"=>'CinemaMystery'),
                  array("title"=>'News',        "url"=>'CinemaNews'),
                  array("title"=>'Reality-TV',  "url"=>'CinemaRealityTV'),
                  array("title"=>'Romance',     "url"=>'CinemaRomance'),
                  array("title"=>'Sci-Fi',      "url"=>'CinemaSciFi'),
                  array("title"=>'Short',       "url"=>'CinemaShort'),
                  array("title"=>'Sport',       "url"=>'CinemaSport'),
                  array("title"=>'Talk Show',   "url"=>'CinemaTalkShow'),
                  array("title"=>'Thriller',    "url"=>'CinemaThriller'),
                  array("title"=>'War',         "url"=>'CinemaWar'),
                  array("title"=>'Western',     "url"=>'CinemaWestern'),
                  array("title"=>'Children',    "url"=>'CinemaChildrenMovie'));

  return $genres;
}

/**
 * Functions for managing the trailer navigation history.
 */
function film_trailer_hist_init( $url )
{
  $_SESSION["history"] = array();
  $_SESSION["history"][] = $url;
}

function film_trailer_hist_push( $url )
{
  $_SESSION["history"][] = $url;
}

function film_trailer_hist_pop()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return array_pop($_SESSION["history"]);
}

function film_trailer_hist_most_recent()
{
  if (count($_SESSION["history"]) == 0)
    page_error(str('DATABASE_ERROR'));

  return $_SESSION["history"][count($_SESSION["history"])-1];
}

function film_trailer_page_params()
{
  // Remove pages from history
  if (isset($_REQUEST["del"]))
    for ($i=0; $i<$_REQUEST["del"]; $i++)
      film_trailer_hist_pop();

  $this_url = url_remove_param(current_url(), 'del');
  $back_url = url_add_param(film_trailer_hist_most_recent(), 'del', 2);

  // Add page to history
  film_trailer_hist_push($this_url);

  return $back_url;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
