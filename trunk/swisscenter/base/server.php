<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/mysql.php'));

  // ----------------------------------------------------------------------------------
  // Returns the Operating System type
  // ----------------------------------------------------------------------------------

  function get_os_type()
  {
    if ( substr(PHP_OS,0,3)=='WIN' )
      return 'WINDOWS';
    else
      return 'UNIX';
  }

  function is_windows()
  { return get_os_type() == "WINDOWS"; }

  function is_unix()
  { return get_os_type() == "UNIX"; }

  function is_synology()
  {
    // Have we already done the Sybology test?
    if (! isset($_SESSION["Synology"]) )
    {
      if ( preg_match('/phpinfo/', ini_get('disable_functions')) == 0)
      {
        // Is phpinfo() available for us to use?
        ob_start();
        phpinfo(8);
        $info = ob_get_contents();
        ob_end_clean();
        $_SESSION["Synology"] = !(stristr($info, 'synology') === false);
      }
      else
      {
        $_SESSION["Synology"] = ($_SERVER["SERVER_NAME"] == "synology");
      }
    }
    return $_SESSION["Synology"];
  }

  // ----------------------------------------------------------------------------------
  // Returns the webserver type
  // ----------------------------------------------------------------------------------

  function get_server_type()
  {
    if (!key_exists('SERVER_SOFTWARE',$_SERVER))
      $server_type = 'SIMESE';
    if(strpos($_SERVER["SERVER_SOFTWARE"], "Apache") !== false )
      $server_type = "APACHE";
    elseif(strpos($_SERVER["SERVER_SOFTWARE"], "IIS") !== false)
      $server_type = "IIS";
    elseif(strpos($_SERVER["SERVER_SOFTWARE"], "Simese") !== false)
      $server_type = "SIMESE";
    elseif(strpos($_SERVER["SERVER_SOFTWARE"], "lighttpd") !== false)
      $server_type = "LIGHTTPD";
    else
      $server_type = "SIMESE";

    return $server_type;
  }

  function is_server_iis()
  { return get_server_type() == "IIS"; }

  function is_server_apache()
  { return get_server_type() == "APACHE"; }

  function is_server_simese()
  { return get_server_type() == "SIMESE"; }

  function is_server_lighttpd()
  { return get_server_type() == "LIGHTTPD"; }

  function apache_version()
  {
    preg_match('#Apache/(.*?) #',$_SERVER["SERVER_SOFTWARE"], $matches);
    if (!empty($matches[1]))
      return $matches[1];
    else
      return false;
  }

  function simese_version()
  {
    if ( is_server_simese() )
      return substr($_SERVER["SERVER_SOFTWARE"],7);
    else
      return false;
  }

  function lighttpd_version()
  {
    if ( is_server_lighttpd() )
      return substr($_SERVER["SERVER_SOFTWARE"],9);
    else
      return false;
  }

  // ----------------------------------------------------------------------------------
  // Returns the version of GD installed on the system (or FALSE if not installed)
  // ----------------------------------------------------------------------------------

  function gd_version()
  {
    // Have we already determined the version?
    if (! isset($_SESSION["GD Version"]) )
    {
      if ( !extension_loaded('gd'))
      {
        $_SESSION["GD Version"] = false;
      }
      elseif (function_exists('gd_info'))
      {
        // Use the gd_info() function if possible.
        $ver_info = gd_info();
        preg_match('/\d/', $ver_info['GD Version'], $match);
        $_SESSION["GD Version"] =  $match[0];
      }
      elseif ( preg_match('/phpinfo/', ini_get('disable_functions')) == 0)
      {
        // Is phpinfo() available for us to use?
        ob_start();
        phpinfo(8);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'gd version');
        preg_match('/\d/', $info, $match);
        $_SESSION["GD Version"] =  $match[0];
      }
      else
      {
        // Otherwise, return a hardcoded failsafe.
        $_SESSION["GD Version"] =  0;
      }
    }

    // Return the version number.
    return $_SESSION["GD Version"];
  }

  // ----------------------------------------------------------------------------------
  // Returns the full URL (SCRIPT_NAME + QUERY_STRING) of the current page
  // ----------------------------------------------------------------------------------

  function current_url( $post_vars = false)
  {
    $host   = $_SERVER["HTTP_HOST"];

    if(is_server_apache() || is_server_iis())
      $url = "http://".$host.$_SERVER["REQUEST_URI"];
    else
    {
      $params = (empty($_SERVER["QUERY_STRING"]) ? "" : "?".$_SERVER["QUERY_STRING"]);
      $url = "http://$host".str_replace('\\','/',stripslashes($_SERVER["SCRIPT_NAME"].$params));
    }

    if ($post_vars)
      foreach ($_POST as $arg => $val)
        $url = url_add_param($url,$arg,$val);

    return $url;
  }

  // ----------------------------------------------------------------------------------
  // Returns the IP address of the Client
  // ----------------------------------------------------------------------------------

  function client_ip()
  {
    return str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
  }

  /**
   * Returns the HTTP full address (including port) of the server on which SwissCenter
   * is installed.
   *
   * Note: There appears to be a bug in the Syabas/Sigma Designs firmware which means
   * that the HTTP request is not properly formed. Most notably, the PORT is missing
   * so we have to try and deduce it using other means!
   *
   * @return string
   */

  function server_address()
  {
    send_to_log(8,'Device details (in the session)',$_SESSION["device"]);
    $server = $_SERVER['SERVER_NAME'];
    $override = get_sys_pref('SERVER_PORT');

    if (strpos($server,':') === false)
    {
      if (!empty($override))
        $server = $server.':'.$override;
      elseif (empty($_SERVER['SERVER_PORT']) || ($_SERVER["SERVER_PORT"] != $_SESSION["device"]["port"]) )
        $server = $server.':'.$_SESSION["device"]["port"];
      else
        $server = $server.':'.$_SERVER['SERVER_PORT'];
    }

    return 'http://'.$server.'/';
  }

  // ----------------------------------------------------------------------------------
  // Returns TRUE if the windows Task Scheduler service is running
  // ----------------------------------------------------------------------------------

  function is_task_scheduler_running()
  {
    if (is_windows())
    {
      $services = syscall('net start');
      return ( strpos($services,'Task Scheduler') !== false || strpos($services,str('TASK_SCHEDULER')) !== false);
    }
    else
      return 'Not running on windows';
  }

  // ----------------------------------------------------------------------------------
  // Functions to manage windows services
  // ----------------------------------------------------------------------------------

  define ("SERVICE_NOT_INSTALLED",false);
  define ("SERVICE_STOPPED",'START');
  define ("SERVICE_STARTED",'STOP');

  function win_service_sc($command, $service)
  {
    $output = '';
    send_to_log(8,"Attempting to $command the $service service");
    @exec("sc $command $service",$output);

    if ($output[3] != "")
    {
      $status = explode(':',$output[3]);
      $status = explode(' ',trim($status[1]));
      send_to_log(8,"Response code : ".$status[0]);
      if ( is_numeric($status[0]))
        return $status[0];
    }

    return false;
  }

  function win_service_installed( $service )
  {
    return (win_service_sc("query",$service) !== false);
  }

  function win_service_status( $service )
  {
    switch (win_service_sc("query",$service))
    {
      case 1:
      case 3:
        return SERVICE_STOPPED;
      case 2:
      case 4:
        return SERVICE_STARTED;
      default:
        return SERVICE_NOT_INSTALLED;
    }
  }

  function win_service_start( $service )
  {
    return (win_service_sc("start",$service) == 2);
  }

  function win_service_stop( $service )
  {
    return (win_service_sc("stop",$service) == 3);
  }

  function win_dotnet2_installed()
  {
    return file_exists(getenv("SystemRoot")."/microsoft.net/framework/v2.0.50727/InstallUtil.exe");
  }

  // ----------------------------------------------------------------------------------
  // Record the details about the client accessing the server in the database.
  // ----------------------------------------------------------------------------------

  function record_client_details()
  {
    // Do not record client details if:
    // - HTTP_USER_AGENT contains 'internal dummy connection' (Apache connection)
    // - HTTP_USER_AGENT'] contains '000000' (invalid id from media player when requesting playlists)
    // - PHP_SELF contains 'media_search' (server requested a media search)
    // - PHP_SELF contains 'media_monitor' (SwissMonitor updating database)
    if (test_db() == 'OK' && !isset($_SESSION["device"]) && strpos($_SERVER['HTTP_USER_AGENT'],'internal dummy connection') === false
                                                         && strpos($_SERVER['HTTP_USER_AGENT'],'000000') === false
                                                         && strpos($_SERVER['PHP_SELF'],'media_search') === false
                                                         && strpos($_SERVER['PHP_SELF'],'media_monitor') === false)
    {
      $matches = array();
      preg_match('#.*syabas/([^ ]*) .*#i',$_SERVER['HTTP_USER_AGENT'],$matches);
      $_SESSION["device"]["last_seen"]  = db_datestr();
      $_SESSION["device"]["ip_address"] = str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
      $_SESSION["device"]["port"] = $_SERVER['SERVER_PORT'];
      if (!empty($matches))
        $_SESSION["device"]["box_id"] = $matches[1];

      get_player_type();
      get_screen_type();

      if (strlen($_SESSION["device"]["ip_address"]) > 0 )
      {
        $_SESSION["device"]["mac_addr"] = get_mac_addr($_SESSION["device"]["ip_address"]);
    	  db_sqlcommand("delete from clients where ip_address='".$_SESSION["device"]["ip_address"]."' or mac_addr='".$_SESSION["device"]["mac_addr"]."'");
    	  db_insert_row('clients',$_SESSION["device"]);
      }
    }
  }

  // ----------------------------------------------------------------------------------
  // Get the MAC address of a client on the LAN.
  // ----------------------------------------------------------------------------------

  function get_mac_addr($ip)
  {
    exec("arp -a ".$ip, $output);
    foreach($output as $line)
    {
      if (preg_match("/[0-9A-F]{2}[-:][0-9A-F]{2}[-:][0-9A-F]{2}[-:][0-9A-F]{2}[-:][0-9A-F]{2}[-:][0-9A-F]{2}/i", $line, $matches))
      {
        $mac = $matches[0];
      }
    }
    return str_replace('-',':',$mac);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
