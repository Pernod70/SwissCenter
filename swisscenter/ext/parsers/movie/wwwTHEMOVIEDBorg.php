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
   29-Jul-2010: v2.1:     Use JSON instead of XML

 *************************************************************************************************/

require_once (SC_LOCATION."/ext/json/json.php");

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

  protected $site_url = 'http://themoviedb.org/';

  /**
   * Searches the themoviedb.org site for movie details
   *
   * @param array $search_params
   * @return bool
   */
  function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];
    if (isset($search_params['YEAR']) && !empty($search_params['YEAR']))
      $year = $search_params['YEAR'];

    send_to_log(4, "Searching for details about " . $this->title . " " . $year . " online at " . $this->site_url);

    // Check filename and title for an IMDb ID
    if (isset($search_params['IGNORE_IMDBTT']) && !$search_params['IGNORE_IMDBTT']) {
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    // User TMDb's internal search to get a list a possible matches
    $moviedb_id = $this->get_moviedb_id(htmlspecialchars($this->title, ENT_QUOTES), $year, $imdbtt);
    if ($moviedb_id) {
      // Parse the movie details
      $url = 'http://api.themoviedb.org/2.1/Movie.getInfo/en/json/' . MOVIEDB_API_KEY . '/' . $moviedb_id;
      $index = json_decode( file_get_contents($url) );
      $moviematches = object_to_array($index);
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
    $imdbtt = $moviematches[0]['imdb_id'];
    $this->setProperty(IMDBTT, $imdbtt);
    return $imdbtt;
  }
  protected function parseTitle() {
    $moviematches = $this->page;
    $title = $moviematches[0]['name'];
    $this->setProperty(TITLE, $title);
    return $title;
  }
  protected function parseSynopsis() {
    $moviematches = $this->page;
    $synopsis = $moviematches[0]['overview'];
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $moviematches = $this->page;
    $cast = $moviematches[0]['cast'];
    if (isset($cast) && !empty($cast)) {
      $names = array();
      foreach ($cast as $person)
        if ( $person['job'] == 'Actor' )
          $names[] = $person['name'];
      $this->setProperty(ACTORS, $names);
      return $names;
    }
  }
  protected function parseDirectors() {
    $moviematches = $this->page;
    $cast = $moviematches[0]['cast'];
    if (isset($cast) && !empty($cast)) {
      $names = array();
      foreach ($cast as $person)
        if ( $person['job'] == 'Director' )
          $names[] = $person['name'];
      $this->setProperty(DIRECTORS, $names);
      return $names;
    }
  }
  protected function parseGenres() {
    $moviematches = $this->page;
    $genres = $moviematches[0]['genres'];
    if (isset($genres) && !empty($genres)) {
      $names = array();
      foreach ($genres as $genre)
        $names[] = $genre['name'];
      $this->setProperty(GENRES, $names);
      return $names;
    }
  }
  protected function parseYear() {
    $moviematches = $this->page;
    $year = substr($moviematches[0]['released'], 0, 4);
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $moviematches = $this->page;
    $rating = floor($moviematches[0]['rating'] * 10);
    if (isset($rating) && !empty($rating)) {
      $this->setProperty(EXTERNAL_RATING_PC, $rating);
      return $rating;
    }
  }
  protected function parseTrailer() {
    $moviematches = $this->page;
    $trailer = $moviematches[0]['trailer'];
    $this->setProperty(TRAILER, $trailer);
    return $trailer;
  }
  protected function parsePoster() {
    $moviematches = $this->page;
    $posters = $moviematches[0]['posters'];
    if (isset($posters) && !empty($posters)) {
      foreach ($posters as $image) {
        if ( $image['image']['size'] == 'original' ) {
          $poster = $image['image']['url'];
          break;
        }
      }
      $this->setProperty(POSTER, $poster);
      return $poster;
    }
  }
  protected function parseFanart() {
    $moviematches = $this->page;
    $backdrops = $moviematches[0]['backdrops'];
    if (isset($backdrops) && !empty($backdrops)) {
      $fanart = array();
      foreach ($backdrops as $image) {
        $fanart[$image['image']['id']]['id'] = $image['image']['id'];
        $fanart[$image['image']['id']][$image['image']['size']] = $image['image']['url'];
      }
      $this->setProperty(FANART, $fanart);
      return $fanart;
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
  function get_moviedb_id($title, $year = '', $imdbtt = '') {
    // Use IMDb id (if provided), otherwise submit a search
    if (!empty ($imdbtt))
      $url = 'http://api.themoviedb.org/2.1/Movie.imdbLookup/en/json/' . MOVIEDB_API_KEY . '/' . $imdbtt;
    else
      $url = 'http://api.themoviedb.org/2.1/Movie.search/en/json/' . MOVIEDB_API_KEY . '/' . urlencode(trim($title.' '.$year));

    // Parse the xml results and determine best match for title
    $index = json_decode( file_get_contents($url) );
    $moviematches = object_to_array($index);

    // Find best match for required title
    if (count($moviematches) > 0) {
      if (!empty ($imdbtt)) {
        // Found IMDb id
        $this->accuracy = 100;
        $index = 0;
        send_to_log(4, "Matched IMDb Id:", $moviematches[$index]['name']);
        return $moviematches[$index]['id'];
      } else {
        // There are multiple matches found... process them
        $matches = array ();
        $matches_id = array ();
        foreach ($moviematches as $movie) {
          $matches[] = $movie['name'];
          $matches_id[] = $movie['id'];
          if (isset ($movie['alternative_name']) && !empty ($movie['alternative_name'])) {
            $matches[] = $movie['alternative_name'];
            $matches_id[] = $movie['id'];
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
?>