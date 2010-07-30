<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

class wwwIMDBcom extends Parser implements ParserInterface {

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    LANGUAGES,
    YEAR,
    CERTIFICATE,
    POSTER,
    EXTERNAL_RATING_PC,
    MATCH_PC
  );

  public static function getName() {
    return "www.IMDb.com";
  }

  protected $url_imdb;
  protected $site_url = 'http://www.imdb.com/';
  protected $search_url = 'http://www.imdb.com/find?s=tt;q=';

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $search_params
   */

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];
    if (isset($search_params['YEAR']) && !empty($search_params['YEAR']))
      $year = $search_params['YEAR'];

    send_to_log(4, "Searching for details about " . $this->title . " " . $year . " online at " . $this->site_url);

    // Check filename and title for an IMDb ID
    if (! (isset ($search_params['IGNORE_IMDBTT']) && $search_params['IGNORE_IMDBTT']) ) {
	    send_to_log(4, "IMDB.com: Searching for IMDB in file details.");
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    $temp_title = $this->title;
    if (isset ($year))
      $temp_title .= " (" . $year . ")";

    if (!$imdbtt) {
      // Use IMDb's internal search to get a list a possible matches
      send_to_log(8, "Using IMDb internal search: " . $temp_title);
      $html = file_get_contents($this->search_url . str_replace('%20', '+', urlencode($temp_title)));
    } else {
      // Use IMDb ID to get movie page
      send_to_log(8, "Using IMDb ID to retrieve page: " . $imdbtt);
      $html = file_get_contents($this->search_url . $imdbtt);
    }

    // Decode HTML entities found on page
    $html = html_entity_decode($html, ENT_QUOTES);

    // Examine returned page
    if (strpos(strtolower($html), "no matches") !== false) {
      // There are no matches found... do nothing
      $this->accuracy = 0;
      send_to_log(4, $this->getNoMatchFoundHTML());
    } else
      if (strpos($html, $this->getSearchPageHTML()) > 0) {
        send_to_log(8, "Multiple IMDb matches...");

        if ((preg_match("/\((\d{4})\)/", $details["TITLE"], $title_year) != 0) || (preg_match("/\((\d{4})\)/", $temp_title, $title_year) != 0)) {
          send_to_log(8, "Found year in the title: " . $title_year[0]);
          $html = preg_replace('/<\/a>\s+\((\d{4}).*\)/Ui', ' ($1)</a>', $html);
        }
        $html = substr($html, strpos($html, "Titles"));
        $matches = get_urls_from_html($html, '\/title\/tt\d+\/');
        $index = ParserUtil :: most_likely_match($this->title, $matches[2], $this->accuracy, $year);
        // If we are sure that we found a good result, then get the file details.
        if ($this->accuracy > 75) {
          $url_imdb = add_site_to_url($matches[1][$index], $this->site_url);
          $url_imdb = substr($url_imdb, 0, strpos($url_imdb, "?fr=") - 1);
          $html = html_entity_decode(file_get_contents($url_imdb), ENT_QUOTES);
        }
      } else {
        // Direct hit on the title
        send_to_log(8, "Direct IMDb hit...");
        $this->accuracy = 100;
      }

    if ($this->accuracy >= 75) {
      $this->page = $this->getRelevantPartOfHTML($html);
      $this->url_imdb = $this->site_url . "title/" . $this->findIMDBTTInPage();
      if (isset ($this->page)) {
        send_to_log(8, "returning true..." . $this->url_imdb);
        return true;
      }
    }
    return false;
  }
  protected function getSearchPageHTML(){
    return "<title>IMDb Title Search</title>";

  }
  protected function getNoMatchFoundHTML(){
    return "No Match found.";

  }
  /*
   * store the relevant part of the page
   */
  private function getRelevantPartOfHTML($html) {
    $start = strpos($html, "<div class=\"photo\">");
    if ($start !== false) {
      $end = strpos($html, "<a name=\"comment\">");
      if ($end !== false) {
        return substr($html, $start, $end - $start + 1);
      }
    }
  }

  /**
   * Properties supported by this parser.
   *
   */

  protected function parseYear() {
    $html = $this->page;
    $year = array();
    preg_match('/href=\"\/year\/(\d+)/', $html, $year);
    if (isset($year[1]) && !empty($year[1])) {
      $this->setProperty(YEAR, $year[1]);
      return $year[1];
    }
  }
  protected function parseTitle() {
    $html = $this->page;
    if (strlen($html) !== 0) {
      $start = strpos($html, "<h1>") + 4;
      $end = strpos($html, "<span>", $start + 1);
      $title = substr($html, $start, $end - $start);
    }
    $title = trim(preg_replace('/\"/', '', ParserUtil :: decodeSpecialCharacters($title)));
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $synopsis = array();
    preg_match("/<h5>Plot(| Outline| Summary):<\/h5>\n<div class=\"info-content\">([^<]*)</sm", $html, $synopsis);
    $synopsis = trim(trim($synopsis[2]), " |");
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseIMDBTT() {
    $imdbtt = $this->findIMDBTTInPage();
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, $imdbtt);
      return $imdbtt;
    }
  }
  protected function findIMDBTTInPage() {
    $html = $this->page;
    $searchstring = "/fullcredits';";
    if (strpos($html, $searchstring) > 0) {
      $pos = strpos($html, $searchstring);
      if ($pos !== false) {
        $imdbtt = substr($html, $pos -9, 9);
        return $imdbtt;
      }
    }
  }
  protected function parseActors() {
    if (get_sys_pref('PARSER_IMDB_FULL_CAST', 'NO') == 'YES')
      $html = file_get_contents($this->url_imdb . "/fullcredits#cast");
    else
      $html = $this->page;

    $start = strpos($html, "<table class=\"cast\">");
    $end = strpos($html, "</table>", $start + 1);
    $html_actors = substr($html, $start, $end - $start);
    $matches = get_urls_from_html($html_actors, "\/name\/nm\d+\/");
    for ($i = 0; $i < count($matches[2]); $i++) {
      if (strlen($matches[2][$i]) == 0) {
        array_splice($matches[2], $i, 1);
        $i--;
      }
    }
    $actors = ParserUtil :: decodeSpecialCharactersList($matches[2]);
    if (isset($actors) && !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $html  = $this->page;
    $start = strpos($html, "<h5>Director");
    if ($start !== false) {
      $end = strpos($html, "<h5>", $start + 1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, "\/name\/nm\d+\/");
        if (isset($matches[2])&& !empty($matches[2])) {
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseGenres() {
    $html  = $this->page;
    $start = strpos($html,"<h5>Genre:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_genres = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_genres,"\/Sections\/Genres\/");
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(GENRES, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseLanguages() {
    $html  = $this->page;
    $start = strpos($html,"<h5>Language:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_langs = str_replace("\n","",substr($html,$start,$end-$start));
        $matches = get_urls_from_html($html_langs,"\/Sections\/Languages\/");
        $new_languages = $matches[2];
        if (isset($new_languages) && !empty($new_languages)) {
          $this->setProperty(LANGUAGES, $new_languages);
          return $new_languages;
        }
      }
    }
  }
  protected function parseExternalRatingPc() {
    $html = $this->page;
    $user_rating = preg_get("/<h5>User Rating:<\/h5>.*?<b>(.*)\/10<\/b>/sm", $html);
    if (!empty ($user_rating)) {
      $user_rating = intval($user_rating * 10);
      $this->setProperty(EXTERNAL_RATING_PC, $user_rating);
      return $user_rating;
    }
  }
  protected function parseCertificate() {
    $html = $this->page;
    $certlist = array ();
    foreach (explode('|', substr_between_strings($html, 'Certification:', '</div>')) as $cert) {
      $country = trim(substr($cert, 0, strpos($cert, ':')));
      $certificate = trim(substr($cert, strpos($cert, ':') + 1)) . ' ';
      $certlist[$country] = substr($certificate, 0, strpos($certificate, ' '));
    }
    if (get_rating_scheme_name() == 'BBFC')
      $rating = (isset ($certlist["UK"]) ? $certlist["UK"] : $certlist["USA"]);
    elseif (get_rating_scheme_name() == 'MPAA')
      $rating = (isset ($certlist["USA"]) ? $certlist["USA"] : $certlist["UK"]);
    elseif (get_rating_scheme_name() == 'Kijkwijzer')
      $rating = (isset ($certlist["Netherlands"]) ? $certlist["Netherlands"] : (isset ($certlist["USA"]) ? $certlist["USA"] : $certlist["UK"]));
    if(isset($rating) && !empty($rating)){
      $this->setProperty(CERTIFICATE, $rating);
      return $rating;
    }
  }
  protected function parsePoster() {
    $html = $this->page;
    $matches = get_images_from_html($html);
    $img_addr = $matches[1][0];
    if (file_ext($img_addr) == 'jpg' && !stristr($img_addr, 'addposter')) {
      // Replace resize attributes with maximum allowed
      $img_addr = preg_replace('/SX\d+_/', 'SX450_', $img_addr);
      $img_addr = preg_replace('/SY\d+_/', 'SY700_', $img_addr);
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