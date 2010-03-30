<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

   Version history:
   15-Oct-2007: v1.0:     First public release
   10-Jan-2008: v1.1:     Added image download, and removed 'more' from synopsis.
   11-Jan-2008: v1.2:     Fixed bug with search, and now gets full synopsis.
   01-Feb-2008: v1.3:     Improved accuracy of search results.
   05-Apr-2008: v1.4:     Fixed image and synopsis due to site changes. Page is now UTF8 encoded.
   28-Jun-2008: v1.5:     Removed year ie.(2007) when matching titles, also changed word order.
   29-Sep-2008: Mod alpha Trying to improve Match Quality (KMan Mod)
   17-Feb-2009: Mod 0.2   Changed to use the google api, just the original parser
   19-Feb-2009: Mod 0.3   EXTERNAL_RATING_PC field added

 *************************************************************************************************/

require_once( SC_LOCATION."/ext/json/json.php");

class wwwOFDBde extends Parser implements ParserInterface {

  public static function getName() {
    return "www.OFDb.de";
  }

  protected $site_url = 'http://www.ofdb.de/';

  public $supportedProperties = array (
    IMDBTT,
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

    // Get search results from google.
    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
    $results = google_api_search('allintitle:-review+'.$this->title, "ofdb.de");

    // Change the word order
    $title = $this->title;
    if ( substr($this->title,0,4) == 'The ' ) $title = substr($this->title,4).', The';
    if ( substr($this->title,0,4) == 'Der ' ) $title = substr($this->title,4).', Der';
    if ( substr($this->title,0,4) == 'Die ' ) $title = substr($this->title,4).', Die';
    if ( substr($this->title,0,4) == 'Das ' ) $title = substr($this->title,4).', Das';

    $this->accuracy = 0;

    if (count($results) == 0) {
      send_to_log(4, "No Match found.");
      $html = false;
    } else {
      $best_match = google_best_match('OFDb - ' . $title, $results, $this->accuracy);

      if ($best_match === false)
        $html = false;
    }

    if ($html === false)
    {
      $this->accuracy = 0;

      // Get search results from google.
      send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
      $results = google_api_search($this->title, "ofdb.de");

      if (count($results)==0) {
        send_to_log(4,"No Match found.");
        $html = false;
      } else {
        $best_match = google_best_match('OFDb - ' . $title, $results, $this->accuracy);

        if ($best_match === false)
          $html = false;
      }
    }

    if ($best_match === false)
        $html = false;
    else {
      $ofdb_url = $best_match->url;

      if (strpos($ofdb_url,'film') == false && strpos($ofdb_url,'fid') !== false)
      {
        $ofdb_url = str_replace('fassung','film',$ofdb_url);
        $ofdb_url = str_replace('inhalt','film',$ofdb_url);
        $ofdb_url = str_replace('review','film',$ofdb_url);
      }
      send_to_log(6,'Fetching information from: '.$ofdb_url);
      $html = utf8_decode(file_get_contents( $ofdb_url ));
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

  protected function parseIMDBTT() {
    $html = $this->page;
    $imdbtt = preg_get('#imdb.com/Title\?(.*)"#Ui', $html);
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, 'tt'.$imdbtt);
      return 'tt'.$imdbtt;
    }
  }
  protected function parseTitle() {
    $html = $this->page;
    $title = substr_between_strings($html,'<title>','</title>');
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $start = strpos($html,"Inhalt:");
    if ($start !== false) {
      $end = strpos($html, "</tr>", $start + 1);
      if ($end !== false) {
        $html_synopsis = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_synopsis, "plot");
        if (isset($matches[1]) && !empty($matches[1])) {
          send_to_log(6,'Fetching information from: '.$this->site_url.$matches[1][0]);
          $html = utf8_decode(file_get_contents( $this->site_url.$matches[1][0] ));
          $synopsis = substr_between_strings($html,'</b><br><br>','</p>');
          $this->setProperty(SYNOPSIS, $synopsis);
          return $synopsis;
        }
      }
    }
  }
  protected function parseYear() {
    $html = $this->page;
    $start = strpos($html,"Erscheinungsjahr:");
    if ($start !== false) {
      $end = strpos($html,"</tr>", $start + 1);
      if ($end !== false) {
        $html_year = substr($html, $start, $end  -$start);
        $matches = get_urls_from_html($html_year,"Jahr");
        if(isset($matches[2]) && !empty($matches[2])){
          $this->setProperty(ACTORS, $matches[2][0]);
          return $matches[2][0];
        }
      }
    }
  }
  protected function parseActors() {
    $html = $this->page;
    $start = strpos($html,"Darsteller:");
    if ($start !== false) {
      $end = strpos($html, "</tr>", $start + 1);
      if ($end !== false) {
        $html_actors = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_actors, "Name");
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
    $start = strpos($html,"Regie:");
    if ($start !== false) {
      $end = strpos($html, "</tr>", $start + 1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, "Name");
        if (isset($matches[2]) && !empty($matches[2])) {
          $matches[2] = array_map("trim", $matches[2]);
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseGenres() {
    $html = $this->page;
    $start = strpos($html,"Genre(s):");
    if ($start !== false) {
      $end = strpos($html, "</tr>", $start + 1);
      if ($end !== false) {
        $html_genres = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_genres, "genre");
        if (isset($matches[2]) && !empty($matches[2])) {
          $matches[2] = array_map("trim", $matches[2]);
          $this->setProperty(GENRES, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseExternalRatingPc() {
    $html = $this->page;
    $start = strpos($html,"Note:");
    if ($start !== false) {
      $end = strpos($html, "&nbsp;", $start + 1);
      if ($end !== false) {
        $matches = array();
        if (preg_match("/(\d+\.\d+)/", substr($html,$start,$end-$start), $matches) != 0)
          $extrating = intval($matches[1]*10);
        elseif (preg_match("/(\d+)/", substr($html,$start,$end-$start), $matches) != 0)
          $extrating = intval($matches[1]*10);
        else
          $extrating = 0;
        $this->setProperty(EXTERNAL_RATING_PC, $extrating);
        return $extrating;
      }
    }
  }
  protected function parsePoster() {
    $html = $this->page;
    $img_addr = get_html_tag_attrib($html,'img', 'img.ofdb.de/film/', 'src');
    if ($img_addr !== false) {
      if (url_exists($img_addr)) {
        $this->setProperty(POSTER, $img_addr);
        return $img_addr;
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
