<?php

require_once( realpath(dirname(__FILE__).'/../ext/svnclient/phpsvnclient.php') );

define ('SVN_PATH', '/trunk/swisscenter/');

/**
 * Returns an instance of the phpSvnClient class attached to the Swisscenter
 * subversion repository.
 *
 * @return phpsvnclient
 */

function svn_swisscenter_repository()
{
  $location = trim(file_get_contents('http://update.swisscenter.co.uk/svn_repository.txt'));
  return $location;
}

/**
 * Returns the SVN revision number for the current installation using the following rules:
 *
 * 1. If the SVN_REVISION preference is set (from a previous update) then it is returned.
 * 2. The current swisscenter release is matched against the tags in the release directory.
 * 3. Revision 1 is returned as we cannot be sure of the true revision number.
 *
 * @return integer
 */

function svn_current_revision()
{
  $svn = new phpsvnclient;
  $svn->setRepository( svn_swisscenter_repository() );
  $tags = $svn->getDirectoryFiles("/tags/release");
  $releases = array();

  foreach($tags as $t)
    $releases[basename($t["path"])] = $t["revision"];

  if ( get_sys_pref("SVN_REVISION", 'NULL') != 'NULL')
    return get_sys_pref("SVN_REVISION");
  elseif ( isset($releases[swisscenter_version()]) )
    return $releases[swisscenter_version()];
  else
    return 1;
}

/**
 * Returns an associative array which maps the various release tags which have a reversion
 * greater than that specified (1 by default - lists all release tags).
 *
 * @param integer $revision - [Optional] Only tags with a higher revision than this will be returned
 * @return array
 */

function svn_release_tags( $revision = 1)
{
  $svn = new phpsvnclient;
  $svn->setRepository( svn_swisscenter_repository() );
  $tags = $svn->getDirectoryFiles("/tags/release");
  $releases = array();

  foreach($tags as $t)
    if ($t["revision"] > $revision)
      $releases[basename($t["path"])] = $t["revision"];

  return $releases;
}

/**
 * Returns an associative array containing a list of files to download
 * to update SwissCenter from the current version to the version
 * specified in the path.
 *
 * @param unknown_type $path
 * @return unknown
 */

function svn_update_filelist( $path = SVN_PATH )
{
  $repository = svn_swisscenter_repository();
  send_to_log(5,"SVN Repository : ".$repository);
  $svn = new phpsvnclient;
  $svn->setRepository( $repository );
  $current_revision = svn_current_revision();
  send_to_log(5,"Current SwissCenter subversion revision : ".$current_revision);

  $files = $svn->getDirectoryFilesRecursive( $path, $current_revision );
  array_sort($files, "path");

  return $files;
}

/**
 * Returns an associative array containing a list of all files in
 * the version specified in the path.
 * The array is also serialized to filelist_svn.txt.
 *
 * @param unknown_type $path
 * @return unknown
 */

function svn_revision_filelist( $path = SVN_PATH )
{
  $repository = svn_swisscenter_repository();
  send_to_log(5,"SVN Repository : ".$repository);
  $svn = new phpsvnclient;
  $svn->setRepository( $repository );
  $current_revision = svn_current_revision();
  send_to_log(5,"Current SwissCenter subversion revision : ".$current_revision);

  $files = $svn->getDirectoryFilesRecursive( $path, 0, $current_revision );
  array_sort($files, "path");

  // Save the file list for later verification
  file_put_contents(SC_LOCATION.'filelist_svn.txt',serialize($files));

  return $files;
}

/**
 * Updates the Swisscenter installation to the version specified by the $path
 * ("/trunk/swisscenter" by default - the current development build).
 *
 * Possbile return values are:
 *
 * ERROR    - An error occurred during the update
 * NONE     - No update is available at the moment
 * UPDATED  - The update was successful
 *
 * @param string $path
 * @return string
 */

function svn_update( $path = SVN_PATH )
{
  set_time_limit(0);
  send_to_log(1,"SwissCenter update from SVN started");
  $repository     = svn_swisscenter_repository();
  $updates_path   = SC_LOCATION.'updates/';
  $updates_list   = array();
  $swiss_revision = svn_current_revision();
  $max_revision   = $swiss_revision;

  if ($path[strlen($path)-1] != '/' || $path[0] != '/')
  {
    send_to_log(1,'ERROR: Update path should begin and end with a slash.');
    return 'ERROR';
  }

  // Create update directory to hold files if it doesn't already exist
  if (!file_exists($updates_path))
  {
    if ( mkdir($updates_path) === false )
    {
      send_to_log(1,'ERROR: Unable to create directory.',$updates_path);
      return 'ERROR';
    }
  }

  // Get filelist and act upon each file.
  send_to_log(4,"Getting list of files to update");
  $file_list = svn_update_filelist($path);
  send_to_log(8,'Filelist of updates:',$file_list);

  // Any files to update?
  if (count($file_list) == 0)
  {
    send_to_log(4,"No updates available.");
    return 'NONE';
  }

  send_to_log(4,"Downloading files");
  foreach ($file_list as $fsp)
  {
    if (!isset($fsp["md5_checksum"]))
    {
      if ( !file_exists(SC_LOCATION.$fsp["relative_path"]) )
      {
        send_to_log(4,"Creating directory",SC_LOCATION.$fsp["relative_path"]);
        @mkdir( SC_LOCATION.$fsp["relative_path"]);
      }
    }
    else
    {
      // Item is a file
      $url = $repository.$path.$fsp["relative_path"];
      $tmp = $updates_path.md5( $fsp["relative_path"].$fsp["md5_checksum"]).'.update';
      $current_file_md5 = @md5_file($fsp["relative_path"]);

      // Does it need to be downloaded?
      if ($current_file_md5 === false || $current_file_md5 != $fsp["md5_checksum"] )
      {
        // Yes. Has it already been downloaded?
        if (!file_exists($tmp) || $fsp["md5_checksum"] != md5_file($tmp))
        {
          // No. Download the file.
          if ( file_download_and_save($url , $tmp, true) === false)
          {
            // Error downloading file
            send_to_log(1,"ERROR: Unable to download file",$url);
            return 'ERROR';
          }
          else
          {
            // File downloaded, add it to the files to move.
            $updates_list[] = array("downloaded"=>$tmp, "existing"=>SC_LOCATION.$fsp["relative_path"]);
          }
        }
        else
        {
          // File already downloaded (and checksums match).
          send_to_log(4, "'$fsp[relative_path]' has already been downloaded into the updates directory.");
          $updates_list[] = array("downloaded"=>$tmp, "existing"=>SC_LOCATION.$fsp["relative_path"]);
        }
      }
      else
      {
        // Current file is identical to the one in subversion
        send_to_log(4, "'$fsp[relative_path]' is identical to that in subversion. No need to download");
      }
    }

    // Track the highest revision we've applied
    if (isset($fsp["path"]) && $fsp["revision"]>$max_revision)
      $max_revision = $fsp["revision"];
  }

  // All files have been downloaded
  send_to_log(4,"Replacing existing files with those downloaded");
  foreach ($updates_list as $action)
  {
    unlink($action["existing"]);
    rename($action["downloaded"],$action["existing"]);
    send_to_log(4,"'".$action["existing"]."' updated");
  }

  // Update complete
  if ($max_revision == $swiss_revision)
  {
    send_to_log(1,"ERROR: Unable to apply changes.",$url);
    return 'ERROR';
  }
  else
  {
    // Update the filelist_svn.txt before completing
    $file_list = svn_revision_filelist($path);

    set_sys_pref("SVN_REVISION",$max_revision);
    return 'UPDATED';
  }
}