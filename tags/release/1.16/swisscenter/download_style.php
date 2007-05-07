<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  
  set_time_limit(60*5);

  $style    = rawurldecode($_REQUEST["name"]);
  $dir      = SC_LOCATION.'styles/'.$style.'/';
  $filename = SC_LOCATION.'_tmp_style.zip';
  $zipfile  = str_replace(' ','%20','http://update.swisscenter.co.uk/styles/'.$style.'.zip');
  send_to_log(5,'User has requested a download of the style "'.$style.'"');
  
  // First attempt to get the style
  if ( ($zip = file_get_contents($zipfile)) !== false)
  {
    // Write the zipfile to a temporary file
    write_binary_file($filename,$zip);
    
    // Delete the directory if it already exists (an update?)
    if ( file_exists($dir))
      force_rmdir($dir);

    // Create the directory
    mkdir($dir);

    // Extract the zipfile
    if ( file_exists($dir))
    {
      if ( extension_loaded('zip') && ($fh = zip_open($filename)) !== false)
      {
        // The zip extension is loaded, so we can attempt to use it to extract the files
        while ($zip_entry = zip_read($fh))
        {
          $ze_filename = zip_entry_name($zip_entry);
          if (zip_entry_open($fh, $zip_entry, "r"))
          {
            $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));  
            zip_entry_close($zip_entry);
            write_binary_file($dir.$ze_filename,$buf);
            send_to_log(8,'Extracting file from zipfile : '.$dir.$ze_filename);
          }
        }
        zip_close($fh);
      }
      elseif (is_unix())
      {
        // On LINUX machines, we can write to a temporary file and then use the standard "unzip" command to 
        // perform the same functions as the zip extension
        exec('unzip '.$filename.' -d '.$dir);
      }
    }
    
    // Delete temporary file
    unlink($filename);
  }
  else 
    send_to_log(1,'Unable to download the style',array("Style Name"=>$style,"Zipfile"=>$zipfile));
  
  // Set the style in the swssion, and redirect back to the main style page.
  set_user_pref('style',$style);
  load_style();
  header("Location: /style.php");


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
