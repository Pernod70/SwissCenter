<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

  /**
   * Displays the SwissMonitor service options, including the ability to install, uninstall,
   * start and stop the service.
   *
   * @param string $msg - A success/fail message when updating lastFM settings
   */

  function swissmonitor_display( $msg='' )
  {
    echo '<p><h1>'.str('SWISSMONITOR_TITLE').'</h1><p>';
    message($msg);
    echo str('SWISSMONITOR_DESCRIPTION');

    // Test to see if .Net 2 is installed.
    if ( win_dotnet2_installed() )
    {
      form_start("index.php", 150, "lastfm_auth");
      form_hidden('section','SWISSMONITOR');
      form_hidden('action','UPDATE');
      echo '<tr><td colspan="2" align="center">';
      if (win_service_installed("SwissMonitorService"))
      {
        if (win_service_status("SwissMonitorService") == SERVICE_STARTED)
          echo form_submit_html(str('SERVICE_STOP')).' &nbsp; ';
        else
          echo form_submit_html(str('SERVICE_START')).' &nbsp; ';

        echo form_submit_html(str('SERVICE_UNINSTALL')).' &nbsp; ';
      }
      else
      {
        echo form_submit_html(str('SERVICE_INSTALL')).' &nbsp; ';
      }

      echo '</td></tr>';
      form_end();
    }
    else
      echo "<p>".str('FAIL_DOTNET2_INSTALLED');
  }

  function swissmonitor_update()
  {
    $result = false;
    $output = '';
    $monitor_exe = SC_LOCATION.'ext/SwissMonitor/SwissMonitor.exe';

    switch ($_REQUEST["submit_action"])
    {
      case str('SERVICE_STOP'):
        $result = win_service_stop("SwissMonitorService");
        break;
      case str('SERVICE_START'):
        $result = win_service_start("SwissMonitorService");
        break;
      case str('SERVICE_INSTALL'):
        send_to_log(8,"Installing SwissMonitorService service");
        @exec('%systemroot%\microsoft.net\framework\v2.0.50727\installutil -i "'.$monitor_exe.'"',$output);
        @win_service_start("SwissMonitorService");
        send_to_log(8,"Install SwissMonitorService Output",$output);
        $result = win_service_installed("SwissMonitorService");
        break;
      case str('SERVICE_UNINSTALL'):
        send_to_log(8,"Uninstalling SwissMonitorService service");
        @exec('%systemroot%\microsoft.net\framework\v2.0.50727\installutil -u "'.$monitor_exe.'"',$output);
        send_to_log(8,"Uninstall SwissMonitorService Output",$output);
        $result = ! win_service_installed("SwissMonitorService");
        break;
    }

    if ($result)
      swissmonitor_display(str('SWISSMONITOR_ACTION_SUCCESS'));
    else
      swissmonitor_display('!'.str('SWISSMONITOR_ACTION_FAILED'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
