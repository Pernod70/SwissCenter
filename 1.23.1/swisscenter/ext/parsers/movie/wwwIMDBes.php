<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

/**
 * Class to query the Spanish IMDb for imdbtt and title
 */
class wwwIMDBes extends wwwIMDBcom implements ParserInterface {
  protected $site_url = 'http://www.imdb.es/';
  protected $search_url = 'http://www.imdb.es/find?s=tt&q=';

  protected $match_plot = 'Trama';
  protected $match_genre = 'Género';
  protected $match_director = 'Director';
  protected $match_language = 'Idioma';
  protected $match_certificate = 'Clasificación';

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
    return "www.IMDb.es";
  }

  protected function getSearchPageHTML() {
    return "<title>Búsqueda de Títulos de IMDb</title>";
  }

  protected function getNoMatchFoundHTML() {
    return "No hay resultados.";
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
protected function parseCertificate() {
    $html = $this->page;
    $certlist = array ();
    foreach (explode('|', substr_between_strings($html, $this->match_certificate.':', '</div>')) as $cert) {
      $country = trim(substr($cert, 0, strpos($cert, ':')));
      $certificate = trim(substr($cert, strpos($cert, ':') + 1)) . ' ';
      $certlist[$country] = substr($certificate, 0, strpos($certificate, ' '));
    }
    if (get_rating_scheme_name() == 'BBFC')
      $rating = (isset ($certlist["Reino Unido"]) ? $certlist["Reino Unido"] : $certlist["Estados Unidos"]);
    elseif (get_rating_scheme_name() == 'MPAA')
      $rating = (isset ($certlist["Estados Unidos"]) ? $certlist["Estados Unidos"] : $certlist["Reino Unido"]);
    elseif (get_rating_scheme_name() == 'Kijkwijzer')
      $rating = (isset ($certlist["Netherlands"]) ? $certlist["Netherlands"] : (isset ($certlist["Estados Unidos"]) ? $certlist["Estados Unidos"] : $certlist["Reino Unido"]));
    if(isset($rating) && !empty($rating)){
      $this->setProperty(CERTIFICATE, $rating);
      return $rating;
    }
  }
}
?>
