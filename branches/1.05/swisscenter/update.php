<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/file.php");
  require_once("base/prefs.php");

  function chksum_files($pre, $dir, &$files)
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
  }

 function set_last_update()
 {
   $last_update = file_get_contents('http://update.swisscenter.co.uk/release/last_update.txt');
   set_sys_pref('last_update',$last_update);
   $_SESSION["update"]["available"] = false;
 }
 
 function run_sql_files($sql_files)
 {
   $errors = 0;
   
   if(count($sql_files) > 0)
   {
     $last_sql_file_processed = $_SESSION["opts"]["database_vn"];

     // Key sort to ensure they are in the correct order
     ksort($sql_files, SORT_NUMERIC);

     foreach($sql_files as $sql_file_num => $sql_file)
     {
       send_to_log("Processing SQL file ".$sql_file_num);
       foreach ( split(";",implode(" ",file($sql_file))) as $sql)
       {
         if ( strlen(trim($sql)) > 0 ) 
         {
           if (db_sqlcommand($sql))
             send_to_log("SQL command executed : ".$sql);
           else 
           {
             send_to_log("SQL command failed : ".$sql);
             $errors++;
             // Exit the sql update procedure at this point as further files may depend on these changes
             break 2;
           }
         }
       }

       $last_sql_file_processed = $sql_file_num;
     }

     // Update the db with the last applied file
     db_sqlcommand("UPDATE system_prefs SET value='".$last_sql_file_processed."' WHERE name='DATABASE_VN'");
   }
   
   return $errors;
 }
 
 
 function find_fileinfo($file_info_array, $filename)
 {
   foreach($file_info_array as $file_info)
   {
     if($file_info["filename"] == $filename)
       return &$file_info;
   }
   
   return 0;
 }
   
//*************************************************************************************************
// Main Code
//*************************************************************************************************
  
  set_time_limit(60*25);
  $local   = array();
  $update  = array();
  $actions = array();
  $sql_files = array();
  $upd_script_name = "update.php";
  $upd_loc = 'http://update.swisscenter.co.uk/release/';
  $errors  = 0;
  $updated = false;
  $update_updatefile_only = false;
  $current_sql_version = $_SESSION["opts"]["database_vn"];
  
  // Get file checksums from the online update file  
  $file_contents = file_get_contents($upd_loc.'filelist.txt');
  $update = unserialize($file_contents);
  
  if ($file_contents === false)
  {
    $errtxt = "Unable to download update file";
    $errors += 1;
  }
  else
  {
    // Get the checksums for the local files, and unserialize the downloaded file.
    if (file_exists("filelist.txt"))
      $local = unserialize(file_get_contents('filelist.txt'));
    else
      chksum_files('./','',$local);
    
    // Create update directory to hold files if it doesn't already exist
    if (!file_exists('updates'))
    {
      mkdir('updates');
    }
    
    // Check to see if there is an update to the update script (update.php)
    $remote_update_file = find_fileinfo($update["files"], $upd_script_name);
    $local_update_file = find_fileinfo($local, $upd_script_name);
    if(($remote_update_file != 0) && ($local_update_file != 0) 
       && ($remote_update_file["checksum"] != $local_update_file["checksum"]))
    {
      // There is a new update file, ensure that it is the only thing downloaded
      send_to_log($upd_script_name." has changed, updating that file only and restarting update");
      $update = array("files" => array("filename"=>$upd_script_name, "checksum"=>$remote_update_file["checksum"]));
      $update_updatefile_only = true;
    }
    
    
    // Download the required files    
    foreach ($update["files"] as $test)
    {   
      $filename = md5( $test["filename"].$test["checksum"]).'.bin';
      $tmp_file = 'updates/'.md5( $test["filename"].$test["checksum"]).'.update';
      $skip_file = false;
  
      if(file_ext($test["filename"]) == 'sql')
      {
        // Its an SQL file, check the file number and ignore files that we've already applied
        // The file number is expected to be between the last _ and the . of the extension
        $file_num = substr(strrchr($test["filename"], "_"), 1);
        $file_num = substr($file_num,0,strpos($file_num,"."));

        // If we couldn't get the file number or the file is <= the current version then skip        
        if(($file_num === false) || ($file_num <= $current_sql_version) || !is_numeric($file_num))
          $skip_file = true;
        else
          $sql_files[$file_num] = $tmp_file;
      }
  
      // Don't download files that we've alread got or that we've been told to skip
      // I.e. SQL files that are older than the current DB vn or invalid
      if (!in_array($test,$local) && !$skip_file)
      {
        if (file_exists($tmp_file))
        {
          // File was already downloaded (previous failed update attempt?)
          $actions[] = array("old"=>$tmp_file, "new"=>$test["filename"]);
          send_to_log( $tmp_file." - already downloaded");
        }
        else
        {
          // New or changed file
          $file_contents = file_get_contents($upd_loc.$filename);
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
                $actions[] = array("old"=>$tmp_file, "new"=>$test["filename"]);
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
        if ( mkdir($dir["directory"]) == false )
          $errors += 1;
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
        // If the file is a sql file then skip it for now, we'll process them after the renames
        if(!in_array($a["old"], $sql_files))
        {
          unlink($a["new"]);
          rename($a["old"],$a["new"]);
          send_to_log("'".$a["new"]."' updated");
        }
        else
          send_to_log("'".$a["new"]."' rename skipped, is a SQL file");
      }
      
      // Run the SQL
      $errors += run_sql_files($sql_files);

      $updated = true;
      force_rmdir('updates');

      // Update complete, so save file list for comparison to new updates
      $out = fopen("filelist.txt", "w");
      
      // If this was an update of the updatefile only then, update the local filelist to
      // have the new update file in it
      if($update_updatefile_only)
      {
        $local_update_file["checksum"] = $remote_update_file["checksum"];
        fwrite($out, serialize($local));
      }
      else
        fwrite($out, serialize($update["files"]));
        
      fclose($out);   
      set_last_update();
      send_to_log("Update complete");

      
      if($update_updatefile_only)
      {
        // Re-run the update with the new script
        send_to_log("Update script changed, restarting update with new script");
        header("Location: /update.php");
      }
      else
      {
        // The update is complete
        header("Location: /update_outcome.php?status=UPDATED");
      }
   }
   else 
   {
      set_last_update();
      send_to_log("No files to update");
      header("Location: /update_outcome.php?status=NONE");
   }
        
  }    
  
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
