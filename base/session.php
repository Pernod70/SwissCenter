<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// If the current page has a session_id parameter, then this will be used to "share" a session.
if (isset($_REQUEST["session_id"]) && !empty($_REQUEST["session_id"]))
  session_id($_REQUEST["session_id"]);

// Disable any additional cache headers being sent by PHP.
session_cache_limiter( FALSE );

@session_start();
ini_set("session.gc_maxlifetime", "86400"); // Set session timeout to 1 day
ob_start();

function current_session()
{
  if (isset($_COOKIE["PHPSESSID"]))
    return 'session_id='.$_COOKIE["PHPSESSID"];
  else
    return 'session_id='.substr(SID,strpos(SID,'=')+1);
}

/**
 * Simple routine to set preformatted text and recursively output the contents or a variable or
 * array for debugging purposed
 *
 * @return unknown_type
 */
function dump()
{
  for ($i=0;$i<@func_num_args();$i++)
  {
    echo "<pre>";
    print_r(@func_get_arg($i));
    echo "</pre>";
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
