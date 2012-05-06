<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

// API key registered to SwissCenter project
define('ROTTEN_API_KEY', 'pancugh3tam5dsuppxqrhme2');

class wwwROTTENTOMATOEScom extends Parser implements ParserInterface {

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    CERTIFICATE,
    EXTERNAL_RATING_PC,
    POSTER,
    MATCH_PC
  );

  public $settings = array ();

  public static function getName() {
    return "www.RottenTomatoes.com";
  }

  protected $site_url = 'http://www.rottentomatoes.com/';

  /**
   * Searches the Rotten Tomatoes site for movie details
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

    // Use Rotten Tomatoes internal search to get a list a possible matches
    $rotten_id = $this->get_rotten_id($this->title, $year, $imdbtt);
    if ($rotten_id) {
      // Parse the movie details
      $url = 'http://api.rottentomatoes.com/api/public/v1.0/movies/'.$rotten_id.'.json?apikey='.ROTTEN_API_KEY;
      $moviematches = json_decode( file_get_contents($url), true );
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
    $imdbtt = $moviematches['alternate_ids']['imdb'];
    if (isset($imdbtt) && !empty($imdbtt)) {
      $imdbtt = 'tt'.str_pad($imdbtt,7,'0',STR_PAD_LEFT);
      $this->setProperty(IMDBTT, $imdbtt);
      return $imdbtt;
    }
  }
  protected function parseTitle() {
    $moviematches = $this->page;
    $title = utf8_decode($moviematches['title']);
    $this->setProperty(TITLE, $title);
    return $title;
  }
  protected function parseSynopsis() {
    $moviematches = $this->page;
    $synopsis = utf8_decode($moviematches['synopsis']);
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $moviematches = $this->page;
    $cast = $moviematches['abridged_cast'];
    if (isset($cast) && !empty($cast)) {
      $names = array();
      foreach ($cast as $person)
        $names[] = utf8_decode($person['name']);
      $this->setProperty(ACTORS, $names);
      return $names;
    }
  }
  protected function parseDirectors() {
    $moviematches = $this->page;
    $cast = $moviematches['abridged_directors'];
    if (isset($cast) && !empty($cast)) {
      $names = array();
      foreach ($cast as $person)
        $names[] = utf8_decode($person['name']);
      $this->setProperty(DIRECTORS, $names);
      return $names;
    }
  }
  protected function parseGenres() {
    $moviematches = $this->page;
    $genres = $moviematches['genres'];
    if (isset($genres) && !empty($genres)) {
      $names = array();
      foreach ($genres as $genre)
        $names[] = utf8_decode($genre);
      $this->setProperty(GENRES, $names);
      return $names;
    }
  }
  protected function parseYear() {
    $moviematches = $this->page;
    $year = $moviematches['year'];
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $moviematches = $this->page;
    $rating = $moviematches['ratings']['audience_score'];
    if (isset($rating) && !empty($rating)) {
      $this->setProperty(EXTERNAL_RATING_PC, $rating);
      return $rating;
    }
  }
  protected function parseCertificate() {
    $moviematches = $this->page;
    $cert = $moviematches['mpaa_rating'];
    if(isset($cert) && !empty($cert)){
      $this->setProperty(CERTIFICATE, $cert);
      return $cert;
    }
  }
  protected function parsePoster() {
    $moviematches = $this->page;
    $poster = $moviematches['posters']['original'];
    if (isset($poster) && !empty($poster)) {
      $this->setProperty(POSTER, $poster);
      return $poster;
    }
  }
  protected function parseMatchPc() {
    if (isset ($this->accuracy)) {
      $this->setProperty(MATCH_PC, $this->accuracy);
      return $this->accuracy;
    }
  }

  /**
   * Returns the Rotten Tomatoes id by using the API and finding the closest match to our movie title.
   *
   * @param  string $title - Name of movie to search for
   * @param  string $imdbtt - IMDB id if provided
   * @return integer
   */
  function get_rotten_id($title, $year = '', $imdbtt = '') {
    // Use IMDb id (if provided), otherwise submit a search
    if (!empty ($imdbtt))
      $url = 'http://api.rottentomatoes.com/api/public/v1.0/movie_alias.json?apikey='.ROTTEN_API_KEY.'&type=imdb&id='.preg_get('/(\d+)/',$imdbtt);
    else
      $url = 'http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey='.ROTTEN_API_KEY.'&q='.urlencode(trim($title));
    send_to_log(6, 'Feed request', $url);

    // Parse the xml results and determine best match for title
    $moviematches = json_decode( file_get_contents($url), true );

    // Find best match for required title
    if (count($moviematches['movies']) > 0) {
      if (!empty ($imdbtt)) {
        // Found IMDb id
        $this->accuracy = 100;
        $index = 0;
        send_to_log(4, "Matched IMDb Id:", $moviematches['movies'][$index]['title']);
        return $moviematches['movies'][$index]['id'];
      } else {
        // There are multiple matches found... process them
        $matches = array ();
        $matches_id = array ();
        foreach ($moviematches['movies'] as $movie) {
          $matches[] = utf8_decode($movie['title']);
          $matches_id[] = $movie['id'];
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

      //A little sketchy, but I'll take the chance, Rotten Tomatoes doesn't always handle imdbtt.
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