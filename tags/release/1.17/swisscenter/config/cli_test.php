<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/install_checks.php'));

  send_to_log(5,'Command Line PHP Tests...');

  $results = array();
  $results['PHP version']            = check_php_version();
  $results['PHP required mods']      = check_php_required_modules();
  $results['PHP suggested mods']     = check_php_suggested_modules();
  $results['PHP ini file']           = check_php_ini_location();
  $results['MYSQL version']          = check_mysql_version();
  $results['MYSQL connect']          = check_mysql_connect();
  $results['MYSQL database']         = check_mysql_database_exists();
  $results['SWISS ini file']         = check_swiss_ini_file();
  $results['SWISS write log']        = check_swiss_write_log_dir();
  $results['SWISS write root']       = check_swiss_write_rootdir();
  $results['SWISS root install']     = check_not_root_install();
  $results['SWISS media locs']       = check_swiss_media_locations();
  $results['SERVER scheduler']       = check_server_scheduler();
  $results['MUSICIP api']            = musicip_available();
  $results['MUSICIP mixable']        = (musicip_mixable_percent() >= 50);

  echo serialize($results);
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
