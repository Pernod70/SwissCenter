<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

$update_location = 'http://update.swisscenter.co.uk/release/';

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/base/utils.php'));
require_once( realpath(dirname(__FILE__).'/base/file.php'));
require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
set_time_limit(60*25);

function chksum_files($pre, $dir, &$files)
{
  if ($dir!='media/' && $dir!='cache/')
  {
    if (($dh = opendir($pre.$dir)) !== false)
    {
      while (($file = readdir($dh)) !== false)
      {
        if (isdir($pre.$dir.$file) && ($file) !='.' && ($file) !='..')
          chksum_files(  $pre, $dir.$file.'/', $files);
        if (is_file($pre.$dir.$file) && ($file) !='.' && ($file) !='..')
          $files[] = array('filename'=>$dir.$file
                          ,'checksum'=> md5(file_get_contents($pre.$dir.$file)) );
      }
      closedir($dh);
    }
    else
      send_to_log(1,"ERROR : Unable to open directory for reading.",$pre.$dir);
  }
}

function set_last_update($release_dir)
{
 $last_update = file_get_contents($release_dir.'last_update.txt');
 set_sys_pref('LAST_UPDATE',$last_update);
 set_sys_pref('UPDATE_AVAILABLE',false);
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
      $oldumask = umask(0);
      if ( mkdir('updates',0777) === false )
      {
        send_to_log(1,'ERROR: Unable to create directory.',"Updates");
        $errors += 1;
      }
      umask($oldumask);
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
          send_to_log(4, $tmp_file." - already downloaded");
        }
        else
        {
          // New or changed file
          $file_contents = file_get_contents($update_location.$filename);
          send_to_log(4,$tmp_file." - downloading");

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
                send_to_log(4,$tmp_file." - stored on disk ready for rename");
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
        $oldumask = umask(0);
        if ( mkdir($dir["directory"],0777) === false )
        {
         send_to_log(1,'ERROR: Unable to create directory.',SC_LOCATION.$dir["directory"]);
         $errors += 1;
        }
        else
          send_to_log(4,"'".$dir["directory"]."' directory created : ");
        umask($oldumask);
      }
    }
  }

  // Any errors so far?
  if ($errors !=0)
  {
    send_to_log(1,"There were errors during the update process : ".$errtxt);
    header("Location: /update_outcome.php?status=ERROR");
  }
  else
  {
    if (count($actions) > 0)
    {
      // Windows services
      $services = array('SwissMonitorService', 'upnp2http');
      $services_status = array();

      // Stop Windows services to allow overwrite
      if ( is_windows() )
      {
        foreach ($services as $service)
        {
          $services_status[$service] = (win_service_status($service) == SERVICE_STARTED);
          if ($services_status[$service])
          {
            send_to_log(4,"Stopping Windows service: ".$service);
            win_service_stop($service);
          }
        }
      }

      // Rename Files
      foreach($actions as $a)
      {
        if (file_exist($a["existing"])) unlink($a["existing"]);
        rename($a["downloaded"],$a["existing"]);
        send_to_log(4,"'".$a["existing"]."' updated");
      }

      $updated = true;
      force_rmdir('updates');

      // Start Windows services
      if ( is_windows() )
      {
        foreach ($services as $service)
        {
          if ($services_status[$service])
          {
            send_to_log(4,"Starting Windows service: ".$service);
            win_service_start($service);
          }
        }
      }

      // Update complete, so save file list for comparison to new updates and reset the session
      $_SESSION = array();
      $out = fopen("filelist.txt", "w");
      fwrite($out, serialize($update["files"]));
      fclose($out);
      set_last_update($update_location);
      set_sys_pref("SVN_REVISION","");
      send_to_log(4,"Update complete");
      header("Location: /update_outcome.php?status=UPDATED");
    }
    else
    {
       set_last_update($update_location);
       send_to_log(4,"No files to update");
       header("Location: /update_outcome.php?status=NONE");
    }
  }

  // Refresh style and language
  load_style();
  load_translations();

  // Update the players config
  load_players_config();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
