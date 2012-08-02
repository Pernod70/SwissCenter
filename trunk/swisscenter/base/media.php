<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/sched.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/image.php'));
require_once( realpath(dirname(__FILE__).'/screen.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/users.php'));
require_once( realpath(dirname(__FILE__).'/musicip.php'));
require_once( realpath(dirname(__FILE__).'/../resources/subtitles/opensubtitles.php'));

// Libraries for reading file metadata
require_once( realpath(dirname(__FILE__).'/../ext/getid3/getid3.php'));
require_once( realpath(dirname(__FILE__).'/../ext/exif/exif_reader.php'));

/**
 * Removes orphaned media files and albumart from the database (rows that exist in
 * the database for a media location that is not valid anymore).
 */

function remove_orphaned_records()
{
  @db_sqlcommand('delete from mp3s  '.
                 ' using mp3s  left outer join media_locations  '.
                 '    on media_locations.location_id = mp3s.location_id '.
                 ' where media_locations.location_id is null');

  @db_sqlcommand('delete from movies '.
                 ' using movies left outer join media_locations  '.
                 '    on media_locations.location_id = movies.location_id  '.
                 ' where media_locations.location_id is null');

  @db_sqlcommand('delete from tv '.
                 ' using tv left outer join media_locations  '.
                 '    on media_locations.location_id = tv.location_id  '.
                 ' where media_locations.location_id is null');

  @db_sqlcommand('delete from photos '.
                 ' using photos left outer join media_locations '.
                 '    on media_locations.location_id = photos.location_id '.
                 ' where media_locations.location_id is null');

  @db_sqlcommand('delete from photo_albums '.
                 ' using photo_albums left outer join photos '.
                 '    on photo_albums.dirname = left(photos.dirname,length(photo_albums.dirname)) '.
                 ' where left(photos.dirname,length(photo_albums.dirname)) is null');

  @db_sqlcommand('delete from media_art '.
                 ' using media_art left outer join mp3s  '.
                 '    on media_art.art_sha1 = mp3s.art_sha1 '.
                 ' left outer join movies '.
                 '    on media_art.art_sha1 = movies.art_sha1 '.
                 ' where mp3s.art_sha1 is null and movies.art_sha1 is null');

  @db_sqlcommand('delete from viewings '.
                 ' using viewings left outer join mp3s '.
                 '    on viewings.media_id = mp3s.file_id '.
                 ' where viewings.media_type = '.MEDIA_TYPE_MUSIC.' and mp3s.file_id is null');

  @db_sqlcommand('delete from viewings '.
                 ' using viewings left outer join photos '.
                 '    on viewings.media_id = photos.file_id '.
                 ' where viewings.media_type = '.MEDIA_TYPE_PHOTO.' and photos.file_id is null');

  @db_sqlcommand('delete from viewings '.
                 ' using viewings left outer join movies '.
                 '    on viewings.media_id = movies.file_id '.
                 ' where viewings.media_type = '.MEDIA_TYPE_VIDEO.' and movies.file_id is null');

  @db_sqlcommand('delete from viewings '.
                 ' using viewings left outer join tv '.
                 '    on viewings.media_id = tv.file_id '.
                 ' where viewings.media_type = '.MEDIA_TYPE_TV.' and tv.file_id is null');
}

/**
 * Removes orphaned themes and associated images from the database (themes that exist in
 * the database for media that no longer exists).
 */

function remove_orphaned_themes()
{
  // Remove any redundant themes and associated images
  $themes = db_col_to_list('select t.file_id from themes t left outer join movies '.
                           '    on t.title = movies.title'.
                           ' where t.media_type = '.MEDIA_TYPE_VIDEO.' and movies.file_id is null');
  foreach ($themes as $id)
  {
    $images = db_row("select * from themes where file_id = $id");
    send_to_log(8,'images',$images);
    send_to_log(8, "Deleting theme image: ".$images["THUMB_CACHE"]);
    if ( file_exists($images["THUMB_CACHE"]) ) { unlink($images["THUMB_CACHE"]); }
    send_to_log(8, "Deleting theme image: ".$images["PROCESSED_IMAGE"]);
    if ( file_exists($images["PROCESSED_IMAGE"]) ) { unlink($images["PROCESSED_IMAGE"]); }
    send_to_log(8, "Deleting theme image: ".$images["ORIGINAL_CACHE"]);
    if ( file_exists($images["ORIGINAL_CACHE"]) ) { unlink($images["ORIGINAL_CACHE"]); }
    @db_sqlcommand("delete from themes where file_id = $id");
  }

  $themes = db_col_to_list('select t.file_id from themes t left outer join tv '.
                           '    on t.title = tv.programme'.
                           ' where t.media_type = '.MEDIA_TYPE_TV.' and tv.file_id is null');
  foreach ($themes as $id)
  {
    $images = db_row("select * from themes where file_id = $id");
    send_to_log(8, "Deleting theme image: ".$images["THUMB_CACHE"]);
    if ( file_exists($images["THUMB_CACHE"]) ) { unlink($images["THUMB_CACHE"]); }
    send_to_log(8, "Deleting theme image: ".$images["PROCESSED_IMAGE"]);
    if ( file_exists($images["PROCESSED_IMAGE"]) ) { unlink($images["PROCESSED_IMAGE"]); }
    send_to_log(8, "Deleting theme image: ".$images["ORIGINAL_CACHE"]);
    if ( file_exists($images["ORIGINAL_CACHE"]) ) { unlink($images["ORIGINAL_CACHE"]); }
    @db_sqlcommand("delete from themes where file_id = $id");
  }
}

/**
 * Removes orphaned actors, directors and genres from the movie tables.
 *
 */

function remove_orphaned_movie_info()
{
  @db_sqlcommand('delete from actors_in_movie '.
                 ' using actors_in_movie left outer join movies '.
                 '    on actors_in_movie.movie_id = movies.file_id '.
                 ' where movies.file_id is null');

  @db_sqlcommand('delete from genres_of_movie '.
                 ' using genres_of_movie left outer join movies '.
                 '    on genres_of_movie.movie_id = movies.file_id '.
                 ' where movies.file_id is null');

  @db_sqlcommand('delete from directors_of_movie '.
                 ' using directors_of_movie left outer join movies '.
                 '    on directors_of_movie.movie_id = movies.file_id '.
                 ' where movies.file_id is null');

  @db_sqlcommand('delete from languages_of_movie '.
                 ' using languages_of_movie left outer join movies '.
                 '    on languages_of_movie.movie_id = movies.file_id '.
                 ' where movies.file_id is null');
}

/**
 * Removes orphaned actors, directors and genres from the tv tables.
 *
 */

function remove_orphaned_tv_info()
{
  @db_sqlcommand('delete from actors_in_tv '.
                 ' using actors_in_tv left outer join tv '.
                 '    on actors_in_tv.tv_id = tv.file_id '.
                 ' where tv.file_id is null');

  @db_sqlcommand('delete from genres_of_tv '.
                 ' using genres_of_tv left outer join tv '.
                 '    on genres_of_tv.tv_id = tv.file_id '.
                 ' where tv.file_id is null');

  @db_sqlcommand('delete from directors_of_tv '.
                 ' using directors_of_tv left outer join tv '.
                 '    on directors_of_tv.tv_id = tv.file_id '.
                 ' where tv.file_id is null');

  @db_sqlcommand('delete from languages_of_tv '.
                 ' using languages_of_tv left outer join tv '.
                 '    on languages_of_tv.tv_id = tv.file_id '.
                 ' where tv.file_id is null');
}

/**
 * Eliminate duplicate records (when the version of MySQL is too low to support the
 * unique indexes created).
 *
 */

function eliminate_duplicates()
{
  @db_sqlcommand('   CREATE TEMPORARY TABLE mp3s_del AS    '.
                 '   SELECT max(file_id) file_id           '.
                 '     FROM mp3s                           '.
                 ' GROUP BY dirname,filename               '.
                 '   HAVING count(*)>1');

  @db_sqlcommand('   CREATE TEMPORARY TABLE movies_del AS  '.
                 '   SELECT max(file_id) file_id           '.
                 '     FROM movies                         '.
                 ' GROUP BY dirname,filename               '.
                 '   HAVING count(*)>1');

  @db_sqlcommand('   CREATE TEMPORARY TABLE tv_del AS      '.
                 '   SELECT max(file_id) file_id           '.
                 '     FROM tv                             '.
                 ' GROUP BY dirname,filename               '.
                 '   HAVING count(*)>1');

  @db_sqlcommand('   CREATE TEMPORARY TABLE photos_del AS  '.
                 '   SELECT max(file_id) file_id           '.
                 '     FROM photos                         '.
                 ' GROUP BY dirname,filename               '.
                 '   HAVING count(*)>1');

  @db_sqlcommand('DELETE FROM mp3s   USING mp3s, mp3s_del     WHERE mp3s.file_id = mp3s_del.file_id');
  @db_sqlcommand('DELETE FROM movies USING movies, movies_del WHERE movies.file_id = movies_del.file_id');
  @db_sqlcommand('DELETE FROM tv     USING tv, tv_del         WHERE tv.file_id = tv_del.file_id');
  @db_sqlcommand('DELETE FROM photos USING photos, photos_del WHERE photos.file_id = photos_del.file_id');
}

/**
 * Remove all MEDIA_SCAN settings from SYSTEM_PREFS.
 *
 */

function clear_media_scan_prefs()
{
  db_sqlcommand("delete from system_prefs where name like 'media_scan_%'");
}

/**
 * Returns the database table name for the specified media type.
 *
 * @param integer $media_type
 * @return string
 */

function get_media_table( $media_type )
{
  return db_value("select media_table from media_types where media_id = $media_type");
}

/**
 * Returns the $media_type and $file_id of the piece of media in the database that matches the
 * specified file (including full path).
 *
 * @param string $fsp - File location, including full path
 * @return array($media_type, $file_id)
 */

function find_media_in_db( $fsp)
{
  $media_type = false;
  $file_id = false;

  $types = db_toarray("select media_id, media_table from media_types where media_table is not null");
  foreach ($types as $type)
  {
    $file_id = db_value("select file_id from $type[MEDIA_TABLE] where dirname='".db_escape_str(dirname($fsp).'/')."' and filename='".db_escape_str(basename($fsp))."' limit 1");

    if (! is_null($file_id))
      return array( $type["MEDIA_ID"], $file_id);
  }

  // We didn't find anything
  return array(false, false);
}

/**
 * Returns the full information for the specified piece of media (by path+filename) if it can
 * be found in the database. Otherwise FALSE is returned.
 *
 * @param string $fsp
 * @return array
 */

function find_media_get_full_details( $fsp )
{
  // Search for the item in the database
  list($media_type, $file_id) = find_media_in_db($fsp);

  // Return the full details (or FALSE if the item could not be found)
  if ($media_type === false)
    return false;
  else
    return db_row("select * from ".get_media_table($media_type)." where file_id = $file_id");
}

/**
 * Returns the number of times that the specified media file has been viewed.
 *
 * @param enum $media_type - MEDIA_TYPE_MUSIC | MEDIA_TYPE_PHOTO | MEDIA_TYPE_RADIO | MEDIA_TYPE_VIDEO
 * @param mixed $file - Filename (inc. path) or FILE_ID of the media file
 * @param integer $user [optional] - The user_ID of the user to check the number of viewings for. Defaults to the current user.
 * @return integer
 */

function viewings_count( $media_type, $file, $user = '')
{
  if (empty($user))
    $user = get_current_user_id();

  if ( is_numeric($file) )
  {
    $val = db_value("select total_viewings from viewings
                      where user_id = $user
                         and media_type = $media_type and media_id = $file");
  }
  elseif ( is_string($file))
  {
    $table = get_media_table($media_type);
    if (empty($table))
      $val = false;
    else
      $val = db_value("select total_viewings from viewings, $table
                        where user_id = $user and viewings.media_id = $table.file_id
                        and media_type = $media_type and dirname='".db_escape_str(dirname($file).'/')."' and filename='".db_escape_str(basename($file))."'");
  }
  else
  {
    send_to_log(1,'Incorrect call to the viewings_count() function.');
    $val = FALSE;
  }

  return ($val !== FALSE ? $val : 0);
}

/**
 * Returns the appropriate style identifier for the number of items viewed compared to the total number of items
 *
 * @param integer $viewed
 * @param integer $total
 * @return integer
 */

function viewed_icon( $viewed, $total=1)
{
  if ($viewed == 0)
    return 'IMG_VIEWED_0';
  elseif ($viewed >= $total)
    return 'IMG_VIEWED_4';
  elseif ($viewed/$total <0.34)
    return 'IMG_VIEWED_1';
  elseif ($viewed/$total <0.67)
    return 'IMG_VIEWED_2';
  else
    return 'IMG_VIEWED_3';
}

/**
 * Returns the SQL join which makes it possible to restrict media based on the number of times it has been viewed, or when
 * it was last viewed.
 *
 * @param integer $media_type - Use the defined constants MEDIA_TYPE_xxx
 * @param integer $user_id - The user whose viewings are to be checked (defaults to the current user if not specified)
 *
 * @return string (SQL join)
 */

function viewed_join( $media_type, $user_id = '' )
{
  $user_id   = nvl($user_id, get_current_user_id());
  return " left outer join viewings v on (media.file_id = v.media_id and v.user_id = $user_id and v.media_type = $media_type) ";
}

/**
 * Returns the SQL predicate  which restricts the media returned to those that have been viewed as specified.
 *
 * @param char $operator - The comparison operator to use against the number of viewings (defaults to '=')
 * @param integer $viewings - The number of viewings (or more) to match against (defaults to zero)
 * @returnstring (SQL predicate)
 */

function viewed_n_times_predicate( $operator = '=', $viewings = 0)
{
  return " and IFNULL(v.total_viewings,0) $operator $viewings ";
}

/**
 * Returns the SQL predicate (for the HAVING clause) that restricts the media returned to those with a particular
 * viewing state.
 *
 * @param string $status - Viewing state ("viewed:none", "viewed:notcomplete" , "viewed:started" or "viewed:complete")
 * @return string (SQL predicate)
 */

function viewed_status_predicate( $status )
{
  $calc = "sum(if(v.total_viewings>0,1,0))/greatest(count(*),1)";

  if (strtolower($status) == 'viewed:none')
    return " $calc = 0 ";
  elseif (strtolower($status) == 'viewed:notcomplete')
    return " $calc < 1 ";
  elseif (strtolower($status) == 'viewed:started')
    return " $calc > 0 ";
  elseif (strtolower($status) == 'viewed:complete')
    return " $calc = 1 ";
  else
    return " 1=1 ";
}

/**
 * Increment the downloads counter so that we can track which files are played by which user,
 * and how often. Also store the details on the last file played in the user's preferences.
 *
 * @param integer $media
 * @param integer $file_id
 * @param boolean $viewed
 */

function store_request_details( $media_type, $file_id, $viewed = true )
{
  // Return if no file_id is given
  if ( empty($file_id) ) return;

  // Current user
  $user_id = get_current_user_id();

  if ( $viewed )
  {
    // Increment the downloads counter for this file
    if ( db_value("select count(*) from viewings where user_id=$user_id and media_type=$media_type and media_id=$file_id") == 0)
    {
      db_sqlcommand("insert into viewings ( user_id, media_type, media_id, last_viewed, total_viewings )
                     values ( $user_id, $media_type, $file_id, now(), 1) ");
    }
    else
    {
      db_sqlcommand("update viewings set total_viewings = total_viewings+1 , last_viewed = now()
                     where user_id=$user_id and media_type=$media_type and media_id=$file_id");
    }
  }
  else
  {
    // Remove downloads counter for this file
    db_sqlcommand("delete from viewings where user_id=$user_id and media_type=$media_type and media_id=$file_id");
  }
}

//-------------------------------------------------------------------------------------------------
// The following functions all relate explicitly to searching for new media.
//-------------------------------------------------------------------------------------------------

/**
 * Causes an immediate refresh of the media database
 *
 */

function media_refresh_now()
{
  if (is_server_simese() && version_compare(simese_version(), '1.31', '>='))
  {
    $dir = SC_LOCATION.'config/simese';

    if ( !file_exists($dir) )
      mkdir($dir);

    if (isdir($dir))
      write_binary_file($dir.'/Simese.ini',"MediaRefresh=Now");
    else
      send_to_log(2,'Unable to create Simese.ini to initiate media search', $dir);
  }
  else
    run_background('media_search.php');
}

/**
 * Takes the specified file and checks the format to determine if it should be added to the
 * appropriate media table in the database.
 *
 * @param directory $dir
 * @param integer $id - Media Location ID
 * @param filename $file
 */

function process_mp3( $dir, $id, $file)
{
  send_to_log(4,'Found MP3    : '.$file);
  $filepath = os_path($dir.$file);
  $data     = array();
  $getID3   = new getID3;
  $getID3->setOption(array('encoding' => "ISO-8859-1", 'option_tags_html' => false));
  $id3      = $getID3->analyze($filepath);

  // Log warnings generated by GetID3 library
  if ( isset($id3['warning']) )
  {
    send_to_log(2,'Warnings occurred whilst reading ID3 tag information');
    foreach ($id3['warning'] as $err)
      send_to_log(2,' - '.$err);
  }

  // Standard information about the file
  $data['dirname']      = $dir;
  $data['filename']     = $file;
  $data['location_id']  = $id;
  $data['title']        = file_noext($file);
  $data['size']         = filesize($dir.$file);
  $data['verified']     = 'Y';
  $data['discovered']   = db_datestr();
  $data['timestamp']    = db_datestr(filemtime($filepath));

  if ( ! isset($id3['error']) )
  {
    if (in_array( $id3['fileformat'], media_exts_with_GetID3_support() ))
    {
      // ID3 data successfully obtained, so enter it into the database
      $data['size']         = $id3['filesize'];
      $data['length']       = floor($id3['playtime_seconds']);
      $data['lengthstring'] = $id3['playtime_string'];
      $data['bitrate']      = floor($id3['bitrate']);
      $data['bitrate_mode'] = strtoupper($id3['audio']['bitrate_mode']);
      $data['version']      = 0;
      $data['title']        = null;
      $data['artist']       = null;
      $data['album']        = null;
      $data['genre']        = null;
      $data['year']         = null;
      $data['track']        = null;
      $data['disc']         = null;
      $data['band']         = null;
      $data['composer']     = null;
      $data['publisher']    = null;
      $image                = array();

      // Set specific tags for each file format
      if ( isset($id3['tags']['cue']) )
      {
        set_var( $data['artist'], $id3['cue']['performer'] );
        set_var( $data['album'],  $id3['cue']['title'] );
        set_var( $data['genre'],  array_last($id3['cue']['comments']['genre']) );
        set_var( $data['year'],   array_last($id3['cue']['comments']['date']) );
      }

      if ( isset($id3['tags']['id3v1']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['id3v1']['title']) );
        set_var( $data['artist'], array_last($id3['tags']['id3v1']['artist']) );
        set_var( $data['album'],  array_last($id3['tags']['id3v1']['album']) );
        set_var( $data['genre'],  array_last($id3['tags']['id3v1']['genre']) );
        set_var( $data['year'],   array_last($id3['tags']['id3v1']['year']) );
        set_var( $data['track'],  ltrim(array_last($id3['tags']['id3v1']['track']),'0') );
      }

      if ( isset($id3['tags']['vorbiscomment']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['vorbiscomment']['title']) );
        set_var( $data['artist'], array_last($id3['tags']['vorbiscomment']['artist']) );
        set_var( $data['album'],  array_last($id3['tags']['vorbiscomment']['album']) );
        set_var( $data['genre'],  array_last($id3['tags']['vorbiscomment']['genre']) );
        set_var( $data['year'],   array_last($id3['tags']['vorbiscomment']['date']) );
        set_var( $data['band'],   array_last($id3['tags']['vorbiscomment']['band']) );
        set_var( $data['band'],   array_last($id3['tags']['vorbiscomment']['album artist']) );
        set_var( $data['composer'], array_last($id3['tags']['vorbiscomment']['composer']) );
        set_var( $data['track'],  ltrim(array_last($id3['tags']['vorbiscomment']['tracknumber']),'0') );
        set_var( $data['disc'],   ltrim(array_last($id3['tags']['vorbiscomment']['discnumber']),'0') );
        set_var( $image,          array_last($id3['comments']['picture']) );
      }

      if ( isset($id3['flac']['PICTURE']) )
      {
        $image = array_last($id3['flac']['PICTURE']);
        $image = array('data'=>$image['data'], 'image_mime'=>$image['image_mime']);
      }

      if ( isset($id3['tags']['ape']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['ape']['title']) );
        set_var( $data['artist'], array_last($id3['tags']['ape']['artist']) );
        set_var( $data['album'],  array_last($id3['tags']['ape']['album']) );
        set_var( $data['genre'],  array_last($id3['tags']['ape']['genre']) );
        set_var( $data['year'],   array_last($id3['tags']['ape']['year']) );
        set_var( $data['composer'], array_last($id3['tags']['ape']['composer']) );
        set_var( $data['track'],  ltrim(array_last($id3['tags']['ape']['track']),'0') );
        set_var( $data['disc'],   ltrim(array_last($id3['tags']['ape']['disc']),'0') );
        set_var( $image,          array_last($id3['comments']['picture']) );
      }

      if ( isset($id3['tags']['quicktime']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['quicktime']['title']) );
        set_var( $data['artist'], array_last($id3['tags']['quicktime']['artist']) );
        set_var( $data['album'],  array_last($id3['tags']['quicktime']['album']) );
        set_var( $data['genre'],  array_last($id3['tags']['quicktime']['genre']) );
        set_var( $data['year'],   substr(array_last($id3['tags']['quicktime']['creation_date']),0,4) );
        set_var( $data['band'],   array_last($id3['tags']['quicktime']['album_artist']) );
        set_var( $data['composer'], array_last($id3['tags']['quicktime']['composer']) );
        set_var( $data['track'],  ltrim(array_last($id3['tags']['quicktime']['track_number']),'0') );
        set_var( $data['disc'],   ltrim(array_last($id3['tags']['quicktime']['disc_number']),'0') );
        set_var( $image,          array_last($id3['comments']['picture']) );
      }

      if ( isset($id3['tags']['asf']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['asf']['title']) );
        set_var( $data['artist'], array_last($id3['tags']['asf']['artist']) );
        set_var( $data['album'],  array_last($id3['tags']['asf']['album']) );
        set_var( $data['genre'],  array_last($id3['tags']['asf']['genre']) );
        set_var( $data['year'],   array_last($id3['tags']['asf']['year']) );
        set_var( $data['band'],   array_last($id3['tags']['asf']['albumartist']) );
        set_var( $data['track'],  ltrim(array_last($id3['tags']['asf']['track']),'0') );
        set_var( $data['disc'],   ltrim(array_last($id3['tags']['asf']['partofset']),'0') );
        set_var( $image,          array_last($id3['comments']['picture']) );
      }

      if ( isset($id3['tags']['id3v2']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['id3v2']['title']) );
        set_var( $data['artist'], array_last($id3['tags']['id3v2']['artist']) );
        set_var( $data['album'],  array_last($id3['tags']['id3v2']['album']) );
        set_var( $data['genre'],  array_last($id3['tags']['id3v2']['genre']) );
        set_var( $data['year'],   array_last($id3['tags']['id3v2']['year']) );
        set_var( $data['band'],   array_last($id3['tags']['id3v2']['band']) );
        set_var( $data['mood'],   array_last($id3['tags']['id3v2']['mood']) );
        set_var( $data['composer'], array_last($id3['tags']['id3v2']['composer']) );
        set_var( $data['publisher'], array_last($id3['tags']['id3v2']['publisher']) );
        set_var( $data['involved_people_list'], array_last($id3['tags']['id3v2']['involved_people_list']) );
        set_var( $data['track'],  ltrim(array_last($id3['tags']['id3v2']['track_number']),'0') );
        set_var( $data['disc'],   ltrim(array_last($id3['tags']['id3v2']['part_of_a_set']),'0') );
        set_var( $image,          array_last($id3['comments']['picture']) );
      }

      // Remove track and disc totals, ie. 3/10 becomes 3
      if (strstr($data['track'], '/')) list($data['track'], $dummy) = explode('/', $data['track']);
      if (strstr($data['disc'], '/'))  list($data['disc'], $dummy) = explode('/', $data['disc']);

      if (get_sys_pref('USE_ID3_ART','YES') == 'YES' && !empty($image))
      {
        send_to_log(4,'Image found within ID3 tag - will use as album art');
        $data['art_sha1'] = sha1($image['data']);
        // Store media art if it doesn't already exist
        if ( !db_value("select art_sha1 from media_art where art_sha1='".$data['art_sha1']."'") )
          db_insert_row('media_art',array('art_sha1'=>$data['art_sha1'], 'image'=>$image['data'] ));
        elseif ( db_value("select sha1(image) from media_art where art_sha1='".$data['art_sha1']."'") !== $data['art_sha1'] )
          db_sqlcommand("update media_art set image='".db_escape_str($image['data'])."' where art_sha1='".$data['art_sha1']."'");
      }
      else
        $data['art_sha1'] = null;
    }
    else
    {
      // File extension is MP3, but the file itself isn't!
      send_to_log(3,'This filetype ('.$id3['fileformat'].') is not supported by the GetID3 library, so although it will be added there will not be any supplemental information available.');
    }
  }
  else
  {
    // File is an MP3, but there were (critical) problems reading the ID3 tag info
    // or the file itself is not an MP3
    send_to_log(2,'Errors occurred whilst reading ID3 tag information');
    foreach ($id3['error'] as $err)
      send_to_log(2,' - '.$err);
  }

  $file_id = db_value("select file_id from mp3s where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
  if ( $file_id )
  {
    // Update the existing record
    send_to_log(5,'Updating MP3  : '.$file);
    unset($data['discovered']);
    $success = db_update_row( 'mp3s', $file_id, $data);
  }
  else
  {
    // Insert the row into the database
    send_to_log(5,'Adding MP3    : '.$file);
    $success = db_insert_row( 'mp3s', $data);
  }

  if ( !$success )
    send_to_log(2,'Unable to add/update MP3 to the database');
}

/**
 * Creates a new photo album within the database for this directory.
 *
 * @param directory $dir
 * @param integer $id - Media Location ID
 */

function add_photo_album( $dir, $id )
{
  $media_loc = db_value("select name from media_locations where location_id=$id");
  $title     = str_replace('/', ':', trim(substr($dir, strlen($media_loc)+1), '/'));
  $row       = array('dirname'      => $dir
                    ,'title'        => $title
                    ,'verified'     => 'Y'
                    ,'discovered'   => db_datestr()
                    ,'timestamp'    => db_datestr(filemtime($dir))
                    ,'location_id'  => $id
                    );

  send_to_log(6,'Adding photo album "'.$title.'"');

  $file_id = db_value("select file_id from photo_albums where dirname='".db_escape_str($dir)."'");
  if ( $file_id )
  {
    if ( db_update_row( 'photo_albums', $file_id, $row) === false )
      send_to_log(1,'Unable to update photo album to the database');
  }
  else
  {
    if ( db_insert_row( 'photo_albums', $row) === false )
      send_to_log(1,'Unable to add photo album to the database');
  }
}

/**
 * Takes the specified file and checks the format to determine if it should be added to the
 * appropriate media table in the database.
 *
 * @param directory $dir
 * @param integer $id - Media Location ID
 * @param filename $file
 */

function process_photo( $dir, $id, $file)
{
  global $cache_dir;

  send_to_log(4,'Found Photo    : '.$file);
  $filepath = os_path($dir.$file);
  $data     = array();
  $iptcxmp  = array();

  // Get ID3 data (file format)
  $getID3   = new getID3;
  $id3      = $getID3->analyze($filepath);

  // Log warnings generated by GetID3 library
  if ( isset($id3['warning']) )
  {
    send_to_log(2,'Warnings occurred whilst reading ID3 tag information');
    foreach ($id3['warning'] as $err)
      send_to_log(2,' - '.$err);
  }

  // Standard information about the file
  $data['dirname']      = $dir;
  $data['filename']     = $file;
  $data['location_id']  = $id;
  $data['size']         = filesize($dir.$file);
  $data['verified']     = 'Y';
  $data['discovered']   = db_datestr();
  $data['timestamp']    = db_datestr(filemtime($filepath));

  // Get EXIF data
  $exif = exif($dir.$file);
  if ($exif['Make'] != "")
    send_to_log(5,'Found EXIF data : Yes');
  else
    send_to_log(5,'Found EXIF data : No');

  if ( ! isset($id3['error']) )
  {
    if (in_array( $id3['fileformat'], media_exts_with_GetID3_support() ))
    {
      // Get IPTC (IIM legacy) data
      if (isset($id3["iptc"]))
      {
        send_to_log(5,'Found IPTC data : Yes');
        set_var($iptcxmp['byline'],         implode(',',$id3['iptc']['IPTCApplication']['By-line']));
        set_var($iptcxmp['caption'],        implode(',',$id3['iptc']['IPTCApplication']['Caption-Abstract']));
        set_var($iptcxmp['keywords'],       implode(',',$id3['iptc']['IPTCApplication']['Keywords']));
        set_var($iptcxmp['city'],           implode(',',$id3['iptc']['IPTCApplication']['City']));
        set_var($iptcxmp['country'],        implode(',',$id3['iptc']['IPTCApplication']['Country-PrimaryLocationName']));
        set_var($iptcxmp['province_state'], implode(',',$id3['iptc']['IPTCApplication']['Province-State']));
        set_var($iptcxmp['suppcategories'], implode(',',$id3['iptc']['IPTCApplication']['SupplementalCategories']));
        set_var($iptcxmp['date_created'],   implode(',',$id3['iptc']['IPTCApplication']['DateCreated']));
        set_var($iptcxmp['location'],       implode(',',$id3['iptc']['IPTCApplication']['Sub-location']));
      }
      else
        send_to_log(5,'Found IPTC data : No');

      // Get XMP data
      if (isset($id3['xmp']))
      {
        send_to_log(5,'Found XMP data : Yes');
        set_var($iptcxmp['byline'],         implode(',',$id3['xmp']['dc']['creator']));
        set_var($iptcxmp['caption'],        implode(',',$id3['xmp']['dc']['description']));
        set_var($iptcxmp['keywords'],       implode(',',$id3['xmp']['dc']['subject']));
        set_var($iptcxmp['city'],           implode(',',$id3['xmp']['photoshop']['City']));
        set_var($iptcxmp['country'],        implode(',',$id3['xmp']['photoshop']['Country']));
        set_var($iptcxmp['province_state'], implode(',',$id3['xmp']['photoshop']['State']));
        set_var($iptcxmp['suppcategories'], implode(',',$id3['xmp']['photoshop']['SupplementalCategories']));
        set_var($iptcxmp['date_created'],   implode(',',$id3['xmp']['photoshop']['DateCreated']));
        set_var($iptcxmp['location'],       implode(',',$id3['xmp']['Iptc4xmpCore']['Location']));
        if (is_numeric($id3['xmp']['xap']['Rating']))
          $iptcxmp['rating'] = str_repeat('*',$id3['xmp']['xap']['Rating']);
        elseif (is_numeric($id3['xmp']['xmp']['Rating']))
          $iptcxmp['rating'] = str_repeat('*',$id3['xmp']['xmp']['Rating']);
        else
          $iptcxmp['rating'] = str('NOT_RATED');
      }
      else
      {
        $iptcxmp['rating'] = str('NOT_RATED');
        send_to_log(5,'Found XMP data : No');
      }

      // File Info successfully obtained, so enter it into the database
      $data['size']                = $id3['filesize'];
      $data['width']               = $id3['video']['resolution_x'];
      $data['height']              = $id3['video']['resolution_y'];
      $data['date_modified']       = filemtime($filepath);
      $data['date_created']        = $exif['DTDigitised'];
      $data['exif_exposure_mode']  = $exif['ExposureMode'];
      $data['exif_exposure_time']  = dec2frac($exif['ExposureTime']);
      $data['exif_fnumber']        = rtrim($exif['FNumber'],'0');
      $data['exif_focal_length']   = (empty($exif['FocalLength']) ? null : $exif['FocalLength'].str('LENGTH_MM') );
      $data['exif_image_source']   = $exif['ImageSource'];
      $data['exif_make']           = $exif['Make'];
      $data['exif_model']          = $exif['Model'];
      $data['exif_orientation']    = $exif['Orientation'];
      $data['exif_white_balance']  = $exif['WhiteBalance'];
      $data['exif_flash']          = $exif['Flash'][1];
      $data['exif_iso']            = $exif['ISOSpeedRating'];
      $data['exif_light_source']   = $exif['LightSource'];
      $data['exif_exposure_prog']  = $exif['ExpProg'];
      $data['exif_meter_mode']     = $exif['MeterMode'];
      $data['exif_capture_type']   = $exif['SceneCaptureType'];
      $data['iptc_caption']        = $iptcxmp['caption'];
      $data['iptc_suppcategory']   = $iptcxmp['suppcategories'];
      $data['iptc_keywords']       = $iptcxmp['keywords'];
      $data['iptc_city']           = $iptcxmp['city'];
      $data['iptc_province_state'] = $iptcxmp['province_state'];
      $data['iptc_country']        = $iptcxmp['country'];
      $data['iptc_byline']         = $iptcxmp['byline'];
      $data['iptc_date_created']   = $iptcxmp['date_created'];
      $data['iptc_location']       = $iptcxmp['location'];
      $data['xmp_rating']          = $iptcxmp['rating'];
    }
    else
    {
      // File extension is OK, but the file itself isn't!
      send_to_log(3,'GETID3 claims this is not a valid photo file: '.$id3['fileformat']);
    }
  }
  else
  {
    // File is a photo, but there were problems reading the info
    send_to_log(2,'Errors occurred whilst reading photo information');
    foreach ($id3['error'] as $err)
      send_to_log(2,' - '.$err);
  }

  $file_id = db_value("select file_id from photos where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
  if ( $file_id )
  {
    // Update the existing record
    send_to_log(5,'Updating Photo : '.$file);
    unset($data['discovered']);
    $success = db_update_row( 'photos', $file_id, $data);
  }
  else
  {
    // Insert the row into the database
    send_to_log(5,'Adding Photo   : '.$file);
    $success = db_insert_row( 'photos', $data);
  }

  if ( $success )
  {
    // Pre-cache the image thumbnail if the user has selected that option.
    $browsers = db_toarray("select distinct browser_x_res, browser_y_res from clients where ip_address != '127.0.0.1'");
    if ($cache_dir != '' && get_sys_pref('CACHE_PRECACHE_IMAGES','NO') == 'YES' && count($browsers)>0 )
    {
      send_to_log(6,'Pre-caching thumbnail');
      foreach ($browsers as $row)
      {
        if ( !empty($row['BROWSER_X_RES']) && !empty($row['BROWSER_Y_RES']) )
        {
          $_SESSION['device']['browser_x_res']=$row['BROWSER_X_RES'];
          $_SESSION['device']['browser_y_res']=$row['"BROWSER_Y_RES'];
          send_to_log(6,'- for browser size '.$row['BROWSER_X_RES'].'x'.$row['BROWSER_Y_RES']);
          precache($dir.$file, convert_x(THUMBNAIL_X_SIZE), convert_y(THUMBNAIL_Y_SIZE) );
        }
      }
    }
  }
  else
  {
    send_to_log(2,'Unable to add/update photo to the database');
  }
}

/**
 * Given the file path to a VOB file, this function will attempt to determine the correct
 * title to store in the database. This is to account for files ripped directly from DVD
 * and still in the same format (eg: filenames such as "VTS_01_1.vob").
 *
 * @param string $fsp
 */

function determine_dvd_name( $fsp )
{
  // Only attempt to determine the DVD title if this is a IFO or VOB file
  if ( in_array(file_ext($fsp), array('ifo','vob')) )
  {
    // Process files that match the pattern "VTS_nn_n.vob"
    if ( file_ext($fsp)=='ifo' || preg_match('/vts_[0-9]*_[0-9]*.vob/i', basename($fsp) ) > 0 )
    {
      $fsp = dirname($fsp);
      if ( strtolower(basename($fsp)) == 'video_ts' )
        $fsp = dirname($fsp);
    }
  }

  // Only attempt to determine the BluRay title if this is a M2TS file
  if ( file_ext($fsp) == 'm2ts' )
  {
    // Check for BluRay folder structure
    if ( stripos(dirname($fsp), 'BDMV/STREAM') > 0 )
    {
      $fsp = str_ireplace('BDMV/STREAM/', '', $fsp);
      $fsp = dirname($fsp);
    }
  }

  return strip_title( basename($fsp) );
}

/**
 * Takes the specified file and checks the format to determine if it should be added to the
 * appropriate media table in the database.
 *
 * @param directory $dir
 * @param integer $id - Media Location ID
 * @param filename $file
 */

function process_movie( $dir, $id, $file )
{
  send_to_log(4,'Found Video    : '.$file);
  $data     = array();
  $getID3   = new getID3;
  $getID3->setOption(array('encoding' => "ISO-8859-1", 'option_tags_html' => false));
  $filepath = os_path($dir.$file);
  $id3      = $getID3->analyze($filepath);
  $os_hash  = OpenSubtitlesHash($filepath);
  $imdb_id  = preg_get('/tt(\d+)/', $file);
  $image    = array();

  // Log warnings generated by GetID3 library
  if ( isset($id3['warning']) )
  {
    send_to_log(2,'Warnings occurred whilst reading ID3 tag information');
    foreach ($id3['warning'] as $err)
      send_to_log(2,' - '.$err);
  }

  // Standard information about the file
  $data['dirname']      = $dir;
  $data['filename']     = $file;
  $data['location_id']  = $id;
  $data['size']         = filesize($dir.$file);
  $data['verified']     = 'Y';
  $data['discovered']   = db_datestr();
  $data['timestamp']    = db_datestr(filemtime($filepath));
  $data['art_sha1']     = null;
  $data['os_hash']      = (empty($os_hash) ? null : $os_hash);
  $data['imdb_id']      = (empty($imdb_id) ? null : $imdb_id);

  if ( ! isset($id3['error']) )
  {
    if ( in_array(strtolower($id3['fileformat']), media_exts_with_GetID3_support()))
    {
      // Tag data successfully obtained, so record the following information
      getid3_lib::CopyTagsToComments($id3);
      $data['size']          = $id3['filesize'];
      $data['length']        = $id3['playtime_seconds'];
      $data['lengthstring']  = $id3['playtime_string'];
      $data['audio_channels']= $id3['audio']['channels'];
      $data['audio_codec']   = isset($id3['audio']['codec']) ? $id3['audio']['codec'] : $id3['audio']['dataformat'];
      $data['video_codec']   = isset($id3['video']['codec']) ? $id3['video']['codec'] : $id3['video']['dataformat'];
      $data['resolution']    = $id3['video']['resolution_x'].'x'.$id3['video']['resolution_y'];
      $data['frame_rate']    = $id3['video']['frame_rate'];

      if ( isset($id3['tags']['quicktime']) )
      {
        set_var( $data['title'],  array_last($id3['tags']['quicktime']['title']) );
//        set_var( $data['artist'], array_last($id3['tags']['quicktime']['artist']) );
//        set_var( $data['genre'],  array_last($id3['tags']['quicktime']['genre']) );
        set_var( $data['year'],   substr(array_last($id3['tags']['quicktime']['creation_date']),0,4) );
        set_var( $image,          array_last($id3['comments']['picture']) );
      }

      // Get metadata from asf files
      if ( strtolower($id3['fileformat']) == 'asf' )
      {
        if (file_ext($file) == 'dvr-ms') // created by Windows Media Center
        {
          // Synopsis including Subtitle
          $data['synopsis'] = empty($id3['comments']['subtitle']) ? '' : '"'.array_last($id3['comments']['subtitle']).'" - ';
          $data['synopsis'] = $data['synopsis'].array_last($id3['comments']['subtitledescription']);
          if (substr(array_last($id3['comments']['originalreleasetime']),0,4) != '0001')
            $data['year']   = substr(array_last($id3['comments']['originalreleasetime']),0,4);
          else
            $data['year']   = substr(array_last($id3['comments']['mediaoriginalbroadcastdatetime']),0,4);
          $data['details_available'] = 'Y';
          set_var( $image, array_last($id3['comments']['picture']) );
        }
        else
        {
          // TODO: Other asf formats should be added here depending on what metadata they contain
          send_to_log(8,'Found ID3:',$id3);
        }
      }

      if (get_sys_pref('USE_ID3_ART','YES') == 'YES' && !empty($image))
      {
        send_to_log(4,'Image found within ID3 tag - will use as video art');
        $data['art_sha1'] = sha1($image['data']);
      }
      else
        $data['art_sha1'] = null;
    }
    else
    {
      send_to_log(3,"GETID3 claims this is not a valid video: ".$id3['fileformat']);
    }
  }
  else
  {
    // File is a valid movie format, but there were (critical) problems reading the tag info.
    send_to_log(2,"GETID3 claims there are errors in the video file");
    foreach ($id3['error'] as $err)
      send_to_log(2,' - '.$err);
  }

  // Store video snapshot in the database
  if ( !empty($image) )
  {
    $data['art_sha1'] = sha1($image['data']);
    // Store media art if it doesn't already exist
    if ( !db_value("select art_sha1 from media_art where art_sha1='".$data['art_sha1']."'") )
      db_insert_row('media_art',array("art_sha1"=>$data['art_sha1'], "image"=>$image['data'] ));
    elseif ( db_value("select sha1(image) from media_art where art_sha1='".$data['art_sha1']."'") !== $data['art_sha1'] )
      db_sqlcommand("update media_art set image='".db_escape_str($image['data'])."' where art_sha1='".$data['art_sha1']."'");
  }

  $file_id = db_value("select file_id from movies where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
  if ( $file_id )
  {
    // Update the existing record
    send_to_log(5,'Updating Video : '.$file);
    unset($data['discovered']);
    $success = db_update_row( 'movies', $file_id, $data);
  }
  else
  {
    // Only set the title for a new record (an existing title may have been edited)
    if ( empty($data['title']) )
      $data['title'] = determine_dvd_name( $dir.$file );

    // Insert the row into the database
    send_to_log(5,'Adding Video   : '.$file);
    $success = db_insert_row( 'movies', $data);
  }

  if ( $success )
  {
    // Add additional info requiring file_id
    $file_id = db_value("select file_id from movies where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
    if (file_ext($file) == 'dvr-ms')
    {
      $mediacredits = explode(';', $id3['comments']['mediacredits'][0]);
      scdb_add_actors   ($file_id, explode('/', $mediacredits[0]));
      scdb_add_directors($file_id, explode('/', $mediacredits[1]));
      scdb_add_genres   ($file_id, explode(',', $id3['comments']['genre'][0]));
    }

    // DVD Video details are stored in the parent folder
    if ( strtoupper($file) == 'VIDEO_TS.IFO' )
      $filename = rtrim($dir,'/').'.xml';
    else
      $filename = substr($dir.$file,0,strrpos($dir.$file,'.')).'.xml';

    // Check for an accompanying XML file containing details
    if ( file_exists($filename) )
    {
      send_to_log(5,'Importing video details: '.$filename);
      import_movie_from_xml($file_id, $filename);
    }
  }
  else
    send_to_log(1,'Unable to add/update movie to the database');
}

/**
 * Expands the pattern entered by the user (which contains placeholders for the programme,
 * series, episode and title) into a full regular expression.
 *
 * @param string $pattern - User entered pattern containing placeholders.
 * @return string
 */

function tv_expand_pattern( $pattern )
{
  $eparts = array  ( '{p}' => '([^/]+)'
                   , '{s}' => '([0-9]+)'
                   , '{e}' => '([0-9&-]+)'
                   , '{t}' => '([^/]+)'
                   );

  foreach ($eparts as $key => $val)
    $pattern = str_replace($key,$val,$pattern);

  return '`'.$pattern.'`i';
}

/**
 * Given a pattern and the position of a placeholder within the pattern, this function will
 * return the database field into which the metadata should be inserted.
 *
 * @param string $pattern - User entered pattern containing placeholders.
 * @param integer $position - The placeholder position (starting at 1).
 * @return string
 */

function tv_pattern_field( $pattern, $position )
{
  preg_match_all('`\{.\}`',$pattern, $matches);
  switch ( strtolower($matches[0][$position-1]) )
  {
    case '{p}': return 'programme';
    case '{s}': return 'series';
    case '{e}': return 'episode';
    case '{t}': return 'title';
    default: return false;
  }
}

/**
 * Given the path and filename of a media file (minus the extension) this function
 * will return an array of database fields
 *
 * @param string $fsp - path (relative to the media location) and filename, minus the extension
 * @return array
 */

function get_tvseries_info( $fsp )
{
  $details = array();
  $exprs   = db_toarray("select pos,expression from tv_expressions order by pos");
  send_to_log(8,"Expressions to test for path '$fsp'",$exprs);

  // Convert periods to spaces?
  if ( get_sys_pref('TVSERIES_CONVERT_DOTS_TO_SPACES','NO') == 'YES' )
    $fsp = str_replace('.',' ',$fsp);

  // Try all the patterns, stopping as soon as a successful match is made.
  foreach ($exprs as $pattern)
  {
    $regexp = tv_expand_pattern($pattern["EXPRESSION"]);
    if ( preg_match_all($regexp, $fsp, $matches) >= 1)
    {
      $details["rule"] = $pattern["POS"];
      for ( $pos=1; $pos < count($matches); $pos++ )
      {
        $field = tv_pattern_field($pattern["EXPRESSION"],$pos);
        if ($field !== false)
          $details[$field] = $matches[$pos][0];
      }

      break;
    }
  }

  // Trim off any unwanted characters (spaces, '-'s) from the programme and episode titles
  $details['programme'] = trim(trim($details['programme'],'-'));
  $details['title']     = trim(trim($details['title'],'-'));

  send_to_log(8,'Metadata search results',$details);
  return $details;
}

/**
 * Takes the specified file and checks the format to determine if it should be added to the
 * appropriate media table in the database.
 *
 * @param directory $dir
 * @param integer $id - Media Location ID
 * @param filename $file
 */

function process_tv( $dir, $id, $file)
{
  send_to_log(4,'Found TV episode : '.$file);
  $data     = array();
  $getID3   = new getID3;
  $getID3->setOption(array('encoding' => "ISO-8859-1", 'option_tags_html' => false));
  $filepath = os_path($dir.$file);
  $id3      = $getID3->analyze($filepath);
  $os_hash  = OpenSubtitlesHash($filepath);
  $imdb_id  = preg_get('/tt(\d+)/', $file);

  // Log warnings generated by GetID3 library
  if ( isset($id3['warning']) )
  {
    send_to_log(2,'Warnings occurred whilst reading ID3 tag information');
    foreach ($id3['warning'] as $err)
      send_to_log(2,' - '.$err);
  }

  // Standard information about the file
  $data['dirname']      = $dir;
  $data['filename']     = $file;
  $data['title']        = $dir.$file;
  $data['location_id']  = $id;
  $data['size']         = filesize($dir.$file);
  $data['verified']     = 'Y';
  $data['discovered']   = db_datestr();
  $data['timestamp']    = db_datestr(filemtime($filepath));
  $data['os_hash']      = (empty($os_hash) ? null : $os_hash);
  $data['imdb_id']      = (empty($imdb_id) ? null : $imdb_id);

  // Determine the part of the path to process for metadata about the episode.
  $media_loc_dir = db_value("select name from media_locations where location_id=$id");
  $meta_fsp = substr($dir,strlen($media_loc_dir)+1).file_noext($file);

  $data = array_merge($data, get_tvseries_info($meta_fsp) );
  unset($data['rule']);

  if ( ! isset($id3['error']) )
  {
    if ( in_array(strtolower($id3['fileformat']), media_exts_with_GetID3_support() ))
    {
      // Tag data successfully obtained, so record the following information
      getid3_lib::CopyTagsToComments($id3);
      $data['size']          = $id3['filesize'];
      $data['length']        = $id3['playtime_seconds'];
      $data['lengthstring']  = $id3['playtime_string'];
      $data['audio_channels']= $id3['audio']['channels'];
      $data['audio_codec']   = isset($id3['audio']['codec']) ? $id3['audio']['codec'] : $id3['audio']['dataformat'];
      $data['video_codec']   = isset($id3['video']['codec']) ? $id3['video']['codec'] : $id3['video']['dataformat'];
      $data['resolution']    = $id3['video']['resolution_x'].'x'.$id3['video']['resolution_y'];
      $data['frame_rate']    = $id3['video']['frame_rate'];
    }
    else
    {
      send_to_log(3,"GETID3 claims this is not a valid movie (format is '".$id3['fileformat']."')");
    }
  }
  else
  {
    // File is a valid movie format, but there were (critical) problems reading the tag info.
    send_to_log(2,"GETID3 claims there are errors in the video file");
    foreach ($id3['error'] as $err)
      send_to_log(2,' - '.$err);
  }

  $file_id = db_value("select file_id from tv where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
  if ( $file_id )
  {
    // Update the existing record
    send_to_log(5,'Updating TV episode  : '.$file);
    unset($data['discovered']);
    $success = db_update_row( 'tv', $file_id, $data);
  }
  else
  {
    // Insert the row into the database
    send_to_log(5,'Adding TV episode    : '.$file);
    $success = db_insert_row( 'tv', $data);
  }

  if ( !$success )
    send_to_log(1,'Unable to add/update TV episode to the database');
  else
  {
    // Check for an accompanying XML file containing details
    $filename = substr($dir.$file,0,strrpos($dir.$file,'.')).'.xml';
    if ( file_exists($filename) )
    {
      if ( !$file_id )
        $file_id = db_value("select file_id from tv where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
      send_to_log(5,'Importing TV episode details');
      import_tv_from_xml($file_id, $filename);
    }
  }
}

/**
 * Updates the percentage of a media location that has been scanned
 *
 * @param string $table - The database table being updated by the scan
 * @param integer $location_id - The location ID being scanned
 */

function update_scan_progress( $table, $location_id)
{
  set_sys_pref('LAST_MEDIA_SCAN_UPDATE',time());

  $unverified = db_value("select count(*) from $table where location_id = $location_id and verified='N'");
  $total      = db_value("select count(*) from $table where location_id = $location_id");

  if ($total>0)
    db_sqlcommand("update media_locations set percent_scanned = ".(int)(100-($unverified/$total*100))." where location_id = $location_id ");
}

/**
 * Returns TRUE or FALSE depending upon whether the file specified has a modification
 * time newer that that recorded in the database. If there is no corresponding record
 * in the database then the function returns TRUE.
 *
 * @param string $table
 * @param integer $location
 * @param string $dir
 * @param string $file
 * @return boolean
 */

function file_newer_than_db( $table, $location, $dir, $file )
{
  // Date of file of disk (this might fail on linux systems if the file > 2Gb)
  if ( @filemtime($dir.$file) > 0 )
    $file_date = db_datestr(@filemtime($dir.$file));

  // Date of the file in the database
  $db_date = db_value("select timestamp from $table
                        where location_id = $location
                          and dirname     = '".db_escape_str($dir)."'
                          and filename    = '".db_escape_str($file)."'" );

  if ( !is_null($db_date) && ($db_date >= $file_date) )
  {
    // Record exists in database, and there have been no modifications to the file
    db_sqlcommand("update $table
                      set verified     = 'Y'
                    where location_id  = $location
                      and dirname      = '".db_escape_str($dir)."'
                      and filename     = '".db_escape_str($file)."'" );
  }
  elseif (!is_null($db_date) && $db_date < $file_date)
  {
    // Record exists in database, and the file has been modified
    send_to_log(6,"File has been modified ($file_date > $db_date)");
  }

  return (is_null($db_date) || $db_date < $file_date);
}

/**
 * Processes the media file  taking into consideration the file name ,extension
 *and whether this is a DVD image accessed over a network share.
 *
 * @param string $dir - directory
 * @param string $file - filename
 * @param integer $id - media location ID
 * @param string $share - network share
 * @param string $table - database table for this media type
 * @param array $file_exts - array of valid file extensions
 * @param bool $update - update the file even if it is not newer?
 * @return bool
 */

function process_media_file( $dir, $file, $id, $share, $table, $file_exts, $update )
{
  $files_to_ignore = array( 'video_ts.vob' );

  // This is one of the files we are ignoring.
  if ( in_array(strtolower($file),$files_to_ignore) )
    return false;

  // Is not one of the valid file extensions for this media type
  if ( !in_array(file_ext($file),$file_exts) )
   return false;

  // Is a VTS.ifo file
  if ( preg_match('/vts_[0-9]*_[0-9]*.ifo/i', basename($file)) == 1 )
    return false;

  // Is a DVD image and no Network Share is defined. VOB's are ignored if a Network Share is defined, they will be played with IFO.
  if ( $table == 'movies' && ( (empty($share) && in_array(file_ext($file), media_exts_dvd())) || (!empty($share) && file_ext($file)=='vob') ) )
    return false;

  // otherwise process the media file
  if ( file_newer_than_db( $table, $id, $dir, $file ) || $update )
  {
    switch ($table)
    {
      case 'mp3s'   : process_mp3   ( $dir, $id, $file ); break;
      case 'movies' : process_movie ( $dir, $id, $file ); break;
      case 'photos' : process_photo ( $dir, $id, $file ); break;
      case 'tv'     : process_tv    ( $dir, $id, $file ); break;
    }
  }

  return true;
}

/**
 * Recursive scan through the directory, finding all the MP3 files.
 *
 * @param directory $dir - directory to search
 * @param integer $id - media location ID
 * @param string $table - database table for this media type
 * @param array $file_exts - array of allowed file extensions
 * @param boolean $recurse - Process subdirectories?
 */

function process_media_directory( $dir, $id, $share, $table, $file_exts, $recurse = true, $update = false)
{
  // Directories to ignore (lowercase only - case insensitive match).
  $dirs_to_ignore  = explode(',',strtolower(get_sys_pref('IGNORE_DIR_LIST')).',.,..');

  // Mark all the files in this directory as unverified
  db_sqlcommand("update $table set verified ='N' where dirname like'".db_escape_str($dir)."%'");

  send_to_log(4,'Scanning : '.$dir);
  $dh = @opendir($dir);
  if ( $dh )
  {
    while (($file = readdir($dh)) !== false)
    {
      if (isdir($dir.$file))
      {
        // Regular directory
        if ( !in_array(strtolower($file),$dirs_to_ignore) && (get_sys_pref('IGNORE_HIDDEN_DIRECTORIES','NO')=="NO" || strpos($file,'.')!==0))
        {
          if ($table == 'photos')
            add_photo_album($dir.$file.'/', $id);

          if ($recurse)
            process_media_directory( $dir.$file.'/', $id, $share, $table, $file_exts, $recurse, $update);
        }
      }
      else
      {
        process_media_file( $dir, $file, $id, $share, $table, $file_exts, $update );
      }
    }
    closedir($dh);
  }
  else
    send_to_log(1,'Unable to read the directory contents. Are the permissions correct?',$dir);

  // Set the percentage of this media directory scanned.
  update_scan_progress($table, $id);

  // Delete any files which cannot be verified
  $files = db_toarray("select dirname, filename from $table where verified ='N' and dirname like '".db_escape_str($dir)."%'");
  foreach ($files as $file)
    send_to_log(4,'Removed  : '.$file['DIRNAME'].$file['FILENAME']);
  db_sqlcommand("delete from $table where verified ='N' and dirname like '".db_escape_str($dir)."%'");

  // Remove the browser coords from the session to ensure it gets recalculated to the current browser
  unset($_SESSION["device"]);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
