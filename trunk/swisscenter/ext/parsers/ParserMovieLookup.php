<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/get_parsers_list.php'));
require_once( realpath(dirname(__FILE__).'/include_movie_parsers.php'));

function ParserMovieLookup($movie_id, $filename, $search_params) {
  $use_smartsearch  = get_sys_pref('use_smartsearch', 'YES') == 'YES';
  $use_foldersearch = get_sys_pref('use_foldersearch', 'YES') == 'YES';
  $retrycount       = get_sys_pref('movie_parser_retry_count', 1);

  $oneInstancePerParserArray = array ();
  $propertyFail = array();
  $allFail = true;
  for ($i = 0; $i < count(ParserConstants :: $allMovieConstants); $i++) {
    $parser_pref = explode(',', get_sys_pref('movie_parser_' . ParserConstants :: $allMovieConstants[$i]['ID'],
                                                               ParserConstants :: $allMovieConstants[$i]['DEFAULT']));
    for ($x = 0; $x < $retrycount; $x++) {
      if (isset($parser_pref[$x]) && !empty($parser_pref[$x]) && $parser_pref[$x] !== 'NoParser') {
        // Create instance of parser
        $parserclass = $parser_pref[$x];
        if (!isset ($oneInstancePerParserArray[$parserclass])) {
          $parser = new $parserclass ($movie_id, $filename, $search_params, $use_smartsearch, $use_foldersearch);
          $oneInstancePerParserArray[$parserclass] = $parser;
        } else {
          $parser = $oneInstancePerParserArray[$parserclass];
        }
        // Check whether property has already been populated
        $propertyCheck = $parser->getProperty(ParserConstants :: $allMovieConstants[$i]['ID']);
        if (!isset ($propertyCheck)) {
          $returnval = $parser->parseProperty(ParserConstants :: $allMovieConstants[$i]['ID']);
          if (!isset ($returnval)) {
            send_to_log(4, "ERROR: Parsing of " . ParserConstants :: $allMovieConstants[$i]['ID'] . " from " . $parser->getName() . " failed!");
          } else {
            // Store successfully retrieved properties
            $allFail = false;
            // Ensure all data is UTF-8 encoded
            $returnval = encode_utf8($returnval);
            switch (ParserConstants :: $allMovieConstants[$i]['ID']) {
              case ACTORS:
                scdb_add_actors($movie_id, $returnval);
                break;
              case DIRECTORS:
                scdb_add_directors($movie_id, $returnval);
                break;
              case GENRES:
                scdb_add_genres($movie_id, $returnval);
                break;
              case LANGUAGES:
                scdb_add_languages($movie_id, $returnval);
                break;
              case CERTIFICATE:
                $returnval = db_lookup('certificates', 'name', 'cert_id', $parser->getProperty(CERTIFICATE));
                scdb_set_movie_attribs($movie_id, array(ParserConstants :: $allMovieConstants[$i]['ID'] => $returnval));
                break;
              case TITLE:
                // Ensure title is used for subsequent parsers
                $search_params['TITLE'] = $returnval;
              case SYNOPSIS:
              case YEAR:
              case EXTERNAL_RATING_PC:
              case TRAILER:
              case MATCH_PC:
                scdb_set_movie_attribs($movie_id, array(ParserConstants :: $allMovieConstants[$i]['ID'] => $returnval));
                break;
              case POSTER:
                $poster = $returnval;
                break;
            }
          }
        }
      }
    }
    // Check whether property has been populated after parser attempts
    $propertyCheck = $parser->getProperty(ParserConstants :: $allMovieConstants[$i]['ID']);
    if (!isset ($propertyCheck)) {
      $propertyFail[] = str(ParserConstants :: $allMovieConstants[$i]['TEXT']);
    }
  }

  // Download poster
  if (!empty($poster) && file_albumart($filename, false) == '') {
    file_save_albumart($poster, dirname($filename) . '/' . file_noext($filename) . '.' . file_ext($poster), '');
  }

  // Cache folder for downloading images
  $cache_dir = get_sys_pref('cache_dir') . '/tmdb';

  // Ensure local cache folders exist
  $oldumask = umask(0);
  if (!file_exists($cache_dir)) { @mkdir($cache_dir,0777); }
  if (!file_exists($cache_dir.'/fanart')) { @mkdir($cache_dir.'/fanart',0777); }
  if (!file_exists($cache_dir.'/actors')) { @mkdir($cache_dir.'/actors',0777); }
  if (!file_exists(SC_LOCATION.'fanart/actors')) { @mkdir(SC_LOCATION.'fanart/actors',0777); }
  umask($oldumask);

  // Download fanart thumbnails
  $fanart = $parser->getProperty(FANART);
  if (isset ($fanart)) {
    $title = db_value("select title from movies where file_id=$movie_id");
    foreach ($fanart as $image) {
      // Reset the timeout counter for each image downloaded
      set_time_limit(30);
      $thumb_cache = $cache_dir.'/fanart/'.basename($image['thumb']);

      if (!file_exists($thumb_cache))
        file_save_albumart($image['thumb'], $thumb_cache, '');

      // Insert information into database
      $data = array (
        "title" => $title,
        "media_type" => MEDIA_TYPE_VIDEO,
        "thumb_cache" => os_path($thumb_cache),
        "original_url" => $image['original'],
        "resolution"   => $image['resolution']
      );
      $file_id = db_value("select file_id from themes where title='".db_escape_str($title)."' and original_url='".db_escape_str($image['original'])."'");
      if ($file_id)
        db_update_row("themes", $file_id, $data);
      else
        db_insert_row("themes", $data);
    }
  }

  // Download actor images (save to cache before copying to fanart folder)
  $actors = $parser->getProperty(ACTOR_IMAGES);
  if (isset($actors)) {
    foreach ($actors as $actor) {
      if ( !empty($actor['IMAGE']) ) {
        // Reset the timeout counter for each image downloaded
        set_time_limit(30);
        if (!file_exists($cache_dir.'/actors/'.$actor['ID'].'.'.file_ext($actor['IMAGE'])))
          file_save_albumart( $actor['IMAGE']
                            , $cache_dir.'/actors/'.$actor['ID'].'.'.file_ext($actor['IMAGE'])
                            , '');
        $actor_name_safe = filename_safe(strtolower($actor['NAME']));
        if (!file_exists(SC_LOCATION.'fanart/actors/'.$actor_name_safe.'/'.$actor['ID'].'.'.file_ext($actor['IMAGE']))) {
          if (!file_exists(SC_LOCATION.'fanart/actors/'.$actor_name_safe))
          {
            $oldumask = umask(0);
            @mkdir(SC_LOCATION.'fanart/actors/'.$actor_name_safe,0777);
            umask($oldumask);
          }
          copy($cache_dir.'/actors/'.$actor['ID'].'.'.file_ext($actor['IMAGE']),
               SC_LOCATION.'fanart/actors/'.$actor_name_safe.'/'.$actor['ID'].'.'.file_ext($actor['IMAGE']));
        }
      }
    }
  }

  return empty($propertyFail) ? true : ($allFail ? false : $propertyFail);
}
?>

