<?
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
      $url = "http://$host/".str_replace('\\','/',stripslashes($_SERVER["SCRIPT_NAME"].$params));
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
    $server = $_SERVER['SERVER_NAME'];
    
    if (strpos($server,':') === false)
    {
      if (!empty($_SERVER['SERVER_PORT']))
        $server = $server.':'.$_SERVER['SERVER_PORT'];
      else 
        $server = $server.':'.$_SESSION["device"]["port"]; 
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
  
  #-------------------------------------------------------------------------------------------------
  # Record the details about the client accessing the server in the database.
  #-------------------------------------------------------------------------------------------------

  function record_client_details()
  {
    if (test_db() == 'OK' && !isset($_SESSION["device"]))
    { 
      $_SESSION["device"]["last_seen"]  = db_datestr();
      $_SESSION["device"]["ip_address"] = str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
      $_SESSION["device"]["port"] = $_SERVER['SERVER_PORT']; 
      get_player_type();
      get_screen_type();
      
      if (strlen($_SESSION["device"]["ip_address"]) > 0 )
      {
    	  db_sqlcommand("delete from clients where ip_address='".$_SESSION["device"]["ip_address"]."'");
    	  db_insert_row('clients',$_SESSION["device"]);
      }
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
