<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to music videos that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Please help theaudiodb.com website by contributing information and artwork if possible.

 *************************************************************************************************/

require_once (SC_LOCATION.'/resources/audio/theaudiodb.php');

class mvid_wwwTheAudioDBcom extends Parser implements ParserInterface {

  public $supportedProperties = array (
    TITLE,
    SYNOPSIS,
    ACTORS,
    DIRECTORS,
    GENRES,
    YEAR,
    POSTER,
    FANART
  );

  public static function getName() {
    return "www.theaudiodb.com";
  }

  protected $site_url = 'http://theaudiodb.com/';

  /**
   * Searches the theaudiodb.com site for music video details
   *
   * @param array $search_params
   * @return bool
   */
  function populatePage($search_params) {
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];

    send_to_log(4, "Searching for details about " . $this->title . " online at " . $this->site_url);

    // Determine artist and track from title
    if (strpos($this->title, '-')) {
      $artist = trim(array_shift(explode('-', $this->title)));
      $track  = trim(array_pop(explode('-', $this->title)));
    }
    // User TADb's internal search to get a list a possible matches
    $this->page = tadb_track_getInfo($artist, '', $track);

    if ($this->selfTestIsOK())
      return true;
  }
  private function selfTestIsOK(){
    send_to_log(8, "Entering selftest");
    return isset($this->page);
  }
  protected function parseTitle() {
    $track = $this->page;
    $title = $track['strTrack'];
    $this->setProperty(TITLE, $title);
    return $title;
  }
  protected function parseSynopsis() {
    $track = $this->page;
    if (isset($track['strDescriptionEN']) && !empty($track['strDescriptionEN'])) {
      $this->setProperty(SYNOPSIS, $track['strDescriptionEN']);
      return $track['strDescriptionEN'];
    }
  }
  protected function parseActors() {
    $track = $this->page;
    if (isset($track['credits']['cast']) && !empty($track['credits']['cast'])) {
      $names = array();
      foreach ($track['credits']['cast'] as $person)
        $names[] = $person['name'];
      $this->setProperty(ACTORS, $names);
      return $names;
    }
  }
  protected function parseDirectors() {
    $track = $this->page;
    if (isset($track['strMusicVidDirector']) && !empty($track['strMusicVidDirector'])) {
      $names = array($track['strMusicVidDirector']);
      $this->setProperty(DIRECTORS, $names);
      return $names;
    }
  }
  protected function parseGenres() {
    $track = $this->page;
    if (isset($track['strGenre']) && !empty($track['strGenre'])) {
      $names = array($track['strGenre']);
      $this->setProperty(GENRES, $names);
      return $names;
    }
  }
  protected function parseYear() {
    $moviematches = $this->page;
    $year = substr($moviematches['release_date'], 0, 4);
    $this->setProperty(YEAR, $year);
    return $year;
  }
  protected function parseTrailer() {
    $track = $this->page;
    if (isset($track['strMusicVid']) && !empty($track['strMusicVid'])) {
      $trailer = $track['strMusicVid'];
      $this->setProperty(TRAILER, $trailer);
      return $trailer;
    }
  }
  protected function parsePoster() {
    $track = $this->page;
    if (isset($track['strTrackThumb']) && !empty($track['strTrackThumb'])) {
      $poster = $track['strTrackThumb'];
      $this->setProperty(POSTER, $poster);
      return $poster;
    }
  }
  protected function parseFanart() {
    $track = $this->page;
    if (isset($track['strMusicVidScreen1']) && !empty($track['strMusicVidScreen1'])) {
      $fanart = array($track['strMusicVidScreen1']);
      $this->setProperty(FANART, $fanart);
      return $fanart;
    }
  }
}
?>