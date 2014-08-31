<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to tv series that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Please help themoviedb.org website by contributing information and artwork if possible.

 *************************************************************************************************/

// API key registered to SwissCenter project
define('TMDB_API_KEY', '2548980e43e9c7d08b705a2e57e9afe3');

class tv_wwwTHEMOVIEDBorg extends Parser implements ParserInterface {

	public $supportedProperties = array (
			IMDBTT,
			PROGRAMME,
			SERIES,
			EPISODE,
			TITLE,
			ACTORS,
			ACTOR_IMAGES,
			DIRECTORS,
			GENRES,
			YEAR,
			SYNOPSIS,
			EXTERNAL_RATING_PC,
			POSTER,
			BANNERS,
			FANART,
			MATCH_PC
	);

  public static function getName() {
    return "www.themoviedb.org";
  }

  protected $site_url = 'https://themoviedb.org/';
  protected $configuration;

  /**
   * Searches the themoviedb.org site for tv episode details
   *
   * @param array $search_params
   * @return bool
   */
  function populatePage($search_params) {
    if (isset($search_params['PROGRAMME']) && !empty($search_params['PROGRAMME']))
      $this->programme = $search_params['PROGRAMME'];
    if (isset($search_params['SERIES']) && is_numeric($search_params['SERIES']))
      $this->series = $search_params['SERIES'];
    if (isset($search_params['EPISODE']) && is_numeric($search_params['EPISODE']))
      $this->episode = $search_params['EPISODE'];
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    send_to_log(4,"Searching for details about ".$this->programme." Season: ".$this->series." Episode: ".$this->episode."  Title: ".$this->title." online at ".$this->site_url);

    // User TMDb's internal search to get a list a possible matches
    $moviedb_id = $this->get_moviedb_id($this->programme);
    if ($moviedb_id) {
      // Set the system wide configuration
      if (!isset($this->configuration))
        $this->configuration = $this->get_configuration();
      // Parse the tv series details
      $url = 'http://api.themoviedb.org/3/tv/'.$moviedb_id.'?api_key='.TMDB_API_KEY.'&append_to_response=external_ids,videos&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[tmdb] GET: $url");
      $tvmatches = json_decode( file_get_contents($url, false, $context), true );
      // Parse the fanart details
      $url = 'http://api.themoviedb.org/3/tv/'.$moviedb_id.'/images?api_key='.TMDB_API_KEY;
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[tmdb] GET: $url");
      $images = json_decode( file_get_contents($url, false, $context), true );
      $tvmatches = array('series' => array_merge($tvmatches, $images));
      // Parse the tv season details
      $url = 'http://api.themoviedb.org/3/tv/'.$moviedb_id.'/season/'.$this->series.'?api_key='.TMDB_API_KEY.'&append_to_response=images,videos&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[tmdb] GET: $url");
      $season = array('season' => json_decode( file_get_contents($url, false, $context), true ));
      $tvmatches = array_merge($tvmatches, $season);
      // Parse the tv episode details
      $url = 'http://api.themoviedb.org/3/tv/'.$moviedb_id.'/season/'.$this->series.'/episode/'.$this->episode.'?api_key='.TMDB_API_KEY.'&append_to_response=credits,external_ids,videos&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
      $opts = array('http'=>array('method'=>"GET",
                                  'header'=>"Accept: application/json\r\n"));
      $context = stream_context_create($opts);
      send_to_log(6, "[tmdb] GET: $url");
      $episode = array('episode' => json_decode( file_get_contents($url, false, $context), true ));
      $tvmatches = array_merge($tvmatches, $episode);
      send_to_log(8, "[tmdb]", $tvmatches);
      $this->page = $tvmatches;
      $this->accuracy = 100;
    }
  }
  private function selfTestIsOK(){
    send_to_log(8, "Entering selftest");
    return isset($this->page);
  }
  protected function parseIMDBTT() {
    $tvmatches = $this->page;
    $imdbtt = $tvmatches['episode']['external_ids']['imdb_id'];
    $this->setProperty(IMDBTT, $imdbtt);
    return $imdbtt;
  }
  protected function parseProgramme() {
    $tvmatches = $this->page;
    $programme = $tvmatches['series']['name'];
    if (isset($programme) && !empty($programme)) {
      $this->setProperty(PROGRAMME, $programme);
      return $programme;
    }
  }
  protected function parseSeries() {
    $tvmatches = $this->page;
    $series = $tvmatches['episode']['season_number'];
    if (isset($series) && is_numeric($series)) {
      $this->setProperty(SERIES, $series);
      return $series;
    }
  }
  protected function parseEpisode() {
    $tvmatches = $this->page;
    $episode = $tvmatches['episode']['episode_number'];
    if (isset($episode) && is_numeric($episode)) {
      $this->setProperty(EPISODE, $episode);
      return $episode;
    }
  }
  protected function parseTitle() {
    $tvmatches = $this->page;
    $title = $tvmatches['episode']['name'];
    $this->setProperty(TITLE, $title);
    return $title;
  }
  protected function parseSynopsis() {
    $tvmatches = $this->page;
    if (isset($tvmatches['episode']['overview']) && !empty($tvmatches['episode']['overview'])) {
      $this->setProperty(SYNOPSIS, $tvmatches['episode']['overview']);
      return $tvmatches['episode']['overview'];
    }
  }
  protected function parseActors() {
    $tvmatches = $this->page;
    if (isset($tvmatches['episode']['credits']['cast']) && !empty($tvmatches['episode']['credits']['cast'])) {
      $names = array();
      foreach ($tvmatches['episode']['credits']['cast'] as $person)
        $names[] = $person['name'];
      $this->setProperty(ACTORS, $names);
      return $names;
    }
  }
  protected function parseActorImages() {
    $tvmatches = $this->page;
    if (isset($tvmatches['episode']['credits']['cast']) && !empty($tvmatches['episode']['credits']['cast'])) {
      $names = array();
      foreach ($tvmatches['episode']['credits']['cast'] as $person)
        if (!empty($person['profile_path']))
          $names[] = array('ID'    => $person['id'],
                           'IMAGE' => $this->configuration['images']['base_url'].'original'.$person['profile_path'],
                           'NAME'  => $person['name'],
                           'ROLE'  => $person['character']);
      $this->setProperty(ACTOR_IMAGES, $names);
      return $names;
    }
  }
  protected function parseDirectors() {
    $tvmatches = $this->page;
    if (isset($tvmatches['episode']['credits']['crew']) && !empty($tvmatches['episode']['credits']['crew'])) {
      $names = array();
      foreach ($tvmatches['episode']['credits']['crew'] as $person)
        if ( $person['job'] == 'Director' )
          $names[] = $person['name'];
      $this->setProperty(DIRECTORS, $names);
      return $names;
    }
  }
  protected function parseGenres() {
    $tvmatches = $this->page;
    if (isset($tvmatches['series']['genres']) && !empty($tvmatches['series']['genres'])) {
      $names = array();
      foreach ($tvmatches['series']['genres'] as $genre)
        $names[] = $genre['name'];
      $this->setProperty(GENRES, $names);
      return $names;
    }
  }
  protected function parseYear() {
    $tvmatches = $this->page;
    $year = substr($tvmatches['episode']['air_date'], 0, 4);
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseExternalRatingPc() {
    $tvmatches = $this->page;
    $rating = floor($tvmatches['episode']['vote_average'] * 10);
    $this->setProperty(EXTERNAL_RATING_PC, $rating);
    return $rating;
  }
  protected function parseTrailer() {
    $tvmatches = $this->page;
    if (isset($tvmatches['episode']['videos']['results']) && !empty($tvmatches['episode']['videos']['results'])) {
      $trailer = 'http://www.youtube.com/watch?v='.$tvmatches['episode']['videos']['results'][0]['key'];
      $this->setProperty(TRAILER, $trailer);
      return $trailer;
    }
  }
  protected function parsePoster() {
    $tvmatches = $this->page;
    $poster = $this->configuration['images']['base_url'].'original'.$tvmatches['episode']['still_path'];
    $this->setProperty(POSTER, $poster);
    return $poster;
  }
  protected function parseFanart() {
    $tvmatches = $this->page;
    if (isset($tvmatches['series']['backdrops']) && !empty($tvmatches['series']['backdrops'])) {
      $fanart = array();
      foreach ($tvmatches['series']['backdrops'] as $image) {
        if (empty($image['iso_639_1']) || $image['iso_639_1'] == 'xx' || $image['iso_639_1'] == substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2)) {
          $id = file_noext(basename($image['file_path']));
          $fanart[$id]['resolution'] = $image['width'].'x'.$image['height'];
          $fanart[$id]['thumbnail']  = $this->configuration['images']['base_url'].'w342'.$image['file_path'];
          $fanart[$id]['original']   = $this->configuration['images']['base_url'].'original'.$image['file_path'];
        }
      }
      $this->setProperty(FANART, $fanart);
      return $fanart;
    }
  }
  protected function parseBanners() {
    $tvmatches = $this->page;
    if (isset($tvmatches['season']['images']['posters']) && !empty($tvmatches['season']['images']['posters'])) {
      $posters = array();
      foreach ($tvmatches['season']['images']['posters'] as $image) {
        if (empty($image['iso_639_1']) || $image['iso_639_1'] == 'xx' || $image['iso_639_1'] == substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2)) {
          $banners[trim($tvmatches['episode']['season_number'])][] = $this->configuration['images']['base_url'].'original'.$image['file_path'];
        }
      }
      $this->setProperty(BANNERS, $banners);
      return $banners;
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
  function get_moviedb_id($programme, $year = '', $imdbtt = '') {
    // Use IMDb id (if provided), otherwise submit a search
    if (!empty ($imdbtt))
      $url = 'http://api.themoviedb.org/3/tv/'.$imdbtt.'?api_key='.TMDB_API_KEY.'&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
    else
    {
      $url = 'http://api.themoviedb.org/3/search/tv?api_key='.TMDB_API_KEY.'&language='.substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2).'&query='.urlencode($programme);
      if (!empty($year)) $url .= '&year='.$year;
    }
    send_to_log(6, "[tmdb] GET: $url");
    $opts = array('http'=>array('method'=>"GET",
                                'header'=>"Accept: application/json\r\n"));
    $context = stream_context_create($opts);

    // Parse the xml results and determine best match for title
    $tvmatches = json_decode( file_get_contents($url, false, $context), true );

    // Find best match for required title
    if (count($tvmatches['results']) > 0) {
      // There are multiple matches found... process them
      $matches = array ();
      $matches_id = array ();
      foreach ($tvmatches['results'] as $tv) {
        $matches[] = $tv['name'];
        $matches_id[] = $tv['id'];
        if ($tv['original_name'] !== $tv['name']) {
          $matches[] = $tv['original_name'];
          $matches_id[] = $tv['id'];
        }
      }
      $index = best_match($programme, $matches, $this->accuracy);

      // If we are sure that we found a good result, then get the file details.
      if ($this->accuracy > 75)
        return $matches_id[$index];
      else
        return false;
    } else {
      send_to_log(4, "No Match found.");
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