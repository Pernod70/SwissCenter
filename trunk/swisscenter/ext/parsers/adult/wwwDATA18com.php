<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

class adult_wwwDATA18com extends Parser implements ParserInterface {
  protected $site_url = 'http://www.data18.com/';
  protected $search_url = 'http://http://www.data18.com/search/?t=0&k=#####';

  public $supportedProperties = array (
    TITLE,
    SYNOPSIS,
    ACTORS,
    ACTOR_IMAGES,
    DIRECTORS,
    GENRES,
    YEAR,
    POSTER,
    MATCH_PC
  );

  public static function getName() {
    return "www.DATA18.com";
  }

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $search_params
   */

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Get page from Data18.com
    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
    $search_title = str_replace('%20','+',urlencode($this->title));
    $url_load = str_replace('#####', $search_title, $this->search_url);

    send_to_log(6,'Fetching information from: '.$url_load);
    $html = file_get_contents( $url_load );

    $this->accuracy = 0;

    if ($html === false) {
      send_to_log(2,'Failed to access the URL.');
    } else {
      $html = substr($html, strpos($html, "Movies:"));
      $matches = get_urls_from_html($html, '\/movies\/\d+');
      $index = best_match($this->title, $matches[2], $this->accuracy);

      if ($index === false)
          $html = false;
      else {
        $film_url = $matches[1][$index];
        send_to_log(6,'Fetching information from: '.$film_url);
        $html = file_get_contents( $film_url );
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

  protected function parseYear() {
    $html = $this->page;
    $year = substr_between_strings($html,'<title>','</title>');
    $year = preg_get('/\((\d{4})\)/', $year);
    if (!empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parseTitle() {
    $html = $this->page;
    $title = substr_between_strings($html,'<title>','</title>');
    $title = trim(preg_replace('/\(\d{4}\) - Porn Movie - data18.com/', '', $title));
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $start = strpos($html,"<b>Description:</b>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_synopsis = substr($html,$start,$end-$start+1);
        $synopsis = substr_between_strings($html_synopsis,'>','<');
        if (isset($synopsis) && !empty($synopsis)) {
          $this->setProperty(SYNOPSIS, $synopsis);
          return $synopsis;
        }
      }
    }
  }
  protected function parseActors() {
    $html = $this->page;
    $start = strpos($html, "Cast of");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_actors = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_actors, "data18");
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(ACTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseActorImages() {
    $html = $this->page;
    $start = strpos($html, "Cast of");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_actors = substr($html, $start, $end - $start);
        preg_match_all('/<img src="(.*)" class="yborder" alt="(.*)" \/><\/a>/Us', $html_actors, $matches);
        $actors = array();
        for ($i = 0; $i < count($matches[0]); $i++) {
          if (strpos($matches[1][$i], 'no_photo') === false && strlen($matches[2][$i]) > 0) {
            // Get larger size
            $matches[1][$i] = preg_replace('/\/60\//', '/120/', $matches[1][$i]);
            $actors[] = array('ID'    => $matches[2][$i],
                              'IMAGE' => $matches[1][$i],
                              'NAME'  => $matches[2][$i]);
          }
        }
      }
    }
    if (isset($actors) && !empty($actors)) {
      $this->setProperty(ACTOR_IMAGES, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $html  = $this->page;
    $start = strpos($html, "Director:");
    if ($start !== false) {
      $end = strpos($html,"</p>", $start + 1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, 'director');
        if (isset($matches[2])&& !empty($matches[2])) {
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseGenres() {
    $html  = $this->page;
    $start = strpos($html,"Categories:");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_genres = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_genres,'movies');
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(GENRES, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parsePoster() {
    $html = $this->page;
    $poster = preg_get('/<a href="(.*)" class="grouped_elements" rel="covers"/Usm', $html);
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
?>