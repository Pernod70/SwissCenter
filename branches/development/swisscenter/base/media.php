<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/sched.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/image.php'));
require_once( realpath(dirname(__FILE__).'/screen.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

// Libraries for reading file metadata
require_once( realpath(dirname(__FILE__).'/../ext/getid3/getid3.php'));
require_once( realpath(dirname(__FILE__).'/../ext/exif/exif_reader.php'));

//-------------------------------------------------------------------------------------------------
// Causes an immediate refresh of the media database
//-------------------------------------------------------------------------------------------------

function media_refresh_now()
{
  if (is_server_simese() && simese_version() >= 1.31)
  {
    $dir = SC_LOCATION.'config/simese';

    if ( !file_exists($dir) )
      mkdir($dir);
    
    if (is_dir($dir))
      write_binary_file($dir.'/Simese.ini',"MediaRefresh=Now");
  }
  else
  	run_background('media_search.php');
}

//-------------------------------------------------------------------------------------------------
// If the server is anything other than Simese, then we use the bacground scheduler (such as "at"
// or "cron" to schedule a media refresh)
//-------------------------------------------------------------------------------------------------

function media_schedule_refresh($schedule, $time)
{
  // Managing the Simese scheduler is best done in Simese, not by the SwissCenter
  if (!is_server_simese())
    run_background('media_search.php',$schedule, $time);
}

// ----------------------------------------------------------------------------------
// Removes orphaned media files and albumart from the database (rows that exist in
// the database for a media location that is not valid anymore).
// ----------------------------------------------------------------------------------

function remove_orphaned_records()
{
  @db_sqlcommand('delete from mp3_albumart  '.
                 ' using mp3_albumart left outer join mp3s  '.
                 '    on mp3_albumart.file_id = mp3s.file_id '.
                 ' where mp3s.file_id is null');
  
  @db_sqlcommand('delete from mp3s  '.
                 ' using mp3s  left outer join media_locations  '.
                 '    on media_locations.location_id = mp3s.location_id '.
                 ' where media_locations.location_id is null');
  
  @db_sqlcommand('delete from movies '.
                 ' using movies left outer join media_locations  '.
                 '    on media_locations.location_id = movies.location_id  '.
                 ' where media_locations.location_id is null');
  
  @db_sqlcommand('delete from photos '.
                 ' using photos left outer join media_locations '.
                 '    on media_locations.location_id = photos.location_id '.
                 ' where media_locations.location_id is null');
}

// ----------------------------------------------------------------------------------
// Eliminate duplicate records (when the version of MySQL is too low to support the
// unique indexes created).
// ----------------------------------------------------------------------------------

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
  
  @db_sqlcommand('   CREATE TEMPORARY TABLE photos_del AS  '.
                 '   SELECT max(file_id) file_id           '.
                 '     FROM photos                         '.
                 ' GROUP BY dirname,filename               '.
                 '   HAVING count(*)>1');
  
  @db_sqlcommand('DELETE FROM mp3s   USING mp3s, mp3s_del     WHERE mp3s.file_id = mp3s_del.file_id');
  @db_sqlcommand('DELETE FROM movies USING movies, movies_del WHERE movies.file_id = movies_del.file_id');
  @db_sqlcommand('DELETE FROM photos USING photos, photos_del WHERE photos.file_id = photos_del.file_id');  
}
  
//-------------------------------------------------------------------------------------------------
// Takes the specified file and checks the format to determine if it should be added to the 
// appropriate media table in the database.
//-------------------------------------------------------------------------------------------------

function process_mp3( $dir, $id, $file)
{
  send_to_log(4,'New MP3 found : '.$file);
  $filepath = os_path($dir.$file);
  $data     = array();
  $getID3   = new getID3;
  $id3      = $getID3->analyze($filepath);

  // Standard information about the file 
  $data["dirname"]      = $dir;
  $data["filename"]     = $file;
  $data["location_id"]  = $id;
  $data["title"]        = file_noext($file);
  $data["size"]         = filesize($dir.$file);
  $data["verified"]     = 'Y';
  $data["discovered"]   = db_datestr();

  if (in_array( $id3["fileformat"],array('mp3','asf')) )
  {
    if ( ! isset($id3["error"]) )
    {
      getid3_lib::CopyTagsToComments($id3);

      // ID3 data successfully obtained, so enter it into the database
      $data["length"]       = floor($id3["playtime_seconds"]);
      $data["lengthstring"] = $id3["playtime_string"];
      $data["bitrate"]      = floor($id3["bitrate"]);
      $data["version"]      = 0;
      $data["title"]        = array_last($id3["comments"]["title"]);
      $data["artist"]       = array_last($id3["comments"]["artist"]);
      $data["album"]        = array_last($id3["comments"]["album"]);
      $data["year"]         = array_last($id3["comments"]["year"]);
      $data["track"]        = array_last($id3["comments"]["track"]);
      $data["genre"]        = array_last($id3["comments"]["genre"]);
                   
      if (!db_insert_row( "mp3s", $data))
        send_to_log(2,'Unable to add MP3 to the database');
        
      if ( get_sys_pref('USE_ID3_ART','YES') == 'YES' && isset($id3["id3v2"]["APIC"][0]["data"]))
      {
        $file_id = db_value("select file_id from mp3s where concat(dirname,filename)='".db_escape_str($dir.$file)."'");
        db_insert_row('mp3_albumart',array("file_id"=>$file_id, "image"=>addslashes($id3["id3v2"]["APIC"][0]["data"]) ));
        send_to_log(4,"Image found within ID3 tag - will use as album art");
      }
        
    }
    else
    {
      // File is an MP3, but there were (critical) problems reading the ID3 tag info
      // or the file itself is not an MP3
      send_to_log(2,'Errors occurred whilst reading ID3 tag information');
      foreach ($id3["error"] as $err)
        send_to_log(2,' - '.$err);
    }

  }
  else
  {
    // File extension is MP3, but the file itself isn't!
    send_to_log(3,'GETID3 claims this is not an MP3 - adding it anyway, but no ID3 tag information could be read.');
    if ( db_insert_row( "mp3s", $data) === false )
      send_to_log(1,'Unable to add MP3 to the database');
  }
}

// ----------------------------------------------------------------------------------
// Given a directory ($dir) and location id ($id) this function creates a new photo
// album within the database.
// ----------------------------------------------------------------------------------

function add_photo_album( $dir, $id )
{
  $count = db_value("select count(*) from photo_albums where dirname='".db_escape_str($dir)."'");
  if ($count == 0)
  {
    send_to_log(6,'Adding photo album "'.basename($dir).'"');
    
    $row = array("dirname"       => $dir
                 ,"title"        => basename($dir)
                 ,"verified"     => 'Y'
                 ,"discovered"   => db_datestr()
                 ,"location_id"  => $id
                 );

    if ( db_insert_row( "photo_albums", $row) === false )
      send_to_log(1,'Unable to add photo album to the database');
  }
}
  
//-------------------------------------------------------------------------------------------------
// Takes the specified file and checks the format to determine if it should be added to the 
// appropriate media table in the database.
//-------------------------------------------------------------------------------------------------

function process_photo( $dir, $id, $file)
{
  global $cache_dir;

  send_to_log(4,'New Photo found : '.$file);
  $filepath = os_path($dir.$file);
  $data     = array();
  $getID3   = new getID3;
  $id3      = $getID3->analyze($filepath);
  $exif     = exif($dir.$file);

  if (in_array( $id3["fileformat"],array('jpg','gif','png','jpeg')) )
  {
    if ( ! isset($id3["error"]) )
    {
      // File Info successfully obtained, so enter it into the database
      $data = array( "dirname"             => $dir
                   , "filename"            => $file
                   , "location_id"         => $id
                   , "size"                => $id3["filesize"]
                   , "width"               => $id3["video"]["resolution_x"]
                   , "height"              => $id3["video"]["resolution_y"]
                   , "date_modified"       => filemtime($filepath)
                   , "date_created"        => $exif["DTDigitised"]
                   , "verified"            => 'Y'
                   , "discovered"          => db_datestr()
                   , "exif_exposure_mode"  => $exif['ExposureMode']
                   , "exif_exposure_time"  => dec2frac($exif['ExposureTime'])
                   , "exif_fnumber"        => rtrim($exif['FNumber'],'0')
                   , "exif_focal_length"   => (empty($exif['FocalLength']) ? null : $exif['FocalLength'].str('LENGTH_MM') )
                   , "exif_image_source"   => $exif['ImageSource']
                   , "exif_make"           => $exif['Make']
                   , "exif_model"          => $exif['Model']
                   , "exif_orientation"    => $exif['Orientation']
                   , "exif_white_balance"  => $exif['WhiteBalance']
                   , "exif_flash"          => $exif['Flash'][1]
                   , "exif_iso"            => $exif['ISOSpeedRating']
                   , "exif_light_source"   => $exif['LightSource']
                   , "exif_exposure_prog"  => $exif['ExpProg']
                   , "exif_meter_mode"     => $exif['MeterMode']
                   , "exif_capture_type"   => $exif['SceneCaptureType']
                   );
                   
      if (db_insert_row( "photos", $data))
      {
        // Pre-cache the image thumbnail if the user has selected that option.
        if ($cache_dir != '' && get_sys_pref('CACHE_PRECACHE_IMAGES','NO') == 'YES')
        {
          send_to_log(6,'Pre-caching thumbnail');
          precache($dir.$file, convert_x(THUMBNAIL_X_SIZE), convert_y(THUMBNAIL_Y_SIZE) );
        }
      }
      else
        send_to_log(2,'Unable to add photo to the database');
    }
    else
    {
      // File is a photo, but there were problems reading the info
      send_to_log(2,'Errors occurred whilst reading photo information');
      foreach ($id3["error"] as $err)
        send_to_log(2,' - '.$err);
    }

  }
  else
  {
    // File extension is OK, but the file itself isn't!
    send_to_log(3,'GETID3 claims this is not a valid photo file');
  }
}

//-------------------------------------------------------------------------------------------------
// Takes the specified file and checks the format to determine if it should be added to the 
// appropriate media table in the database.
//-------------------------------------------------------------------------------------------------

function process_movie( $dir, $id, $file)
{
  send_to_log(4,'New movie found : '.$file);
  $types    = array('riff','mpeg');
  $data     = array();
  $getID3   = new getID3;
  $filepath = os_path($dir.$file);
  $id3      = $getID3->analyze($filepath);
  
  // Standard information about the file 
  $data["dirname"]      = $dir;
  $data["filename"]     = $file;
  $data["location_id"]  = $id;
  $data["title"]        = file_noext($file);
  $data["size"]         = filesize($dir.$file);
  $data["verified"]     = 'Y';
  $data["discovered"]   = db_datestr();
  
  if ( in_array(strtolower($id3["fileformat"]), $types))
  {
    if ( ! isset($id3["error"]) )
    {
      // Tag data successfully obtained, so record the following information
      getid3_lib::CopyTagsToComments($id3);
      $data["size"]          = $id3["filesize"];
      $data["length"]        = floor($id3["playtime_seconds"]);
      $data["lengthstring"]  = $id3["playtime_string"];                     
    }
    else
    {
      // File is a valid movie format, but there were (critical) problems reading the tag info.
      send_to_log(2,"GETID3 claims there are errors in the video file");
      foreach ($id3["error"] as $err)
        send_to_log(2,' - '.$err);
    }

  }
  else
    send_to_log(3,"GETID3 claims this is not a valid movie");

  // Insert the row into the database
  if ( db_insert_row( "movies", $data) === false )
    send_to_log(1,'Unable to add movie to the database');
}

// ----------------------------------------------------------------------------------
// Recursive scan through the directory, finding all the MP3 files.
// ----------------------------------------------------------------------------------

function process_media_directory( $dir, $id, $table, $file_exts, $recurse = true )
{
  send_to_log(4,'Scanning : '.$dir);

  // Mark all the files in this directory as unverified
  db_sqlcommand("update $table set verified ='N' where dirname like'".db_escape_str($dir)."%'");
    
  if ($dh = @opendir($dir))
  {
    while (($file = readdir($dh)) !== false)
    {
      if (@is_dir($dir.$file))
      {
        // Regular directory
        if (($file) !='.' && ($file) !='..')
        {
          if ($table == 'photos')
            add_photo_album($dir.$file, $id);

          if ($recurse)
            process_media_directory( $dir.$file.'/', $id, $table, $file_exts);
        }
      }
      elseif ( in_array(strtolower(file_ext($file)),$file_exts) )
      {
        if ( @filemtime($dir.$file) > 0 )
          $file_date = db_datestr(@filemtime($dir.$file));
          
        $db_date   = db_value("select discovered from $table 
                                where location_id=$id 
                                  and dirname='".db_escape_str($dir)."' 
                                  and filename='".db_escape_str($file)."'");

        if ( !is_null($db_date) && ($db_date >= $file_date) )
        {
          // Record exists in database, and there have been no modifications to the file
          db_sqlcommand("update $table set verified ='Y' 
                          where location_id=$id 
                            and dirname='".db_escape_str($dir)."' 
                            and filename='".db_escape_str($file)."'");
        }
        else
        {
          if (!is_null($db_date) && $file_date > $db_date)
          {
            send_to_log(6,"File has been modified ($file_date > $db_date)");

            // Record exists, but the modification time of the file is more recent
            db_sqlcommand("delete from $table 
                            where location_id=$id 
                              and dirname='".db_escape_str($dir)."' 
                              and filename='".db_escape_str($file)."'");
          }
            
          // Add the file's details to the database.
          switch ($table)
          {
            case 'mp3s'   : process_mp3(   $dir, $id, $file);  break;
            case 'movies' : process_movie( $dir, $id, $file);  break;
            case 'photos' : process_photo( $dir, $id, $file);  break;
          }
        }
      }
    }
    closedir($dh);
  }
  else 
    send_to_log(1,'Unable to read the directory contents. Are the permissions correct?',$dir);
    
  // Delete any files which cannot be verified
  db_sqlcommand("delete from $table where verified ='N' and dirname like '".db_escape_str($dir)."%'");   
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
