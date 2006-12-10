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
  $results['MYSQL connect']          = check_mysql_connect();

  echo serialize($results);
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
