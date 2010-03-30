<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/utils.php'));

/**
 * Replacement function for is_dir() which returns true if the path specified is
 * a directory OR a valid drive/share/mount.
 *
 * @param string $fsp
 * @return boolean
 */

function isdir( $fsp )
{
  if ($dh = @opendir($fsp))
  {
    closedir($dh);
    return true;
  }
  else
    return false;
}

/**
 * A function to get around the limitation that some versions of PHP on linux machines only have
 * support for 32-bit integers and therefore cannot return the size of a file > 2Gb.
 *
 * @param string $fsp
 * @return integer
 */

function large_filesize( $fsp )
{
  if ( is_windows() )
  {
    return filesize($fsp);
  }
  else
  {
    $details = preg_split('/ +/',exec('ls -l "'.$fsp.'"'));
    send_to_log(8,'File details from "ls -l '.$fsp.'" command',$details);
    return $details[4];
  }
}

/**
 * Returns the correct line ending for files depending on the host OS.
 *
 * @return string
 */

function newline()
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return "\r\n";
  else
    return "\n";
}

define ('DIR_TO_ARRAY_SHOW_FILES',1);
define ('DIR_TO_ARRAY_SHOW_DIRS', 2);
define ('DIR_TO_ARRAY_FULL_PATH', 4);
/**
 * Returns an array containing the names of all files and subdirectories within the specified
 * directory (returns an empty array if the specified directory does not exist or is not readable).
 *
 * @param string $dir
 * @param string $pattern
 * @param integer $opts
 * @param boolean $recursive
 * @return array
 */

function dir_to_array ($dir, $pattern = '.*', $opts = 7, $recursive = false )
{
  $dir = os_path($dir,true);

  $contents = array();
  if ($dh = @opendir($dir))
  {
    while (($file = readdir($dh)) !== false)
    {
      // Does file/folder match pattern?
      if ( preg_match('/'.$pattern.'/', $file) )
      {
        if ( (isdir($dir.$file) && ($opts & DIR_TO_ARRAY_SHOW_DIRS)) ||
           (is_file($dir.$file) && ($opts & DIR_TO_ARRAY_SHOW_FILES)) )
          if ($opts & DIR_TO_ARRAY_FULL_PATH)
            $contents[] = os_path($dir.$file);
          else
            $contents[] = $file;

        // If folder and recursive then add folder contents, excluding special folders
        if ( $recursive && isdir($dir.$file) && !in_array($file, array('.','..','.svn')) )
          $contents = array_merge($contents, dir_to_array($dir.$file, $pattern, $opts, $recursive));
      }
    }
    closedir($dh);
  }

  sort($contents);
  return $contents;
}

/**
 * Returns the path/filename of the logfile.
 *
 * @return string
 */

function logfile_location()
{
  return str_replace('\\','/',realpath(dirname(__FILE__).'/../log')).'/support.log';
}

/**
 * Returns the bookmark filename (for resume playing) for the given filename (DIRNAME.FILENAME).
 *
 * @param string $fsp
 * @return string
 */

function bookmark_file( $fsp )
{
  return SC_LOCATION."config/Bookmarks/".strtoupper(md5("/".ucfirst($fsp))).".dat";
}

/**
 * Routine to add a message and (optionally) the contents of a variable to the swisscenter log.
 *
 * NOTE: If the logv has become more than 1Mb in size then it is archived and a new log is
 *       started. Only one generation of logs is archived (so current log and old log only)
 *
 * ERRORS
 * 1 - Information on critical errors only.
 * 2 - Information on all errors
 * 3 - Detailed information on all erorrs
 *
 * EVENTS
 * 4 - Information on important events (new mp3s, etc)
 * 5 - ALl events
 *
 * DEBUGGING INFORMATION
 * 6 - System modifications -  Files being created, system prefs, updating swisscenter, etc
 * 7 - Information sent to the hardware player
 * 8 - Maximum detail but without database related information
 *
 * EVERYTHING
 * 9 - Maximum detail, includes all SQL statements executed
 *
 * @param integer $level
 * @param string $item
 * @param mixed $var
 */

function send_to_log($level, $item, $var = '')
{
  $log_level = ( defined('LOG_MODE') && (int)LOG_MODE > 0 ? LOG_MODE : 5);

  if (!empty($item) && $log_level >= $level )
  {
    $log = logfile_location();

    if ( $log !== false )
    {
      $time = '['.date('Y.m.d H:i:s').'] ';

      // If the file > 1Mb then archive it and start a new log.
      if (@filesize($log) > 1048576)
      {
        @unlink($log.'.old');
        @rename($log,$log.'.old');
      }

      // Write log entry to file.
      if ($handle = fopen($log, 'a'))
      {
        @fwrite($handle, $time.$item.newline());
        if (!empty($var))
        {
          $out = explode("\n",print_r(str_replace("\r",'',$var),true));
          foreach ($out as $line)
            @fwrite($handle,$time.$line.newline());
        }
        fclose($handle);
      }
      else
      {
        echo str('LOGFILE_ERROR').' '.$log;
        exit;
      }
    }
  }
}

/**
 * Returns an absolute path pointing to the file specified in $fsp.
 * - If $fsp is already an absolute path, then nothing is done.
 * - if $fsp is not an absolute path, then directory $dir is added to make one.
 *
 * @param string $fsp
 * @param string $dir
 * @return string
 */

function make_abs_file( $fsp, $dir )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
  {
    if (substr($fsp,0,2) == '\\\\' || substr($fsp,1,2) == ':\\')
      return $fsp;
    else
      return realpath(str_suffix($dir,'\\').$fsp);
  }
  else
  {
    if ($fsp[0] == '/')
      return $fsp;
    else
      return realpath(str_suffix($dir,'/').$fsp);
  }
}

/**
 * Returns an array of paths from a delimited string.
 *
 * @param string $path_str
 * @return array
 */

function paths_to_array( $path_str )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return split(';',$path_str);
  else
    return split(':',$path_str);
}

/**
 * Returns the file extentsion from a given filename.
 *
 * @param string $filename
 * @return string
 */

function file_ext( $filename )
{
  return strtolower(array_pop(explode( '.' , $filename)));
}

/**
 * Returns the filename with the extentsion removed.
 *
 * @param string $filename
 * @return string
 */

function file_noext( $filename )
{
  $parts = explode( '.' , $filename);
  unset($parts[count($parts)-1]);
  return basename(implode('.',$parts));
}

/**
 * If the file specified in $filename exists, then a new name is returned that is unique, but
 * with the same file extension.
 *
 * @param string $filename
 * @return string
 */

function file_unique_name( $filename )
{
  $orig_ext = file_ext($filename);
  $orig_name = substr($filename,0,strlen($filename)-strlen($orig_ext)-1);
  $n = 1;

  // If the file already ends with "_nnnnn" then remove it.
  $suffix = array_pop(explode('_',$orig_name));
  if ( strlen($suffix) == 5 && is_numeric($suffix))
    $orig_name = substr($orig_name,0,strlen($orig_name)-strlen($suffix)-1);

  while ( file_exists($filename))
    $filename = $orig_name."_".sprintf('%05s',$n++).".".$orig_ext;

  return $filename;
}

/**
 * Returns TRUE if the given file is actually a internet address.
 *
 * @param string $filename
 * @return boolean
 */

function is_remote_file( $filename )
{
  return ( strtolower(substr($filename,0,7)) == 'http://' );
}

/**
 * Returns the parent of the given directory (slash terminated, unlike the built-in "dirname").
 *
 * @param string $dirpath
 * @return string
 */

function parent_dir( $dirpath)
{
  $dirs = explode('/',rtrim($dirpath,'/'));
  array_pop($dirs);
  return ( count($dirs) == 0 ? '' : implode('/',$dirs ).'/');
}

/**
 * Returns the size of a directory in bytes.
 * if $subdirs is true, then includes all subdirs in total.
 *
 * @param string $dir
 * @param boolean $subdirs
 * @return integer
 */

function dir_size($dir, $subdirs = false)
{
   $totalsize=0;
   if ($dirstream = @opendir($dir))
   {
     while (false !== ($filename = readdir($dirstream)))
     {
       if ($filename!="." && $filename!="..")
       {
         if (is_file($dir."/".$filename))
             $totalsize += filesize($dir."/".$filename);

         if (isdir($dir."/".$filename) && $subdirs)
             $totalsize += dir_size($dir."/".$filename, $subdirs);
       }
     }
   }
   closedir($dirstream);
   return $totalsize;
}

/**
 * Searches the given directory for the given filename (case insensitive) and if the
 * file exists then the actual case of the filename is returned. If $filename is an
 * array, then the function will search for any of the files in the array, returning
 * the first one it finds.
 *
 * @param string $dir
 * @param string $filename
 * @return string
 */
function find_in_dir($dir, $filename)
{
  $actual = '';
  if ($dh = @opendir($dir))
  {
    while ( $actual == '' && ($file = readdir($dh)) !== false )
    {
      if     ( is_string($filename) && strtolower($file) == strtolower($filename))
        $actual = $file;
      elseif ( is_array($filename) && in_array_ci(strtolower($file),$filename))
        $actual = $file;
    }
    closedir($dh);
  }

  if (empty($actual))
    return false;
  else
    return str_suffix($dir,'/').$actual;
}

/**
 * Returns all files in the specified directory that match the base filename supplied (ie: <filename>.* )
 *
 * @param path $dir
 * @param fsp $filename_noext
 * @return array of strings
 */

function find_in_dir_all_exts( $dir, $filename_noext )
{
  $matches = array();

  if ($dh = @opendir($dir))
  {
    while ( ($file = readdir($dh)) !== false )
    {
      if (file_noext($file) == $filename_noext)
        $matches[] = os_path($dir,true).$file;
    }
    closedir($dh);
  }

  return $matches;
}

/**
 * Writes the contents of a string into a file.
 *
 * @param string $filename
 * @param string $str
 * @return boolean
 */

function write_binary_file($filename, $str)
{
  $success = false;
  if ( $handle = @fopen($filename, 'wb') )
  {
     if ( fwrite($handle, $str) !== FALSE)
       $success = true;
     fclose($handle);
  }
  return $success;
}

/**
 * Writes the contents of an array which was read from a file using the file()
 * function back to a given filename.
 *
 * @param array $array
 * @param string $filename
 * @return boolean
 */

function array2file( $array, $filename)
{
  $success = false;
  $str = implode(newline(), $array);
  if ( $handle = @fopen($filename, 'wt') )
  {
     if ( fwrite($handle, $str) !== FALSE)
       $success = true;
     fclose($handle);
  }
  return $success;
}

/**
 * Updates the value of the given variable/parameter in the specified ini file.
 *
 * @param string $file
 * @param string $var
 * @param string $value
 */

function update_ini( $file, $var, $value )
{
  // Read in the file, and setup variables
  $contents = @file($file);
  $match    = strtolower($var).'=';
  $len      = strlen($match);
  $found    = false;

  // Update line containing the setting
  for ($i=0; $i<count($contents); $i++)
  {
    if ( strtolower(substr($contents[$i],0,$len)) == $match )
    {
      $contents[$i] = strtoupper($var).'='.$value;
      $found = true;
    }
    else
      $contents[$i] = rtrim($contents[$i]);
  }

  // If no match was found, then this must be a new paramter
  if (!$found)
   $contents[] = strtoupper($var).'='.$value;

  // Overwrite the existing file with the contents of the updated array
  if (! array2file($contents, $file) )
    send_to_log(1,'Error writing to INI file');
}

/**
 * Returns the correct filetype image for the given file based on the file
 * extension. If there is no image within the current style, then one from the
 * default directory is used instead.
 *
 * @param string $fsp
 * @return string - path to image
 */

function file_icon( $fsp )
{
  $ext = strtolower(file_ext($fsp));
  $filetype_icon =  SC_LOCATION.style_value("location").str_replace('XXX',strtoupper($ext),style_value('ICON_FILE_XXX'));

  if (in_array(file_ext(strtolower($fsp)), explode(',' ,ALBUMART_EXT) ))
    return $fsp;
  elseif ( file_exists($filetype_icon) )
    return $filetype_icon;
  elseif ( in_array($ext,media_exts_radio()) )
    return style_img('ICON_RADIO',true);
  elseif ( in_array($ext,media_exts_movies()) )
    return style_img('ICON_VIDEO',true);
  elseif ( in_array($ext,media_exts_music()) )
    return style_img('ICON_AUDIO',true);
  elseif ( in_array($ext,media_exts_photos()) )
    return style_img('ICON_IMAGE',true);
  else
    return style_img('ICON_UNKNOWN',true);
}

/**
 * Returns the correct directory image - If there is no image within the current
 * style, then one from the default directory is used instead.
 *
 * @return string - path to image
 */

function dir_icon()
{
  return style_img('ICON_FOLDER',true);
}

/**
 * Deletes a directory, including all contents.
 *
 * @param string $dir
 * @return boolean
 */

function force_rmdir($dir)
{
  if ( is_file($dir) )
  {
    // It's a file - so just delete it!
    unlink($dir);
  }
  else
  {
    // Recurse sub_directory first, then delete it.
    if ($dh = @opendir($dir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !='.' && $file !='..')
        {
          force_rmdir($file);
        }
      }
      closedir($dh);
      rmdir($dir);
    }
  }

  // Final check to see if it all worked.
  return file_exists($dir);
}

/**
 * Given the path to either a folder or a file, this routine will return the full path to a
 * thumbnail file based on the following (the first matching rule is used):
 *
 * FILES
 *
 * - If the file is an image file, then it will be used
 * - If an image file with the same name (but different extension) exists, then it will be used.
 * - If an icon for the filetype exists in the current style, it will be used.
 * - If an icon for the filetype exists in the default style, it will be used.
 *
 * FOLDERS
 *
 * - If a file named as specified in the "Art Files" configuration is foumd then it will be used.
 * - If an folder icon exists in the current stlye, it will be used.
 * - If an folder icon exists in the default stlye, it will be used.
 *
 * @param string $fsp
 * @return string
 */

function file_thumbnail( $fsp )
{
  $tn_image  = '';

  if (!file_exists($fsp))
  {
    send_to_log(3,"Warning : File/Directory doesn't exist in file.php:file_thumbnail",$fsp);
    $tn_image = file_icon('xxx');
  }
  else
  {
    $tn_image = file_albumart($fsp);

    if (empty($tn_image))
    {
      if (isdir($fsp) )
        $tn_image = dir_icon();
      else
        $tn_image = file_icon($fsp);
    }
  }

  return $tn_image;
}

/**
 * Given a filename of directory, this function will return the filename of the album art
 * associated with it.
 *
 * @param string:path $fsp
 * @param boolean $default_image - Should a default image be returned? Defaults to true
 * @return string:path
 */

function file_albumart( $fsp, $default_image = true )
{
  if (empty($fsp))
  {
    // No directory specified.
    return '';
  }
  elseif ( isdir($fsp) )
  {
    // Is there an image file with the same name as those listed in the configuration page?
    $return = find_in_dir($fsp, db_col_to_list("select filename from art_files"));

    // No albumart for this folder found... is there albumart for the parent folder?
    if ($return === false && dirname($fsp) != $fsp)
      $return = file_albumart(dirname($fsp));
  }
  else
  {
    $return    = '';
    if ( in_array(strtolower(file_ext($fsp)), media_exts_movies()) )
      $id3_image = db_value("select a.art_sha1 from movies m, media_art a where m.art_sha1 = a.art_sha1 and concat(m.dirname,m.filename) = '".db_escape_str($fsp)."'");
    elseif ( in_array(strtolower(file_ext($fsp)), media_exts_music()) )
      $id3_image = db_value("select a.art_sha1 from mp3s m, media_art a where m.art_sha1 = a.art_sha1 and concat(m.dirname,m.filename) = '".db_escape_str($fsp)."'");
    else
      $id3_image = null;

    if ( !empty($id3_image) )
    {
      // This file has album art contained within the ID3 tag
      $return = "select image from media_art where art_sha1='".$id3_image."'.sql";
    }
    else
    {
      // Search the directory for an image with the same name as that given, but with an image extension
      foreach ( explode(',' ,ALBUMART_EXT) as $type)
        if ( $return = find_in_dir( dirname($fsp),file_noext($fsp).'.'.$type))
          break;

      // No albumart found for this specific file.. is there albumart for the directory?
      if ($return == '')
        $return = file_albumart(dirname($fsp));

      // OK, give up! Use a standard picture based on the filetype.
      if ($return == '' && $default_image)
      {
        if ( in_array(strtolower(file_ext($fsp)), media_exts_movies()) )
          $return = style_img('MISSING_FILM_ART',true,false);
        elseif ( in_array(strtolower(file_ext($fsp)), media_exts_music()) )
          $return = style_img('MISSING_ALBUM_ART',true,false);
      }
    }
  }

  return $return;
}

/**
 * Function to download a remote file and save it to the specified location on the local
 * filesystem.
 *
 * @param URL $url - Location of the file to download
 * @param string $filename - Location to save the file to
 * @param boolean $overwrite - [Optional] Overwrite local file it is exists
 */

function file_download_and_save( $url, $filename, $overwrite = false )
{
  send_to_log(4,'Downloading remote file to the local filesystem',array("remote"=>$url, "local"=>$filename));
  if ( is_remote_file($url))
  {
    if ($overwrite || !file_exists($filename))
    {
      // Reset the timeout counter for each file downloaded
      set_time_limit(30);

      $img = @file_get_contents(str_replace(' ','%20',$url));
      if ($img !== false)
      {
        if ($out = @fopen($filename, "wb") )
        {
          @fwrite($out, $img);
          @fclose($out);
          return true;
        }
        else
          send_to_log(4,'Error : Unable to create Local file.');
      }
      else
        send_to_log(4,'Error : Unable to download remote file.');
    }
    else
    {
      send_to_log(5,'File already exists locally.');
      return true;
    }
  }
  else
    send_to_log(4,'Error : The file specified is not a remote file.');

  return false;
}

/**
 * Downloads a JPG image from the given URL and saves it into the filmart/albumart file specified
 *
 * @param URL $url
 * @param string $fsp
 * @param string $film_title (not used)
 */

function file_save_albumart( $url, $fsp, $film_title )
{
  if (!file_exists($fsp))
    file_download_and_save($url,$fsp);
}

/**
 * Returns the appropriate path delimiter based on the user's operating system.
 *
 * @return string
 */

function path_delim()
{
  if ( is_windows() )
    return '\\';
 else
    return '/';
}

/**
 * Returns the given path will all occurances of '/' and '\' changed to the appropriate
 * form for the current OS.
 *
 * @param string $path - Path to convert
 * @param boolean $addslash - [false] Adds a trailing folder delimiter
 * @return string
 */

function os_path( $dir, $addslash=false )
{
  if ( is_windows() )
  {
    $delim1 = '/';
    $delim2 = '\\';
  }
  else
  {
    $delim1 = '\\';
    $delim2 = '/';
  }

  $dir = str_replace($delim1, $delim2, $dir);
  $dir = rtrim($dir, $delim2);

  if ($addslash)
    $dir = $dir.path_delim();

  return $dir;
}

/**
 * Alias for os_path()
 *
 * @param string $dir
 * @return string
 */

function normalize_path( $dir )
{
  return os_path($dir);
}

/**
 * Returns the location of the BGRUN command
 *
 * @return string
 */

function bgrun_location()
{
  if (is_windows())
    return os_path(SC_LOCATION.'ext/bgrun/bgrun.exe');
  else
    return '';
}

/**
 * Returns the location of the WGET command
 *
 * @return string
 */

function wget_location()
{
  if (is_windows())
    return os_path(SC_LOCATION.'ext/wget/wget.exe');
  elseif (is_synology())
    return trim(exec("which wget | grep '^/' | head -1"));
  else
    return trim(shell_exec("which wget | grep '^/' | head -1"));
}

/**
 * Returns the windows directory (otherwise known as the systemroot).
 *
 * @return string
 */

function system_root()
{
  return str_replace('\\\\','\\',getenv('SystemRoot'));
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
