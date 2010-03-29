<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Please help themoviedb.org website by contributing information and artwork if possible.

   Version history:
   01-Feb-2009: v1.0:     First public release
   29-May-2009: v2.0:     Updated to use API 2.1

 *************************************************************************************************/

// API key registered to SwissCenter project
define('MOVIEDB_API_KEY', '2548980e43e9c7d08b705a2e57e9afe3');

class wwwTHEMOVIEDBorg extends Parser implements ParserInterface {

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    EXTERNAL_RATING_PC,
    POSTER,
    TRAILER,
    FANART,
    MATCH_PC
  );

  public static function getName() {
    return "www.themoviedb.org";
  }

  private $cache_dir;
  private $xmlparser;
  protected $site_url = 'http://themoviedb.org/';

  /**
   * Searches the themoviedb.org site for movie details
   *
   * @param array $search_params
   * @return bool
   */
  function populatePage($search_params) {
    global $moviematches;

    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Ensure local cache folders exist
    $this->cache_dir = get_sys_pref('cache_dir') . '/tmdb';
    if (!file_exists($this->cache_dir)) {
      @ mkdir($this->cache_dir);
    }
    if (!file_exists($this->cache_dir . '/fanart')) {
      @ mkdir($this->cache_dir . '/fanart');
    }

    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);

    // Check filename and title for an IMDb ID
    if (isset($search_params['IGNORE_IMDBTT']) && !$search_params['IGNORE_IMDBTT']) {
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    // User TMDb's internal search to get a list a possible matches
    $moviedb_id = $this->get_moviedb_id(htmlspecialchars($this->title, ENT_QUOTES), $imdbtt);
    if ($moviedb_id) {
      // Parse the movie details
      $moviematches = array ();
      parse_moviedb_xml('http://api.themoviedb.org/2.1/Movie.getInfo/en/xml/' . MOVIEDB_API_KEY . '/' . $moviedb_id, 'start_tag_moviematches', 'end_tag_moviematches', 'tag_contents_moviematches');
      $this->page = $moviematches;
      $this->accuracy = 100;
      if ( isset ($imdbtt) && !empty($imdbtt)) {
        if ($this->selfTestIsOK())
          return true;
        else {
          send_to_log(4, "Calling populatePage a second time with IMDb ID: " . $imdbtt);
          $this->populatePage(array('TITLE'         => $this->title,
                                    'IGNORE_IMDBTT' => true));
        }
      }
    }
  }
  private function selfTestIsOK(){
    send_to_log(8, "Entering selftest");
    return isset($this->page);
  }
  protected function parseIMDBTT() {
    $moviematches = $this->page;
    $imdbtt = $moviematches[0]['IMDB_ID'];
    $this->setProperty(IMDBTT, $imdbtt);
    return $imdbtt;
  }
  protected function parseTitle() {
    $moviematches = $this->page;
    $title = $moviematches[0]['NAME'];
    $this->setProperty(TITLE, $title);
    return $title;
  }
  protected function parseSynopsis() {
    $moviematches = $this->page;
    $synopsis = $moviematches[0]['OVERVIEW'];
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $moviematches = $this->page;
    $actors = $moviematches[0]['ACTOR'];
    if (isset($actors) && !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $moviematches = $this->page;
    $directors = $moviematches[0]['DIRECTOR'];
    if (isset($directors) && !empty($directors)) {
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $moviematches = $this->page;
    $genres = $moviematches[0]['CATEGORY'];
    if (isset($genres) && !empty($genres)) {
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $moviematches = $this->page;
    $year = substr($moviematches[0]['RELEASED'], 0, 4);
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $moviematches = $this->page;
    $rating = floor($moviematches[0]['RATING'] * 10);
    if (isset($rating) && !empty($rating)) {
      $this->setProperty(EXTERNAL_RATING_PC, $rating);
      return $rating;
    }
  }
  protected function parseTrailer() {
    $moviematches = $this->page;
    $trailer = $moviematches[0]['TRAILER'];
    $this->setProperty(TRAILER, $trailer);
    return $trailer;
  }
  protected function parsePoster() {
    $moviematches = $this->page;
    $poster = $moviematches[0]['POSTER']['ORIGINAL'][0];
    if (isset($poster) && !empty ($poster)) {
      if (url_exists($poster)) {
        $this->setProperty(POSTER, $poster);
        return $poster;
      }
    }
  }
  protected function parseFanart() {
    $moviematches = $this->page;
    if (isset ($moviematches[0]['BACKDROP'])) {
      $this->setProperty(FANART, $moviematches[0]['BACKDROP']);
      return $moviematches[0]['BACKDROP'];
    }
  }
  protected function parseMatchPc() {
    if (isset ($this->accuracy)) {
      $this->setProperty(MATCH_PC, $this->accuracy);
      return $this->accuracy;
    }
  }

  /**
   * Returns the moviedb id by using the API and finding the closest match to our movie title.
   *
   * @param  string $title - Name of movie to search for
   * @param  string $imdbtt - IMDB id if provided
   * @return integer
   */
  function get_moviedb_id($title, $imdbtt = '') {
    global $moviematches;

    // Use IMDb id (if provided), otherwise submit a search
    if (!empty ($imdbtt))
      $filename = 'http://api.themoviedb.org/2.1/Movie.imdbLookup/en/xml/' . MOVIEDB_API_KEY . '/' . $imdbtt;
    else
      $filename = 'http://api.themoviedb.org/2.1/Movie.search/en/xml/' . MOVIEDB_API_KEY . '/' . urlencode($title);

    // Parse the xml results and determine best match for title
    $moviematches = array ();
    parse_moviedb_xml($filename, 'start_tag_moviematches', 'end_tag_moviematches', 'tag_contents_moviematches');

    // Find best match for required title
    if (count($moviematches) > 0) {
      if (!empty ($imdbtt)) {
        // Found IMDb id
        $this->accuracy = 100;
        $index = 0;
        send_to_log(4, "Matched IMDb Id:", $moviematches[$index]['NAME']);
        return $moviematches[$index]['ID'];
      } else {
        // There are multiple matches found... process them
        $matches = array ();
        $matches_id = array ();
        foreach ($moviematches as $movie) {
          $matches[] = $movie['NAME'];
          $matches_id[] = $movie['ID'];
          if (isset ($movie['ALTERNATIVE_NAME']) && !empty ($movie['ALTERNATIVE_NAME'])) {
            $matches[] = $movie['ALTERNATIVE_NAME'];
            $matches_id[] = $movie['ID'];
          }
        }
        $index = best_match($title, $matches, $this->accuracy);
      }
      // If we are sure that we found a good result, then get the file details.
      if ($this->accuracy > 75)
        return $matches_id[$index];
      else
        return false;
    } else {
      send_to_log(4, "No Match found.");

      //A little sketchy, but I'll take the chance, moviedb doesn't always handle imdbtt.
      if (isset($imdbtt))
        return $this->populatePage(array('TITLE'         => $this->title,
                                         'IGNORE_IMDBTT' => true));
      else {
        return false;
      }
    }
  }
}
/**
 * Parse the XML file
 *
 * @param string $filename
 */
function parse_moviedb_xml($filename, $start_tag, $end_tag, $tag_contents) {
  // Create XML parser
  $xmlparser = xml_parser_create();
  $success = true;
  if ($xmlparser !== false) {
    xml_set_element_handler($xmlparser, $start_tag, $end_tag);
    xml_set_character_data_handler($xmlparser, $tag_contents);

    // Read and process XML file
    $fp = @ fopen($filename, "r");
    if ($fp !== false) {
      send_to_log(6, 'Parsing XML: ' . $filename);
      while ($data = fread($fp, 8192)) {
        $data = preg_replace("/>\s+/u", ">", $data);
        $data = preg_replace("/\s+</u", "<", $data);
        if (!xml_parse($xmlparser, $data, feof($fp))) {
          send_to_log(8, 'XML parse error: ' . xml_error_string(xml_get_error_code($xmlparser)) . xml_get_current_line_number($xmlparser));
          $success = false;
          break;
        }
      }
    } else {
      send_to_log(5, 'Unable to read the specified file: ' . $filename);
      $success = false;
    }

  } else {
    send_to_log(5, 'Unable to create an expat XML parser - is the "xml" extension loaded into PHP?');
    $success = false;
  }
  return $success;
}

//-------------------------------------------------------------------------------------------------
// Callback functions to perform parsing of the various XML files.
//-------------------------------------------------------------------------------------------------

function start_tag_moviematches($parser, $name, $attribs)
{
  global $tag;
  global $movie;

  switch ($name)
  {
    case 'MOVIE':
      $movie = array();
      break;
    case 'PERSON':
      if (strtoupper($attribs['JOB']) == 'ACTOR')    { $movie['ACTOR'][]    = utf8_decode($attribs['NAME']); }
      if (strtoupper($attribs['JOB']) == 'DIRECTOR') { $movie['DIRECTOR'][] = utf8_decode($attribs['NAME']); }
      break;
    case 'CATEGORY':
      if (strtoupper($attribs['TYPE']) == 'GENRE')   { $movie['CATEGORY'][] = utf8_decode($attribs['NAME']); }
      break;
    case 'STUDIO':
      $movie['STUDIO'][] = utf8_decode($attribs['NAME']);
      break;
    case 'COUNTRY':
      $movie['COUNTRY'][] = utf8_decode($attribs['NAME']);
      break;
    case 'IMAGE':
      $movie[strtoupper($attribs['TYPE'])][strtoupper($attribs['SIZE'])][] = $attribs['URL'];
      break;
    default:
      $tag = $name;
  }
}

function end_tag_moviematches($parser, $name)
{
  global $movie;
  global $moviematches;

  switch ($name)
  {
    case 'MOVIE':
      if ( $movie['TYPE'] == 'movie' ) $moviematches[] = $movie;
      break;
    default:
  }
}

function tag_contents_moviematches($parser, $data)
{
  global $tag;
  global $movie;

  $data = utf8_decode($data);

  switch ($tag)
  {
    case 'NAME':            { $movie['NAME'] .= $data; break; }
    case 'ALTERNATIVE_NAME':{ $movie['ALTERNATIVE_NAME'] .= $data; break; }
    case 'TYPE':            { $movie['TYPE'] .= $data; break; }
    case 'ID':              { $movie['ID'] .= $data; break; }
    case 'IMDB_ID':         { $movie['IMDB_ID'] .= $data; break; }
    case 'URL':             { $movie['URL'] .= $data; break; }
    case 'OVERVIEW':        { $movie['OVERVIEW'] .= $data; break; }
    case 'RATING':          { $movie['RATING'] .= $data; break; }
    case 'RELEASED':        { $movie['RELEASED'] .= $data; break; }
    case 'RUNTIME':         { $movie['RUNTIME'] .= $data; break; }
    case 'TRAILER':         { $movie['TRAILER'] .= $data; break; }
  }
}
?>