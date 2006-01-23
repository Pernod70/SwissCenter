<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  $update_location = 'http://update.swisscenter.co.uk/release/';

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/file.php");
  require_once("base/prefs.php");
  set_time_limit(60*25);

  function chksum_files($pre, $dir, &$files)
  {
    if ($dir!='media/')
    {
      if ($dh = opendir($pre.$dir))
      {
        while (($file = readdir($dh)) !== false)
        {
          if (is_dir($pre.$dir.$file) && ($file) !='.' && ($file) !='..')
            chksum_files(  $pre, $dir.$file.'/', $files);
          if (is_file($pre.$dir.$file) && ($file) !='.' && ($file) !='..')
            $files[] = array('filename'=>$dir.$file
                            ,'checksum'=> md5(file_get_contents($pre.$dir.$file)) );
        }
        closedir($dh);
      }
      else 
        send_to_log("ERROR : Unable to open directory for reading.",$pre.$dir);
    }
  }

 function set_last_update($release_dir)
 {
   $last_update = file_get_contents($release_dir.'last_update.txt');
   set_sys_pref('last_update',$last_update);
   $_SESSION["update"]["available"] = false;
 }
   
//*************************************************************************************************
// Main Code
//*************************************************************************************************  
    
  $local   = array();
  $update  = array();
  $actions = array();
  $errors  = 0;
  $updated = false;
  
  // Get file checksums from the online update file  
  $file_contents = file_get_contents($update_location.'filelist.txt');
  
  if ($file_contents === false)
  {
    $errtxt = "Unable to download update file";
    $errors += 1;
  }
  else
  {
    $update = unserialize($file_contents);

    // Get the checksums for the local files, and unserialize the downloaded file.
    if (file_exists("filelist.txt"))
      $local = unserialize(file_get_contents('filelist.txt'));
    else
      chksum_files('./','',$local);
    
    // Create update directory to hold files if it doesn't already exist
    if (!file_exists('updates'))
    {
      if ( mkdir('updates') === false )
      {
        send_to_log('ERROR: Unable to create directory.',"Updates");
        $errors += 1;
      }
    }
    
    // Download the required files    
    foreach ($update["files"] as $test)
    {   
      $filename = md5( $test["filename"].$test["checksum"]).'.bin';
      $tmp_file = 'updates/'.md5( $test["filename"].$test["checksum"]).'.update';
  
      if (!in_array($test,$local) )
      {
        if (file_exists($tmp_file))
        {
          // File was already downloaded (previous failed update attempt?)
          $actions[] = array("downloaded"=>$tmp_file, "existing"=>$test["filename"]);
          send_to_log( $tmp_file." - already downloaded");
        }
        else
        {
          // New or changed file
          $file_contents = file_get_contents($update_location.$filename);
          send_to_log($tmp_file." - downloading");
  
          if ($file_contents === false)
          {
            $errtxt = "<p>Unable to download file : ".$test["filename"]." (".$filename.")";
            $errors += 1;
          }
          else 
          {
            $text = @gzuncompress($file_contents);
            if ($text === false)
            {
              $errtxt = "Error uncompressing file : ".$filename;
              $errors += 1;
            }
            else 
            {
              $out = fopen($tmp_file, "w");
              fwrite($out, $text);
              fclose($out);
              
              if (!file_exists($tmp_file))
              {
                $errtxt = 'Unable to write file : '.$tmp_file;
                $errors +=1;
              }
              else
              {
                send_to_log($tmp_file." - stored on disk ready for rename");
                $actions[] = array("downloaded"=>$tmp_file, "existing"=>$test["filename"]);
              }
            }          
          }
        }
      }
    }
    
    // Sync directory structure  
    foreach ($update["dirs"] as $dir)
    {
      if (!file_exists($dir["directory"]))
      {
        if ( mkdir($dir["directory"]) === false )
        {
         send_to_log('ERROR: Unable to create directory.',SC_LOCATION.$dir["directory"]);
         $errors += 1;
        }
        else 
          send_to_log("'".$dir["directory"]."' directory created : ");
      }
    }
  }
  
  // Any errors so far?
  if ($errors !=0)
  {
    send_to_log("There were errors during the update process : ".$errtxt);
    header("Location: /update_outcome.php?status=ERROR");
  }
  else
  {
    // Rename Files
    if (count($actions) > 0)
    {
      foreach($actions as $a)
      {
        if ( unlink($a["existing"]) )
        {
          if ( rename($a["downloaded"],$a["existing"]) )
            send_to_log("'".$a["existing"]."' updated");
          else 
            send_to_log('ERROR: Unable to create new file.',SC_LOCATION.$a["existing"]);
        }
        else
        {
          send_to_log('ERROR: Unable to delete existing file.',SC_LOCATION.$a["existing"]);
        }          

        // If the file that has been updated is a database update, then apply it to the database
        if ( preg_match('/.*update_[0-9]*.sql/',$a["existing"]) )
        {
          foreach ( split(";",implode(" ",file($a["existing"]))) as $sql)
            if ( strlen(trim($sql)) > 0 ) 
            {
              if (db_sqlcommand($sql))
                send_to_log("SQL command executed : ".$sql);
              else 
                send_to_log("SQL command failed : ".$sql);
            }
        } 
      }

      $updated = true;
      force_rmdir('updates');

      // Update complete, so save file list for comparison to new updates and reset the session
      $_SESSION = array();
      $out = fopen("filelist.txt", "w");
      fwrite($out, serialize($update["files"]));
      fclose($out);   
      set_last_update($update_location);
      send_to_log("Update complete");
      header("Location: /update_outcome.php?status=UPDATED");
   }
   else 
   {
      set_last_update($update_location);
      send_to_log("No files to update");
      header("Location: /update_outcome.php?status=NONE");
   }
        
  }    
  
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
