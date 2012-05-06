<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

abstract class Parser
{
  protected $accuracy;
  protected $id;
  protected $filename;
  protected $title;
  protected $programme;
  protected $series;
  protected $episode;
  protected $year;
  protected $populatePageCalledOnce;
  protected $page;

  // Array to contain the attributes.
  public static $properties = array ();

  public function __destruct() {
    unset ($this->accuracy);
    unset ($this->id);
    unset ($this->filename);
    unset ($this->title);
    unset ($this->programme);
    unset ($this->series);
    unset ($this->episode);
    unset ($this->year);
    unset ($this->populatePageCalledOnce);
    unset ($this->page);
    self :: $properties = NULL;
  }

  public function __construct($id = null, $filename = null, $search_params = array(), $use_smartsearch = false, $use_foldersearch = false) {
    if (!($this->getName() == "None")) {
      $this->init($id, $filename, $search_params);

      // Ensure we have required parameters to perform a search
      if (!isset ($this->page) && isset ($this->id) && isset ($this->filename) && (isset ($this->title) || isset ($this->programme))) {
        // Check if first time constructor called for this title and this parser
        if (!$this->populatePageCalledOnce) {
          $props_title = ($this->isSupportedProperty(TITLE) ? $this->getProperty(TITLE) : false);
          $props_imdbtt = ($this->isSupportedProperty(IMDBTT) ? $this->getProperty(IMDBTT) : false);

          // If title is set in property map, use it, and no need for smartsearch
          if (isset ($props_title) && !empty ($props_title)) {
            $title = $props_title;
            $use_smartsearch = false;
          }

          // If IMDBTT is set in property map, there's no need for smartsearch
          if (isset ($props_imdbtt) && !empty ($props_imdbtt))
            $use_smartsearch = false;

          // Populate the page
          if ($use_smartsearch)
            $this->doSmartSearch($id, $filename, $search_params['TITLE'], $use_foldersearch);
          else
            $this->populatePage($search_params);

          // Flag that constructor has been called for this parser/page so don't try again
          $this->populatePageCalledOnce = true;
        }
      }
    }
  }

  public function init($id, $filename, $search_params) {
    if ($filename != null)
      $this->filename = $filename;
    if ($id != null)
      $this->id = $id;
    if (isset($search_params['TITLE']) && !empty($search_params['TITLE']))
      $this->title = $search_params['TITLE'];
    if (isset($search_params['PROGRAMME']) && !empty($search_params['PROGRAMME']))
      $this->programme = $search_params['PROGRAMME'];
    if (isset($search_params['SERIES']) && is_numeric($search_params['SERIES']))
      $this->series = $search_params['SERIES'];
    if (isset($search_params['EPISODE']) && is_numeric($search_params['EPISODE']))
      $this->episode = $search_params['EPISODE'];
    if (isset($search_params['YEAR']) && is_numeric($search_params['YEAR']))
      $this->year = $search_params['YEAR'];
  }

  public function getProperty($propertyName) {
    return self :: $properties[$propertyName];
  }

  public function isSupportedProperty($propertyName) {
    for ($i = 0; $i < count($this->supportedProperties); $i++) {
      if ($this->supportedProperties[$i] == $propertyName) {
        return true;
      }
    }
    return false;
  }

  public function setProperty($propertyName, $value) {
    self :: $properties[$propertyName] = $value;
  }

  public function parseProperty($propertyName) {
    if (isset ($this->page)) {
      switch ($propertyName) {
        case TITLE :
          $result = $this->parseTitle();
          break;
        case IMDBTT :
          $result = $this->parseIMDBTT();
          break;
        case PROGRAMME :
          $result = $this->parseProgramme();
          break;
        case SERIES :
          $result = $this->parseSeries();
          break;
        case EPISODE :
          $result = $this->parseEpisode();
          break;
        case SYNOPSIS :
          $result = $this->parseSynopsis();
          break;
        case ACTORS :
          $result = $this->parseActors();
          break;
        case DIRECTORS :
          $result = $this->parseDirectors();
          break;
        case GENRES :
          $result = $this->parseGenres();
          break;
        case LANGUAGES :
          return $this->parseLanguages();
          break;
        case YEAR :
          $result = $this->parseYear();
          break;
        case CERTIFICATE :
          $result = $this->parseCertificate();
          break;
        case EXTERNAL_RATING_PC :
          $result = $this->parseExternalRatingPc();
          break;
        case TRAILER :
          $result = $this->parseTrailer();
          break;
        case POSTER :
          $result = $this->parsePoster();
          break;
        case BANNERS :
          $result = $this->parseBanners();
          break;
        case FANART :
          $result = $this->parseFanart();
          break;
        case ACTOR_IMAGES :
          $result = $this->parseActorImages();
          break;
        case MATCH_PC :
          $result = $this->parseMatchPc();
          break;
        default :
          die("ERROR: Property " . $this->getName() . "." . $propertyName." not supported by parser");
          break;
      }
      send_to_log(8, "Parsed property: " . $this->getName() . "." . $propertyName, $result);
      return $result;
    } else
      send_to_log(4, "PAGE WAS NOT SET! " . $this->getName());
  }

  protected function doSmartSearch($id, $filename, $title, $use_foldersearch = false) {
    send_to_log (8, "SmartSearch: ".$title." parser ".$this->getName());
    // Perform search for matching titles
    $is_sample = ParserUtil :: is_sample_folder($filename);

    if ($use_foldersearch)
      $moviefolder_name = ParserUtil :: get_moviefolder_name($filename);

    $moviefolder_year = ParserUtil :: get_year_from_title(ParserUtil :: strip_moviefolder_title(dirname($filename)));
    $title_year = ParserUtil :: get_year_from_title(strip_title($title));
    $title = ParserUtil :: remove_metadata($title);
    $movie_year = false;

    if ($title_year != false) {
      $movie_year = $title_year;
    } else {
      if ($moviefolder_year != false) {
        $movie_year = $moviefolder_year;
      } else {
        $movie_year = ParserUtil :: getYearFromFilePath($filename);
      }
    }

    $title = ParserUtil :: my_ucwords($title);
    send_to_log(4, "Searching for details about " . $title . " online at " . $this->site_url);
    if ($use_foldersearch)
      send_to_log(4, "In case of folder search: " . $moviefolder_name);

    $this->accuracy = 0;
    $searchStrings = null;

    $index = 0;
    $searchList = array();
    $searchList[$index]["title"] = $title;
    $searchList[$index]["year"] = ($movie_year != false) ? $movie_year : null;

    if ($use_foldersearch) {
      $searchList[++ $index]["title"] = $moviefolder_name;
      $searchList[$index]["year"] = ($movie_year != false) ? $movie_year : null;
    }
    if ($movie_year != false) {
      $searchList[++ $index]["title"] = $title;
      $searchList[$index]["year"] = null;
      if ($use_foldersearch) {
        $searchList[++ $index]["title"] = $moviefolder_name;
        $searchList[$index]["year"] = null;
      }
    }

    foreach ($searchList as $parameters) {
      send_to_log(6, "Using search parameters: " . $parameters["title"]." ".$parameters["year"]);
      //Don't search for title if it's a typically generic moviefolder name and folder names are to be used.
      if (!(ParserUtil :: is_standard_moviefolder_name($parameters["title"]) && $use_foldersearch && $parameters["title"] == $moviefolder_name)) {
        $this->populatePage( array('TITLE' => $parameters["title"],
                                   'YEAR'  => $parameters["year"]) );
        if ($this->accuracy >= 75) {
          send_to_log(8, "Success for string: " . $parameters["title"]." ".$parameters["year"]);
          return true;
        } else
          send_to_log(8, "This string didn't work: " . $parameters["title"]." ".$parameters["year"]);
      }
    }
    return false;
  }

  /**
   * Check filename and title for an IMDb ID.
   *
   * @param array $details
   * @return string
   */
  protected function checkForIMDBTT($details) {
    if (preg_match("/\[(tt\d+)\]/", $details["FILENAME"], $imdbtt) != 0) {
      // Filename includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
      return $imdbtt[1];
    }
    elseif (preg_match("/\[(tt\d+)\]/", $details["TITLE"], $imdbtt) != 0) {
      // Film title includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
      return $imdbtt[1];
    }
    elseif ($this->getProperty(IMDBTT) != null && $this->getProperty(IMDBTT) != "") {
      return $this->getProperty(IMDBTT);
    } else {
      return false;
    }
  }
}
?>
