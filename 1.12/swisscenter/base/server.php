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

  // ----------------------------------------------------------------------------------
  // Returns the browser type
  // ----------------------------------------------------------------------------------
  
  function get_browser_type()
  {
    if     ( !isset($_SERVER["HTTP_USER_AGENT"]) ) 
      return 'SYABAS';
    elseif ( empty($_SERVER["HTTP_USER_AGENT"])  ) // Variable is set, but empty
      return 'SYABAS';
    elseif ( strpos($_SERVER["HTTP_USER_AGENT"],'Syabas') !== false )
      return 'SYABAS';
    else
      return 'UNKNOWN';
  }
  
  function is_showcenter()
  { return get_browser_type() == "SYABAS"; }

  // ----------------------------------------------------------------------------------
  // Returns the webserver type
  // ----------------------------------------------------------------------------------
  
  function get_server_type()
  {
    $server_type = "UNKNOWN";
    
    if(strpos($_SERVER["SERVER_SOFTWARE"], "Apache") !== false )
      $server_type = "APACHE";
    else if(strpos($_SERVER["SERVER_SOFTWARE"], "IIS") !== false)
      $server_type = "IIS";
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

  // ----------------------------------------------------------------------------------
  // Returns the full URL (SCRIPT_NAME + QUERY_STRING) of the current page
  // ----------------------------------------------------------------------------------
  
  function current_url()
  {
    if(is_server_apache() || is_server_iis())
      return "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
    else 
      return $_SERVER["SCRIPT_NAME"].(empty($_SERVER["QUERY_STRING"]) ? "" : "?".$_SERVER["QUERY_STRING"]);
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
      if ( $sock = @fsockopen('www.google.com', 80, $temp, $temp, 1))
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
      $_SESSION["internet"]["timeout"]   = time()+300; // 5 mins
    }
    
    return ( $_SESSION["internet"]["available"] == 'YES' );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
