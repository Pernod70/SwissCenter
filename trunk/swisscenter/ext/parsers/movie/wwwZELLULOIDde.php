<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as genre, year of release, synopsis, directors and actors.

 *************************************************************************************************/

class wwwZELLULOIDde extends Parser implements ParserInterface {

  public static function getName() {
    return "www.zelluloid.de";
  }

  protected $site_url = 'http://www.zelluloid.de/';

  public $supportedProperties = array (
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    CERTIFICATE,
    EXTERNAL_RATING_PC,
    POSTER,
    MATCH_PC,
  );

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $params_in
   */

  protected function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    // Get search results from google.
    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);
    $results = google_api_search('allintitle:'.$this->title, "zelluloid.de");

    // Adjust results to improve possible matches
    foreach ($results as $i=>$result) {
      // Remove site ' | zelluloid.de'
      $results[$i]->titleNoFormatting = trim(preg_replace('/ \| zelluloid.de/', '', $result->titleNoFormatting));
    }

    $this->accuracy = 0;

    if (count($results)==0)
    {
      send_to_log(4,"No Match found.");
      $html = false;
    }
    else
    {
      $best_match = google_best_match($this->title, $results, $this->accuracy);

      if ($best_match === false)
        $html = false;
      else
      {
        $zelluloid_url = urldecode($best_match->url);
        if (strpos($zelluloid_url,'index.php3')==false)
        {
          $zelluloid_url = str_replace('details.php3','index.php3',$zelluloid_url);
        }
        send_to_log(6,'Fetching information from: '.$zelluloid_url);
        $html = file_get_contents( $zelluloid_url );
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

  protected function parseActors() {
    $html = $this->page;
    $start = strpos($html,"<DIV CLASS=\"castcrew\">mit");
    if ($start !== false) {
      $end = strpos($html," <A TITLE=\"Detaillierte Besetzung", $start + 1);
      if ($end !== false) {
        $html_actors = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_actors, "person");
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(ACTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseDirectors() {
    $html = $this->page;
    $start = strpos($html,"<DIV><B>Regie:</B>");
    if ($start !== false) {
      $end = strpos($html, "<BR>" ,$start + 1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, "person");
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseGenres() {
    $html = $this->page;
    $start = strpos($html, "ALT=\"Poster zu ");
    if ($start !== false) {
      $end = strpos($html, "az.php3?l=", $start + 1);
      if ($end !== false) {
        $html_genres = substr($html ,$start, $end - $start);
        $matches = get_urls_from_html($html_genres,"g=");
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(GENRES, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $start = strpos($html, '<div class="bigtext">');
    if ($start !== false) {
      $end = strpos($html, "</div>", $start + 1);
      if ($end !== false) {
        $synopsis = strip_tags(substr($html, $start, $end - $start));
        if (isset($synopsis) && !empty($synopsis)) {
          $this->setProperty(SYNOPSIS, $synopsis);
          return $synopsis;
        }
      }
    }
  }
  protected function parseYear() {
    $html = $this->page;
    $start = strpos($html, "az.php3?l=");
    if ($start !== false) {
      $end = strpos($html,"<BR>", $start + 1);
      if ($end !== false) {
        $html_year = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_year,"j=");
        $year = $matches[2][0];
        if (isset($year) && !empty($year)) {
          $this->setProperty(YEAR, $year);
          return $year;
        }
      }
    }
  }
  protected function parseCertificate() {
    $html = $this->page;
    $start = strpos($html,"FSK: ");
    if ($start !== false) {
      $end = strpos($html, "</TD>", $start + 1);
      if ($start !== false) {
        $cert = preg_get('/(FSK: ab \d+|FSK: ohne)/',substr($html, $start, $end - $start));
        $cert = str_replace('FSK: ab', 'FSK', $cert);
        $cert = str_replace('FSK: ohne','FSK 0',$cert);
        if (isset($cert) && !empty($cert)) {
          $this->setProperty(CERTIFICATE, $cert);
          return $cert;
        }
      }
    }
  }
  protected function parseExternalRatingPc() {
    $html = $this->page;
    $start = strpos($html,"<b>Besucher</b>");
    if ($start !== false) {
      $end = strpos($html,'</table>', $start + 1);
      if ($end !== false) {
        $html_rating = substr($html, $start, $end - $start);
        $matches = array();
        preg_match('/<div>(\d+)%<\/div>/', $html_rating, $matches);
        $rating = $matches[1];
        if (isset($rating) && !empty($rating)) {
          $this->setProperty(EXTERNAL_RATING_PC, $rating);
          return $rating;
        }
      }
    }
  }
  protected function parsePoster() {
    $html = $this->page;
    $img_addr = get_html_tag_attrib($html,'a','images/poster/','pic');
    if ($img_addr === false)
      $img_addr = get_html_tag_attrib($html,'img','images/poster/','src');
    $poster = $this->site_url.$img_addr;
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
