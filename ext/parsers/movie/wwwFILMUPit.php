<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

class wwwFILMUPit extends Parser implements ParserInterface {

  public static function getName() {
    return "www.FilmUP.it";
  }

  protected $site_url = 'http://filmup.leonardo.it/';

  public $supportedProperties = array (
    ACTORS,
    DIRECTORS,
    GENRES,
    SYNOPSIS,
    TITLE,
    YEAR,
    EXTERNAL_RATING_PC,
    POSTER,
    MATCH_PC
  );

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $search_params
   */

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Get search results from google.
    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
    $results = google_api_search("Scheda: " . $this->title, "filmup.leonardo.it");

    $this->accuracy = 0;

    if (count($results) == 0) {
      send_to_log(4, "No Match found.");
      $html = false;
    } else {
      $best_match = google_best_match('FilmUP - Scheda: ' . $this->title, $results, $this->accuracy);

      if ($best_match === false)
        $html = false;
      else {
        $filmup_url = $best_match->url;
        send_to_log(6, 'Fetching information from: ' . $filmup_url);
        $html = file_get_contents($filmup_url);
      }
    }
    if ($html !== false) {
      $this->page = $html;
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
    $html = $this->page;
    $title = substr_between_strings($html, '<title>FilmUP - Scheda: ', '</title>');
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $synopsis = html_entity_decode(substr_between_strings($html, 'Trama:<br>', '<br>'), ENT_QUOTES);
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $html = $this->page;
    $actors = explode(",", html_entity_decode(substr_between_strings($html, 'Cast:&nbsp;</font>', '</font>'), ENT_QUOTES));
    if (isset($actors) && !empty($actors)) {
      $actors = array_map("trim", $actors);
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $html = $this->page;
    $directors = explode(",", html_entity_decode(substr_between_strings($html, 'Regia:&nbsp;</font>', '</font>'), ENT_QUOTES));
    if (isset($directors) && !empty($directors)) {
      $directors = array_map("trim", $directors);
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $html = $this->page;
    $genres = explode(",", html_entity_decode(substr_between_strings($html, 'Genere:&nbsp;</font>', '</font>'), ENT_QUOTES));
    if (isset($genres) && !empty($genres)) {
      $genres = array_map("trim", $genres);
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $html = $this->page;
    $year = substr_between_strings($html, 'Anno:&nbsp;</font>', '</font>');
    if (isset($year) && !empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parseExternalRatingPc() {
    $html = $this->page;
    $opinioni_uid = preg_get('/opinioni\/op.php\?uid=(\d+)"/', $html);
    if (!empty ($opinioni_uid)) {
      $html = file_get_contents($this->site_url . 'opinioni/op.php?uid=' . $opinioni_uid);
      $user_rating = preg_get('/Media Voto:.*<b>(.*)<\/b>/Uism', $html);
      $rating = (empty ($user_rating) ? '' : $user_rating * 10);
      if (!empty($rating)) {
        $this->setProperty(EXTERNAL_RATING_PC, $rating);
        return $rating;
      }
    }
  }
  protected function parsePoster() {
    $html = $this->page;
    $img_addr = get_html_tag_attrib($html, 'img', 'locand/', 'src');
    if ($img_addr !== false) {
      $poster = $this->site_url.$img_addr;
      if (url_exists($poster)) {
        $this->setProperty(POSTER, $poster);
        return $poster;
      }
    }
  }
  protected function parseMatchPc() {
    if (isset ($this->accuracy)) {
      $this->setProperty(MATCH_PC, $this->accuracy);
      return $this->accuracy;
    }
  }
}
?>