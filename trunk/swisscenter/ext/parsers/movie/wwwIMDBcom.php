<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

class wwwIMDBcom extends Parser implements ParserInterface {
  protected $site_url = 'http://www.imdb.com/';
  protected $search_url = 'http://www.imdb.com/find?s=tt&q=';

  protected $match_plot = 'Plot';
  protected $match_genre = 'Genre';
  protected $match_director = 'Director';
  protected $match_language = 'Language';
  protected $match_certificate = 'Certification';

  public $supportedProperties = array (
    IMDBTT,
    TITLE,
    SYNOPSIS,
    ACTORS,
    ACTOR_IMAGES,
    DIRECTORS,
    GENRES,
    LANGUAGES,
    YEAR,
    CERTIFICATE,
    POSTER,
    EXTERNAL_RATING_PC,
    MATCH_PC
  );

  public $settings = array (
    FULL_CAST => array("options" => array('Yes', 'No'),
                       "default" => 'No')
  );

  public static function getName() {
    return "www.IMDb.com";
  }

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
      send_to_log(4, "Searching for IMDb ID in file details.");
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    $temp_title = $this->title;
    if (isset ($year))
      $temp_title .= " (" . $year . ")";

    if (!$imdbtt) {
      // Use IMDb's internal search to get a list a possible matches
      $search_url = $this->search_url . str_replace('%20', '+', urlencode($temp_title));
      send_to_log(8, "Using IMDb internal search: " . $search_url);

      // Download page and decode HTML entities
      $html = html_entity_decode(file_get_contents($search_url), ENT_QUOTES);
      $html = html_entity_decode(ParserUtil :: decodeHexEntities($html), ENT_QUOTES, 'ISO-8859-15');

      // Examine returned page
      if (strpos($html, $this->getNoMatchFoundHTML()) > 0) {
        // No matches found
        $this->accuracy = 0;
        send_to_log(4, "No matches");
      } elseif (strpos($html, $this->getSearchPageHTML()) > 0) {
        // Search results for a match
        send_to_log(8, "Multiple IMDb matches...");

        if ((preg_match('/\((\d{4})\)/', $details["TITLE"], $title_year) != 0) || (preg_match('/\((\d{4})\)/', $temp_title, $title_year) != 0)) {
          send_to_log(8, "Found year in the title: " . $title_year[0]);
          $html = preg_replace('/<\/a>\s+\((\d{4}).*\)/Ui', ' ($1)</a>', $html);
        }
        $html = substr($html, strpos($html, "Titles"));
        $matches = get_urls_from_html($html, '\/title\/tt\d+\/');
        $index = ParserUtil :: most_likely_match($this->title, $matches[2], $this->accuracy, $year);

        // If we are sure that we found a good result, then get the file details.
        if ($this->accuracy > 75) {
          $url_imdb = add_site_to_url($matches[1][$index], $this->site_url);
          $url_imdb = substr($url_imdb, 0, strpos($url_imdb, "?") - 1);
          send_to_log(8, 'Using IMDb ID: '.$url_imdb);
        }
      } else {
        // Direct hit on the title
        $url_imdb = $this->site_url . "title/" . $this->findIMDBTTInPage($html);
        send_to_log(8, 'Direct IMDb hit: '.$url_imdb);
        $this->accuracy = 100;
      }
    } else {
      // Use IMDb ID to get movie page
      $url_imdb = $this->site_url . 'title/' . $imdbtt;
      send_to_log(8, 'Using IMDb ID: '.$url_imdb);
      $this->accuracy = 100;
    }

    // Download the combined view of the required page
    if ($this->accuracy >= 75) {
      $html = html_entity_decode(file_get_contents($url_imdb . '/combined'), ENT_QUOTES);
      $html = html_entity_decode(ParserUtil :: decodeHexEntities($html), ENT_QUOTES, 'ISO-8859-15');
      $this->page = $this->getRelevantPartOfHTML($html);
      if (isset ($this->page)) {
        send_to_log(8, "Returning true..." . $url_imdb);
        return true;
      }
    }
    return false;
  }
  protected function getSearchPageHTML(){
    return "<title>Find - IMDb</title>";
  }
  protected function getNoMatchFoundHTML(){
    return "No Matches.";
  }

  /**
   * Get the relevant part of the page
   */
  private function getRelevantPartOfHTML($html) {
    $start = strpos($html, "<div id=\"pagecontent\">");
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
    $year = preg_get('/<b>.*\((\d{4})\).*<\/b>/Us', $html);
    if (!empty($year)) {
      $this->setProperty(YEAR, $year);
      return $year;
    }
  }
  protected function parseTitle() {
    $html = $this->page;
    if (strlen($html) !== 0) {
      $start = strpos($html, "<h1>") + 4;
      $end = strpos($html, "<span>", $start + 1);
      $title = substr($html, $start, $end - $start);
    }
    $title = trim(preg_replace('/\"/', '', ParserUtil :: decodeHexEntities($title)));
    if (isset($title) && !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $start = strpos($html,"<h5>$this->match_plot:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_synopsis = substr($html,$start,$end-$start+1);
        $matches = preg_get("/class=\"info-content\">(.*)</Usm", $html_synopsis);
        $synopsis = trim(trim($matches), " |");
        if (isset($synopsis) && !empty($synopsis)) {
          $this->setProperty(SYNOPSIS, $synopsis);
          return $synopsis;
        }
      }
    }
  }
  protected function parseIMDBTT() {
    $imdbtt = $this->findIMDBTTInPage($this->page);
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, $imdbtt);
      return $imdbtt;
    }
  }
  protected function findIMDBTTInPage($html) {
    return preg_get('~;id=(tt\d+);~U', $html);
  }
  protected function parseActors() {
    $html = $this->page;
    $start = strpos($html, "<table class=\"cast\">");
    if (get_sys_pref(get_class($this).'_FULL_CAST', $this->settings[FULL_CAST]["default"]) == 'YES')
      $end = strpos($html, "</table>", $start + 1);
    else
      $end = (strpos($html, "<small>", $start + 1) !== false ? strpos($html, "<small>", $start + 1) : strpos($html, "</table>", $start + 1));
    $html_actors = substr($html, $start, $end - $start);
    $matches = get_urls_from_html($html_actors, '\/name\/nm\d+\/');
    for ($i = 0; $i < count($matches[2]); $i++) {
      if (strlen($matches[2][$i]) == 0) {
        array_splice($matches[2], $i, 1);
        $i--;
      }
    }
    $actors = ParserUtil :: decodeHexEntitiesList($matches[2]);
    if (isset($actors) && !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseActorImages() {
    $html = $this->page;
    $start = strpos($html, "<table class=\"cast\">");
    if (get_sys_pref(get_class($this).'_FULL_CAST', $this->settings[FULL_CAST]["default"]) == 'YES')
      $end = strpos($html, "</table>", $start + 1);
    else
      $end = strpos($html, "<small>", $start + 1);
    $html_actors = substr($html, $start, $end - $start);
    preg_match_all('/<img src="(.*)" width="\d{2}" height="\d{2}" border="0">.*<\/td><td class="nm"><a href="\/name\/(nm\d+)\/" onclick=".*">(.*)<\/a>/Us', $html_actors, $matches);
    $actors = array();
    for ($i = 0; $i < count($matches[0]); $i++) {
      if (strpos($matches[1][$i], 'no_photo') === false && strlen($matches[2][$i]) > 0) {
        // Remove the resize attributes (_V1._SYxx_SXxx_)
        $matches[1][$i] = preg_replace('/\._V\d\._.*\.jpg/U', '.jpg', $matches[1][$i]);

        $actors[] = array('ID'    => $matches[2][$i],
                          'IMAGE' => $matches[1][$i],
                          'NAME'  => ParserUtil :: decodeHexEntities($matches[3][$i]));
      }
    }
    if (isset($actors) && !empty($actors)) {
      $this->setProperty(ACTOR_IMAGES, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $html  = $this->page;
    $start = strpos($html, "<h5>$this->match_director");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, '\/name\/nm\d+\/');
        if (isset($matches[2])&& !empty($matches[2])) {
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseGenres() {
    $html  = $this->page;
    $start = strpos($html,"<h5>$this->match_genre:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_genres = substr($html,$start,$end-$start);
        $matches = get_urls_from_html($html_genres,'\/Sections\/Genres\/');
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(GENRES, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseLanguages() {
    $html  = $this->page;
    $start = strpos($html,"<h5>$this->match_language:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_langs = str_replace("\n","",substr($html,$start,$end-$start));
        $matches = get_urls_from_html($html_langs,'\/language\/');
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
    $user_rating = preg_get('/<div.*class=\"starbar-meta\">.*<b>(.*)\/10<\/b>/Usm', $html);
    if (!empty ($user_rating)) {
      $user_rating = intval($user_rating * 10);
      $this->setProperty(EXTERNAL_RATING_PC, $user_rating);
      return $user_rating;
    }
  }
  protected function parseCertificate() {
    $html = $this->page;
    $certlist = array ();
    foreach (explode('|', substr_between_strings($html, $this->match_certificate.':', '</div>')) as $cert) {
      $cert = preg_replace('/\(.*\)/U', '', $cert);
      $country = trim(substr($cert, 0, strpos($cert, ':')));
      $certificate = trim(substr($cert, strpos($cert, ':') + 1)) . ' ';
      $certlist[$country] = substr($certificate, 0, strpos($certificate, ' '));
    }
    switch (get_rating_scheme_name())
    {
      case 'FSK':
        if (isset($certlist["Germany"])) {
          $rating = 'FSK '.$certlist["Germany"];
          break;
        }
      case 'Kijkwijzer':
        if (isset($certlist["Netherlands"])) {
          $rating = $certlist["Netherlands"];
          break;
        }
      case 'BBFC':
        if (isset($certlist["UK"])) {
          $rating = $certlist["UK"];
          break;
        }
      default:
        if (isset($certlist["USA"])) {
          $rating = $certlist["USA"];
        }
    }
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
      // Remove the resize attributes (_V1._SYxx_SXxx_)
      $img_addr = preg_replace('/\._V\d\._.*\.jpg/U', '.jpg', $img_addr);
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