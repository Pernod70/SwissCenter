<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

class movie_wwwOFDBde extends Parser implements ParserInterface {

  public static function getName() {
    return "www.OFDb.de";
  }

  protected $site_url = 'http://www.ofdb.de/';

  // See OFDb Scraper at https://github.com/andig/videodb/blob/master/engines/ofdbscraper.php
  protected $search_url = 'http://www.ofdb.de/view.php?page=suchergebnis&SText=#####&Kat=All';

  // OFDb Gateway URL's as documented at http://www.ofdbgw.org/
  protected $searchgw_url = 'http://ofdbgw.org/search_json/#####';
  protected $moviegw_url = 'http://ofdbgw.org/movie_json/#####';
  protected $imdbgw_url = 'http://ofdbgw.org/imdb2ofdb_json/#####';
  protected $searchpersongw_url = 'http://ofdbgw.org/searchperson_json/#####';
  protected $persongw_url = 'http://ofdbgw.org/person_json/#####';

  protected $use_gateway = false;

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    ACTORS,
    DIRECTORS,
    GENRES,
    SYNOPSIS,
    YEAR,
    EXTERNAL_RATING_PC,
    MATCH_PC,
    POSTER
  );

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $search_params
   */

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Change the word order
    $this->title = db_value("select trim_article('$this->title', 'the,der,die,das,les')");

    // Get page from ofdb.de
    if ($this->use_gateway)
    {
      send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
      $search_title = str_replace('%20','+',urlencode($this->title));
      $url_load = str_replace('#####', $this->title, $this->searchgw_url);

      send_to_log(6,'Fetching information from: '.$url_load);
      $result = json_decode(file_get_contents( $url_load ), true);
    } else {
      send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
      $search_title = str_replace('%20','+',urlencode($this->title));
      $url_load = str_replace('#####', $search_title, $this->search_url);

      send_to_log(6,'Fetching information from: '.$url_load);
      $result = file_get_contents( $url_load );

      $this->accuracy = 0;

      if ($result === false) {
        send_to_log(2,'Failed to access the URL.');
      } else {
        // Is the text that signifies a successful search present within the HTML?
        if (strpos(strtolower($result),strtolower('OFDb - Suchergebnis')) !== false) {
          preg_match_all('/<br>[0-9]+\.\s*<a href="(film\/[0-9]+,[^"]*)" onmouseover="[^"]*"[^>]*>([^<]*)<font.*?\/font> \(([\/\-0-9]+)\)<\/a>/', $result, $matches);
          $index = best_match($this->title, $matches[2], $this->accuracy);

          if ($index === false)
            $result = false;
          else {
            $film_url = add_site_to_url($matches[1][$index], $this->site_url);
            send_to_log(6,'Fetching information from: '.$film_url);
            $result = file_get_contents( $film_url );
          }
        }
      }
    }
    if ($result !== false) {
      $this->page = $result;
      return true;
    } else {
      return false;
    }
  }

  /**
   * Properties supported by this parser.
   *
   */

  protected function parseIMDBTT() {
    if ($this->use_gateway) {
      $json = $this->page;
      send_to_log(2, $json);
      $imdbtt = $json['ofdbgw']['resultat']['imdbid'];
    } else {
      $html = $this->page;
      $imdbtt = preg_get('#imdb.com/Title\?(.*)"#Ui', $html);
    }
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, 'tt'.$imdbtt);
      return 'tt'.$imdbtt;
    }
  }
  protected function parseTitle() {
    if ($this->use_gateway) {
      $json = $this->page;
      $title = $json['ofdbgw']['resultat']['titel'];
    } else {
      $html = $this->page;
      $title = substr_between_strings($html,'<title>','</title>');
      $title = trim(preg_replace(array('/OFDb -/', '/\(\d{4}\)/'), '', $title));
    }
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    if ($this->use_gateway) {
      $json = $this->page;
      $synopsis = $json['ofdbgw']['resultat']['beschreibung'];
    } else {
      $html = $this->page;
      $start = strpos($html,"Inhalt:");
      if ($start !== false) {
        $end = strpos($html, "</tr>", $start + 1);
        if ($end !== false) {
          $html_synopsis = substr($html, $start, $end - $start);
          $matches = get_urls_from_html($html_synopsis, "plot");
          if (isset($matches[1]) && !empty($matches[1])) {
            send_to_log(6,'Fetching information from: '.$this->site_url.$matches[1][0]);
            $html = file_get_contents( $this->site_url.$matches[1][0] );
            $synopsis = substr_between_strings($html,'</b><br><br>','</p>');
          }
        }
      }
    }
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseYear() {
    if ($this->use_gateway) {
      $json = $this->page;
      $year = $json['ofdbgw']['resultat']['jahr'];
    } else {
      $html = $this->page;
      $start = strpos($html,"Erscheinungsjahr:");
      if ($start !== false) {
        $end = strpos($html,"</tr>", $start + 1);
        if ($end !== false) {
          $html_year = substr($html, $start, $end - $start);
          $matches = get_urls_from_html($html_year,"Jahr");
          if(isset($matches[2]) && !empty($matches[2])){
            $year = $matches[2][0];
          }
        }
      }
    }
    if (isset($year) && !empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parseActors() {
    if ($this->use_gateway) {
      $json = $this->page;
      $actors = array();
      foreach ($json['ofdbgw']['resultat']['besetzung'] as $actor)
        $actors[] = $actor['name'];
    } else {
      $html = $this->page;
      $start = strpos($html,"Darsteller:");
      if ($start !== false) {
        $end = strpos($html, "</tr>", $start + 1);
        if ($end !== false) {
          $html_actors = substr($html, $start, $end - $start);
          $matches = get_urls_from_html($html_actors, "Name");
          if(isset($matches[2]) && !empty($matches[2])){
            $actors = array_map("trim", $matches[2]);
          }
        }
      }
    }
    if (isset($actors) && !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    if ($this->use_gateway) {
      $json = $this->page;
      $directors = array($json['ofdbgw']['resultat']['regie']['name']);
    } else {
      $html = $this->page;
      $start = strpos($html,"Regie:");
      if ($start !== false) {
        $end = strpos($html, "</tr>", $start + 1);
        if ($end !== false) {
          $html_directed = substr($html, $start, $end - $start);
          $matches = get_urls_from_html($html_directed, "Name");
          if (isset($matches[2]) && !empty($matches[2])) {
            $directors = array_map("trim", $matches[2]);
          }
        }
      }
    }
    if (isset($directors) && !empty($directors)) {
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    if ($this->use_gateway) {
      $json = $this->page;
      $genres = $json['ofdbgw']['resultat']['genre'];
    } else {
      $html = $this->page;
      $start = strpos($html,"Genre(s):");
      if ($start !== false) {
        $end = strpos($html, "</tr>", $start + 1);
        if ($end !== false) {
          $html_genres = substr($html, $start, $end - $start);
          $matches = get_urls_from_html($html_genres, "genre");
          if (isset($matches[2]) && !empty($matches[2])) {
            $genres = array_map("trim", $matches[2]);
          }
        }
      }
    }
    if (isset($genres) && !empty($genres)) {
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseExternalRatingPc() {
    if ($this->use_gateway) {
      $json = $this->page;
      $extrating = $json['ofdbgw']['resultat']['bewertung']['note']*10;
    } else {
      $html = $this->page;
      $start = strpos($html,"Note:");
      if ($start !== false) {
        $end = strpos($html, "&nbsp;", $start + 1);
        if ($end !== false) {
          $matches = array();
          if (preg_match('/(\d+\.\d+)/', substr($html,$start,$end-$start), $matches) != 0)
            $extrating = intval($matches[1]*10);
          elseif (preg_match('/(\d+)/', substr($html,$start,$end-$start), $matches) != 0)
            $extrating = intval($matches[1]*10);
          else
            $extrating = 0;
        }
      }
    }
    if (isset($extrating) && !empty($extrating)) {
      $this->setProperty(EXTERNAL_RATING_PC, $extrating);
      return $extrating;
    }
  }
  protected function parsePoster() {
    if ($this->use_gateway) {
      $json = $this->page;
      $img_addr = $json['ofdbgw']['resultat']['bild'];
    } else {
      $html = $this->page;
      $img_addr = get_html_tag_attrib($html,'img', 'img.ofdb.de/film/', 'src');
    }
    if (isset($img_addr) && !empty($img_addr) && url_exists($img_addr)) {
      $this->setProperty(POSTER, $img_addr);
      return $img_addr;
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
