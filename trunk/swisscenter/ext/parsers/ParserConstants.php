<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

define("IMDBTT", "IMDBTT");
define("PROGRAMME", "PROGRAMME");
define("SERIES", "SERIES");
define("EPISODE", "EPISODE");
define("TITLE", "TITLE");
define("SYNOPSIS", "SYNOPSIS");
define("ACTORS", "ACTORS");
define("DIRECTORS", "DIRECTORS");
define("GENRES", "GENRES");
define("LANGUAGES", "LANGUAGES");
define("YEAR", "YEAR");
define("CERTIFICATE", "CERTIFICATE");
define("EXTERNAL_RATING_PC", "EXTERNAL_RATING_PC");
define("TRAILER", "TRAILER");
define("POSTER", "POSTER");
define("FANART", "FANART");
define("BANNERS", "BANNERS");
define("ACTOR_IMAGES", "ACTOR_IMAGES");
define("DIRECTOR_IMAGES", "DIRECTOR_IMAGES");
define("MATCH_PC", "MATCH_PC");

abstract class ParserConstants {

  public static $allMovieConstants = array (
    array('ID'=>'IMDBTT',             'TEXT'=>'IMDBTT',           'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'TITLE',              'TEXT'=>'TITLE',            'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'SYNOPSIS',           'TEXT'=>'SYNOPSIS',         'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'YEAR',               'TEXT'=>'YEAR',             'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'ACTORS',             'TEXT'=>'ACTOR',            'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'DIRECTORS',          'TEXT'=>'DIRECTOR',         'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'GENRES',             'TEXT'=>'GENRE',            'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'LANGUAGES',          'TEXT'=>'SPOKEN_LANGUAGE',  'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'CERTIFICATE',        'TEXT'=>'CERTIFICATE',      'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'EXTERNAL_RATING_PC', 'TEXT'=>'RATING',           'DEFAULT'=>'wwwIMDBcom'),
    array('ID'=>'TRAILER',            'TEXT'=>'TRAILER_LOCATION', 'DEFAULT'=>'wwwTHEMOVIEDBorg'),
    array('ID'=>'POSTER',             'TEXT'=>'POSTER',           'DEFAULT'=>'wwwTHEMOVIEDBorg'),
    array('ID'=>'FANART',             'TEXT'=>'FANART',           'DEFAULT'=>'wwwTHEMOVIEDBorg')
  );

  public static $allTvConstants = array (
    array('ID'=>'IMDBTT',             'TEXT'=>'IMDBTT',           'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'PROGRAMME',          'TEXT'=>'PROGRAMME',        'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'SERIES',             'TEXT'=>'SERIES',           'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'EPISODE',            'TEXT'=>'EPISODE',          'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'TITLE',              'TEXT'=>'TITLE',            'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'SYNOPSIS',           'TEXT'=>'SYNOPSIS',         'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'YEAR',               'TEXT'=>'YEAR',             'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'ACTORS',             'TEXT'=>'ACTOR',            'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'DIRECTORS',          'TEXT'=>'DIRECTOR',         'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'GENRES',             'TEXT'=>'GENRE',            'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'LANGUAGES',          'TEXT'=>'SPOKEN_LANGUAGE',  'DEFAULT'=>'NoParser'),
    array('ID'=>'CERTIFICATE',        'TEXT'=>'CERTIFICATE',      'DEFAULT'=>'NoParser'),
    array('ID'=>'EXTERNAL_RATING_PC', 'TEXT'=>'RATING',           'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'POSTER',             'TEXT'=>'POSTER',           'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'FANART',             'TEXT'=>'FANART',           'DEFAULT'=>'wwwTHETVDBcom'),
    array('ID'=>'BANNERS',            'TEXT'=>'BANNERS',          'DEFAULT'=>'wwwTHETVDBcom')
  );
}
?>
