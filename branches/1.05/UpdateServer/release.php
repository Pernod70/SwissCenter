<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  function debug($var)
  {
  	echo '<pre>';
  	print_r($var);
  	echo '</pre>';
  }
 
  function force_rmdir($dir)
  {
    if ( is_file($dir) )
      unlink($dir);
    else 
    {
      // Recurse sub_directory first, then delete it.
      if ($dh = opendir($dir))
      {
        while (($file = readdir($dh)) !== false)
          if ($file !='.' && $file !='..')
            force_rmdir($file);

        closedir($dh);
        rmdir($dir);
      }
    }

    return file_exists($dir);
  }

  function chksum_files( $pre, $dir, &$files, &$dirs )
  {
    if ($dh = opendir($pre.$dir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if (is_dir($pre.$dir.$file) && ($file) !='.' && ($file) !='..')
        {
          $dirs[] = array('directory'=>$dir.$file);
          chksum_files( $pre, $dir.$file.'/', $files, $dirs);
        }
        elseif (is_file($pre.$dir.$file) && $file !='.' && $file !='..' && $file != 'Thumbs.db')
        {
          $files[] = array('filename'=>$dir.$file
                          ,'checksum'=> md5(file_get_contents($pre.$dir.$file)) );
        }
      }
      closedir($dh);
    }
  }

  function write_filelist( $filename, $files, $dirs)
  {
    $out = fopen($filename, "w");
    fwrite($out, serialize(array("dirs"=>$dirs,"files"=>$files)) );
    fclose($out);
  }

  function zip_files($from, $to, $files, $dirs)
  {
     foreach ($files as $fsp)
     {
     // Compress the file
      $file_contents = file_get_contents($from.$fsp["filename"]);
       
      if ( $file_contents === false)
        echo "Unable to read contents of file : ".$fsp["filename"];
      else
      {
        $str = gzcompress($file_contents);
        if ($str === false)
          echo "Unable to compress file : ".$fsp["filename"];
        else
        {
          $tmp_file = $to.md5( $fsp["filename"].$fsp["checksum"]).'.bin';
          $out = fopen($tmp_file, "w");
          fwrite($out, $str);
          fclose($out);
        }
      }
    }
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  if (empty($_REQUEST["version"]))
  {
  	echo '<p>Please enter the version number for this release:
  	      <dir>
  	      <form enctype="multipart/form-data" action="release.php" method="post">
  	      <input size=10 name="version" value=""> &nbsp;
  	      <input type="submit" value=" Go ">
  	      </form>
  	      </dir>
  	      ';
  }
  else 
  {
    set_time_limit(86400);
    @force_rmdir('release');
    @mkdir('release');
    
    $files = array();
    echo '<h1>Releasing version '.$_REQUEST["version"].'</h1>';
    echo '<p>Checksuming Files';
    chksum_files( 'source/','',$files, $dirs);
    echo '<p>Writing the filelist';
    write_filelist('release/filelist.txt',$files,$dirs);
    echo '<p>Writing the last update file';
    $out = fopen('release/last_update.txt', "w");
    fwrite($out, $_REQUEST["version"]);
    fclose($out);
    echo '<p>Compressing the files';
    zip_files('source/','release/',$files,$dirs);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
