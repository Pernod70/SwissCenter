<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/server.php'));
require_once( realpath(dirname(__FILE__).'/sched.php'));

//-------------------------------------------------------------------------------------------------
// Causes an immediate refresh of the media database
//-------------------------------------------------------------------------------------------------

function media_refresh_now()
{
  if (is_server_simese() && simese_version() >= 1.31)
  {
    $dir = SC_LOCATION.'config/simese';

    if ( !file_exists($dir) )
      mkdir($dir);
    
    if (is_dir($dir))
      write_binary_file($dir.'/Simese.ini',"MediaRefresh=Now");
  }
  else
  	run_background('media_search.php');
}

//-------------------------------------------------------------------------------------------------
// If the server is anything other than Simese, then we use the bacground scheduler (such as "at"
// or "cron" to schedule a media refresh)
//-------------------------------------------------------------------------------------------------

function media_schedule_refresh($schedule, $time)
{
  // Managing the Simese scheduler is best done in Simese, not by the SwissCenter
  if (!is_server_simese())
    run_background('media_search.php',$schedule, $time);
}


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
