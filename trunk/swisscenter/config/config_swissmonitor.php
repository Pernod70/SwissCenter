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

  function swissmonitor_display( $msg = '', $edit_id = '')
  {
    echo '<p><h1>'.str('SWISSMONITOR_TITLE').'</h1><p>';
    message($msg);
    echo str('SWISSMONITOR_DESCRIPTION');

    // Test to see if .Net 2 is installed.
    if ( win_dotnet2_installed() )
    {
      form_start('index.php', 150, 'service');
      form_hidden('section','SWISSMONITOR');
      form_hidden('action','SERVICE');
      echo '<tr><td colspan="2" align="center">';
      if (win_service_installed("SwissMonitorService"))
      {
        if (win_service_status("SwissMonitorService") == SERVICE_STARTED)
        {
          echo form_submit_html(str('SERVICE_RESTART')).' &nbsp; ';
          echo form_submit_html(str('SERVICE_STOP')).' &nbsp; ';
        }
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

      // Parse the SwissMonitor config
      $config  = SC_LOCATION.'ext/SwissMonitor/SwissMonitor.exe.config';
      $doc = new DOMDocument();
      $doc->load($config);
      $xpath = new DOMXPath($doc);

      $data = array();
      foreach ($xpath->query('/configuration/swissMonitor/ignoredExtensions/add') as $node)
      {
        $ext    = ltrim($node->getAttribute("extension"), '.');
        $data[] = array('EXT'=>$ext, 'NAME'=>$ext);
      }
      $simese      = $xpath->query('/configuration/swissMonitor/simese');
      $swisscenter = $xpath->query('/configuration/swissMonitor/swissCenter');

      // Display ignored extensions
      echo '<p><h1>'.str('SWISSMONITOR_CONFIG').'<p>';
      form_start('index.php', 200, 'ignore');
      form_hidden('section','SWISSMONITOR');
      form_hidden('action','EXT_MODIFY');
      form_select_table('ext',$data,str('IGNORED_EXTENSIONS')
                       ,array('class'=>'form_select_tab','width'=>'100%'),'ext',
                        array('NAME'=>''), $edit_id, 'ignore');
      if (!$edit_id)
        form_submit(str('IGNORE_EXTENSION_DEL_BUTTON'),1,'center');
      form_end();

      // Add an ignored extension
      echo '<p><h1>'.str('IGNORE_EXTENSION_ADD_TITLE').'<p>';
      form_start('index.php', 200);
      form_hidden('section','SWISSMONITOR');
      form_hidden('action','EXT_NEW');
      form_input('name',str('EXTENSION'),3);
      form_label(str('IGNORE_EXTENSION_PROMPT'));
      form_submit(str('IGNORE_EXTENSION_ADD_BUTTON'),2);
      form_end();

      // Configuration options
      form_start('index.php', 200);
      form_hidden('section','SWISSMONITOR');
      form_hidden('action','UPDATE');
      form_input('swissport',str('SWISSMONITOR_PORT'),3,'', $simese->length > 0 ? $simese->item(0)->getAttribute("port") : '', true);
      form_label(str('SWISSMONITOR_PORT_PROMPT'));
      form_input('swissini',str('SWISSMONITOR_INI'),80,'', $swisscenter->length > 0 ? os_path($swisscenter->item(0)->getAttribute("iniPath")) : '', true);
      form_label(str('SWISSMONITOR_INI_PROMPT'));
      form_submit(str('SAVE_SETTINGS'));
      form_end();
    }
    else
      echo "<p>".str('FAIL_DOTNET2_INSTALLED');
  }

  function swissmonitor_service()
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
      case str('SERVICE_RESTART'):
        $result = win_service_stop("SwissMonitorService");
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

  /**
   * Modify/delete ignored extensions
   *
   */

  function swissmonitor_ext_modify()
  {
    $selected = form_select_table_vals('ext');
    $edit_id = form_select_table_edit('ext', 'ignore');
    $update_data = form_select_table_update('ext', 'ignore');

    // Parse the SwissMonitor config
    $config  = SC_LOCATION.'ext/SwissMonitor/SwissMonitor.exe.config';
    $dom = new DOMDocument();
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;
    $dom->load($config);
    $xpath = new DOMXPath($dom);

    if (!empty($edit_id))
    {
      swissmonitor_display('', $edit_id);
    }
    elseif (!empty($update_data))
    {

      $name = $update_data["NAME"];
      $oldext = $update_data["EXT"];

      if (empty($name))
        swissmonitor_display("!".str('IGNORE_EXTENSION_ERROR_FILENAME'));
      else
      {
        // Update the modified extension
        $node = $xpath->query('/configuration/swissMonitor/ignoredExtensions/add[@extension=".'.$oldext.'"]')->item(0);
        $node->setAttribute("extension", '.'.$name);
        $dom->save($config);

        swissmonitor_display(str('IGNORE_EXTENSION_UPDATE_OK'));
      }
    }
    elseif (!empty($selected))
    {
      // Remove the selected extensions
      foreach ($selected as $ext)
      {
        $node = $xpath->query('/configuration/swissMonitor/ignoredExtensions/add[@extension=".'.$ext.'"]')->item(0);
        $node->parentNode->removeChild($node);
      }
      $dom->save($config);

      swissmonitor_display(str('IGNORE_EXTENSION_DELETE_OK'));
    }
    else
      swissmonitor_display();
  }

  /**
   * Add a new ignored extension
   *
   */

  function swissmonitor_ext_new()
  {
    $name = un_magic_quote($_REQUEST["name"]);

    if (empty($name))
      swissmonitor_display("!".str('IGNORE_EXTENSION_ERROR'));
    else
    {
      // Parse the SwissMonitor config
      $config = SC_LOCATION.'ext/SwissMonitor/SwissMonitor.exe.config';
      $dom = new DOMDocument();
      $dom->formatOutput = true;
      $dom->preserveWhiteSpace = false;
      $dom->load($config);
      $xpath = new DOMXPath($dom);

      // Add the new extension
      $add = $dom->createElement("add");
      $add->setAttribute("extension", '.'.$name);
      $node = $xpath->query('/configuration/swissMonitor/ignoredExtensions')->item(0);
      $node->appendChild($add);
      $dom->save($config);

      swissmonitor_display(str('IGNORE_EXTENSION_ADDED_OK'));
    }
  }

  /**
   * Update configuration
   *
   */
  function swissmonitor_update()
  {
    $swissport = $_REQUEST["swissport"];
    $swissini  = os_path($_REQUEST["swissini"]);

    if (!empty($swissport) && !is_numeric($swissport))
      swissmonitor_display("!".str('SWISSMONITOR_PORT_INVALID'));
    elseif (!empty($swissini) && !file_exists($swissini))
      swissmonitor_display("!".str('SWISSMONITOR_INI_NOT_EXIST'));
    else
    {
      // Parse the SwissMonitor config
      $config = SC_LOCATION.'ext/SwissMonitor/SwissMonitor.exe.config';
      $dom = new DOMDocument();
      $dom->formatOutput = true;
      $dom->preserveWhiteSpace = false;
      $dom->load($config);
      $xpath = new DOMXPath($dom);

      $node = $xpath->query('/configuration/swissMonitor')->item(0);

      $simese = $xpath->query('/configuration/swissMonitor/simese');
      if (!empty($swissport))
      {
        // Add/update simese element
        if ($simese->length == 0)
        {
          $simese = $dom->createElement("simese");
          $simese->setAttribute("port", $swissport);
          $simese->setAttribute("iniPath", "path to simese ini file");
          $node->appendChild($simese);
        }
        else
        {
          $simese->item(0)->setAttribute("port", $swissport);
        }
      }
      else
      {
        // Remove simese element
        if ($simese->length > 0)
        {
          $node = $simese->item(0);
          $node->parentNode->removeChild($node);
        }
      }

      $swiss = $xpath->query('/configuration/swissMonitor/swissCenter');
      if (!empty($swissini))
      {
        // Add/update swissCenter element
        if ($swiss->length == 0)
        {
          $swiss = $dom->createElement("swissCenter");
          $swiss->setAttribute("database", "server=localhost;user id=swiss; password=swiss; database=swisscenter; pooling=false");
          $swiss->setAttribute("iniPath", $swissini);
          $node->appendChild($swiss);
        }
        else
        {
          $swiss->item(0)->setAttribute("iniPath", $swissini);
        }
      }
      else
      {
        // Remove swissCenter element
        if ($swiss->length > 0)
        {
          $node = $swiss->item(0);
          $node->parentNode->removeChild($node);
        }
      }
      $dom->save($config);

      swissmonitor_display(str('SWISSMONITOR_CONFIG_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
