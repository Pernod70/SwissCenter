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

  // Attempts to read the tag information, and either reports errors, or inserts the details into
  // the database.

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

  //
  // Recursive scan through the directory, finding all the movie files.
  //

  function scan_dirs( $dir )
  {
    send_to_log('Scanning for movies in : '.$dir);
    if ($dh = opendir($dir))
    {
      $types = array('avi','mpg','mpeg');
      
      while (($file = readdir($dh)) !== false)
      {
        if (is_dir($dir.$file))
        {
          // Regular directory, so recurse and get files.
          if (($file) !='.' && ($file) !='..')
            scan_dirs( $dir.$file.'/');
        }
        elseif ( in_array(strtolower(file_ext($file)), $types))
        {
          // Is this file already in the database?
          $cnt = db_value("select count(*) from movies where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
          if ($cnt == 0)
            process_movie( $dir, $file);
          else
            db_sqlcommand("update movies set verified ='Y' where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
        }
      }
      closedir($dh);
    }
  }

  //===========================================================================================
  // Main script logic
  //===========================================================================================

  media_indicator('BLINK');
  send_to_log('Refreshing VIDEO database');

  // First of all, mark all the movies as unverified.
  db_sqlcommand("update movies set verified ='N'");
  
  $dirs = $_SESSION["opts"]["dirs"]["video"];

  echo "<pre>";
  if (is_string($dirs))
    scan_dirs(str_suffix($dirs,'/'));
  elseif (is_array($dirs))
    foreach ($dirs as $directory)
      scan_dirs( str_suffix($directory,'/') );
  echo "</pre>";
      
  // Now delete any music that is still unverified, as it must point to orphaned files.
  db_sqlcommand("delete from movies where verified ='N'");
  send_to_log('Completed refreshing VIDEO database');
  media_indicator('OFF');
      

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
