<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/file.php");
  require_once("ext/getid3/getid3.php");
  require_once("video_obtain_info.php");

  set_time_limit(86400);

  // ----------------------------------------------------------------------------------
  // MUSIC
  // ----------------------------------------------------------------------------------

  function process_mp3( $dir, $file)
  {
    send_to_log('New MP3 found : '.$file);
    $filepath = os_path($dir.$file);
    $data     = array();
    $getID3  = new getID3;
    $id3      = $getID3->analyze($filepath);

    if ( $id3["fileformat"] == 'mp3')
    {
      if ( ! isset($id3["error"]) )
      {
        getid3_lib::CopyTagsToComments($id3);

        // ID3 data successfully obtained, so enter it into the database
        $data = array("dirname"      => $dir
                     ,"filename"     => $file
                     ,"verified"     => 'Y'
                     ,"size"         => $id3["filesize"]
                     ,"length"       => floor($id3["playtime_seconds"])
                     ,"lengthstring" => $id4["playtime_string"]
                     ,"bitrate"      => floor($id3["bitrate"])
                     ,"version"      => 0
                     ,"title"        => array_last($id3["comments"]["title"])
                     ,"artist"       => array_last($id3["comments"]["artist"])
                     ,"album"        => array_last($id3["comments"]["album"])
                     ,"year"         => array_last($id3["comments"]["year"])
                     ,"track"        => array_last($id3["comments"]["track"])
                     ,"genre"        => array_last($id3["comments"]["genre"]) );
        if (!db_insert_row( "mp3s", $data))
          send_to_log('Unable to add MP3 to the database');
      }
      else
      {
        // File is an MP3, but there were (critical) problems reading the ID3 tag info
        // or the file itself is not an MP3
        send_to_log('Errors occurred whilst reading ID3 tag information');
        foreach ($id3["error"] as $err)
          send_to_log(' - '.$err);
      }

    }
    else
    {
      // File extension is MP3, but the file itself isn't!
      send_to_log('GETID3 claims this is not an MP3');
    }
  }

  // ----------------------------------------------------------------------------------
  // PHOTOS
  // ----------------------------------------------------------------------------------

  function process_photo( $dir, $file)
  {
    send_to_log('New Photo found : '.$file);
    $filepath = os_path($dir.$file);
    $data     = array();
    $getID3   = new getID3;
    $id3      = $getID3->analyze($filepath);

    if (in_array( $id3["fileformat"],array('jpg','gif','png','jpeg')) )
    {
      if ( ! isset($id3["error"]) )
      {
        // File Info successfully obtained, so enter it into the database
        $data = array("dirname"        => $dir
                     ,"filename"       => $file
                     ,"size"           => $id3["filesize"]
                     ,"width"          => $id3["video"]["resolution_x"]
                     ,"height"         => $id3["video"]["resolution_y"]
                     ,"date_modified"  => filemtime($filepath)
                     ,"date_created"   => 0
                     ,"verified"       => 'Y' );

        if (!db_insert_row( "photos", $data))
          send_to_log('Unable to add PHOTO to the database');
      }
      else
      {
        // File is a photo, but there were problems reading the info
        send_to_log('Errors occurred whilst reading photo information');
        foreach ($id3["error"] as $err)
          send_to_log(' - '.$err);
      }

    }
    else
    {
      // File extension is OK, but the file itself isn't!
      send_to_log('GETID3 claims this is not a PHOTO');
    }
  }

  // ----------------------------------------------------------------------------------
  // MOVIES
  // ----------------------------------------------------------------------------------

  function process_movie( $dir, $file)
  {
    send_to_log('New movie found : '.$file);
    $types    = array('riff','mpeg');
    $filepath = os_path($dir.$file);
    $data     = array();
    $getID3   = new getID3;
    $id3      = $getID3->analyze($filepath);

    if ( in_array(strtolower($id3["fileformat"]), $types))
    {
      if ( ! isset($id3["error"]) )
      {
        getid3_lib::CopyTagsToComments($id3);

        // Tag data successfully obtained, so enter it into the database
        $data = array("dirname"      => $dir
                     ,"filename"     => $file
                     ,"title"        => file_noext($file)
                     ,"size"         => $id3["filesize"]
                     ,"length"       => floor($id3["playtime_seconds"])
                     ,"lengthstring" => $id4["playtime_string"] );
        if ( db_insert_row( "movies", $data) === false )
          send_to_log('Unable to add movie to the database');
      }
      else
      {
        // File is a valid movie format, but there were (critical) problems reading the tag info
        // or the file itself is not a movie
        send_to_log('Errors occurred whilst reading tag information');
        foreach ($id3["error"] as $err)
          send_to_log(' - '.$err);
      }

    }
    else
    {
      // File extension is correct, but the file itself isn't!
      send_to_log("GETID3 claims this is not a valid movie (but we'll add it anyway!)");

      $data = array("dirname"      => $dir
                   ,"filename"     => $file
                   ,"title"        => file_noext($file)
                   ,"size"         => filesize($dir.$file) );
      if ( db_insert_row( "movies", $data) === false )
        send_to_log('Unable to add movie to the database');
    }
  }

  // ----------------------------------------------------------------------------------
  // Recursive scan through the directory, finding all the MP3 files.
  // ----------------------------------------------------------------------------------

  function scan_dirs( $dir, $table, $file_exts )
  {
    send_to_log('Scanning : '.$dir);
    if ($dh = opendir($dir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if (is_dir($dir.$file))
        {
          // Regular directory, so recurse and get files.
          if (($file) !='.' && ($file) !='..')
            scan_dirs( $dir.$file.'/', $table, $file_exts);
        }
        elseif ( in_array(strtolower(file_ext($file)),$file_exts) )
        {
          // Is this file already in the database?
          $cnt = db_value("select count(*) from $table where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
          if ($cnt == 0)
          {
            switch ($table)
            {
              case 'mp3s'   : process_mp3(   $dir, $file);  break;
              case 'movies' : process_movie( $dir, $file);  break;
              case 'photos' : process_photo( $dir, $file);  break;
            }
          }
          else
            db_sqlcommand("update $table set verified ='Y' where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
        }
      }
      closedir($dh);
    }
  }

  function process_media_dirs( $dirname, $table, $types)
  {
    $dirs = $_SESSION["opts"]["dirs"][$dirname];
    send_to_log('Refreshing '.strtoupper($dirname).' database');
    db_sqlcommand("update $table set verified ='N'");
  
    if (is_string($dirs))
      $dirs = array(dirs);
      
    foreach ($dirs as $directory)
      scan_dirs( str_suffix($directory,'/'), $table, $types );
        
    db_sqlcommand("delete from $table where verified ='N'");
    send_to_log('Completed refreshing '.strtoupper($dirname).' database');
  }
  
  //===========================================================================================
  // Main script logic
  //===========================================================================================

  media_indicator('BLINK');
  
  process_media_dirs('music','mp3s',   explode(',' ,MEDIA_EXT_MUSIC));
  process_media_dirs('video','movies', explode(',' ,MEDIA_EXT_MOVIE));
  extra_get_all_movie_details();
  process_media_dirs('photo','photos', explode(',' ,MEDIA_EXT_PHOTOS));
     
  media_indicator('OFF');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
