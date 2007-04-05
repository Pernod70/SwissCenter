<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

// Runs a job in the background
//
// Note: As windows cannot execute a job in the background, it is scheduled for execution
//       in one minute using "at". (also, $days can be set to "M,T,W,Th,F,S,su" to repeat execution
//       on every day of the week).

function run_background ( $command, $days = '', $soon ='' )
{
  $php = php_cli_location();
  if ($php !== false && !empty($command))
  {
    if ( is_windows() )
    { 
      if (!empty($days)) 
      {
        // Using a schedule, so we need to use at to create the job
        $soon.= date('H:i',time()+70).' /every:'.$days;
        $cmd = 'at '.$soon.' CMD /C " "'.os_path($php).'" -c "'.os_path(SC_LOCATION.$command).'" "';
      }
      else 
      {
        // Running immediately.
		    pclose(popen('start "" "'.os_path($php).'" '.escapeshellarg(os_path(SC_LOCATION.$command)), "r"));        
      }
        
      send_to_log(4,'Executing the following command in the background:',$cmd);
      exec($cmd);
  
    }
    elseif ( is_unix() )
    {
      // Try to work out where the php.ini file lives so that we can use it in the background command.
      if (file_exists('/etc/php.ini'))
        $php_ini = '-c /etc';
      else 
        $php_ini = '';
  
      // UNIX, so run with '&' to force it to the background.
      $cmd = '"'.os_path($php).'" '.$php_ini.' "'.os_path(SC_LOCATION.$command).'" > "/dev/null" &' ;
      send_to_log(4,'Executing the following command in the background:',$cmd);
      exec($cmd);
    }
  }
}

function run_foreground ( $command )
{
  $php = php_cli_location();
  if ($php !== false && !empty($command))
  {
    // Determine the command to execute
    if ( is_windows() )
      $cmd = 'CMD /C " "'.os_path($php).'" -c "'.os_path(php_ini_location()).'" "'.os_path(SC_LOCATION.$command).'" "';
    elseif ( is_unix() )
      $cmd = '"'.os_path($php).'" -c "'.os_path(php_ini_location()).'" "'.os_path(SC_LOCATION.$command).'"' ;
    else
      return false;
      
    return syscall($cmd);
  }
  else 
  {
    return false;
  }
}

  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
