<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to tv series that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   This script will download the full series record zip (in the users default language) from
   tvdb.com, then parse the contents to retrieve episode title, genre, year, synopsis,
   directors, actors and an episode thumbnail. It will then downlaod any related banners that
   are available.

   All downloaded files are first cached in your defined cache folder/tvdb to avoid having to
   redownload the zip and banners for each episode of a series.

   Please help thetvdb.com website by contributing information and artwork if possible.

 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../xml/xmlparser.php'));

// API key registered to SwissCenter project
define('TVDB_API_KEY', 'FA3F6F720A61DE71');

class wwwTHETVDBcom extends Parser implements ParserInterface {

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
    return "www.TheTVDB.com";
  }

  protected $site_url = 'http://thetvdb.com/';

  private $cache_dir;
  private $xmlparser;
  private $xmlmirror;
  private $bannermirror;

  /**
   * Searches the thetvdb.com site for tv episode details
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

    $this->accuracy = 0;

    // Supported languages (hardcoded from languages.xml)
    $languages = array('da','fi','nl','de','it','es','fr','pl','hu','el','tr','ru','he','ja','pt','zh','cs','en','sv','no');

    send_to_log(4,"Searching for details about ".$this->programme." Season: ".$this->series." Episode: ".$this->episode."  Title: ".$this->title." online at ".$this->site_url);

    // Users preferred language (use 'en' if not supported)
    $language = substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
    if ( !in_array($language, $languages) ) {
      $language = 'en';
      send_to_log(5,"User language '".$language."' not supported, using 'en'");
    }

    // Ensure local cache folders exist
    $cache_dir = get_sys_pref('cache_dir').'/tvdb';
    if (!file_exists($cache_dir))
    {
      $oldumask = umask(0);
      @mkdir($cache_dir,0777);
      umask($oldumask);
    }

    // Get mirror for xml and banners
    $this->get_mirrors();

    if ( !empty($this->xmlmirror) ) {
      // Get the series id using the API
      $series_id = $this->get_series_id($this->programme);

      if ( !empty($series_id) ) {

        $series_zip_url   = $this->xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/all/'.$language.'.zip';
        $series_xml_url   = $this->xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/all/'.$language.'.xml';
        $banner_xml_url   = $this->xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/banners.xml';
        $actors_xml_url   = $this->xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/actors.xml';
        $series_zip_cache = $cache_dir.'/'.$series_id.'_'.$language.'.zip';
        $series_cache     = $cache_dir.'/'.$series_id.'_'.$language.'.xml';
        $banner_cache     = $cache_dir.'/'.$series_id.'_banners.xml';
        $actors_cache     = $cache_dir.'/'.$series_id.'_actors.xml';

        // Ensure local copy of full series zip is uptodate (cache valid for 6 hours)
        $series_cache_time  = (file_exists($series_cache) ? filemtime($series_cache) : 0);
        if ($series_cache_time < (time() - 21600)) {
          if ( !extension_loaded('zip') ) {
            // Zip extension not loaded so download non-zipped data
            file_download_and_save($series_xml_url, $series_cache, true);
            file_download_and_save($banner_xml_url, $banner_cache, true);
            file_download_and_save($actors_xml_url, $actors_cache, true);
          } else {
            file_download_and_save($series_zip_url, $series_zip_cache, true);
            if (file_exists($series_zip_cache)) {
              // Extract zip contents
              if ( is_resource($zip = @zip_open($series_zip_cache)) ) {
                while ($zip_entry = zip_read($zip)) {
                  // Get file contents from zip
                  $entry = zip_entry_open($zip,$zip_entry);
                  $zfilename = zip_entry_name($zip_entry);
                  $zfilesize = zip_entry_filesize($zip_entry);
                  $zcontents = zip_entry_read($zip_entry, $zfilesize);
                  $fp=@fopen($cache_dir.'/'.$series_id.'_'.$zfilename,"w");
                  @fwrite($fp,$zcontents);
                  @fclose($fp);
                  zip_entry_close($zip_entry);
                }
                @zip_close($zip);
              }
              elseif (is_unix()) {
                // On LINUX machines, we can use the standard "unzip" command to
                // perform the same functions as the zip extension
                exec('unzip '.$series_zip_cache.' -d '.$cache_dir);
              } else {
                send_to_log(6,"Failed to open series zip from www.thetvdb.com. (zip_open errno=$zip)");
              }
            }
          }
        }

        // Parse the Full Series Record
        send_to_log(6,'Parsing XML: '.$series_cache);
        $series = file_get_contents($series_cache);
        $xml = new XmlParser($series, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
        $tvdb_series = $xml->GetData();
        if ( !isset($tvdb_series['DATA']['EPISODE'][0]) )
          $tvdb_series['DATA']['EPISODE'] = array($tvdb_series['DATA']['EPISODE']);

        $ep = false;

        // Search for matching series and episode numbers
        $episode_titles = array();
        foreach ($tvdb_series['DATA']['EPISODE'] as $idx=>$episode) {
          $episode_titles[$idx] = $episode['EPISODENAME']['VALUE'];
          // Check for matching series and episode numbers
          if ( $episode['SEASONNUMBER']['VALUE'] == $this->series && $episode['EPISODENUMBER']['VALUE'] == $this->episode ) {
            $ep = $idx;
            $this->accuracy = 100;
            break;
          }
        }

        // Determine best match from available episode titles
        if ($ep === false) {
          $ep = best_match(ucwords(strtolower($this->title)), $episode_titles, $this->accuracy);
        }

        if ($ep !== false) {
          // Parse the banners
          send_to_log(6,'Parsing XML: '.$banner_cache);
          $banners = file_get_contents($banner_cache);
          $xml = new XmlParser($banners, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
          $tvdb_banners = $xml->GetData();
          if ( !isset($tvdb_banners['BANNERS']['BANNER'][0]) )
            $tvdb_banners['BANNERS']['BANNER'] = array($tvdb_banners['BANNERS']['BANNER']);

          // Parse the actor images
          send_to_log(6,'Parsing XML: '.$actors_cache);
          $actors = file_get_contents($actors_cache);
          $xml = new XmlParser($actors, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
          $tvdb_actors = $xml->GetData();
          if ( !isset($tvdb_actors['ACTORS']['ACTOR'][0]) )
            $tvdb_actors['ACTORS']['ACTOR'] = array($tvdb_actors['ACTORS']['ACTOR']);

          $this->page = array('SERIES'  => $tvdb_series['DATA']['SERIES'],
                              'EPISODE' => $tvdb_series['DATA']['EPISODE'][$ep],
                              'IMAGES'  => $tvdb_banners['BANNERS'],
                              'ACTORS'  => $tvdb_actors['ACTORS']['ACTOR']);
          return true;
        } else {
          send_to_log(4,"Cannot find details for specified episode at tvdb.");
        }
      } else {
        send_to_log(4,"Unable to find series for details about ".basename($this->filename));
      }
    } else {
      send_to_log(6,'Unable to get mirrors from www.thetvdb.com');
    }
    return false;
  }

  protected function parseIMDBTT() {
    $tvdb = $this->page;
    $imdbtt = $tvdb['EPISODE']['IMDB_ID']['VALUE'];
    if (isset($imdbtt) && !empty($imdbtt)) {
      $this->setProperty(IMDBTT, $imdbtt);
      return $imdbtt;
    }
  }
  protected function parseProgramme() {
    $tvdb = $this->page;
    $programme = $tvdb['SERIES']['SERIESNAME']['VALUE'];
    if (isset($programme) && !empty($programme)) {
      $this->setProperty(PROGRAMME, $programme);
      return $programme;
    }
  }
  protected function parseSeries() {
    $tvdb = $this->page;
    $series = $tvdb['EPISODE']['SEASONNUMBER']['VALUE'];
    if (isset($series) && is_numeric($series)) {
      $this->setProperty(SERIES, $series);
      return $series;
    }
  }
  protected function parseEpisode() {
    $tvdb = $this->page;
    $episode = $tvdb['EPISODE']['EPISODENUMBER']['VALUE'];
    if (isset($episode) && is_numeric($episode)) {
      $this->setProperty(EPISODE, $episode);
      return $episode;
    }
  }
  protected function parseTitle() {
    $tvdb = $this->page;
    $title = $tvdb['EPISODE']['EPISODENAME']['VALUE'];
    if (isset($title)&& !empty($title)) {
      $this->setProperty(TITLE, $title);
      return $title;
    }
  }
  protected function parseSynopsis() {
    $tvdb = $this->page;
    $synopsis = $tvdb['EPISODE']['OVERVIEW']['VALUE'];
    if (isset($synopsis) && !empty($synopsis)) {
      $this->setProperty(SYNOPSIS, $synopsis);
      return $synopsis;
    }
  }
  protected function parseActors() {
    $tvdb = $this->page;
    $actors = array_merge(explode('|', $this->clean_name_list($tvdb['SERIES']['ACTORS']['VALUE'])),
                          explode('|', $this->clean_name_list($tvdb['EPISODE']['GUESTSTARS']['VALUE'])));
    if (isset($actors)&& !empty($actors)) {
      $this->setProperty(ACTORS, $actors);
      return $actors;
    }
  }
  protected function parseActorImages() {
    $tvdb = $this->page;
    $actors = isset($tvdb['ACTORS']) ? $tvdb['ACTORS'] : array();
    if (isset($actors) && !empty($actors)) {
      // Add mirror path to images
      foreach ($actors as $id=>$actor) {
        foreach ($actor as $key=>$data)
          $actors[$id][$key] = isset($data['VALUE']) ? $data['VALUE'] : '';
        if ( isset($actor['IMAGE']) )
          $actors[$id]['IMAGE'] = $this->bannermirror.'/banners/'.$actor['IMAGE']['VALUE'];
      }
      $this->setProperty(ACTOR_IMAGES, $actors);
      return $actors;
    }
  }
  protected function parseDirectors() {
    $tvdb = $this->page;
    $directors = explode('|', $this->clean_name_list($tvdb['EPISODE']['DIRECTOR']['VALUE']));
    if (isset($directors)&& !empty($directors)) {
      $this->setProperty(DIRECTORS, $directors);
      return $directors;
    }
  }
  protected function parseGenres() {
    $tvdb = $this->page;
    $genres = explode('|', $this->clean_name_list($tvdb['SERIES']['GENRE']['VALUE']));
    if (isset($genres)&& !empty($genres)) {
      $this->setProperty(GENRES, $genres);
      return $genres;
    }
  }
  protected function parseYear() {
    $tvdb = $this->page;
    $year = $tvdb['EPISODE']['FIRSTAIRED']['VALUE'];
    if (isset($year)&& !empty($year)) {
      $this->setProperty(YEAR, substr($year, 0, 4));
      return substr($year, 0, 4);
    }
  }
  protected function parseExternalRatingPc() {
    $tvdb = $this->page;
    $rating = $tvdb['EPISODE']['RATING']['VALUE'];
    if (isset($rating)&& !empty($rating)) {
      $this->setProperty(EXTERNAL_RATING_PC, floor($rating * 10));
      return floor($rating * 10);
    }
  }
  protected function parsePoster() {
    $tvdb = $this->page;
    $poster = $tvdb['EPISODE']['FILENAME']['VALUE'];
    if (isset($poster)&& !empty($poster)) {
      $this->setProperty(POSTER, $this->bannermirror.'/banners/'.$poster);
      return $this->bannermirror.'/banners/'.$poster;
    }
  }
  protected function parseFanart() {
    $tvdb = $this->page;
    $images = isset($tvdb['IMAGES']) ? $tvdb['IMAGES'] : array();
    if (isset($images) && !empty($images)) {
      $fanart = array();
      foreach ($images['BANNER'] as $id=>$image) {
        if ($image['LANGUAGE']['VALUE'] == 'en' || $image['LANGUAGE']['VALUE'] == get_sys_pref('DEFAULT_LANGUAGE','en')) {
          // Filter fanart images only
          if ($image['BANNERTYPE']['VALUE'] == 'fanart') {
            // Add mirror path to images
            $fanart['FANART'][$id]['ORIGINAL']   = $this->bannermirror.'/banners/'.$image['BANNERPATH']['VALUE'];
            $fanart['FANART'][$id]['VIGNETTE']   = $this->bannermirror.'/banners/'.$image['VIGNETTEPATH']['VALUE'];
            $fanart['FANART'][$id]['THUMBNAIL']  = $this->bannermirror.'/banners/'.$image['THUMBNAILPATH']['VALUE'];
            $fanart['FANART'][$id]['ID']         = $image['ID']['VALUE'];
            $fanart['FANART'][$id]['RESOLUTION'] = $image['BANNERTYPE2']['VALUE'];
            $fanart['FANART'][$id]['COLORS']     = $image['COLORS']['VALUE'];
          }
        }
      }
      $this->setProperty(FANART, $fanart);
      return $fanart;
    }
  }
  protected function parseBanners() {
    $tvdb = $this->page;
    $images = isset($tvdb['IMAGES']) ? $tvdb['IMAGES'] : array();
    if (isset($images) && !empty($images)) {
      $banners = array();
      foreach ($images['BANNER'] as $id=>$image) {
        if ($image['LANGUAGE']['VALUE'] == 'en' || $image['LANGUAGE']['VALUE'] == get_sys_pref('DEFAULT_LANGUAGE','en')) {
          // Filter banner images only
          if ($image['BANNERTYPE']['VALUE'] == 'series' && $image['BANNERTYPE2']['VALUE'] == 'graphical')
            $banners['BANNER'][] = $this->bannermirror.'/banners/'.$image['BANNERPATH']['VALUE'];
          elseif ($image['BANNERTYPE']['VALUE'] == 'season' && $image['BANNERTYPE2']['VALUE'] == 'season' && is_numeric($image['SEASON']['VALUE'])) {
            $banners[trim($image['SEASON']['VALUE'])][] = $this->bannermirror.'/banners/'.$image['BANNERPATH']['VALUE'];
          }
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
   *  Clean a string of actors, genres, etc. by replacing , with | and removing character names in brackets.
   *
   */
  function clean_name_list($list)
  {
    $list = preg_replace('/\(.*?\)/', '', $list);
    $list = str_replace(',', '|', $list);
    return trim($list, '| ');
  }

  /**
   * Returns the series id by using the API and finding the closest match to our programme.
   *
   * @param  string $programme - Name of programme to search for
   * @return integer
   */
  function get_series_id($programme)
  {
    $url = 'http://www.thetvdb.com/api/GetSeries.php?seriesname='.urlencode($programme).'&language=all';
    send_to_log(6,'Parsing XML: '.$url);
    $series = file_get_contents($url);
    $xml = new XmlParser($series, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
    $data = $xml->GetData();
    if ( !isset($data['DATA']['SERIES'][0]) )
      $data['DATA']['SERIES'] = array($data['DATA']['SERIES']);

    $seriesids = array();
    foreach ($data['DATA']['SERIES'] as $series)
    {
      $seriesids['SERIESNAME'][] = $series['SERIESNAME']['VALUE'];
      $seriesids['SERIESID'][] = $series['SERIESID']['VALUE'];
    }

    // Find best match for required programme
    if (count($seriesids)>0)
    {
      $index = best_match(ucwords(strtolower($programme)), $seriesids['SERIESNAME'], $accuracy);
      return $seriesids['SERIESID'][$index];
    }
    else
      return false;
  }

  /**
   * Returns random mirror paths for xml and banner URL's.
   * Mirrors are read from http://www.thetvdb.com/api/<apikey>/mirrors.xml
   *
   * @param string $xmlmirror
   * @param string $bannermirror
   */
  function get_mirrors()
  {
    $this->xmlmirror = 'http://thetvdb.com';
    $this->bannermirror = 'http://thetvdb.com';
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
