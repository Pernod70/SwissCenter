<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/utils.php'));

/**
 * Wrapper for PHP filesystem functions
 */
class Fsw {

  /**
   * Returns the filename for use with filesystem functions
   */
  static function setName ($file) {
    if (!is_remote_file($file) && DIRECTORY_SEPARATOR=="\\") {
      $file=decode_utf8($file);
      $file=str_replace('?', '_', $file);
    }
    return $file;
  }

  /**
   * Encodes the filename returned by filesystem functions
   */
  static function getName ($file) {
    if (DIRECTORY_SEPARATOR=="\\") {
      $file=encode_utf8($file);
    }
    return $file;
  }


  static function file_exists ($filename) {
    return file_exists(self::setName($filename));
  }

  static function file_get_contents ($filename, $use_include_path = false) {
    return file_get_contents(self::setName($filename), $use_include_path);
  }

  static function file_put_contents ($filename, $data) {
    return file_put_contents(self::setName($filename), $data);
  }

  static function file ($filename) {
    return file(self::setName($filename));
  }

  static function fileinode ($filename) {
    return fileinode(self::setName($filename));
  }

  static function filemtime ($filename) {
    return filemtime(self::setName($filename));
  }

  static function filesize ($filename) {
    return filesize(self::setName($filename));
  }

  static function fopen ($filename, $mode) {
    return fopen(self::setName($filename), $mode);
  }

  static function is_file ($filename) {
    return is_file(self::setName($filename));
  }

  static function is_readable ($filename) {
    return is_readable(self::setName($filename));
  }

  static function is_writable ($filename) {
    return is_writable(self::setName($filename));
  }

  static function opendir ($dirname) {
    return opendir(self::setName($dirname));
  }

  static function parse_ini_file ($filename, $process_sections = false) {
    return parse_ini_file(self::setName($filename), $process_sections);
  }

  static function readdir ($dir_handle) {
    $file=readdir($dir_handle);
    if ($file) {
      // check if file is found before converting it's
      // name or we will convert bool(false) to string
      $file=self::getName($file);
    }
    return $file;
  }

  static function rmdir ($dirname) {
    return rmdir(self::setName($dirname));
  }

  static function touch ($filename, $time = null) {
    if (is_null($time)) $time = time();
    return touch(self::setName($filename), $time);
  }

  static function unlink ($filename) {
    return unlink(self::setName($filename));
  }
}

/**
 * Replacement function for is_dir() which returns true if the path specified is
 * a directory OR a valid drive/share/mount.
 *
 * @param string $fsp
 * @return boolean
 */

function isdir( $fsp )
{
  $dh = @Fsw::opendir($fsp);
  if ($dh !== false)
  {
    closedir($dh);
    return true;
  }
  else
    return false;
}

/**
 * A function to get around the limitation that some versions of PHP only have
 * support for 32-bit integers and therefore cannot return the size of a file > 2Gb.
 * This workaround uses the web server to determine the size and return it in the
 * header Content-Length.
 *
 * @param string $fsp
 * @return float
 */

function large_filesize( $fsp )
{
  if ( Fsw::file_exists($fsp) )
  {
    $server  = server_address();
    $headers = get_headers( $server.make_url_path($fsp), 1 );
    $filesize = (float)$headers["Content-Length"];
    return $filesize;
  }
  else
    return false;
}

/**
 * Returns the correct line ending for files depending on the host OS.
 *
 * @return string
 */

function newline()
{
  if ( is_windows() )
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
  $dir = encode_utf8(os_path($dir,true));
  $contents = array();
  $dh = @Fsw::opendir($dir);
  if ($dh !== false)
  {
    while (($file = readdir($dh)) !== false)
    {
      // Ensure filename read from filesystem is UTF-8 encoded
      $file = encode_utf8($file);

      // Does file/folder match pattern?
      if ( preg_match('/'.$pattern.'/', $file) )
      {
        if ( (isdir($dir.$file) && ($opts & DIR_TO_ARRAY_SHOW_DIRS)) ||
           (Fsw::is_file($dir.$file) && ($opts & DIR_TO_ARRAY_SHOW_FILES)) )
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
 * Returns the bookmark filename (for resume playing) for the given filename (DIRNAME.FILENAME).
 *
 * @param string $fsp
 * @return string
 */

function bookmark_file( $fsp )
{
  return SC_LOCATION.'config/Bookmarks/'.strtoupper(md5('/'.ucfirst($fsp))).'.dat';
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
  if ( is_windows() )
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
  if ( is_windows() )
    return explode(';', $path_str);
  else
    return explode(':', $path_str);
}

/**
 * Returns the file extension from a given filename.
 *
 * @param string $filename
 * @return string
 */

function file_ext( $filename )
{
  $parts = explode('.', $filename);
  return strtolower(array_pop($parts));
}

/**
 * Returns the filename with the extension removed.
 *
 * @param string $filename
 * @return string
 */

function file_noext( $filename )
{
  $parts = explode( '.', $filename);
  unset($parts[count($parts)-1]);
  return basename(implode('.', $parts));
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
  $orig_name = mb_substr($filename,0,mb_strlen($filename)-mb_strlen($orig_ext)-1);
  $n = 1;

  // If the file already ends with "_nnnnn" then remove it.
  $suffix = array_pop(explode('_',$orig_name));
  if ( mb_strlen($suffix) == 5 && is_numeric($suffix))
    $orig_name = mb_substr($orig_name,0,mb_strlen($orig_name)-mb_strlen($suffix)-1);

  while ( Fsw::file_exists($filename))
    $filename = $orig_name.'_'.sprintf('%05s',$n++).'.'.$orig_ext;

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
  $filename = strtolower($filename);
  if (substr($filename,0,7) == 'http://' || substr($filename,0,8) == 'https://')
    return true;
  else
    return false;
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
   $dir = encode_utf8($dir);
   $totalsize = 0;
   $dh = @Fsw::opendir($dir);
   if ($dh !== false)
   {
     while (false !== ($filename = readdir($dh)))
     {
       // Ensure filename read from filesystem is UTF-8 encoded
       $filename = encode_utf8($filename);

       if ($filename != '.' && $filename != '..')
       {
         if (Fsw::is_file($dir."/".$filename))
             $totalsize += filesize($dir."/".$filename);

         if (isdir($dir."/".$filename) && $subdirs)
             $totalsize += dir_size($dir."/".$filename, $subdirs);
       }
     }
   }
   closedir($dh);
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
  $dir = encode_utf8($dir);
  $actual = '';
  $dh = @Fsw::opendir($dir);
  if ($dh !== false )
  {
    while ( $actual == '' && ($file = readdir($dh)) !== false )
    {
      // Ensure filename read from filesystem is UTF-8 encoded
      $file = encode_utf8($file);

      if     ( is_string($filename) && mb_strtolower($file) == mb_strtolower($filename))
        $actual = $file;
      elseif ( is_array($filename) && in_array_ci(mb_strtolower($file),$filename))
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
  $dir = encode_utf8($dir);
  $matches = array();
  $dh = @Fsw::opendir($dir);
  if ($dh !== false)
  {
    while ( ($file = readdir($dh)) !== false )
    {
      // Ensure filename read from filesystem is UTF-8 encoded
      $file = encode_utf8($file);

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
  $fh = @Fsw::fopen($filename, 'wb');
  if ( $fh !== false )
  {
    if ( fwrite($fh, $str) !== false)
      $success = true;
    fclose($fh);
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
  $fh = @Fsw::fopen($filename, 'wt');
  if ( $fh !== false )
  {
    if ( fwrite($fh, $str) !== false)
      $success = true;
    fclose($fh);
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
  $contents = @Fsw::file($file);
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
  $filetype_icon = SC_LOCATION.style_value("location").str_replace('XXX',strtoupper($ext),style_value('ICON_FILE_XXX'));

  if (in_array(file_ext(strtolower($fsp)), explode(',' ,ALBUMART_EXT) ))
    return $fsp;
  elseif ( Fsw::file_exists($filetype_icon) )
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
  if ( Fsw::is_file($dir) )
  {
    // It's a file - so just delete it!
    Fsw::unlink($dir);
  }
  else
  {
    // Recurse sub_directory first, then delete it.
    $dh = @Fsw::opendir($dir);
    if ($dh !== false)
    {
      while (($file = readdir($dh)) !== false)
      {
        if ($file !='.' && $file !='..')
        {
          force_rmdir($file);
        }
      }
      closedir($dh);
      Fsw::rmdir($dir);
    }
  }

  // Final check to see if it all worked.
  return Fsw::file_exists($dir);
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
  $tn_image = '';

  if (!Fsw::file_exists($fsp))
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

function file_albumart( $fsp, $default_image = true, $folder_image = true )
{
  if (empty($fsp))
  {
    // No directory specified.
    return '';
  }
//  elseif ((0 == strncmp($fsp, '\\', 2)) && (strrpos(dirname($fsp), '/') == 1))
//  {
//    // Its a UNC path with just a hostname
//    return '';
//  }
  elseif ( isdir($fsp) )
  {
  	$return = '';
    // Is there an image file with the same name as those listed in the configuration page?
    if ($folder_image)
      $return = find_in_dir($fsp, db_col_to_list("select filename from art_files"));  

    // No albumart for this folder found... is there albumart for the parent folder?
    if (empty($return) && dirname($fsp) != $fsp)
      $return = file_albumart(dirname($fsp), $default_image, $folder_image);
  }
  else
  {
    $return = '';
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
        if ( ($return = find_in_dir( dirname($fsp),file_noext($fsp).'.'.$type)) !== false )
          break;

      // No albumart found for this specific file.. is there albumart for the directory?
      if (empty($return) && dirname($fsp) != $fsp)
        $return = file_albumart(dirname($fsp), $default_image, $folder_image);

      // OK, give up! Use a standard picture based on the filetype.
      if (empty($return) && $default_image)
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

function file_download_and_save( $url, $filename, $overwrite = false, $modified = false )
{
  send_to_log(4,'Downloading remote file to the local filesystem',array("remote"=>$url, "local"=>$filename));
  if ( is_remote_file($url))
  {
    if ($overwrite || !Fsw::file_exists($filename))
    {
      // Reset the timeout counter for each file downloaded
      set_time_limit(60);

      $img = @file_get_contents(str_replace(' ','%20',$url));
      if ($img !== false)
      {
        $fh = @Fsw::fopen($filename, 'wb');
        if ($fh !== false)
        {
          @fwrite($fh, $img);
          @fclose($fh);

          // Set modified date on downloaded file
          if ( $modified )
            Fsw::touch($filename, $modified);

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
  if (!Fsw::file_exists($fsp))
    file_download_and_save($url,$fsp);
}

/**
 * Returns the appropriate path delimiter based on the user's operating system.
 *
 * @return string
 */

function path_delim()
{
  return DIRECTORY_SEPARATOR;
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
  $dir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $dir);
  $dir = rtrim($dir, DIRECTORY_SEPARATOR);

  if ($addslash)
    $dir = $dir.DIRECTORY_SEPARATOR;

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
  if ( is_windows() )
    return os_path(SC_LOCATION.'ext/bgrun/bgrun.exe');
  else
    return '';
}

/**
 * Returns the location of the WGET command
 *
 * @return string
 */

function wget_location($system_setting = true)
{
  if ( $system_setting )
    return get_sys_pref('WGET_PATH', wget_location(false));
  else
  {
    if ( is_windows() )
      return os_path(SC_LOCATION.'ext/wget/wget.exe');
    elseif ( is_synology() )
      return trim(exec("which wget | grep '^/' | head -1"));
    else
      return trim(shell_exec("which wget | grep '^/' | head -1"));
  }
}

/**
 * Returns the version of the WGET command
 *
 * @return string
 */

function wget_version()
{
  if ( Fsw::is_file(wget_location()) )
  {
    $cmd = wget_location().' --version';
    ob_start();
    passthru($cmd);
    $output = ob_get_contents();
    ob_end_clean();
    return preg_get('/Wget ([0-9.]+)/i', $output);
  }
  else
    return '';
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

/**
 * Generate Apache 2.2 ETag.
 *
 * @param $filename
 * @return string
 */

function file_etag($filename)
{
  // Inode
  $ETag = dechex(Fsw::fileinode($filename));
  // Size
  $ETag.= "-".dechex(Fsw::filesize($filename));
  // Modification time in useconds & (2^33-1)
  $ETag.= "-".dechex(((Fsw::filemtime($filename).str_repeat("0",6)+0) & (8589934591)));
  return $ETag;
}

/**
 * Makes a filename safe by replacing reserved characters.
 *
 * @param $filename
 * @return string
 */

function filename_safe($filename)
{
  $reserved = array('<', '>', ':', '"', '/', '\\', '|', '?', '%', '*', '__');
  $replace  = '_';
  $filename = str_replace($reserved, $replace, $filename);
  return $filename;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
