<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/utils.php'));

//-------------------------------------------------------------------------------------------------
// A function to get around the limitation that some versions of PHP on linux machines only have
// support for 32-bit integers and therefore cannot return the size of a file > 2Gb
//-------------------------------------------------------------------------------------------------

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

//-------------------------------------------------------------------------------------------------
// Returns the correct line ending for files depending on the host OS.
//-------------------------------------------------------------------------------------------------

function newline()
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return "\r\n";
  else 
    return "\n";
}

//-------------------------------------------------------------------------------------------------
// Returns an array containing the names of all files and subdirectories within the specified
// directory (returns an empty array if the specified directory does not exist or is not readable)
//-------------------------------------------------------------------------------------------------

define ('DIR_TO_ARRAY_SHOW_FILES',1);
define ('DIR_TO_ARRAY_SHOW_DIRS', 2);
define ('DIR_TO_ARRAY_FULL_PATH',4);

function dir_to_array ($dir, $pattern = '.*', $opts = 7 )
{
  $dir = os_path($dir,true);

  $contents = array();
  if ($dh = opendir($dir))
  {
    while (($file = readdir($dh)) !== false)
    {
      if ( preg_match('/'.$pattern.'/',$file) && 
           (  (is_dir($dir.$file)  && ($opts & DIR_TO_ARRAY_SHOW_DIRS))
           || (is_file($dir.$file) && ($opts & DIR_TO_ARRAY_SHOW_FILES)) ) )
      {
        if ($opts & DIR_TO_ARRAY_FULL_PATH)
          $contents[] = os_path($dir.$file);
        else 
          $contents[] = $file;
      }
    }
    closedir($dh);
  }
  
  sort($contents);
  return $contents;
}

//-------------------------------------------------------------------------------------------------
// Returns the path/filename of the logfile.
//-------------------------------------------------------------------------------------------------

function logfile_location()
{
  return str_replace('\\','/',realpath(dirname(__FILE__).'/../log')).'/support.log';
}

//-------------------------------------------------------------------------------------------------
// Returns the bookmark filename (for resume playing) for the given filename (DIRNAME.FILENAME)
//-------------------------------------------------------------------------------------------------

function bookmark_file( $fsp )
{
  return SC_LOCATION."config/bookmarks/".md5("/".ucfirst($fsp)).".dat";
}

//-------------------------------------------------------------------------------------------------
// Routine to add a message and (optionally) the contents of a variable to the swisscenter log.
//
// NOTE: If the logv has become more than 1Mb in size then it is archived and a new log is 
//       started. Only one generation of logs is archived (so current log and old log only)
//
// ERRORS
// 1 - Information on critical errors only.
// 2 - Information on all errors
// 3 - Detailed information on all erorrs
//
// EVENTS
// 4 - Information on important events (new mp3s, etc)
// 5 - ALl events
//
// DEBUGGING INFORMATION
// 6 - System modifications -  Files being created, system prefs, updating swisscenter, etc
// 7 - Information sent to the hardware player
// 8 - Maximum detail but without database related information
//
// EVERYTHING
// 9 - Maximum detail, includes all SQL statements executed
//-------------------------------------------------------------------------------------------------

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
          $out = explode("\n",print_r($var,true));
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

//-------------------------------------------------------------------------------------------------
// Returns an absolute path pointing to the file specified in $fsp.
// - If $fsp is already an absolute path, then nothing is done.
// - if $fsp is not an absolute path, then directory $dir is added to make one.
//-------------------------------------------------------------------------------------------------

function make_abs_file( $fsp, $dir )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
  {
    if (substr($fsp,1,2) == ':\\')
      return $fsp;
    else
      return str_suffix($dir,'\\').$fsp;
  }
  else
  {
    if ($fsp[0] == '/')
      return $fsp;
    else
      return str_suffix($dir,'/').$fsp;
  }
}

//-------------------------------------------------------------------------------------------------
// Returns the file extentsion from a given filename
//-------------------------------------------------------------------------------------------------
function paths_to_array( $path_str )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return split(';',$path_str);
  else
    return split(':',$path_str);
}

//-------------------------------------------------------------------------------------------------
// Returns the file extentsion from a given filename
//-------------------------------------------------------------------------------------------------

function file_ext( $filename )
{
  return strtolower(array_pop(explode( '.' , $filename)));
}

//-------------------------------------------------------------------------------------------------
// Returns the filename with the extentsion removed
//-------------------------------------------------------------------------------------------------

function file_noext( $filename )
{
  $parts = explode( '.' , $filename);
  unset($parts[count($parts)-1]);
  return basename(implode('.',$parts));
}

//-------------------------------------------------------------------------------------------------
// Returns TRUE if the given file is actually a internet address.
//-------------------------------------------------------------------------------------------------

function is_remote_file( $filename )
{
  return ( strtolower(substr($filename,0,7)) == 'http://' );
}

//-------------------------------------------------------------------------------------------------
// Returns the parent of the given directory (slash terminated, unlike the built-in "dirname").
//-------------------------------------------------------------------------------------------------

function parent_dir( $dirpath)
{
  $dirs = explode('/',rtrim($dirpath,'/'));
  array_pop($dirs);
  return ( count($dirs) == 0 ? '' : implode('/',$dirs ).'/');
}

//-------------------------------------------------------------------------------------------------
// Returns the size of a directory in bytes.
// if $subdirs is true, then includes all subdirs in total.
//-------------------------------------------------------------------------------------------------

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

         if (is_dir($dir."/".$filename) && $subdirs)
             $totalsize += dir_size($dir."/".$filename, $subdirs);
       }
     }
   }
   closedir($dirstream);
   return $totalsize;
}

//-------------------------------------------------------------------------------------------------
// Searches the given directory for the given filename (case insensitive) and if the
// file exists then the actual case of the filename is returned. If $filename is an
// array, then the function will search for any of the files in the array, returning
// the first one it finds.
//-------------------------------------------------------------------------------------------------

function find_in_dir($dir, $filename)
{
  $actual = '';
  if ($dh = opendir($dir))
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

//-------------------------------------------------------------------------------------------------
// Writes the contents of a string into a file
//-------------------------------------------------------------------------------------------------

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

//-------------------------------------------------------------------------------------------------
// Writes the contents of an array which was read from a file using the file()
// function back to a given filename.
//-------------------------------------------------------------------------------------------------

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

//-------------------------------------------------------------------------------------------------
// Updates the value of the given variable/parameter in the specified ini file.
//-------------------------------------------------------------------------------------------------

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

//-------------------------------------------------------------------------------------------------
// Returns the correct filetype image for the given file based on the file
// extension. If there is no image within the current style, then one from the
// default directory is used instead.
//-------------------------------------------------------------------------------------------------

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

//-------------------------------------------------------------------------------------------------
// Returns the correct directory image - If there is no image within the current 
// style, then one from the default directory is used instead.
//-------------------------------------------------------------------------------------------------

function dir_icon()
{
  return style_img('ICON_FOLDER',true);
}

//-------------------------------------------------------------------------------------------------
// Deletes a directory, including all contents.
//-------------------------------------------------------------------------------------------------

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
    if ($dh = opendir($dir))
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

//-------------------------------------------------------------------------------------------------
// Given the path to either a folder or a file, this routine will return the full path to a
// thumbnail file based on the following (the first matching rule is used):
//
// FILES
//
// - If the file is an image file, then it will be used
// - If an image file with the same name (but different extension) exists, then it will be used.
// - If an icon for the filetype exists in the current style, it will be used.
// - If an icon for the filetype exists in the default style, it will be used.
//
// FOLDERS
//
// - If a file named as specified in the "Art Files" configuration is foumd then it will be used.
// - If an folder icon exists in the current stlye, it will be used.
// - If an folder icon exists in the default stlye, it will be used.
//-------------------------------------------------------------------------------------------------

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
      if (@is_dir($fsp) )
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
 * @param boolean $default_imgage - Should a default image be returned? Defaults to true
 * @return string:path
 */

function file_albumart( $fsp, $default_imgage = true )
{
  if (empty($fsp))
  {
    // No directory specified.
    return '';
  }
  elseif ( @is_dir($fsp) )
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
    $id3_image = db_value("select m.file_id from mp3s m,mp3_albumart ma where m.file_id = ma.file_id and concat(m.dirname,m.filename) = '".db_escape_str($fsp)."'");
    
    if ( !empty($id3_image) )
    {
      // This file has album art contained within the ID3 tag
      $return = 'select image from mp3_albumart where file_id='.$id3_image.'.sql';
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
      if ($return == '' && $default_imgage)
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
      $img = file_get_contents(str_replace(' ','%20',$url));
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
      send_to_log(4,'Error : Local file exists (overwrite option not specified).');
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
 * fom for the current OS. 
 *
 * @param string $path - Path to convert
 * @param boolean $addslash - [false] Adds a trailing folder delimiter
 * @return string
 */

function os_path( $path, $addslash=false )
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
 * Alternative alias for os_path()
 */

function normalize_path( $dir )
{ 
  return os_path($dir); 
}

/**
 * Returns the location of the PHP.INI file
 *
 * @return string
 */

function  php_ini_location()
{
  $location = false;

  // Fetch phpinfo text
  ob_start();
  phpinfo(INFO_GENERAL);
  $text = ob_get_contents();
  ob_end_clean();
  
  // Process the output depending on what format it is in.
  if ( strpos( $text, '<!DOCTYPE html') !== false)
  {  
    // HTML format
    preg_match('#php.ini.*<td class="v">(.*?)<#',$text,$matches);
    if (!empty($matches[1]))
      $location = trim($matches[1]);
  }
  else 
  { 
    // Text format
    preg_match('#php.ini.*=>(.*)\n#',$text,$matches);
    if (!empty($matches[1]))
      $location = trim($matches[1]);
  }

  // fix for PHP 5.x.x parsing
  if ( strpos($location, 'php.ini') == false)
  {
    $location = $location.path_delim().'php.ini';
  }
  
  return $location; 
}

/**
 * Returns the location of the PHP CLI executable
 *
 * @return string
 */

function php_cli_location()
{
  if ( is_windows() )
  { 
    // fix for PHP 5.x.x or of own PHP installation is used
    $location = getenv("ORIG_SCRIPT_FILENAME"); 
    if ( !empty($location))
      return $location;

    $location = $_SERVER["SCRIPT_FILENAME"];
    if ( !empty($location))
    {
      if (file_exists( dirname($location).'/cli/php.exe'))
        return str_replace('\\\\','\\',dirname($location).'\\cli\\php.exe');
      else 
        return str_replace('\\\\','\\',$location);
    }
    
    // Unable to find a cli location
    return false;
  }
  else
  {
    $location = trim(shell_exec("which php php4 | grep '^/' | head -1"));
    if (empty($location) || strpos($location,'no php') !== false)
      return false;
    else 
      return $location;
  }
}

/**
 * Returns the windows directory (otherwise known as the systemroot).
 *
 * @return string
 */

function system_root()
{
  return $_ENV['SystemRoot'];
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
