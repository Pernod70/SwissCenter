<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/file.php");
  require_once("ext/getid3/getid3.php");

  set_time_limit(86400);

  // Attempts to read the ID3 tag information, and either reports errors, or inserts the details into
  // the database.

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

  //
  // Recursive scan through the directory, finding all the MP3 files.
  //

  function scan_dirs( $dir )
  {
    send_to_log('Scanning for music in : '.$dir);
    if ($dh = opendir($dir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if (is_dir($dir.$file))
        {
          // Regular directory, so recurse and get files.
          if (($file) !='.' && ($file) !='..')
            scan_dirs( $dir.$file.'/');
        }
        elseif ( file_ext($file) == 'mp3')
        {
          // Is this file already in the database?
          $cnt = db_value("select count(*) from mp3s where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
          if ($cnt == 0)
            process_mp3( $dir, $file);
          else
            db_sqlcommand("update mp3s set verified ='Y' where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
        }
      }
      closedir($dh);
    }
  }

  //===========================================================================================
  // Main script logic
  //===========================================================================================

  media_indicator('BLINK');
  send_to_log('Refreshing MUSIC database');

  // First of all, mark all the music as unverified.
  db_sqlcommand("update mp3s set verified ='N'");
  
  $dirs = $_SESSION["opts"]["dirs"]["music"];

  if (is_string($dirs))
    scan_dirs(str_suffix($dirs,'/'));
  elseif (is_array($dirs))
    foreach ($dirs as $directory)
      scan_dirs( str_suffix($directory,'/') );
      
  // Now delete any music that is still unverified, as it must point to orphaned files.
  db_sqlcommand("delete from mp3s where verified ='N'");
  send_to_log('Completed refreshing MUSIC database');
  media_indicator('OFF');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
