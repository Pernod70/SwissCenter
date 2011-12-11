<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/get_parsers_list.php'));
require_once( realpath(dirname(__FILE__).'/include_tv_parsers.php'));

function ParserTvLookup($tv_id, $filename, $search_params) {
  $retrycount = get_sys_pref('tv_parser_retry_count', 1);

  $oneInstancePerParserArray = array ();
  $propertyFail = array();
  $allFail = true;
  for ($i = 0; $i < count(ParserConstants :: $allTvConstants); $i++) {
    $parser_pref = explode(',', get_sys_pref('tv_parser_' . ParserConstants :: $allTvConstants[$i]['ID'],
                                                            ParserConstants :: $allTvConstants[$i]['DEFAULT']));
    for ($x = 0; $x < $retrycount; $x++) {
      if (isset($parser_pref[$x]) && $parser_pref[$x] !== 'NoParser') {
        // Create instance of parser
        $parserclass = $parser_pref[$x];
        if (!isset ($oneInstancePerParserArray[$parserclass])) {
          $parser = new $parserclass ($tv_id, $filename, $search_params);
          $oneInstancePerParserArray[$parserclass] = $parser;
        } else {
          $parser = $oneInstancePerParserArray[$parserclass];
        }
        // Check whether property has already been populated
        $propertyCheck = $parser->getProperty(ParserConstants :: $allTvConstants[$i]['ID']);
        if (!isset ($propertyCheck)) {
          $returnval = $parser->parseProperty(ParserConstants :: $allTvConstants[$i]['ID']);
          if (!isset ($returnval)) {
            send_to_log(4, "ERROR: Parsing of " . ParserConstants :: $allTvConstants[$i]['ID'] . " from " . $parser->getName() . " failed!");
          } else {
            // Store successfully retrieved properties
            $allFail = false;
            switch (ParserConstants :: $allTvConstants[$i]['ID']) {
              case ACTORS:
                scdb_add_tv_actors($tv_id, $returnval);
                break;
              case DIRECTORS:
                scdb_add_tv_directors($tv_id, $returnval);
                break;
              case GENRES:
                scdb_add_tv_genres($tv_id, $returnval);
                break;
              case LANGUAGES:
                scdb_add_tv_languages($tv_id, $returnval);
                break;
              case CERTIFICATE:
                $returnval = db_lookup('certificates', 'name', 'cert_id', $parser->getProperty(CERTIFICATE));
              case PROGRAMME:
              case SERIES:
              case EPISODE:
              case TITLE:
              case SYNOPSIS:
              case YEAR:
              case EXTERNAL_RATING_PC:
              case MATCH_PC:
                scdb_set_tv_attribs($tv_id, array(ParserConstants :: $allTvConstants[$i]['ID'] => $returnval));
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
    $propertyCheck = $parser->getProperty(ParserConstants :: $allTvConstants[$i]['ID']);
    if (!isset ($propertyCheck)) {
      $propertyFail[] = str(ParserConstants :: $allTvConstants[$i]['TEXT']);
    }
  }

  // Download poster
  if (!empty($poster) && file_albumart($filename, false) == '') {
    file_save_albumart($poster, dirname($filename) . '/' . file_noext($filename) . '.' . file_ext($poster), '');
  }

  // Cache folder for downloading images
  $cache_dir = get_sys_pref('cache_dir').'/tvdb';

  // Ensure local cache folders exist
  if (!file_exists($cache_dir)) { @mkdir($cache_dir); }
  if (!file_exists($cache_dir.'/banners')) { @mkdir($cache_dir.'/banners'); }
  if (!file_exists(dirname($filename).'/banners')) { @mkdir(dirname($filename).'/banners'); }
  if (!file_exists($cache_dir.'/fanart')) { @mkdir($cache_dir.'/fanart'); }
  if (!file_exists($cache_dir.'/actors')) { @mkdir($cache_dir.'/actors'); }
  if (!file_exists(SC_LOCATION.'fanart/actors')) { @mkdir(SC_LOCATION.'fanart/actors'); }

  // Download series banners (save to cache before copying to video folder)
  $banners = $parser->getProperty(BANNERS);
  $series = db_value("select series from tv where file_id=$tv_id");
  if (isset($banners[$series])) {
    foreach ($banners[$series] as $banner_path) {
      // Reset the timeout counter for each image downloaded
      set_time_limit(30);
      if (!file_exists($cache_dir.'/banners/'.basename($banner_path)))
        file_save_albumart( $banner_path
                          , $cache_dir.'/banners/'.basename($banner_path)
                          , '');
      if (!file_exists(dirname($filename).'/banners/series'.sprintf("%02d", $series).'_'.basename($banner_path)))
        @copy($cache_dir.'/banners/'.basename($banner_path),
              dirname($filename).'/banners/series'.sprintf("%02d", $series).'_'.basename($banner_path));
    }
  }

  // Download programme banners (save to cache before copying to video folder)
  if (isset($banners['BANNER'])) {
    foreach ($banners['BANNER'] as $banner_path) {
      // Reset the timeout counter for each image downloaded
      set_time_limit(30);
      if (!file_exists($cache_dir.'/banners/'.basename($banner_path)))
        file_save_albumart( $banner_path
                          , $cache_dir.'/banners/'.basename($banner_path)
                          , '');
      if (!file_exists(dirname($filename).'/banners/banner_'.basename($banner_path)))
        @copy($cache_dir.'/banners/'.basename($banner_path),
              dirname($filename).'/banners/banner_'.basename($banner_path));
    }
  }

  // Download programme fanart thumbnails
  $fanart = $parser->getProperty(FANART);
  if (isset($fanart['FANART'])) {
    $programme = db_value("select programme from tv where file_id=$tv_id");
    foreach ($fanart['FANART'] as $thumb) {
      // Reset the timeout counter for each image downloaded
      set_time_limit(30);
      $thumb_cache = $cache_dir.'/fanart/'.basename($thumb['THUMBNAIL']);
      if (!file_exists($thumb_cache))
        file_save_albumart( $thumb['THUMBNAIL'], $thumb_cache, '');

      // Insert information into database
      $data = array( "title"        => $programme
                   , "media_type"   => MEDIA_TYPE_TV
                   , "thumb_cache"  => os_path($thumb_cache)
                   , "original_url" => $thumb['ORIGINAL']
                   , "resolution"   => $thumb['RESOLUTION']
                   , "colors"       => $thumb['COLORS'] );
      $file_id = db_value("select file_id from themes where title='".db_escape_str($programme)."' and original_url='".db_escape_str($thumb['ORIGINAL'])."'");
      if ( $file_id )
        db_update_row( "themes", $file_id, $data);
      else
        db_insert_row( "themes", $data);
    }
  }

  // Download actor images (save to cache before copying to fanart folder)
  $actors = $parser->getProperty(ACTOR_IMAGES);
  if (isset($actors)) {
    foreach ($actors as $actor) {
      if ( !empty($actor['IMAGE']) ) {
        // Reset the timeout counter for each image downloaded
        set_time_limit(30);
        if (!file_exists($cache_dir.'/actors/'.basename($actor['IMAGE'])))
          file_save_albumart( $actor['IMAGE']
                            , $cache_dir.'/actors/'.basename($actor['IMAGE'])
                            , '');
        $actor_name_safe = filename_safe(strtolower($actor['NAME']));
        if (!file_exists(SC_LOCATION.'fanart/actors/'.$actor_name_safe.'/'.basename($actor['IMAGE']))) {
          if (!file_exists(SC_LOCATION.'fanart/actors/'.$actor_name_safe)) { @mkdir(SC_LOCATION.'fanart/actors/'.$actor_name_safe); }
            copy($cache_dir.'/actors/'.basename($actor['IMAGE']),
                 SC_LOCATION.'fanart/actors/'.$actor_name_safe.'/'.basename($actor['IMAGE']));
        }
      }
    }
  }

  return empty($propertyFail) ? true : ($allFail ? false : $propertyFail);
}
?>

