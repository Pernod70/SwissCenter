<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

/**
 * Class to query the Italian IMDb for imdbtt and title
 */
class wwwIMDBit extends wwwIMDBcom implements ParserInterface {
  protected $site_url = 'http://www.imdb.it/';
  protected $search_url = 'http://www.imdb.it/find?s=tt&q=';

  protected $match_plot = 'Trama';
  protected $match_genre = 'Genere';
  protected $match_director = 'Regista';
  protected $match_language = 'Lingua';
  protected $match_certificate = 'Divieti';

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

  public $settings = array ();

  public static function getName() {
    return "www.IMDb.it";
  }

  protected function getSearchPageHTML() {
    return "<title>IMDb Ricerca Titolo</title>";
  }

  protected function getNoMatchFoundHTML() {
    return "Nessun risultato.";
  }

  /**
   * Properties supported by this parser.
   *
   */

  protected function parseGenres() {
    $html = $this->page;
    $start = strpos($html,"<h5>$this->match_genre:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_genres = substr($html,$start,$end-$start+1);
        $matches = preg_get("/class=\"info-content\">(.*)</Us", $html_genres);
        if (!empty($matches)) {
          $new_genres = array_map("trim", explode(' | ', $matches));
          $this->setProperty(GENRES, $new_genres);
          return $new_genres;
        }
      }
    }
  }
  protected function parseLanguages() {
    $html = $this->page;
    $start = strpos($html,"<h5>$this->match_language:</h5>");
    if ($start !== false) {
      $end = strpos($html,"</div>", $start + 1);
      if ($end !== false) {
        $html_languages = substr($html,$start,$end-$start+1);
        $matches = preg_get("/class=\"info-content\">(.*)</Us", $html_languages);
        if (!empty($matches)) {
          $new_languages = array_map("trim", explode(' | ', $matches));
          $this->setProperty(GENRES, $new_languages);
          return $new_languages;
        }
      }
    }
  }
}
?>
