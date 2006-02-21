<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/settings.php");
  require_once("base/utils.php");
  require_once("base/file.php");

  set_time_limit(86400);

  // Attempts to read the ID3 tag information, and either reports errors, or inserts the details into
  // the database.

  function process_img( $dir, $file)
  {
    // Here we will load the database with the filenames, so that searches can be performed.
  }

  //
  // Recursive scan through the directory, finding all the MP3 files.
  //

  function scan_dirs( $dir )
  {
    $types = array('gif','png','jpg','jpeg');

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
        elseif ( in_array(strtolower(file_ext($file)), $types))
        {
           process_img( $dir, $file);
        }
      }
      closedir($dh);
    }
  }

  //
  // Main script logic
  //

  scan_dirs( str_suffix($_SESSION["opts"]["dirs"]["photo"],'/') );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
