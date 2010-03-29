<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to the movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, certificate, synopsis, directors and actors.

 *************************************************************************************************/

/**
 * Class to query the German IMDb for imdbtt and title
 */
class wwwIMDBde extends wwwIMDBcom implements ParserInterface {
  protected $site_url = 'http://www.imdb.de/';
  protected $search_url = 'http://www.imdb.de/find?s=tt;q=';

  public $supportedProperties = array (
    IMDBTT,
    TITLE
  );

  public static function getName() {
    return "www.IMDb.de";
  }

  protected function getSearchPageHTML() {
    return "<title>IMDb Titelsuche</title>";
  }

  protected function getNoMatchFoundHTML() {
    return "Keine Treffer.";
  }
}
?>
