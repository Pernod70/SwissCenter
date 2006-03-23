<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/file.php'));

// Runs a job in the background
//
// Note: As windows cannot execute a job in the background, it is scheduled for execution
//       in one minute using "at". (also, $days can be set to "M,T,W,Th,F,S,su" to repeat execution
//       on every day of the week).

function run_background ( $command, $days = '', $soon ='' )
{
  if ( is_windows() )
  {
    if (empty($soon))
      $soon = date('H:i',time()+70);

    if (!empty($days)) 
      $soon.= ' /every:'.$days;
      
    // Windows, so use the "Start" command to run it in another process.
    $cmd = 'at '.$soon.' CMD /C """"'.os_path( php_cli_location() ).'" "'.os_path(SC_LOCATION.$command).'""""';
    send_to_log('Executing the following command in the background:',$cmd);
    exec($cmd);

  }
  elseif ( is_unix() )
  {
    $log = (is_null($logfile) ? '/dev/null' : os_path(SC_LOCATION.$logfile));
    
    // Try to work out where the php.ini file lives so that we can use it in the background command.
    if (file_exists('/etc/php.ini'))
      $php_ini = '-c /etc';
    else 
      $php_ini = '';

    // UNIX, so run with '&' to force it to the background.
    $cmd = '"'.os_path( php_cli_location() ).'" '.$php_ini.' "'.os_path(SC_LOCATION.$command).'" > "'.$log.'" &' ;
    send_to_log('Executing the following command in the background:',$cmd);
    exec($cmd);
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
