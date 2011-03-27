<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/

define(BASE_URL,'http://tools.assembla.com/svn/swiss/tags/');

  function file_get_contents_authenticated( $url, $username = '', $password = '')
  {
    $opts = array('http'=>array('method'=>'GET','header'=>'Authorization: Basic '.base64_encode($username.':'.$password)));
    $context = stream_context_create($opts);
    return file_get_contents($url,false,$context);
  }


  function get_urls_from_html ($string, $search )
  {
    preg_match_all ('/<a.*href="(.*'.$search.'[^"]*)"[^>]*>(.*)<\/a>/Ui', $string, &$matches);
    for ($i = 0; $i<count($matches[2]); $i++)
      $matches[2][$i] = preg_replace('/<[^>]*>/','',$matches[2][$i]);
    return $matches;
  }


  function get_page_links( $url )
  {
    $html = file_get_contents_authenticated($url);
    preg_match_all ('/<a.*href="(.*[^"]*)"[^>]*>(.*)<\/a>/Ui', $html, &$matches);

    for ($i = 0; $i<count($matches[2]); $i++)
      $matches[2][$i] = preg_replace('/<[^>]*>/','',$matches[2][$i]);

    return array_splice( $matches[1], 1, count($matches[1])-1);
  }


  function download_files ( $base_url, $dir )
  {
    if ( ! file_exists($dir) )
      mkdir ($dir);

    foreach ( get_page_links($base_url) as $url )
    {
      if ( $url[strlen($url)-1] == '/' )
        download_files( $base_url.$url, $dir.'/'.urldecode($url));
      else
      {
        if (! file_exists($dir.'/'.urldecode($url)) )
        {
          file_put_contents( $dir.'/'.urldecode($url), file_get_contents_authenticated($base_url.$url));
        }
      }
    }
  }

//*************************************************************************************************
// Routines to perform the release
//*************************************************************************************************

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

function release_display()
{
  echo "<h1>Release SwissCenter Version</h1>";

  if (empty($_REQUEST["type"]))
  {
    form_start('index.php5', 150, 'mesg');
    form_hidden('section','RELEASE');
    form_hidden('action','TAG');
    form_radio_static( 'type', 'Release Type', array('Beta'=>'beta', 'Release'=>'release'), 'beta');
    form_label('');
    form_submit('Submit');
    form_end();
  }
}

function release_tag()
{
  echo "<h1>Release SwissCenter Version</h1>";
  $type = $_REQUEST["type"];

  $html = file_get_contents_authenticated(BASE_URL.$type);
  $urls = get_urls_from_html( $html, '');
  $urls = array_slice($urls[1],1,count($urls[1])-1);
  $tag = array();

  foreach ($urls as $tagname)
    $tag[rtrim($tagname,'/')] = rtrim($tagname,'/');

  form_start('index.php5', 150, 'mesg');
  form_hidden('section','RELEASE');
  form_hidden('action','FETCH');
  form_hidden('type',$type);
  form_radio_static( 'tag', 'Available in SVN', $tag, '');
  form_label('');
  form_submit('Submit');
  form_end();
}

function release_fetch()
{
  set_time_limit(86400);
  echo "<h1>Release SwissCenter Version</h1>";
  echo '<p>Please be patient... the release is being fetched from SVN and processed in the background. This may take several minutes';

  $type   = $_REQUEST["type"];
  $tag    = $_REQUEST["tag"];
  $reldir = $_REQUEST["type"];
  $files = array();

  echo '<li>Downloading files';
  exec('mkdir /home/swisscenter/www/update/source');
  exec('rm -rf /home/swisscenter/www/update/source/*');
  download_files( BASE_URL."$type/$tag/swisscenter/", '/home/swisscenter/www/update/source' );

  echo '<li>Checksuming Files';
  chksum_files( '../source/','',$files, $dirs);

  echo '<li>Writing the filelist and last_update file';
  exec("mkdir /home/swisscenter/www/update/$reldir");
  exec("rm -rf /home/swisscenter/www/update/$reldir/*");
  write_filelist("../$reldir/filelist.txt",$files,$dirs);
  $out = fopen("../$reldir/last_update.txt", "w");
  fwrite($out, $tag);
  fclose($out);

  echo '<li>Compressing the files';
  zip_files('../source/',"../$reldir/", $files,$dirs);

  echo '<li>Creating a zipfile with the release in it';
  exec('rm -f /home/swisscenter/www/update/swisscenter.zip');
  exec('cd /home/swisscenter/www/update/source ; zip -r ../swisscenter.zip *');

  echo '<li>Copying the zipfile to the download area';
  exec('cp /home/swisscenter/www/update/swisscenter.zip /home/swisscenter/www/www/downloads/swisscenter.zip');
  exec('cp /home/swisscenter/www/update/swisscenter.zip /home/swisscenter/www/www/downloads/swisscenter_'.$tag.'.zip');

  echo '<li>Release complete';
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
