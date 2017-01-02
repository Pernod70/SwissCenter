<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

// API key registered to SwissCenter project
define('MOVIEMETER_API_KEY', 'wfdk9v1w8dxycgw0g9w9xdq3qt3nu2td');

class movie_wwwMOVIEMETERnl extends Parser implements ParserInterface {

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    SYNOPSIS,
    POSTER,
    EXTERNAL_RATING_PC
  );

  public static function getName() {
    return "www.MovieMeter.nl";
  }

  protected $site_url = 'http://www.moviemeter.nl/';

  function populatePage($search_params) {
    if (isset ($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Get search results
    send_to_log(4,"Searching for details about ".$this->title." online at '$this->site_url'");

    // Check filename and title for an IMDb ID
    if (isset ($search_params['IGNORE_IMDBTT']) && !$search_params['IGNORE_IMDBTT']) {
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    $filmid = $this->getMovieMeter_Id($this->title, $imdbtt);
    if ($filmid) {
      // Parse the movie details
      $url = 'http://www.moviemeter.nl/api/film/'.$filmid.'?api_key='.MOVIEMETER_API_KEY;
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[moviemeter] GET: $url");
      $moviematches = json_decode( file_get_contents($url, false, $context), true );
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

  /**
   * Properties supported by this parser.
   *
   */

   protected function parseIMDBTT() {
    $results = $this->page;
    $imdbtt = $results["imdb"];
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, $imdbtt);
      return $imdbtt;
    }
  }
  protected function parseTitle() {
    $results = $this->page;
    $title = $results["display_title"];
    if (isset($title)&& !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $results = $this->page;
    $synopsis = $results["plot"];
    if (isset($synopsis)&& !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $results = $this->page;
    $actors = array();
    foreach ($results["actors"] as $actor)
      $actors[] = $actor["name"];
    if (isset($actors)&& !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $results = $this->page;
    $directors = array();
    foreach ($results["directors"] as $director)
      $directors[] = $director;
    if (isset($directors)&& !empty($directors)) {
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $results = $this->page;
    $genres = $results["genres"];
    if (isset($genres)&& !empty($genres)) {
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $results = $this->page;
    $year = $results["year"];
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $results = $this->page;
    $rating = floor($results["average"] * 20); // scale 1-5 so multiply by 2
    if (isset($rating)&& !empty($rating)) {
      $this->setProperty(EXTERNAL_RATING_PC, $rating);
      return $rating;
    }
  }
  protected function parsePoster() {
    $results = $this->page;
    if (!empty($results["posters"]["large"])) {
      $poster = $results["posters"]["large"];
      $this->setProperty(POSTER, $poster);
      return $poster;
    }
  }

  /**
   * Searches the moviemeter.nl site for movie id
   *
   * @param string $title
   * @param string $imdbtt
   * @return filmid
   */
  function getMovieMeter_Id($title, $imdbtt = '')
  {
    // Use IMDb id (if provided), otherwise submit a search
    if (!empty ($imdbtt))
      $url = 'http://www.moviemeter.nl/api/film/'.$imdb_id.'?api_key='.MOVIEMETER_API_KEY;
    else
      $url = 'http://www.moviemeter.nl/api/film/?q='.urlencode($title).'&api_key='.MOVIEMETER_API_KEY;

    send_to_log(6, "[moviemeter] GET: $url");
    $opts = array('http'=>array('method'=>"GET",
                                'header'=>"Accept: application/json\r\n"));
    $context = stream_context_create($opts);

    // Parse the JSON results and determine best match for title
    $moviematches = json_decode( file_get_contents($url, false, $context), true );

    // Find best match for required title
    if (count($moviematches) > 0) {
      if (!empty ($imdbtt)) {
        // Found IMDb id
        $this->accuracy = 100;
        $index = 0;
        send_to_log(4, "Matched IMDb Id:", $moviematches['title']);
        return $moviematches['id'];
      } else {
        // There are multiple matches found... process them
        $matches = array ();
        $matches_id = array ();
        foreach ($moviematches as $movie) {
          $matches[] = $movie['title'];
          $matches_id[] = $movie['id'];
          if ($movie['alternative_title'] !== $movie['title']) {
            $matches[] = $movie['alternative_title'];
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
      return false;
    }
  }
}
?>
