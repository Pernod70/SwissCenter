<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

require_once (SC_LOCATION.'/resources/trailers/film_trailer_feeds.php');

class wwwFILMTRAILERcom extends Parser implements ParserInterface {

  protected $site_url = 'http://www.filmtrailer.com';

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    POSTER,
    TRAILER,
    MATCH_PC,
  );

  public $settings = array (
    TRAILER_FORMAT => array("options" => array('MP4', 'WMV'),
                            "default" => 'WMV'),
    TRAILER_SIZE   => array("options" => array('Small', 'Medium', 'Large', 'xLarge', 'xxLarge'),
                            "default" => 'xxLarge')
  );

  public static function getName() {
    return "www.FilmTrailer.com";
  }

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Perform search for matching titles
    send_to_log(4, "Searching for details about ".$this->title." online at ".$this->site_url);
    $filmtrailer = new FilmTrailer();
    $filmtrailer->setTrailerType(get_sys_pref(get_class($this).'_TRAILER_FORMAT', $this->settings[TRAILER_FORMAT]["default"]));
    $filmtrailer->setTrailerSize(get_sys_pref(get_class($this).'_TRAILER_SIZE', $this->settings[TRAILER_SIZE]["default"]));
    $trailers = $filmtrailer->quickFind('');

    // Examine returned page
    if (count($trailers) == 0) {
      // There are no matches found... do nothing
      $this->accuracy = 0;
      send_to_log(4, "No Match found.");
    } else {
      $trailer_titles = array ();
      foreach ($trailers as $index => $trailer)
        $trailer_titles[$index] = $trailer["title"];

      // There are multiple matches found... process them
      $index = best_match($this->title, $trailer_titles, $this->accuracy);
    }

    // Determine attributes for the movie and update the database
    if ($this->accuracy >= 75) {
      $filmtrailer->set_region_code(substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2));
      $this->page = $filmtrailer->getFeed($trailers[$index]["film_id"]);
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
    $results = $this->page;
    $title = $results[0]["ORIGINAL_TITLE"];
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $results = $this->page;
    $synopsis = $results[0]["PRODUCTS"][0]["DESCRIPTION"];
    if (isset($synopsis)&& !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseIMDBTT() {
    $results = $this->page;
    $imdbtt = $results[0]["IMDB_ID"];
    $this->setProperty(IMDBTT, $imdbtt);
    return $imdbtt;
  }
  protected function parseActors() {
    $results = $this->page;
    $actors = $results[0]["ACTORS"];
    if (isset($actors) && !empty($actors)) {
      $actors = array_map("trim", $actors);
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $results = $this->page;
    $directors = $results[0]["DIRECTORS"];
    if (isset($directors) && !empty($directors)) {
      $directors = array_map("trim", $directors);
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $results = $this->page;
    $genres = $results[0]["CATEGORIES"];
    if (isset($genres) && !empty($genres)) {
      $genres = array_map("trim", $genres);
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $results = $this->page;
    $year = $results[0]["PRODUCTION_YEAR"];
    if (isset($year)&& !empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parseTrailer() {
    $results = $this->page;
    if (isset($results[0]["CLIPS"])) {
      $trailer = array_pop($results[0]["CLIPS"][1]["FILES"]);
      $trailer = $trailer["URL"];
    }
    if (isset($trailer) && !empty($trailer)) {
      $this->setProperty(TRAILER, $trailer);
      return $trailer;
    }
  }
  protected function parsePoster() {
    $results = $this->page;
    $poster = isset($results[0]["PICTURES"][3]) ? $results[0]["PICTURES"][3]["URL"] : $results[0]["PICTURES"][1]["URL"];
    if (url_exists($poster)) {
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