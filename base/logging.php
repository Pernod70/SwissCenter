<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/file.php'));

/**
 * Returns the path/filename of the logfile.
 *
 * @return string
 */

function logfile_location()
{
  return str_replace('\\','/',realpath(dirname(__FILE__).'/../log')).'/support.log';
}

/**
 * Routine to add a message and (optionally) the contents of a variable to the swisscenter log.
 *
 * NOTE: If the log has become more than 1Mb in size then it is archived and a new log is
 *       started. Only one generation of logs is archived (so current log and old log only)
 *
 * ERRORS
 * 1 - Information on critical errors only.
 * 2 - Information on all errors
 * 3 - Detailed information on all erorrs
 *
 * EVENTS
 * 4 - Information on important events (new mp3s, etc)
 * 5 - ALl events
 *
 * DEBUGGING INFORMATION
 * 6 - System modifications -  Files being created, system prefs, updating swisscenter, etc
 * 7 - Information sent to the hardware player
 * 8 - Maximum detail but without database related information
 *
 * EVERYTHING
 * 9 - Maximum detail, includes all SQL statements executed
 *
 * @param integer $level
 * @param string $item
 * @param mixed $var
 */

function send_to_log($level, $item, $var = '')
{
  $log_level = ( defined('LOG_MODE') && (int)LOG_MODE > 0 ? LOG_MODE : 5);

  if (!empty($item) && $log_level >= $level )
  {
    $log = logfile_location();

    if ( $log !== false )
    {
      $time = '['.date('Y.m.d H:i:s').'] ';

      // If the file > 1Mb then archive it and start a new log.
      if (@filesize($log) > 1048576)
      {
        @unlink($log.'.old');
        @rename($log,$log.'.old');
      }

      // Write log entry to file.
      if ($handle = fopen($log, 'a'))
      {
        @fwrite($handle, $time.$item.newline());
        if (!empty($var))
        {
          $out = explode("\n",print_r(str_replace("\r",'',$var),true));
          foreach ($out as $line)
            @fwrite($handle,$time.$line.newline());
        }
        fclose($handle);
      }
      else
      {
        echo str('LOGFILE_ERROR').' '.$log;
        exit;
      }
    }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
