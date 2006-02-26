<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

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
    // Change recommended by Marco : http://www.swisscenter.co.uk/component/option,com_simpleboard/Itemid,42/func,view/id,29/catid,10/
    exec('at '.$soon.' CMD /C """"'.os_path(PHP_LOCATION).'" "'.os_path(SC_LOCATION.$command).'""""');

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
    exec( '"'.os_path(PHP_LOCATION).'" '.$php_ini.' "'.os_path(SC_LOCATION.$command).'" > "'.$log.'" &' );
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
