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
    system('"'.bgrun_location().'" "'.wget_location().'" -T 0 -O :null '.server_address().$url);      
  elseif ( is_unix() )
    system('"'.wget_location().'" -T 0 -O /dev/null '.server_address().$url.' &');      
  else 
    send_to_log(1,'Unable to determine which type of underlying OS we are running on.');
}


  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
