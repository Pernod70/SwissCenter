<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Please help themoviedb.org website by contributing information and artwork if possible.

   Version history:
   01-Feb-2009: v1.0:     First public release
   29-May-2009: v2.0:     Updated to use API 2.1
   29-Jul-2010: v2.1:     Use JSON instead of XML
   20-Dec-2011: v3.0:     Updated to use API 3

 *************************************************************************************************/

// API key registered to SwissCenter project
define('TMDB_API_KEY', '2548980e43e9c7d08b705a2e57e9afe3');

class wwwTHEMOVIEDBorg extends Parser implements ParserInterface {

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
    EXTERNAL_RATING_PC,
    POSTER,
    TRAILER,
    FANART,
    MATCH_PC
  );

  public $settings = array (
    ADULT_RESULTS => array("options" => array('Yes', 'No'),
                           "default" => 'No')
  );

  public static function getName() {
    return "www.themoviedb.org";
  }

  protected $site_url = 'http://themoviedb.org/';
  protected $configuration;

  /**
   * Searches the themoviedb.org site for movie details
   *
   * @param array $search_params
   * @return bool
   */
  function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];
    if (isset($search_params['YEAR']) && !empty($search_params['YEAR']))
      $year = $search_params['YEAR'];

    send_to_log(4, "Searching for details about " . $this->title . " " . $year . " online at " . $this->site_url);

    // Check filename and title for an IMDb ID
    if (isset($search_params['IGNORE_IMDBTT']) && !$search_params['IGNORE_IMDBTT']) {
      $details = db_row("select * from movies where file_id=" . $this->id);
      $imdbtt = $this->checkForIMDBTT($details);
    }

    // User TMDb's internal search to get a list a possible matches
    $moviedb_id = $this->get_moviedb_id($this->title, $year, $imdbtt);
    if ($moviedb_id) {
      // Set the system wide configuration
      if (!isset($this->configuration))
        $this->configuration = $this->get_configuration();
      // Parse the movie details
      $url = 'http://api.themoviedb.org/3/movie/'.$moviedb_id.'?api_key='.TMDB_API_KEY.'&append_to_response=casts,releases,trailers&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[tmdb] GET: $url");
      $moviematches = json_decode( file_get_contents($url, false, $context), true );
      // Parse the fanart details
      $url = 'http://api.themoviedb.org/3/movie/'.$moviedb_id.'/images?api_key='.TMDB_API_KEY;
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[tmdb] GET: $url");
      $images = json_decode( file_get_contents($url, false, $context), true );
      $moviematches = array_merge($moviematches, $images);
      send_to_log(6, "[tmdb]", $moviematches);
      $this->page = $moviematches;
      $this->accuracy = 100;
      if ( isset ($imdbtt) && !empty($imdbtt)) {
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
  private function selfTestIsOK(){
    send_to_log(8, "Entering selftest");
    return isset($this->page);
  }
  protected function parseIMDBTT() {
    $moviematches = $this->page;
    $imdbtt = $moviematches['imdb_id'];
    $this->setProperty(IMDBTT, $imdbtt);
    return $imdbtt;
  }
  protected function parseTitle() {
    $moviematches = $this->page;
    $title = $moviematches['title'];
    $this->setProperty(TITLE, $title);
    return $title;
  }
  protected function parseSynopsis() {
    $moviematches = $this->page;
    if (isset($moviematches['overview']) && !empty($moviematches['overview'])) {
      $this->setProperty(SYNOPSIS, $moviematches['overview']);
      return $moviematches['overview'];
    }
  }
  protected function parseActors() {
    $moviematches = $this->page;
    if (isset($moviematches['casts']['cast']) && !empty($moviematches['casts']['cast'])) {
      $names = array();
      foreach ($moviematches['casts']['cast'] as $person)
        $names[] = $person['name'];
      $this->setProperty(ACTORS, $names);
      return $names;
    }
  }
  protected function parseActorImages() {
    $moviematches = $this->page;
    if (isset($moviematches['casts']['cast']) && !empty($moviematches['casts']['cast'])) {
      $names = array();
      foreach ($moviematches['casts']['cast'] as $person)
        if (!empty($person['profile_path']))
          $names[] = array('ID'    => $person['id'],
                           'IMAGE' => $this->configuration['images']['base_url'].'original'.$person['profile_path'],
                           'NAME'  => $person['name']);
      $this->setProperty(ACTOR_IMAGES, $names);
      return $names;
    }
  }
  protected function parseDirectors() {
    $moviematches = $this->page;
    if (isset($moviematches['casts']['crew']) && !empty($moviematches['casts']['crew'])) {
      $names = array();
      foreach ($moviematches['casts']['crew'] as $person)
        if ( $person['job'] == 'Director' )
          $names[] = $person['name'];
      $this->setProperty(DIRECTORS, $names);
      return $names;
    }
  }
  protected function parseGenres() {
    $moviematches = $this->page;
    if (isset($moviematches['genres']) && !empty($moviematches['genres'])) {
      $names = array();
      foreach ($moviematches['genres'] as $genre)
        $names[] = $genre['name'];
      $this->setProperty(GENRES, $names);
      return $names;
    }
  }
protected function parseLanguages() {
    $moviematches = $this->page;
    if (isset($moviematches['spoken_languages']) && !empty($moviematches['spoken_languages'])) {
      $names = array();
      foreach ($moviematches['spoken_languages'] as $language)
        $names[] = $language['name'];
      $this->setProperty(LANGUAGES, $names);
      return $names;
    }
  }
  protected function parseYear() {
    $moviematches = $this->page;
    $year = substr($moviematches['release_date'], 0, 4);
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $moviematches = $this->page;
    $rating = floor($moviematches['vote_average'] * 10);
    $this->setProperty(EXTERNAL_RATING_PC, $rating);
    return $rating;
  }
  protected function parseCertificate() {
    $moviematches = $this->page;
    $certlist = array ();
    foreach ($moviematches['releases']['countries'] as $country)
      $certlist[$country['iso_3166_1']] = $country['certification'];
    switch (get_rating_scheme_name())
    {
      case 'FSK':
        if (isset($certlist["DE"])) {
          $rating = 'FSK '.$certlist["DE"];
          break;
        }
      case 'Kijkwijzer':
        if (isset($certlist["NL"])) {
          $rating = $certlist["NL"];
          break;
        }
      case 'BBFC':
        if (isset($certlist["GB"])) {
          $rating = $certlist["GB"];
          break;
        }
      default:
        if (isset($certlist["US"])) {
          $rating = $certlist["US"];
        }
    }
    if(isset($rating) && !empty($rating)){
      $this->setProperty(CERTIFICATE, $rating);
      return $rating;
    }
  }
  protected function parseTrailer() {
    $moviematches = $this->page;
    if (isset($moviematches['trailers']['youtube']) && !empty($moviematches['trailers']['youtube'])) {
      $trailer = 'http://www.youtube.com/watch?v='.$moviematches['trailers']['youtube'][0]['source'];
      $this->setProperty(TRAILER, $trailer);
      return $trailer;
    }
  }
  protected function parsePoster() {
    $moviematches = $this->page;
    $poster = $this->configuration['images']['base_url'].'original'.$moviematches['poster_path'];
    $this->setProperty(POSTER, $poster);
    return $poster;
  }
  protected function parseFanart() {
    $moviematches = $this->page;
    if (isset($moviematches['backdrops']) && !empty($moviematches['backdrops'])) {
      $fanart = array();
      foreach ($moviematches['backdrops'] as $image) {
        if (empty($image['iso_639_1']) || $image['iso_639_1'] == substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2)) {
          $id = file_noext(basename($image['file_path']));
          $fanart[$id]['id'] = $id;
          $fanart[$id]['resolution'] = $image['width'].'x'.$image['height'];
          $fanart[$id]['thumb']      = $this->configuration['images']['base_url'].'w342'.$image['file_path'];
          $fanart[$id]['original']   = $this->configuration['images']['base_url'].'original'.$image['file_path'];
        }
      }
      $this->setProperty(FANART, $fanart);
      return $fanart;
    }
  }
  protected function parseMatchPc() {
    if (isset ($this->accuracy)) {
      $this->setProperty(MATCH_PC, $this->accuracy);
      return $this->accuracy;
    }
  }

  /**
   * Returns the moviedb id by using the API and finding the closest match to our movie title.
   *
   * @param  string $title - Name of movie to search for
   * @param  string $imdbtt - IMDB id if provided
   * @return integer
   */
  function get_moviedb_id($title, $year = '', $imdbtt = '') {
    // Use IMDb id (if provided), otherwise submit a search
    if (!empty ($imdbtt))
      $url = 'http://api.themoviedb.org/3/movie/'.$imdbtt.'?api_key='.TMDB_API_KEY.'&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
    else
    {
      $url = 'http://api.themoviedb.org/3/search/movie?api_key='.TMDB_API_KEY.'&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2).'&query='.urlencode($title);
      if (!empty($year)) $url .= '&year='.$year;
    }
    send_to_log(6, "[tmdb] GET: $url");
    $opts = array('http'=>array('method'=>"GET",
                                'header'=>"Accept: application/json\r\n"));
    $context = stream_context_create($opts);

    // Parse the xml results and determine best match for title
    $moviematches = json_decode( file_get_contents($url, false, $context), true );

    // Find best match for required title
    if (count($moviematches['results']) > 0) {
      if (!empty ($imdbtt)) {
        // Found IMDb id
        $this->accuracy = 100;
        $index = 0;
        send_to_log(4, "Matched IMDb Id:", $moviematches['results'][0]['title']);
        return $moviematches['results'][$index]['id'];
      } else {
        // There are multiple matches found... process them
        $matches = array ();
        $matches_id = array ();
        $adult_results = (get_sys_pref(get_class($this).'_ADULT_RESULTS', $this->settings[ADULT_RESULTS]["default"]) === 'YES');
        foreach ($moviematches['results'] as $movie) {
          // Filter out adult results if not required
          if ($adult_results || !$movie['adult']) {
            $matches[] = $movie['title'];
            $matches_id[] = $movie['id'];
            if ($movie['original_title'] !== $movie['title']) {
              $matches[] = $movie['original_title'];
              $matches_id[] = $movie['id'];
            }
          }
        }
        $index = best_match($title, $matches, $this->accuracy);
      }
      // If we are sure that we found a good result, then get the file details.
      if ($this->accuracy > 75)
        return $matches_id[$index];
      else
        return false;
    } else {
      send_to_log(4, "No Match found.");

      //A little sketchy, but I'll take the chance, moviedb doesn't always handle imdbtt.
      if (isset($imdbtt))
        return $this->populatePage(array('TITLE'         => $this->title,
                                         'IGNORE_IMDBTT' => true));
      else {
        return false;
      }
    }
  }

  /**
   * Get the system wide configuration information.
   *
   * @return array
   */
  function get_configuration() {
    // Get the system wide configuration information.
    $url = 'http://api.themoviedb.org/3/configuration?api_key='.TMDB_API_KEY;
    send_to_log(6, "[tmdb] GET: $url");
    $opts = array('http'=>array('method'=>"GET",
                                'header'=>"Accept: application/json\r\n"));
    $context = stream_context_create($opts);

    return json_decode( file_get_contents($url, false, $context), true );
  }
}
?>