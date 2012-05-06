<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

// Runs a job in the background

function run_background ( $url )
{
  if (empty($url))
    return;

  send_to_log(4,'Executing (via wget) URL in the background: '.$url);

  if ( is_windows() )
  {
    // Note: The "c: &" proceeding the command and ">NUL 2>&1" afterwards is a little trick for
    //       windows that allows you to specify multiple sets of double quotes in a command.
    $cmd = '"'.bgrun_location().'" "'.wget_location().'" -T 0 -O :null '.server_address().$url;
    exec("c: & ".$cmd." >NUL 2>&1");
  }
  elseif ( is_unix() )
  {
    $cmd = '"'.wget_location().'" -T 0 -O /dev/null '.server_address().$url;
    system($cmd." > /dev/null &");
  }
  else
    send_to_log(1,'Unable to determine which type of underlying OS we are running on.');

  send_to_log(8,"Command line executed:",$cmd);
}


  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
