<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Version history:
   20-May-2009: v1.0:     First public release
   14-Mar-2010: v2.0:     Utsi's parser format

 *************************************************************************************************/

require_once( SC_LOCATION."/ext/xmlrpc/xmlrpc.inc" );

// API key registered to SwissCenter project
define('MOVIEMETER_API_KEY', 'wfdk9v1w8dxycgw0g9w9xdq3qt3nu2td');

class wwwMOVIEMETERnl extends Parser implements ParserInterface {

  public $supportedProperties = array (
    IMDBTT,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    SYNOPSIS,
    POSTER,
    EXTERNAL_RATING_PC
  );

  public static function getName() {
    return "www.MovieMeter.nl";
  }

  private $cache_dir;
  private $xmlparser;
  private $client;
  private $session_key;
  protected $site_url = 'http://www.moviemeter.nl/';

  function populatePage($search_params) {
    if (isset ($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    send_to_log(8,"Start xmlrpc client.");
    // Start xmlrpc client
    $this->client = new xmlrpc_client("http://www.moviemeter.nl/ws");
    $this->client->return_type = 'phpvals';

    // Start session and retrieve sessionkey
    $message = new xmlrpcmsg("api.startSession", array(new xmlrpcval(MOVIEMETER_API_KEY, "string")));
    $resp = $this->client->send($message);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error:", $resp->faultString());
    }
    else
    {
      $session_info = $resp->value();
      $this->session_key = $session_info['session_key'];
    }

    // Change the word order
    if ( substr($this->title,0,4)=='The ' ) $this->title = substr($this->title,5).', The';
    if ( substr($this->title,0,4)=='Der ' ) $this->title = substr($this->title,5).', Der';
    if ( substr($this->title,0,4)=='Die ' ) $this->title = substr($this->title,5).', Die';
    if ( substr($this->title,0,4)=='Das ' ) $this->title = substr($this->title,5).', Das';

    // Get search results
    send_to_log(4,"Searching for details about ".$this->title." online at '$this->site_url'");

    // Check filename and title for an IMDb ID
    if (isset ($search_params['IGNORE_IMDBTT']) && !$search_params['IGNORE_IMDBTT']) {
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    $filmid = $this->getMovieMeter_Id(htmlspecialchars($this->title, ENT_QUOTES), $imdbtt);

    $this->accuracy = 0;

    if ( $filmid == -1 )
    {
      send_to_log(4,"No Match found.");
    }
    else
    {
      send_to_log(8, "Found MovieMeter Id: ".$filmid);
      // Retrieve movie details
      $message = new xmlrpcmsg("film.retrieveDetails", array(new xmlrpcval($this->session_key, "string"), new xmlrpcval($filmid, "int")));
      $resp = $this->client->send($message);

      if ($resp->faultCode())
      {
        send_to_log(3,"xmlrpc error:", $resp->faultString());
      }
      else
      {
        $results = $this->page = $resp->value();
        $this->accuracy = 100;
        send_to_log(4,"Found details for '".$results["title"]."'");
        if ( isset ($imdbtt) && !empty($imdbtt)){
          if ($this->selfTestIsOK())
            return true;
          else {
            send_to_log(4, "Calling populatePage a second time with IMDb ID: " . $imdbtt);
            $this->populatePage(array('TITLE'         => $this->title,
                                      'IGNORE_IMDBTT' => true));
          }
        }
      }
    }
  }

  private function selfTestIsOK(){
    send_to_log(8, "Entering selftest");
    return isset($this->page);
  }

  /**
   * Properties supported by this parser.
   *
   */

   protected function parseIMDBTT() {
    $results = $this->page;
    $imdbtt = $results["imdb"];
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, 'tt'.$imdbtt);
      return 'tt'.$imdbtt;
    }
  }
  protected function parseSynopsis() {
    $results = $this->page;
    $synopsis = $results["plot"];
    if (isset($synopsis)&& !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $results = $this->page;
    $actors = array();
    foreach ($results["actors"] as $actor)
      $actors[] = $actor["name"];
    if (isset($actors)&& !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $results = $this->page;
    $directors = array();
    foreach ($results["directors"] as $director)
      $directors[] = $director["name"];
    if (isset($directors)&& !empty($directors)) {
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $results = $this->page;
    $genres = $results["genres"];
    if (isset($genres)&& !empty($genres)) {
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $results = $this->page;
    $year = $results["year"];
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $results = $this->page;
    $rating = floor($results["average"] * 20); // scale 1-5 so multiply by 2
    if (isset($rating)&& !empty($rating)) {
      $this->setProperty(EXTERNAL_RATING_PC, $rating);
      return $rating;
    }
  }
  protected function parsePoster() {
    $results = $this->page;
    if (!empty($results["thumbnail"])) {
      $poster = $results["thumbnail"];
      if (url_exists($poster)) {
        $this->setProperty(POSTER, $poster);
        return $poster;
      }
    }
  }

  /**
   * Searches the moviemeter.nl site for movie id
   *
   * @param string $title
   * @param string $imdbtt
   * @return filmid
   */
  function getMovieMeter_Id($title, $imdbtt = '')
  {
    if (!empty ($imdbtt))
    {
      // Filename includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
      $message = new xmlrpcmsg("film.retrieveByImdb", array(new xmlrpcval($this->session_key, "string"), new xmlrpcval($imdbtt, "string")));
      $resp = $this->client->send($message);

      if ($resp->faultCode())
      {
        send_to_log(3,"xmlrpc error:", $resp->faultString());
      }
      else
      {
        return $resp->value();
      }
    }
    elseif (preg_match("/\[(tt\d+)\]/", $title, $imdbtt) != 0)
    {
      // Film title includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
      $message = new xmlrpcmsg("film.retrieveByImdb", array(new xmlrpcval($this->session_key, "string"), new xmlrpcval($imdbtt[1], "string")));
      $resp = $this->client->send($message);

      if ($resp->faultCode())
      {
        send_to_log(3,"xmlrpc error:", $resp->faultString());
      }
      else
      {
        return $resp->value();
      }
    }
    else
    {
    // User moviemeter's internal search to get a list a possible matches
    $message = new xmlrpcmsg("film.search", array(new xmlrpcval($this->session_key, "string"), new xmlrpcval($title, "string")));
    $resp = $this->client->send($message);

      if ($resp->faultCode())
      {
        send_to_log(3,"xmlrpc error:", $resp->faultString());
      }
      else
      {
        $results = $resp->value();
        if ( count($results) > 0 )
          return $results[0]["filmId"];
      }
    }
    return false;
  }
}
?>
