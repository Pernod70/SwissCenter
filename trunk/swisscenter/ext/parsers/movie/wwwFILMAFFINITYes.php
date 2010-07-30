<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

   Version history:
   26-May-2008: v1.0:     First public release

 *************************************************************************************************/

class wwwFILMAFFINITYes extends Parser implements ParserInterface {

  public static function getName() {
    return "www.FilmAffinity.es";
  }

  protected $site_url   = 'http://www.filmaffinity.com/';
  protected $search_url = 'http://www.filmaffinity.com/es/search.php?stext=#####&stype=title';

  public $supportedProperties = array (
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    YEAR,
    POSTER,
    MATCH_PC,
  );

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $search_params
   */

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Get page from filmaffinity.com
    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
    $search_title = str_replace('%20','+',urlencode($this->title));
    $url_load = str_replace('#####', $search_title, $this->search_url);

    send_to_log(6,'Fetching information from: '.$url_load);
    $html = file_get_contents( $url_load );

    $this->accuracy = 0;

    if ($html === false) {
      send_to_log(2,'Failed to access the URL.');
    } else {
      // Is the text that signifies a successful search present within the HTML?
      if (strpos(strtolower($html),strtolower('Resultados por título')) !== false) {
        preg_match_all ('/<b><a.*href="(.*\/es\/film\d+\.html[^"]*)"[^>]*>(.*)<\/a>/Ui', $html, &$matches);
//        $matches = get_urls_from_html($html, '\/es\/film\d+\.html');
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
    $title = substr_between_strings($html,'<title>','</title>');
    $title = trim(preg_replace('/\(\d{4}\) - FilmAffinity/', '', $title));
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $synopsis = substr_between_strings($html,'SINOPSIS','(FILMAFFINITY)');
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $html = $this->page;
    $start = strpos($html,"REPARTO");
    if ($start !== false) {
      $end = strpos($html, "</tr>", $start +1);
      if ($end !== false) {
        $html_actors = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_actors, "cast");
        if(isset($matches[2]) && !empty($matches[2])){
          $matches[2] = array_map("trim", $matches[2]);
          $this->setProperty(ACTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseDirectors() {
    $html = $this->page;
    $start = strpos($html,"DIRECTOR");
    if ($start !== false) {
      $end = strpos($html, "</tr>", $start +1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, "director");
        if(isset($matches[2]) && !empty($matches[2])) {
          $matches[2] = array_map("trim", $matches[2]);
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseYear() {
    $html = $this->page;
    $year = substr_between_strings($html,'<b>AÑO</b></td>','</td>');
    if (isset($year) && !empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parsePoster() {
    $html = $this->page;
    $poster = get_html_tag_attrib($html,'img','pics.filmaffinity.com','src');
    if (url_exists($poster)) {
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
}
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
