<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
   
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
    $server_type = "UNKNOWN";
    
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
  // Returns the full URL (SCRIPT_NAME + QUERY_STRING) of the current page
  // ----------------------------------------------------------------------------------
  
  function current_url( $post_vars = false)
  {
    if(is_server_apache() || is_server_iis())
      $url = "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
    else 
      $url =  $_SERVER["SCRIPT_NAME"].(empty($_SERVER["QUERY_STRING"]) ? "" : "?".$_SERVER["QUERY_STRING"]);
      
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

  // ----------------------------------------------------------------------------------
  // Returns the HTTP address (IP and Port) where the SwissCenter is installed.
  // ----------------------------------------------------------------------------------

  function server_address()
  {
    if (is_server_iis())
      return 'http://'.$_SERVER['LOCAL_ADDR'].":".$_SERVER['SERVER_PORT'].'/';
    else
      return 'http://'.$_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT'].'/';
  }

  // ----------------------------------------------------------------------------------
  // Returns TRUE if the server has internet connectivity
  // ----------------------------------------------------------------------------------

  function internet_check( $timeouts = 3)
  {
    $temp = '';

    for ($i=0; $i < $timeouts; $i++)
      if ( $sock = @fsockopen('66.249.87.99', 80, $temp, $temp, 0.5)) // www.google.com
      {
        fclose($sock);
        return 'YES'; 
      }

    return 'NO';
  }
  
  function internet_available()
  {
    if ( isset($_SESSION["internet"]) && !is_array($_SESSION["internet"]))
      $_SESSION=array();
    
    if ( !isset($_SESSION["internet"]) || $_SESSION["internet"]["timeout"] < time() )
    {
      $_SESSION["internet"]["available"] = internet_check();
      $_SESSION["internet"]["timeout"]   = time()+900; // 15 mins
    }
    
    return ( $_SESSION["internet"]["available"] == 'YES' );
  }

  // ----------------------------------------------------------------------------------
  // Returns TRUE if the windows Task Scheduler service is running
  // ----------------------------------------------------------------------------------

  function is_task_scheduler_running()
  {
    if (is_windows())
    {
      $services = syscall('net start');
      return ( strpos($services,'Task Scheduler') !== false );
    }
    else 
      return 'Not running on windows';
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
