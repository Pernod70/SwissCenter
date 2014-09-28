<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

class movie_wwwFILMUPit extends Parser implements ParserInterface {

  public static function getName() {
    return "www.FilmUP.it";
  }

  protected $site_url = 'http://filmup.leonardo.it/';
  protected $search_url = 'http://filmup.leonardo.it/cgi-bin/search.cgi?q=#####&ul=%25%2Fsc_%25';

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

    // Get page from filmup.it
    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
    $search_title = str_replace('%20','+',urlencode(decode_utf8($this->title)));
    $url_load = str_replace('#####', $search_title, $this->search_url);

    send_to_log(6,'Fetching information from: '.$url_load);
    $html = file_get_contents( $url_load );

    $this->accuracy = 0;

    if ($html === false) {
      send_to_log(2,'Failed to access the URL.');
    } else {
      // Is the text that signifies a successful search present within the HTML?
      if (strpos(strtolower($html),strtolower('Risultati')) !== false) {
        $html = preg_get('/<\/table>\W<DL>(.*)<\/DL>\W<table/Usi', $html);
        $html = strip_tags($html, '<a>');
        preg_match_all('/<a class="filmup" href="(.*)" TARGET="_blank">.*FilmUP - Scheda: (.*)<\/a>/Us', $html, $matches);
        $index = best_match($this->title, $matches[2], $this->accuracy);

        if ($index === false)
          $html = false;
        else {
          $film_url = add_site_to_url($matches[1][$index], $this->site_url);
          send_to_log(6,'Fetching information from: '.$film_url);
          $html = file_get_contents( $film_url );
        }
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