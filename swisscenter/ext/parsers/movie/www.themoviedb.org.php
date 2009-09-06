<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Please help themoviedb.org website by contributing information and artwork if possible.

   Version history:
   01-Feb-2009: v1.0:     First public release
   29-May-2009: v2.0:     Updated to use API 2.1

 *************************************************************************************************/

// API key registered to SwissCenter project
define('MOVIEDB_API_KEY', '2548980e43e9c7d08b705a2e57e9afe3');

/**
 * Searches the themoviedb.org site for movie details
 *
 * @param integer $id
 * @param string $filename
 * @param string $title
 * @return bool
 */
function extra_get_movie_details($id, $filename, $title)
{
  global $moviematches;

  // The site URL (may be used later)
  $site_url = 'http://themoviedb.org/';
  $details  = db_toarray("select * from movies where file_id=$id");
  $accuracy = 0;

  // Ensure local cache folders exist
  $cache_dir = get_sys_pref('cache_dir').'/tmdb';
  if(!file_exists($cache_dir)) { @mkdir($cache_dir); }
  if(!file_exists($cache_dir.'/fanart')) { @mkdir($cache_dir.'/fanart'); }

  send_to_log(4,"Searching for details about ".$title." online at '$site_url'");

  // Get the moviedb id
  if (preg_match("/\[(tt\d+)\]/",$filename, $imdbtt) != 0)
  {
    // Filename includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
    $moviedb_id = get_moviedb_id($title, $imdbtt[1]);
  }
  elseif (preg_match("/\[(tt\d+)\]/",$details[0]["TITLE"], $imdbtt) != 0)
  {
    // Film title includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
    $moviedb_id = get_moviedb_id($title, $imdbtt[1]);
  }
  else
  {
    // User TMDb's internal search to get a list a possible matches
    $moviedb_id = get_moviedb_id($title, $imdbtt);
  }

  if ($moviedb_id)
  {
    // Parse the movie details
    $moviematches = array();
    parse_moviedb_xml('http://api.themoviedb.org/2.1/Movie.getInfo/en/xml/'.MOVIEDB_API_KEY.'/'.$moviedb_id, 'start_tag_moviematches', 'end_tag_moviematches', 'tag_contents_moviematches');

    // Download poster image
    if (!empty($moviematches[0]['POSTER']['ORIGINAL'][0]))
      file_save_albumart( $moviematches[0]['POSTER']['ORIGINAL'][0]
                        , dirname($filename).'/'.file_noext($filename).'.'.file_ext($moviematches[0]['POSTER']['ORIGINAL'][0])
                        , '');

    scdb_add_directors( $id, $moviematches[0]['DIRECTOR'] );
    scdb_add_actors   ( $id, $moviematches[0]['ACTOR'] );
    scdb_add_genres   ( $id, $moviematches[0]['CATEGORY'] );

    // Store the single-value attributes in the database
    $columns = array ( "YEAR"              => substr($moviematches[0]['RELEASED'],0,4)
                     , "EXTERNAL_RATING_PC"=> floor($moviematches[0]['RATING'] * 10)
                     , "DETAILS_AVAILABLE" => 'Y'
                     , "TRAILER"           => $moviematches[0]['TRAILER']
                     , "SYNOPSIS"          => $moviematches[0]['OVERVIEW'] );
    scdb_set_movie_attribs( $id, $columns );

    // Download fanart thumbnails
    if (isset($moviematches[0]['BACKDROP']))
    {
      foreach ($moviematches[0]['BACKDROP']['THUMB'] as $fanart)
      {
        // TMDb doesn't use unique filenames so retrieve image id
        $image_id = preg_get('/backdrops\/(\d+)/', $fanart);

        // Reset the timeout counter for each image downloaded
        set_time_limit(30);
        $thumb_cache = $cache_dir.'/fanart/'.$image_id.'_'.basename($fanart);

        if (!file_exists($thumb_cache))
          file_save_albumart( $fanart, $thumb_cache, '');

        // Insert information into database
        $data = array( "title"        => $title
                     , "media_type"   => MEDIA_TYPE_VIDEO
                     , "thumb_cache"  => addslashes(os_path($thumb_cache))
                     , "original_url" => str_replace('_thumb','',$fanart) );
        $file_id = db_value("select file_id from themes where title='".db_escape_str($title)."' and instr(original_url,'/".$image_id."/')>0");
        if ( $file_id )
          db_update_row( "themes", $file_id, $data);
        else
          db_insert_row( "themes", $data);
      }
    }

    return true;
  }
  else
  {
    send_to_log(4,"No Match found.");
  }

  // Mark the file as attempted to get details, but none available
  $columns = array ( "DETAILS_AVAILABLE" => 'N');
  scdb_set_movie_attribs($id, $columns);
  return false;
}

/**
 * Returns the moviedb id by using the API and finding the closest match to our movie title.
 *
 * @param  string $title - Name of movie to search for
 * @param  string $imdbtt - IMDB id if provided
 * @return integer
 */
function get_moviedb_id($title, $imdbtt='')
{
  global $moviematches;

  // Use IMDb id (if provided), otherwise submit a search
  if (!empty($imdbtt))
    $filename = 'http://api.themoviedb.org/2.1/Movie.imdbLookup/en/xml/'.MOVIEDB_API_KEY.'/'.$imdbtt;
  else
    $filename = 'http://api.themoviedb.org/2.1/Movie.search/en/xml/'.MOVIEDB_API_KEY.'/'.urlencode($title);

  // Parse the xml results and determine best match for title
  $moviematches = array();
  parse_moviedb_xml($filename, 'start_tag_moviematches', 'end_tag_moviematches', 'tag_contents_moviematches');

  // Find best match for required title
  if (count($moviematches)>0)
  {
    if (!empty($imdbtt))
    {
      // Found IMDb id
      $accuracy = 100;
      $index = 0;
      send_to_log(4,"Matched IMDb Id:", $moviematches[$index]['NAME'] );
    }
    else
    {
      // There are multiple matches found... process them
      $matches = array();
      $matches_id = array();
      foreach ($moviematches as $movie)
      {
        $matches[] = $movie['NAME'];
        $matches_id[] = $movie['ID'];
        if (isset($movie['ALTERNATIVE_NAME']) && !empty($movie['ALTERNATIVE_NAME']))
        {
          $matches[] = $movie['ALTERNATIVE_NAME'];
          $matches_id[] = $movie['ID'];
        }
      }
      $index = best_match($title, $matches, $accuracy);
    }
    // If we are sure that we found a good result, then get the file details.
    if ($accuracy > 75)
      return $matches_id[$index];
    else
      return false;
  }
  else
  {
    send_to_log(4,"No Match found.");
    return false;
  }
}

/**
 * Parse the XML file
 *
 * @param string $filename
 */
function parse_moviedb_xml($filename, $start_tag, $end_tag, $tag_contents)
{
  // Create XML parser
  $xmlparser = xml_parser_create();
  $success = true;
  if ($xmlparser !== false)
  {
    xml_set_element_handler($xmlparser, $start_tag, $end_tag);
    xml_set_character_data_handler($xmlparser, $tag_contents);

    // Read and process XML file
    $fp = @fopen($filename, "r");
    if ($fp !== false)
    {
      send_to_log(6,'Parsing XML: '.$filename);
      while ($data = fread($fp, 8192))
      {
        $data = preg_replace("/>\s+/", ">", $data);
        $data = preg_replace("/\s+</", "<", $data);
        if (!xml_parse($xmlparser, $data , feof($fp)))
        {
          send_to_log(8,'XML parse error: '.xml_error_string(xml_get_error_code($xmlparser)).xml_get_current_line_number($xmlparser));
          $success = false;
          break;
        }
      }
    }
    else
    {
      send_to_log(5,'Unable to read the specified file: '.$filename);
      $success = false;
    }

    xml_parser_free($xmlparser);
  }
  else
  {
    send_to_log(5,'Unable to create an expat XML parser - is the "xml" extension loaded into PHP?');
    $success = false;
  }

  return $success;
}

//-------------------------------------------------------------------------------------------------
// Callback functions to perform parsing of the various XML files.
//-------------------------------------------------------------------------------------------------

function start_tag_moviematches($parser, $name, $attribs)
{
  global $tag;
  global $movie;

  switch ($name)
  {
    case 'MOVIE':
      $movie = array();
      break;
    case 'PERSON':
      if (strtoupper($attribs['JOB']) == 'ACTOR')    { $movie['ACTOR'][]    = utf8_decode($attribs['NAME']); }
      if (strtoupper($attribs['JOB']) == 'DIRECTOR') { $movie['DIRECTOR'][] = utf8_decode($attribs['NAME']); }
      break;
    case 'CATEGORY':
      if (strtoupper($attribs['TYPE']) == 'GENRE')   { $movie['CATEGORY'][] = utf8_decode($attribs['NAME']); }
      break;
    case 'STUDIO':
      $movie['STUDIO'][] = utf8_decode($attribs['NAME']);
      break;
    case 'COUNTRY':
      $movie['COUNTRY'][] = utf8_decode($attribs['NAME']);
      break;
    case 'IMAGE':
      $movie[strtoupper($attribs['TYPE'])][strtoupper($attribs['SIZE'])][] = $attribs['URL'];
      break;
    default:
      $tag = $name;
  }
}

function end_tag_moviematches($parser, $name)
{
  global $movie;
  global $moviematches;

  switch ($name)
  {
    case 'MOVIE':
      if ( $movie['TYPE'] == 'movie' ) $moviematches[] = $movie;
      break;
    default:
  }
}

function tag_contents_moviematches($parser, $data)
{
  global $tag;
  global $movie;

  $data = utf8_decode($data);

  switch ($tag)
  {
    case 'NAME':            { $movie['NAME'] .= $data; break; }
    case 'ALTERNATIVE_NAME':{ $movie['ALTERNATIVE_NAME'] .= $data; break; }
    case 'TYPE':            { $movie['TYPE'] .= $data; break; }
    case 'ID':              { $movie['ID'] .= $data; break; }
    case 'IMDB_ID':         { $movie['IMDB_ID'] .= $data; break; }
    case 'URL':             { $movie['URL'] .= $data; break; }
    case 'OVERVIEW':        { $movie['OVERVIEW'] .= $data; break; }
    case 'RATING':          { $movie['RATING'] .= $data; break; }
    case 'RELEASED':        { $movie['RELEASED'] .= $data; break; }
    case 'RUNTIME':         { $movie['RUNTIME'] .= $data; break; }
    case 'TRAILER':         { $movie['TRAILER'] .= $data; break; }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
