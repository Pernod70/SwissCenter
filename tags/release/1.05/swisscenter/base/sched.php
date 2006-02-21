<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// Runs a job in the background
//
// Note: As windows cannot execute a job in the background, it is scheduled for execution
//       in one minute using "at". (also, $days can be set to "M,T,W,Th,F,S,su" to repeat execution
//       on every day of the week).

function run_background ( $command, $days = '' )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
  {
    $soon = date('H:i',time()+70);
    if (!empty($days)) 
      $soon.= ' /every:'.$days;
      
    // Windows, so use the "Start" command to run it in another process.
    // Change recommended by Marco : http://www.swisscenter.co.uk/component/option,com_simpleboard/Itemid,42/func,view/id,29/catid,10/
    exec('at '.$soon.' CMD /C """"'.os_path($_SESSION["opts"]["php_location"]).'" "'.os_path($_SESSION["opts"]["sc_location"].$command).'""""');

  }
  else
  {
    $log = (is_null($logfile) ? '/dev/null' : os_path($_SESSION["opts"]["sc_location"].$logfile));

    // UNIX, so run with '&' to force it to the background.
    exec( '"'.os_path($_SESSION["opts"]["php_location"]).'" "'.os_path($_SESSION["opts"]["sc_location"].$command).'" > "'.$log.'" &' );
  }
}

// Schedules a job to run in the background using "at" or "cron" depending on the OS.
//
// $command  - the PHP script to run
// $every    - the frequency to repeat the command

function sched_os_add ($command, $every)
{
}

// Removes all SwissCenter related jobs from the system scheduler

function sched_os_del_all ()
{
}

// Adds a job to the internal SwissCenter scheduler

function sched_swiss_add ($command, $every)
{
}

// Removes a job from the internal SwissCenter scheduler

function sched_swiss_del ($command)
{
}

// Removes all jobs from the internal SwissCenter scheduler

function sched_swiss_del_all ()
{
}


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
