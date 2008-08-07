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
// Routine to add a message and (optionally) the contents of a variable to the swisscenter logfile.
// NOTE: If the logfile has become more than 1Mb in size then it is archived and a new log is 
//       started. Only one generation of logs is archived (so current log and old log only)
//-------------------------------------------------------------------------------------------------

function send_to_log( $item, $var = '')
{
  if (defined('LOGFILE'))
  {
    $time = '['.date('H:i:s dmY').'] ';
    
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
// Makes the given filepath acceptable to the webserver (\ become /)
//-------------------------------------------------------------------------------------------------

function make_url_path( $fsp )
{
  $parts = split('/',str_replace('\\','/',$fsp));
  for ($i=0; $i<count($parts); $i++)
    $parts[$i] = rawurlencode($parts[$i]);    

  return join('/',$parts);
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
    // Change suggested by stomper98 - "bug reports" forum. (needs testing)
    if ($addslash)
      return str_suffic(preg_replace('/\//', '/', $path),'/');
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
  return array_shift(explode( '.' , $filename));
}

//-------------------------------------------------------------------------------------------------
// Returns the parent of the given directory (slash terminated)
//-------------------------------------------------------------------------------------------------

function parent_dir( $dirpath)
{
  $dirs = explode('/',$dirpath);
  array_splice($dirs, count($dirs)-2,1);
  return implode('/',$dirs );
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

function ifile_in_dir($dir, $filename)
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
    return '';
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
  if ( $handle = fopen($filename, 'wt') )
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
  $contents = file($file);
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
  $sc_location = $_SESSION["opts"]["sc_location"];
  $name = 'filetype_'.file_ext($fsp).'.gif';
  
  if (file_exists( $sc_location.$_SESSION["opts"]["style"]["location"].$name))
  {
    return $sc_location.$_SESSION["opts"]["style"]["location"].$name;
  }
  elseif (file_exists( $sc_location.'images/'.$name))
  {
    return $sc_location.'images/'.$name;
  }
  else
  {
    if (file_exists( $sc_location.$_SESSION["opts"]["style"]["location"].'filetype_unknown.gif'))
      return $sc_location.$_SESSION["opts"]["style"]["location"].'filetype_unknown.gif';
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
  if (file_exists( $_SESSION["opts"]["sc_location"].$_SESSION["opts"]["style"]["location"]."filetype_directory.gif"))
    return $_SESSION["opts"]["sc_location"].$_SESSION["opts"]["style"]["location"]."filetype_directory.gif";
  else
    return $_SESSION["opts"]["sc_location"]."images/filetype_directory.gif";
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

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>