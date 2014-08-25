<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the music videos that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

class mvid_wwwMVDBASEcom extends Parser implements ParserInterface {

  public $supportedProperties = array (
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR
  );

  public static function getName() {
    return "www.mvdbase.com";
  }

  protected $site_url = 'http://www.mvdbase.com/';
  protected $search_url = 'http://www.mvdbase.com/results.php?term=#####&field=art';

  /**
   * Populate the page variable. This is the part of the html thats needed to get all the properties.
   *
   * @param array $search_params
   */

  protected function populatePage($search_params) {

    $this->accuracy = 0;

    send_to_log(4, "Searching for details about title: " . $this->title . " online at " . $this->site_url);

    // Determine artist and track from title
    if (strpos($this->title, '-')) {
      $artist = trim(array_shift(explode('-', $this->title)));
      $track  = trim(array_pop(explode('-', $this->title)));
    }

    $search_artist = str_replace('%20','+',urlencode($artist));
    $url_load = str_replace('#####', $search_artist, $this->search_url);

    send_to_log(6,'Fetching information from: '.$url_load);
    $html = file_get_contents( $url_load );

    if ($html === false) {
      send_to_log(2,'Failed to access the URL.');
    } else {
      // Is the text that signifies a successful search present within the HTML?
      if (strpos(strtolower($html),'search results') !== false) {
        $matches = get_urls_from_html($html, 'artist\.php.*');
        $index = best_match($artist, $matches[2], $this->accuracy);
        if ($index === false)
          $html = false;
        else {
          // Get page containing all artist videos
          $video_url = add_site_to_url($matches[1][$index], $this->site_url);
          send_to_log(6,'Fetching information from: '.$video_url);
          $html = file_get_contents( $video_url );
        }
      }
    }

    if ($html === false) {
      send_to_log(2,'Failed to access the URL.');
    } else {
      // Is the text that signifies a successful search present within the HTML?
      if (strpos(strtolower($html),'artist videography') !== false) {
        preg_match_all ('/<a.*href="(video\.php.*)">(.*)<\/a>/Ui', $html, &$matches);
        send_to_log(2,'matches',$matches);
        $index = best_match($track, $matches[2], $this->accuracy);
        if ($index === false)
          $html = false;
        else {
          // Get page containing video details
          $video_url = add_site_to_url($matches[1][$index], $this->site_url);
          send_to_log(6,'Fetching information from: '.$video_url);
          $html = file_get_contents( $video_url );
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

  protected function parseActors() {
    $html = $this->page;
    $matches = array();
    preg_match_all('/<TR><TD>actor<\/TD><TD><A HREF="tech.*">(.*)<\/A><\/TD><\/TR>/', $html, $matches);
    if (isset($matches[2]) && !empty($matches[2])) {
      $this->setProperty(ACTORS, $matches[2]);
      return $matches[2];
    }
  }
  protected function parseDirectors() {
    $html = $this->page;
    $start = strpos($html, "<B>Director(s)</B>");
    if ($start !== false) {
      $end = strpos($html, "</TR>", $start + 1);
      if ($end !== false) {
        $html_directed = substr($html, $start, $end - $start);
        $matches = get_urls_from_html($html_directed, "tech.*");
        if (isset($matches[2]) && !empty($matches[2])) {
          $this->setProperty(DIRECTORS, $matches[2]);
          return $matches[2];
        }
      }
    }
  }
  protected function parseYear() {
    $html = $this->page;
    $year = array();
    preg_match('/<B>First aired<\/B><\/TD>.*(\d\d\d\d).*<\/TD>/U', $html, $year);
    if (isset($year[1]) && !empty($year[1])) {
      $this->setProperty(YEAR, $year[1]);
      return $year[1];
    }
  }
  protected function parseSynopsis() {
    $html = $this->page;
    $synopsis = array();
    preg_match("/<TH COLSPAN=2>DESCRIPTION<\/TH><\/TR><TR><TD COLSPAN=2>(.*)<\/TD>/U", $html, $synopsis);
    if (!isset($synopsis[1]))
      preg_match("/<TH COLSPAN=2>TRIVIA<\/TH><\/TR><TR><TD COLSPAN=2>(.*)<\/TD>/U", $html, $synopsis);
    if (isset($synopsis[1]) && !empty($synopsis[1])) {
      $this->setProperty(SYNOPSIS, $synopsis[1]);
      return $synopsis[1];
    }
  }
  protected function parseGenres() {
    $html = $this->page;
    $genre = array();
    preg_match('/<B>Genre<\/B><\/TD><TD VALIGN=Top>(.*)<TR/', $html, $genre);
    if (isset($genre[1]) && !empty($genre[1])) {
      $this->setProperty(GENRES, array($genre[1]));
      return $genre[1];
    }
  }
}
?>

