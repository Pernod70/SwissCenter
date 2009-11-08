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

   Version history:
   08-Mar-2008: v1.0:     First public release
   31-Mar-2008: v1.1:     Fixed episode year and now informs user if episode details are not found.
   23-Apr-2008: v1.2:     Fixed downloading of series 0 (Special) banners.
   25-Jun-2008: v1.3:     Improved zip support on Linux installations.
   08-Jul-2008: v1.4:     Added parsing of actors.xml.
   05-Nov-2008: v1.5:     Actor names 'cleaned' of character names in brackets.

 *************************************************************************************************/

// API key registered to SwissCenter project
define('TVDB_API_KEY', 'FA3F6F720A61DE71');

/**
 * Searches the tv.com site for tv episode details
 *
 * @param integer $id
 * @param string $filename
 * @param string $programme
 * @param string $series
 * @param string $episode
 * @param string $title
 * @return bool
 */
function extra_get_tv_details($id, $filename, $programme, $series='', $episode='', $title='')
{
  // The site URL (may be used later)
  $site_url = 'http://thetvdb.com/';
  $accuracy = 0;

  // Supported languages (hardcoded from languages.xml)
  $languages = array('da','fi','nl','de','it','es','fr','pl','hu','el','tr','ru','he','ja','pt','zh','cs','en','sv','no');

  send_to_log(4,"Searching for details about ".$programme." Season: ".$series." Episode: ".$episode." online at ".$site_url);

  // Users preferred language (use 'en' if not supported)
  $language = substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
  if ( !in_array($language, $languages) )
  {
    $language = 'en';
    send_to_log(5,"User language '".$language."' not supported, using 'en'");
  }

  // Ensure local cache folders exist
  $cache_dir = get_sys_pref('cache_dir').'/tvdb';
  if (!file_exists($cache_dir)) { @mkdir($cache_dir); }
  if (!file_exists($cache_dir.'/banners')) { @mkdir($cache_dir.'/banners'); }
  if (!file_exists(dirname($filename).'/banners')) { @mkdir(dirname($filename).'/banners'); }
  if (!file_exists($cache_dir.'/fanart')) { @mkdir($cache_dir.'/fanart'); }

  // Get mirror for xml and banners
  get_mirrors($xmlmirror, $bannermirror);

  if ( !empty($xmlmirror) )
  {
    // Get the series id using the API
    $series_id = get_series_id($programme);

    if ( !empty($series_id) )
    {
      global $tvdb_series, $tvdb_episodes, $tvdb_banners;

      $series_zip_url   = $xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/all/'.$language.'.zip';
      $series_xml_url   = $xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/all/'.$language.'.xml';
      $banner_xml_url   = $xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/banners.xml';
      $actors_xml_url   = $xmlmirror.'/api/'.TVDB_API_KEY.'/series/'.$series_id.'/actors.xml';
      $series_zip_cache = $cache_dir.'/'.$series_id.'_'.$language.'.zip';
      $series_cache     = $cache_dir.'/'.$series_id.'_'.$language.'.xml';
      $banner_cache     = $cache_dir.'/'.$series_id.'_banners.xml';
      $actors_cache     = $cache_dir.'/'.$series_id.'_banners.xml';

      // Ensure local copy of full series zip is uptodate (cache valid for 6 hours)
      $series_cache_time  = (file_exists($series_cache) ? filemtime($series_cache) : 0);
      if ($series_cache_time < (time() - 21600))
      {
        if ( !extension_loaded('zip') )
        {
          // Zip extension not loaded so download non-zipped data
          file_download_and_save($series_xml_url, $series_cache, true);
          file_download_and_save($banner_xml_url, $banner_cache, true);
//          file_download_and_save($actors_xml_url, $actors_cache, true);
        }
        else
        {
          file_download_and_save($series_zip_url, $series_zip_cache, true);
          if (file_exists($series_zip_cache))
          {
            // Extract zip contents
            if ( is_resource($zip = @zip_open($series_zip_cache)) )
            {
              while ($zip_entry = zip_read($zip))
              {
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
            elseif (is_unix())
            {
              // On LINUX machines, we can use the standard "unzip" command to
              // perform the same functions as the zip extension
              exec('unzip '.$series_zip_cache.' -d '.$cache_dir);
            }
            else
            {
              send_to_log(6,"Failed to open series zip from www.thetvdb.com. (zip_open errno=$zip)");
            }
          }
        }
      }

      // Parse the Full Series Record
      $tvdb_series = array();
      $tvdb_series['SC_SERIES'] = $series;
      $tvdb_series['SC_EPISODE'] = $episode;
      $tvdb_series['SC_TITLE'] = $title;
      parse_tvdb_xml($series_cache, 'start_tag_tvdb_episode', 'end_tag_tvdb_episode', 'tag_contents_tvdb_episode');

      // Determine best match from available episodes
      if (isset($tvdb_series['EPISODEID']))
      {
        $ep = $tvdb_series['EPISODEID'];
        $accuracy = 100;
      }
      else
        $ep = best_match(ucwords(strtolower($title)), $tvdb_episodes['EPISODENAME'], $accuracy);

      if ($ep !== false)
      {
        // Download episode image
        if (!empty($tvdb_episodes['FILENAME'][$ep]))
        {
          // Reset the timeout counter for each image downloaded
          set_time_limit(30);
          file_save_albumart( add_site_to_url($tvdb_episodes['FILENAME'][$ep], $bannermirror.'/banners/')
                            , dirname($filename).'/'.file_noext($filename).'.'.file_ext($tvdb_episodes['FILENAME'][$ep])
                            , '');
        }

        scdb_add_tv_directors( $id, explode('|', clean_name_list($tvdb_episodes['DIRECTOR'][$ep])) );
        scdb_add_tv_actors   ( $id, explode('|', clean_name_list($tvdb_episodes['GUESTSTARS'][$ep])) );
        scdb_add_tv_actors   ( $id, explode('|', clean_name_list($tvdb_series['ACTORS'])) );
        scdb_add_tv_genres   ( $id, explode('|', clean_name_list($tvdb_series['GENRE'])) );

        // Store the single-value attributes in the database
        $columns = array ( "TITLE"             => $tvdb_episodes['EPISODENAME'][$ep]
                         , "SERIES"            => $tvdb_episodes['SEASONNUMBER'][$ep]
                         , "EPISODE"           => $tvdb_episodes['EPISODENUMBER'][$ep]
                         , "YEAR"              => substr($tvdb_episodes['FIRSTAIRED'][$ep],0,4)
                         , "EXTERNAL_RATING_PC"=> (empty($tvdb_episodes['RATING'][$ep]) ? '' : $tvdb_episodes['RATING'][$ep] * 10 )
                         , "DETAILS_AVAILABLE" => 'Y'
                         , "SYNOPSIS"          => $tvdb_episodes['OVERVIEW'][$ep]);
        scdb_set_tv_attribs  ( $id, $columns );

        // Parse and download the banners
        $tvdb_banners = array();
        parse_tvdb_xml($banner_cache, 'start_tag_tvdb_banner', 'end_tag_tvdb_banner', 'tag_contents_tvdb_banner');

        // Programme banners (save to cache before copying to video folder)
        if (isset($tvdb_banners[trim($series)]))
        {
          foreach ($tvdb_banners[trim($series)] as $banner_path)
          {
            // Reset the timeout counter for each image downloaded
            set_time_limit(30);
            if (!file_exists($cache_dir.'/banners/'.basename($banner_path)))
              file_save_albumart( add_site_to_url($banner_path, $bannermirror.'/banners/')
                                , $cache_dir.'/banners/'.basename($banner_path)
                                , '');
            if (!file_exists(dirname($filename).'/banners/series'.sprintf("%02d", $series).'_'.basename($banner_path)))
              @copy($cache_dir.'/banners/'.basename($banner_path),
                   dirname($filename).'/banners/series'.sprintf("%02d", $series).'_'.basename($banner_path));
          }
        }

        // Series banner (save to cache before copying to video folder)
        if (isset($tvdb_banners['BANNER']))
        {
          foreach ($tvdb_banners['BANNER'] as $banner_path)
          {
            // Reset the timeout counter for each image downloaded
            set_time_limit(30);
            if (!file_exists($cache_dir.'/banners/'.basename($banner_path)))
              file_save_albumart( add_site_to_url($banner_path, $bannermirror.'/banners/')
                                , $cache_dir.'/banners/'.basename($banner_path)
                                , '');
            if (!file_exists(dirname($filename).'/banners/banner_'.basename($banner_path)))
              @copy($cache_dir.'/banners/'.basename($banner_path),
                   dirname($filename).'/banners/banner_'.basename($banner_path));
          }
        }

        // Series fanart thumbnails
        if (isset($tvdb_banners['FANART']))
        {
          foreach ($tvdb_banners['FANART'] as $fanart)
          {
            // Reset the timeout counter for each image downloaded
            set_time_limit(30);
            $thumb_cache = $cache_dir.'/fanart/'.basename($fanart['THUMBNAIL']);
            if (!file_exists($thumb_cache))
              file_save_albumart( add_site_to_url($fanart['THUMBNAIL'], $bannermirror.'/banners/'), $thumb_cache, '');

            // Insert information into database
            $data = array( "title"        => $programme
                         , "media_type"   => MEDIA_TYPE_TV
                         , "thumb_cache"  => os_path($thumb_cache)
                         , "original_url" => $bannermirror.'/banners/'.$fanart['ORIGINAL']
                         , "resolution"   => $fanart['RESOLUTION']
                         , "colors"       => $fanart['COLORS'] );
            $file_id = db_value("select file_id from themes where title='".db_escape_str($programme)."' and instr(original_url,'".db_escape_str($fanart['ORIGINAL'])."')>0");
            if ( $file_id )
              db_update_row( "themes", $file_id, $data);
            else
              db_insert_row( "themes", $data);
          }
        }

        // Parse and download the actor images
//        $tvdb_actors = array();
//        parse_xml($actors_cache, 'start_tag_tvdb_actors', 'end_tag_tvdb_actors', 'tag_contents_tvdb_actors');
//
//        // Actor images (save to cache before copying to video folder)
//        foreach ($tvdb_actors as $actor)
//        {
//          scdb_add_tv_actors   ( $id, explode('|', trim($tvdb_series['ACTORS'],'|')) );
//            if (!file_exists($cache_dir.'/banners/'.basename($banner_path)))
//              file_save_albumart( add_site_to_url($banner_path, $bannermirror.'/banners/')
//                                , $cache_dir.'/banners/'.basename($banner_path)
//                                , '');
//            if (!file_exists(dirname($filename).'/banners/series'.sprintf("%02d", $series).'_'.basename($banner_path)))
//              copy($cache_dir.'/banners/'.basename($banner_path),
//                   dirname($filename).'/banners/series'.sprintf("%02d", $series).'_'.basename($banner_path));
//        }

        return true;
      }
      else
      {
        send_to_log(4,"Cannot find details for specified episode at tvdb.");
      }
    }
    else
    {
      send_to_log(4,"Unable to find series for details about ".basename($filename));
    }
  }
  else
  {
    send_to_log(6,'Unable to get mirrors from www.thetvdb.com');
  }

  // Mark the file as attempted to get details, but none available
  $columns = array ( "DETAILS_AVAILABLE" => 'N');
  scdb_set_tv_attribs ($id, $columns);
  return false;
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
  global $seriesids;
  $seriesids = array();

  parse_tvdb_xml('http://www.thetvdb.com/api/GetSeries.php?seriesname='.urlencode(utf8_encode($programme)).'&language=all',
                 'start_tag_tvdb_series', 'end_tag_tvdb_series', 'tag_contents_tvdb_series');

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
function get_mirrors(&$xmlmirror, &$bannermirror)
{
  global $xmlmirrors, $bannermirrors;
  $xmlmirrors    = array();
  $bannermirrors = array();

  parse_tvdb_xml('http://www.thetvdb.com/api/'.TVDB_API_KEY.'/mirrors.xml',
                 'start_tag_tvdb_mirror', 'end_tag_tvdb_mirror', 'tag_contents_tvdb_mirror');

  // Select a mirror at random from those available
  if (count($xmlmirrors)>0)
    $xmlmirror = $xmlmirrors[rand(0, count($xmlmirrors)-1)];
  else
    $xmlmirror = 'http://thetvdb.com';

  if (count($bannermirrors)>0)
    $bannermirror = $bannermirrors[rand(0, count($bannermirrors)-1)];
  else
    $bannermirror = 'http://thetvdb.com';
}

/**
 * Parse the XML file
 *
 * @param string $filename
 */
function parse_tvdb_xml($filename, $start_tag, $end_tag, $tag_contents)
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
        $data = preg_replace("/>\s+/u", ">", $data);
        $data = preg_replace("/\s+</u", "<", $data);
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

function start_tag_tvdb_mirror($parser, $name, $attribs)
{
  global $tag;
  global $tvdb_mirror, $tvdb_seriesid;

  switch ($name)
  {
    // Mirrors
    case 'MIRROR':
      $tvdb_mirror = array();
      break;
    default:
      $tag = $name;
  }
}

function end_tag_tvdb_mirror($parser, $name)
{
  global $tvdb_mirror, $xmlmirrors, $bannermirrors;

  switch ($name)
  {
    // Mirrors
    case 'MIRROR':
      switch ($tvdb['TYPEMASK'])
      {
        case 1:
          $xmlmirrors[]    = $tvdb_mirror['MIRRORPATH'];
          break;
        case 2:
          $bannermirrors[] = $tvdb_mirror['MIRRORPATH'];
          break;
        default:
          $xmlmirrors[]    = $tvdb_mirror['MIRRORPATH'];
          $bannermirrors[] = $tvdb_mirror['MIRRORPATH'];
          break;
      }
      break;
    default:
  }
}

function tag_contents_tvdb_mirror($parser, $data)
{
  global $tag;
  global $tvdb_mirror;

  $data = utf8_decode($data);

  switch ($tag)
  {
    // Mirrors
    case 'MIRRORPATH': { $tvdb_mirror['MIRRORPATH'] .= $data; break; }
    case 'TYPEMASK':   { $tvdb_mirror['TYPEMASK'] .= $data; break; }
  }
}

function start_tag_tvdb_series($parser, $name, $attribs)
{
  global $tag;
  global $tvdb_seriesid;

  switch ($name)
  {
    // Series ID
    case 'SERIES':
      $tvdb_seriesid = array();
      break;
    default:
      $tag = $name;
  }
}

function end_tag_tvdb_series($parser, $name)
{
  global $tvdb_seriesid, $seriesids;

  switch ($name)
  {
    // Series ID
    case 'SERIES':
      $seriesids['SERIESID'][]   = $tvdb_seriesid['SERIESID'];
      $seriesids['SERIESNAME'][] = $tvdb_seriesid['SERIESNAME'];
      break;
    default:
  }
}

function tag_contents_tvdb_series($parser, $data)
{
  global $tag;
  global $tvdb_seriesid;

  $data = utf8_decode($data);

  switch ($tag)
  {
    // Series ID
    case 'SERIESID':   { $tvdb_seriesid['SERIESID'] .= $data; break; }
    case 'SERIESNAME': { $tvdb_seriesid['SERIESNAME'] .= $data; break; }
  }
}

function start_tag_tvdb_episode($parser, $name, $attribs)
{
  global $tag;
  global $tvdb_series, $tvdb_episode, $tvdb_episodes;

  switch ($name)
  {
    // Series
    case 'SERIES':
      $tvdb_episodes = array();
      break;
    // Episode
    case 'EPISODE':
      $tvdb_episode = array();
      break;
    default:
      $tag = $name;
  }
}

function end_tag_tvdb_episode($parser, $name)
{
  global $tvdb_series, $tvdb_episode, $tvdb_episodes;

  switch ($name)
  {
    // Episode
    case 'EPISODE':
      $tvdb_episodes['DIRECTOR'][]      = $tvdb_episode['DIRECTOR'];
      $tvdb_episodes['EPISODENAME'][]   = $tvdb_episode['EPISODENAME'];
      $tvdb_episodes['EPISODENUMBER'][] = $tvdb_episode['EPISODENUMBER'];
      $tvdb_episodes['FIRSTAIRED'][]    = $tvdb_episode['FIRSTAIRED'];
      $tvdb_episodes['GUESTSTARS'][]    = $tvdb_episode['GUESTSTARS'];
      $tvdb_episodes['OVERVIEW'][]      = $tvdb_episode['OVERVIEW'];
      $tvdb_episodes['RATING'][]        = $tvdb_episode['RATING'];
      $tvdb_episodes['SEASONNUMBER'][]  = $tvdb_episode['SEASONNUMBER'];
      $tvdb_episodes['FILENAME'][]      = $tvdb_episode['FILENAME'];

      if (is_numeric($tvdb_series['SC_SERIES']) && is_numeric($tvdb_series['SC_EPISODE']))
      {
        if ($tvdb_episode['SEASONNUMBER'] == $tvdb_series['SC_SERIES'] && $tvdb_episode['EPISODENUMBER'] == $tvdb_series['SC_EPISODE'])
        {
          // Matched series and episode
          $tvdb_series['EPISODEID'] = count($tvdb_episodes['EPISODENAME'])-1;
        }
      }
      break;
    default:
  }
}

function tag_contents_tvdb_episode($parser, $data)
{
  global $tag;
  global $tvdb_series, $tvdb_episode;

  $data = utf8_decode($data);

  switch ($tag)
  {
    // Series
    case 'ACTORS':       { $tvdb_series['ACTORS'] .= $data; break; }
    case 'GENRE':        { $tvdb_series['GENRE'] .= $data; break; }
    // Episode
    case 'DIRECTOR':     { $tvdb_episode['DIRECTOR'] .= $data; break; }
    case 'EPISODENAME':  { $tvdb_episode['EPISODENAME'] .= $data; break; }
    case 'EPISODENUMBER':{ $tvdb_episode['EPISODENUMBER'] .= $data; break; }
    case 'FIRSTAIRED':   { $tvdb_episode['FIRSTAIRED'] .= $data; break; }
    case 'GUESTSTARS':   { $tvdb_episode['GUESTSTARS'] .= $data; break; }
    case 'OVERVIEW':     { $tvdb_episode['OVERVIEW'] .= $data; break; }
    case 'RATING':       { $tvdb_episode['RATING'] .= $data; break; }
    case 'SEASONNUMBER': { $tvdb_episode['SEASONNUMBER'] .= $data; break; }
    case 'FILENAME':     { $tvdb_episode['FILENAME'] .= $data; break; }
  }
}

function start_tag_tvdb_banner($parser, $name, $attribs)
{
  global $tag;
  global $tvdb_banner;

  switch ($name)
  {
    // Banners
    case 'BANNER':
      $tvdb_banner = array();
      break;
    default:
      $tag = $name;
  }
}

function end_tag_tvdb_banner($parser, $name)
{
  global $tvdb_banner, $tvdb_banners;

  switch ($name)
  {
    // Banners
    case 'BANNER':
      if($tvdb_banner['LANGUAGE'] == 'en' || $tvdb_banner['LANGUAGE'] == get_sys_pref('DEFAULT_LANGUAGE','en'))
      {
        switch ($tvdb_banner['BANNERTYPE'])
        {
          case 'fanart':
            $tvdb_banners['FANART'][] = array( 'ID'         => $tvdb_banner['ID']
                                             , 'ORIGINAL'   => $tvdb_banner['BANNERPATH']
                                             , 'VIGNETTE'   => $tvdb_banner['VIGNETTEPATH']
                                             , 'THUMBNAIL'  => $tvdb_banner['THUMBNAILPATH']
                                             , 'RESOLUTION' => $tvdb_banner['BANNERTYPE2']
                                             , 'COLORS'     => $tvdb_banner['COLORS'] );
            break;
          case 'series':
            if($tvdb_banner['BANNERTYPE2'] == 'graphical')
              $tvdb_banners['BANNER'][] = $tvdb_banner['BANNERPATH'];
            break;
          case 'season':
            if ($tvdb_banner['BANNERTYPE2'] == 'season' && is_numeric($tvdb_banner['SEASON']))
              $tvdb_banners[trim($tvdb_banner['SEASON'])][] = $tvdb_banner['BANNERPATH'];
            break;
          default:
        }
      }
      break;
    default:
  }
}

function tag_contents_tvdb_banner($parser, $data)
{
  global $tag;
  global $tvdb_banner;

  $data = utf8_decode($data);

  switch ($tag)
  {
    // Banners
    case 'ID' :         { $tvdb_banner['ID'] .= $data; break; }
    case 'BANNERPATH':  { $tvdb_banner['BANNERPATH'] .= $data; break; }
    case 'BANNERTYPE':  { $tvdb_banner['BANNERTYPE'] .= $data; break; }
    case 'BANNERTYPE2': { $tvdb_banner['BANNERTYPE2'] .= $data; break; }
    case 'LANGUAGE':    { $tvdb_banner['LANGUAGE'] .= $data; break; }
    case 'SEASON':      { $tvdb_banner['SEASON'] .= $data; break; }
    case 'COLORS':      { $tvdb_banner['COLORS'] .= $data; break; }
    case 'THUMBNAILPATH': { $tvdb_banner['THUMBNAILPATH'] .= $data; break; }
    case 'VIGNETTEPATH' : { $tvdb_banner['VIGNETTEPATH'] .= $data; break; }

  }
}

function start_tag_tvdb_actors($parser, $name, $attribs)
{
  global $tag;
  global $tvdb_actor;

  switch ($name)
  {
    // Actors
    case 'ACTOR':
      $tvdb_actor = array();
      break;
    default:
      $tag = $name;
  }
}

function end_tag_tvdb_actors($parser, $name)
{
  global $tvdb_actor, $tvdb_actors;

  switch ($name)
  {
    // Actors
    case 'ACTOR':
      $tvdb_actors[$tvdb_actor['ID']][] = $tvdb_actor;
      break;
    default:
  }
}

function tag_contents_tvdb_actors($parser, $data)
{
  global $tag;
  global $tvdb_actor;

  $data = utf8_decode($data);

  switch ($tag)
  {
    // Actors
    case 'ID':    { $tvdb_actor['ID'] .= $data; break; }
    case 'IMAGE': { $tvdb_actor['IMAGE'] .= $data; break; }
    case 'NAME':  { $tvdb_actor['NAME'] .= $data; break; }
    case 'ROLE':  { $tvdb_actor['ROLE'] .= $data; break; }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
