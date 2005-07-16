<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("utils.php");

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
// Routine to add a message and (optionally) the contents of a variable to the swisscenter logfile.
// NOTE: If the logfile has become more than 1Mb in size then it is archived and a new log is 
//       started. Only one generation of logs is archived (so current log and old log only)
//-------------------------------------------------------------------------------------------------

function send_to_log( $item, $var = '')
{
  if (defined('LOGFILE'))
  {
    $time = '['.date('Y.m.d H:i:s').'] ';
    
    // If the file > 1Mb then archive it and start a new log.
    if (@filesize(LOGFILE) > 1048576)
    {
      @unlink(LOGFILE.'.old');
      @rename(LOGFILE,LOGFILE.'.old');
    }
    
    // Write log entry to file.
    if ($handle = fopen(LOGFILE, 'a'))
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
      echo "Unable to write to logfile: ".LOGFILE;
      exit;
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
// OS path returns the given path with all occurances of '/' and '\' changed to the
// approrpiate form for the current OS. If $addslash is true, then a trailing slash
// or backslash is added as appropriate.
//-------------------------------------------------------------------------------------------------
function os_path( $path, $addslash=false )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
  {
    if ($addslash)
      return str_suffix(preg_replace('/\//', '\\', $path),'\\');
    else
      return preg_replace('/\//', '\\', $path);
  }
  else
  {
    if ($addslash)
      return str_suffix(preg_replace('/\//', '/', $path),'/');
    else
      return preg_replace('/\//', '/', $path);
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
  $actual='';
  if ($dh = opendir($dir))
  {
    while (($file = readdir($dh)) !== false && $acutal == '')
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
    return os_path(str_suffix($dir,'/')).$actual;
}

//-------------------------------------------------------------------------------------------------
// Writes the contents of a string int a file
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
    echo "Error saving settings";
}

//-------------------------------------------------------------------------------------------------
// Returns the correct filetype image for the given file based on the file
// extension. If there is no image within the current style, then one from the
// default directory is used instead.
//-------------------------------------------------------------------------------------------------

function file_icon( $fsp )
{
  $sc_location = SC_LOCATION;
  $name = 'filetype_'.file_ext($fsp).'.gif';

 if (in_array(file_ext(strtolower($fsp)),array('jpg','gif','png','jpeg')))
  {
    // The file is actually an image, so generate it as a thumbnail
    return $fsp;
  }
  elseif (file_exists( $sc_location.style_value("location").$name))
  {
    // There is an icon within the selected style for this filetype
    return $sc_location.style_value("location").$name;
  }
  elseif (file_exists( $sc_location.'images/'.$name))
  {
    // There is a generic icon for this filetype
    return $sc_location.'images/'.$name;
  }
  else
  {
    // Display an "unknown" filetype in the selected style, or failing that, the generic one.
    if (file_exists( $sc_location.style_value("location").'filetype_unknown.gif'))
      return $sc_location.style_value("location").'filetype_unknown.gif';
    else
      return $sc_location.'images/filetype_unknown.gif';
  }
}

//-------------------------------------------------------------------------------------------------
// Returns the correct directory image - If there is no image within the current 
// style, then one from the default directory is used instead.
//-------------------------------------------------------------------------------------------------

function dir_icon()
{
  if (file_exists( SC_LOCATION.style_value("location")."filetype_directory.gif"))
    return SC_LOCATION.style_value("location")."filetype_directory.gif";
  else
    return SC_LOCATION."images/filetype_directory.gif";
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
    send_to_log("Warning : File/Directory doesn't exist in file.php:file_thumbnail",$fsp);
    $tn_image = file_icon('xxx');
  }
  else 
  {
    $tn_image = file_albumart($fsp);

    if (empty($tn_image) && is_file($fsp) )
      $tn_image = file_icon($fsp);
    else
      $tn_image = dir_icon();      
  }

  return $tn_image;
}

//-------------------------------------------------------------------------------------------------
// Given a filename or folder, this function will return the filename of the album art associated
// with it.
//-------------------------------------------------------------------------------------------------

function file_albumart( $fsp )
{
  $return    = '';

  if ( is_file($fsp) )
  {
    $id3_image = db_value("select m.file_id from mp3s m,mp3_albumart ma where m.file_id = ma.file_id and concat(m.dirname,m.filename) = '".db_escape_str($fsp)."'");
    
    if ( !empty($id3_image) )
    {
      // This file has album art contained within the ID3 tag
      $return = 'select image from mp3_albumart where file_id='.$id3_image.'.sql';
    }
    else 
    {
      // Search the directory for an image with the same name as that given, but with an image extension
      foreach ( array('gif','jpg','jpeg','png') as $type)
        if ( $return = find_in_dir( dirname($fsp),file_noext($fsp).'.'.$type))
          break;

      // No albumart found for this specific file.. is there albumart for the directory?
      if ($return == '')
        $return = file_albumart(dirname($fsp));
    }
  }
  elseif ( is_dir($fsp) )
  {
    // Is there an image file with the same name as those listed in the configuration page?
    $return = find_in_dir($fsp, db_col_to_list("select filename from art_files"));
    
    // No albumart for this folder found... is there albumart for the parent folder?
    if ($return === false && dirname($fsp) != $fsp)
      $return = file_albumart(dirname($fsp));    
  } 

  return $return;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
