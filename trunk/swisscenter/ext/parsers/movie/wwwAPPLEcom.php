<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

require_once (SC_LOCATION.'/resources/trailers/apple_trailers.php');

class movie_wwwAPPLEcom extends Parser implements ParserInterface {

  protected $site_url = 'http://www.apple.com/trailers';

  public $supportedProperties = array (
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    CERTIFICATE,
    POSTER,
    TRAILER,
    MATCH_PC,
  );

  public $settings = array (
    TRAILER_SIZE => array("options" => array('Small', 'Medium', 'Large', 'HD 480p', 'HD 720p', 'HD 1080p'),
                          "default" => 'HD 480p')
  );

  public static function getName() {
    return "www.Apple.com";
  }

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Perform search for matching titles
    send_to_log(4, "Searching for details about ".$this->title." online at ".$this->site_url);
    $apple = new AppleTrailers();
    $trailers = $apple->quickFind($this->title);

    // Examine returned page
    if (count($trailers) == 0) {
      // There are no matches found... do nothing
      $this->accuracy = 0;
      send_to_log(4, "No Match found.");
    } else {
      $trailer_titles = array ();
      foreach ($trailers as $index => $trailer)
        $trailer_titles[$index] = html_entity_decode($trailer["title"], ENT_COMPAT, 'UTF-8');

      // There are multiple matches found... process them
      $index = best_match($this->title, $trailer_titles, $this->accuracy);
    }

    // Determine attributes for the movie and update the database
    if ($this->accuracy >= 75) {
      $this->page = $trailers[$index];
      return true;
    } else {
      return false;
    }
  }

  /**
   * Properties supported by this parser.
   *
   */

  protected function parseTitle() {
    $trailers = $this->page;
    $title = html_entity_decode($trailers["title"], ENT_COMPAT, 'UTF-8');
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $trailers = $this->page;
    $synopsis = get_trailer_description($trailers);
    if (isset($synopsis)&& !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $trailers = $this->page;
    $actors = $trailers["actors"];
    if (isset($actors) && !empty($actors)) {
      $actors = array_map("trim", $actors);
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $trailers = $this->page;
    $directors = $trailers["director"];
    if (isset($directors) && !empty($directors)) {
//      $directors = array_map("trim", $directors);
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $trailers = $this->page;
    $genres = $trailers["genre"];
    if (isset($genres) && !empty($genres)) {
      $genres = array_map("trim", $genres);
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $trailers = $this->page;
    $year = date('Y', strtotime($trailers["releasedate"]));
    if (isset($year)&& !empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parseCertificate() {
    $trailers = $this->page;
    $rating = $trailers["rating"];
    if (isset($rating) && !empty($rating)) {
      $this->setProperty(CERTIFICATE, $rating);
      return $rating;
    }
  }
  protected function parseTrailer() {
    $trailers = $this->page;
    $trailer_xmls = get_trailer_index($trailers);
    // Search for a trailer title that contains 'Trailer'
    foreach ($trailer_xmls[2] as $key=>$trailer_title)
    {
      if ( stripos($trailer_title,'Trailer') === 0 )
        break;
    }
    $trailer_urls = get_trailer_urls($trailer_xmls[1][$key]);
    $file_size = get_sys_pref(str_replace('movie_', '', get_class($this)).'_TRAILER_SIZE', $this->settings[TRAILER_SIZE]["default"]);
    // Take the highest resolution trailer
    $trailer = array_pop($trailer_urls[2]);
    // Now search for preferred resolution
    foreach ($trailer_urls[1] as $key=>$name)
    {
      if (strtoupper(preg_get('/\((.*)\)/', $name)) == $file_size)
      {
        $trailer = $trailer_urls[2][$key];
        break;
      }
    }
    if (isset($trailer) && !empty($trailer)) {
      $this->setProperty(TRAILER, $trailer);
      return $trailer;
    }
  }
  protected function parsePoster() {
    $trailers = $this->page;
    if (!empty ($trailers["poster"])) {
      $poster = $trailers["poster"];
      $this->setProperty(POSTER, $poster);
      return $poster;
    }
  }
  protected function parseMatchPc() {
    $this->setProperty(MATCH_PC, $this->accuracy);
    return $this->accuracy;
  }
}
?>