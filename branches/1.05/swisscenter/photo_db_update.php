<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("ext/getid3/getid3.php");

  set_time_limit(86400);

  // ----------------------------------------------------------------------------------
  // Attempts to read the ID3 tag information, and either reports errors, or inserts the 
  // details into the database.
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
  // Recursive scan through the directory, finding all the PHOTO files.
  // ----------------------------------------------------------------------------------

  function scan_dirs( $dir )
  {
    send_to_log('Scanning for photos in : '.$dir);
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
        elseif ( in_array(file_ext(strtolower($file)),array('jpg','gif','png','jpeg')))
        {
          // Is this file already in the database?
          $cnt = db_value("select count(*) from photos where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
          if ($cnt == 0)
            process_photo( $dir, $file);
          else
            db_sqlcommand("update photos set verified ='Y', date_modified=".filemtime(os_path($dir.$file))." where dirname='".db_escape_str($dir)."' and filename='".db_escape_str($file)."'");
        }
      }
      closedir($dh);
    }
  }

  //===========================================================================================
  // Main script logic
  //===========================================================================================

  media_indicator('BLINK');
  send_to_log('Refreshing PHOTO database');

  // First of all, mark all the photos as unverified.
  db_sqlcommand("update photos set verified ='N'");
  
  $dirs = $_SESSION["opts"]["dirs"]["photo"];

 if (is_string($dirs))
    scan_dirs(str_suffix($dirs,'/'));
  elseif (is_array($dirs))
    foreach ($dirs as $directory)
      scan_dirs( str_suffix($directory,'/') );
      
  // Now delete any photos that are still unverified, as it must point to orphaned files.
  db_sqlcommand("delete from photos where verified ='N'");
  send_to_log('Completed refreshing PHOTOS database');
  media_indicator('OFF');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
