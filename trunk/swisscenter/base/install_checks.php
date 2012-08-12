<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor

   This file contains a number of functions to check the installation of the SwissCenter software
   on a server.

   Checks still to implement
   -------------------------

   * SwissCenter configuration
     * The swisscenter files are owned by the webserver user.
     * The swisscenter files (and dirs) are R/W by the webserver user.

   * Scheduler
     * Linux   : cron is enabled for the webserver user

  *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/image.php'));
require_once( realpath(dirname(__FILE__).'/musicip.php'));
require_once( realpath(dirname(__FILE__).'/svn.php'));
require_once( realpath(dirname(__FILE__).'/imagemagick.php'));
require_once( realpath(dirname(__FILE__).'/../resources/radio/shoutcast.php'));
require_once( realpath(dirname(__FILE__).'/../resources/radio/live-radio.php'));
require_once( realpath(dirname(__FILE__).'/../resources/radio/live365.php'));
require_once( realpath(dirname(__FILE__).'/../resources/radio/icecast.php'));
require_once( realpath(dirname(__FILE__).'/../resources/radio/steamcast.php'));
require_once( realpath(dirname(__FILE__).'/../resources/radio/radiotime.php'));

#-------------------------------------------------------------------------------------------------
# PHP checks
#-------------------------------------------------------------------------------------------------

function check_php_version()
{
  send_to_log(5,'- PHP Version : '.phpversion());
  return ( version_compare(phpversion(),'5.2.6','>=') );
}

function check_php_required_modules()
{
  $modules = array_diff(get_required_modules_list(), get_loaded_extensions());
  if (!empty($modules))
  {
    send_to_log(5,"- Required PHP module '".implode(',', $modules)."' not loaded");
    return $modules;
  }
  else
    return true;
}

function check_php_suggested_modules()
{
  $modules = array_diff(get_suggested_modules_list(), get_loaded_extensions());
  if (!empty($modules))
  {
    send_to_log(5,"- Suggested PHP module '".implode(',', $modules)."' not loaded");
    return $modules;
  }
  else
    return true;
}

function check_php_ttf()
{
  $img      = new CImage();
  $defaults = array();

  // Test the currently stored preference.
  if ($img->text('Test',0,0,0,14,get_sys_pref('TTF_FONT')) !== false )
    return true;

  // No font found, so try the default locations for fonts based on the operating system
  if (is_windows())
  {
    $defaults[] = system_root().path_delim()."Fonts".path_delim()."Arial.ttf";
  }
  else
  {
    $defaults[] = "/usr/share/fonts/truetype/msttcorefonts/Arial.ttf";
    $defaults[] = 'luxisr';
  }

  // Test each default in turn.
  foreach ($defaults as $font)
  {
    if ($img->text('Test',0,0,0,14,$font) !== false )
    {
      // Font can be used!
      set_sys_pref('TTF_FONT',db_escape_str($font));
      return true;
    }
  }

  // No fonts found.
  return false;
}

#-------------------------------------------------------------------------------------------------
# MySQL checks
#-------------------------------------------------------------------------------------------------

function check_mysql_connect()
{
  # Do we have defined constants for database connectivity?
  if ( !extension_loaded('mysql') || !defined('DB_HOST') || !defined('DB_DATABASE') || !defined('DB_PASSWORD'))
  {
    send_to_log(5,"- Unable to determine database connection details");
    return false;
  }

  $db = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD );
  return ( $db ? true : false );
}

function check_mysql_version()
{
  if (check_mysql_connect())
  {
    $version = mysql_version();
    send_to_log(5,"- MySQL Version : $version");
    return version_compare($version,'5.0','>=');
  }
  else
  {
    send_to_log(5,"- Unable to connect to MySQL");
    return false;
  }
}

function check_mysql_collation()
{
  if (check_mysql_connect())
  {
    $collation = mysql_collation();
    send_to_log(5,"- MySQL Collation : $collation");
    return ($collation == 'latin1_swedish_ci');
  }
  else
  {
    send_to_log(5,"- Unable to connect to MySQL");
    return false;
  }
}

function check_mysql_charset()
{
  if (check_mysql_connect())
  {
    $charset = mysql_charset();
    send_to_log(5,"- MySQL Charset : $charset");
    return ($charset == 'latin1');
  }
  else
  {
    send_to_log(5,"- Unable to connect to MySQL");
    return false;
  }
}

function check_mysql_database_exists()
{
  if (check_mysql_connect())
  {
    send_to_log(5,"- Successfully connected to MySQL");
    $result = (test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE) == 'OK');

    if ($result)
      send_to_log(5,"- Successfully connected to the SwissCenter database.");
    else
      send_to_log(5,"- Failed to connect to the SwissCenter database.");

    return $result;
  }
  else
  {
    send_to_log(5,"- Unable to connect to MySQL");
    return false;
  }
}

#-------------------------------------------------------------------------------------------------
# Webserver checks
#-------------------------------------------------------------------------------------------------

// If the server is not Simese or Apache, then we just assume that it will work. Better to do that
// than simply report an error!

function check_web_version()
{
  if (is_server_simese())
  {
    send_to_log(5,'- Simese version : '.simese_version());
    return version_compare(simese_version(),'1.31','>=');
  }
  elseif (is_server_apache())
  {
    send_to_log(5,'- Apache version : '.apache_version());
    return version_compare(apache_version(),'2.0','>=');
  }
  else
  {
    send_to_log(5,'- Unknown webserver');
    return true;
  }
}

#-------------------------------------------------------------------------------------------------
# SwissCenter configuration
#-------------------------------------------------------------------------------------------------

function check_swiss_unviewable()
{
  $max_rank = db_toarray("select max(rank) from users u join certificates c on (u.maxcert = c.cert_id)");
  $unviewable = 0;

  foreach (db_toarray("select media_table from media_types") as $table)
  {
    $unviewable += db_value("select count(*) from $table m
                               left outer join certificates c on (m.certificate = c.cert_id)
                               join media_locations ml on (ml.location_id = m.location_id)
                               join certificates mlc on (mlc.cert_id = ml.unrated)
                             where ifnull(c.rank,mlc.rank) > $max_rank ");
  }

  return $unviewable;
}

function check_swiss_write_log_dir()
{
  return is_writeable(dirname(logfile_location()));
}

function check_swiss_write_cache_dir()
{
  return is_writeable(get_sys_pref('cache_dir'));
}

function check_swiss_write_playlist_dir()
{
  $result =  ( is_readable(get_sys_pref('playlists')) && is_writable(get_sys_pref('playlists')) );

  if (!$result)
    send_to_log(5,'- Unable to access playlist directory ('.get_sys_pref('playlists').').');

  return $result;
}

function check_swiss_ini_file()
{
  $result =  file_exists(SC_LOCATION.'/config/swisscenter.ini');

  if (!$result)
    send_to_log(5,'- Unable to access '.SC_LOCATION.'/config/swisscenter.ini');

  return $result;
}

function check_swiss_media_locations()
{
  $result =  (db_value("select count(*) from media_locations") > 0);

  if (!$result)
    send_to_log(5,'- No media locations have been defined yet.');

  return $result;
}

function check_swiss_write_rootdir()
{
  $result =  ( is_readable(SC_LOCATION) && is_writable(SC_LOCATION) );

  if (!$result)
    send_to_log(5,'- The '.SC_LOCATION.' directory is not read/write for the webserver user.');

  return $result;
}

function check_swiss_files()
{
  // Unserialize the filelist_svn.txt
  if (!file_exists(SC_LOCATION."filelist_svn.txt"))
    $file_list = svn_revision_filelist();
  else
    $file_list = unserialize(file_get_contents(SC_LOCATION.'filelist_svn.txt'));

  send_to_log(5,"- Verifying installation files:");

  // Checks may take a while so disable timeout
  set_time_limit(0);

  // Configuration and log file extensions to be ignored
  $ignore = array('conf', 'config', 'ini', 'installlog', 'installstate', 'log', 'old', 'txt');

  // Create array of all local files (excluding Bookmarks, cache, styles, and fanart folders)
  $local = dir_to_array(SC_LOCATION, '^(?!Bookmarks|cache|fanart|genres|media|playlists|styles|Thumbs.db)', 5, true);

  $data = array();
  foreach ($local as $path)
  {
    $relative_path = str_replace('\\','/',substr(realpath($path), strlen(SC_LOCATION)));
    if (!isdir($path) && !in_array(file_ext($path), $ignore))
    {
      // Mark all files for deletion until matched with SVN
      $data[$relative_path] = array("revision" => null,
                                    "error"    => "delete");
    }
  }

  // Compare the checksums of the local files.
  foreach ($file_list as $file)
  {
    // SVN filelist contains (relative_path, md5_checksum) whilst stable contains (filename, checksum)
    $path = urldecode($file["relative_path"]);
    $rev  = $file["revision"];
    $md5  = isset($file["md5_checksum"]) ? $file["md5_checksum"] : '';

    if ( !empty($md5) && !file_exists(SC_LOCATION.$path) )
    {
      // File does not exist locally
      $data[$path] = array("revision" => $rev,
                           "error"    => "missing");
      send_to_log(5,'  Not Found: '.SC_LOCATION.$path);
    }
    elseif ( !empty($md5) && $md5 !== md5_file(SC_LOCATION.$path) && !in_array(file_ext($path), $ignore) )
    {
      // File exists locally but doesn't match SVN
      $data[$path] = array("revision" => $rev,
                           "error"    => "checksum");
      send_to_log(5,'  Checksum:  '.SC_LOCATION.$path);
    }
    else
    {
      unset( $data[$path] );
    }
  }

  ksort($data);
  // Write incorrect files to filelist_missing.txt
  file_put_contents(SC_LOCATION.'filelist_missing.txt',serialize($data));

  return count($data) > 0 ? false : true;
}

function check_not_root_install()
{
  $info = stat(SC_LOCATION.'/index.php');
  send_to_log(8,'- File Stat of /index.php',$info);
  $result = ! (is_unix() && ($info[4]==0 || $info[5]==0));

  if (!$result)
    send_to_log(5,"- Swisscenter appears to have been installed as the 'root' user.");

  return $result;
}

function check_database_patch()
{
  $files = dir_to_array( SC_LOCATION.'database', 'patch_[0-9]*.sql');
  $current_version = get_sys_pref('DATABASE_PATCH',0);
  sort($files);

  $patch = str_replace('patch_','',file_noext(array_pop($files)));
  if ( $current_version < $patch )
    send_to_log(5,"- Database is not fully patched. Installed patch $current_version out of $patch.");

  return ( $patch == $current_version );
}

#-------------------------------------------------------------------------------------------------
# Scheduler
#-------------------------------------------------------------------------------------------------

function check_server_scheduler()
{
  if (is_windows())
  {
    // Windows - Is Simese > xxx or is the task scheduler service running?
    if (is_server_simese() )
    {
      if (version_compare(simese_version(),'1.31','>='))
        return true;
      else
        return false;
    }
    else
    {
      return is_task_scheduler_running();
    }
  }
  else
  {
    // Linux - So check that crontab is available for use.
    $crontab = syscall("crontab -l 2>&1");
    send_to_log(8,'Results of crontab command',$crontab);
    return ( ! (strpos($crontab,'not allowed') !== false || strpos($crontab,'do not have permission') !== false) );
  }
}

#-------------------------------------------------------------------------------------------------
# Radio Parser
#-------------------------------------------------------------------------------------------------

function check_shoutcast()
{
  $shoutcast = new shoutcast();
  $result = $shoutcast->test();
  unset($shoutcast);
  return $result;
}

function check_liveradio()
{
  $liveradio = new liveradio();
  $result = $liveradio->test();
  unset ($liveradio);
  return $result;
}

function check_live365()
{
  $live365 = new live365();
  $result = $live365->test();
  unset ($live365);
  return $result;
}

function check_icecast()
{
  $icecast = new icecast();
  $result = $icecast->test();
  unset ($icecast);
  return $result;
}

function check_steamcast()
{
  $steamcast = new steamcast();
  $result = $steamcast->test();
  unset ($steamcast);
  return $result;
}

function check_radiotime()
{
  $radiotime = new radiotime();
  $result = $radiotime->test();
  unset ($radiotime);
  return $result;
}

#-------------------------------------------------------------------------------------------------
#                                             End of file
#-------------------------------------------------------------------------------------------------
?>
