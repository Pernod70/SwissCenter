<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// ----------------------------------------------------------------------------------
// Adds the given paramter/value pair to the given URL
// ----------------------------------------------------------------------------------

function url_add_param($url, $param, $value)
{
  if (strpos($url,'?') === false)
  {
    // No existing paramters for this url
    return $url.'?'.$param.'='.$value;
  }
  elseif (preg_match('/[?&]'.$param.'=/',$url) == 0) 
  {
    // Paramters present, but this is a new paramter to be appended
    return $url.'&'.$param.'='.$value;  
  }
  else
  {    
    // Paramters present, and there is already a value for this paramter
    return preg_replace('/([?&]'.$param.'=)[^&]*/','${1}'.$value,$url); 
  }
}

// Array version of the url_add_param() function

function url_add_params( $url, $array)
{
  foreach ($array as $param=>$value)
    $url = url_add_param($url, $param, $value);

  return $url;
}

// url_set_param() is actually just another name for url_add_param()

function url_set_param($url, $param, $value)
{ return url_add_param($url, $param, $value); }

function url_set_params( $url, $array)
{ return url_add_params( $url, $array); }

// ----------------------------------------------------------------------------------
// Removes the given paramter from the given URL
// ----------------------------------------------------------------------------------

function url_remove_param($url, $param)
{
  if (preg_match('/\?'.$param.'=/',$url) != 0) 
  {
    // Paramter present as the first parameter
    return rtrim(preg_replace('/(\?'.$param.'=[^&]*)(&*)/','?',$url),'?'); 
  }
  elseif (preg_match('/&'.$param.'=/',$url) != 0) 
  {
    // Paramter present, but not the first one.
    return preg_replace('/(&'.$param.'=[^&]*)/','',$url); 
  }
  else 
  {
    // Parameter not present, so just return the URL unaltered.
    return $url;
  }
}

// Array version of the url_remove_param() function

function url_remove_params( $url, $array)
{
  foreach ($array as $value)
    $url = url_remove_param($url, $value);

  return $url;
}

/**
 * Performs a HTTP POST request and returns the response (or false on error).
 *
 * @param string $url - The full URL to request
 * @param string data - The data (don't forget to urlencode() the arguments).
 * @return string
 */

function http_post( $url, $data, $timeout = 5 )
{
  send_to_log(5,"Sending HTTP POST Request to $url");
  $current = parse_url( current_url() );
  $url = parse_url($url);
  $host = (isset($url["host"]) ? $url["host"] : $current["host"] );
  $port = (isset($url['port']) ? $url['port'] : $current["port"] );
  $path = $url["path"];

  // Generate the request header
  $data_len  = strlen($data);
  $request   = "POST $path HTTP/1.1\r\n".
               "Host: $host\r\n".   
               "Content-Type: application/x-www-form-urlencoded\r\n".
               "Content-Length: $data_len\r\n".
               "Connection: close\r\n".
               "\r\n".
               "$data";

  // Open the connection to the host
  send_to_log(8,"HTTP POST Request (Host: $host Port: $port)",explode("\n",$request));
  if ( ($socket = fsockopen($host, $port, &$errno, &$errstr, $timeout)) === false)
  {
    send_to_log(2,"Failed to open socket to '$host' on port '$port'.");
    return false;    
  }
  
  // Send POST request
  fputs($socket, $request);
  
  // Ensure we are reading in blocking mode, and with the specified timeout
  stream_set_blocking( $socket, TRUE );
  stream_set_timeout( $socket, $timeout );
  $info = stream_get_meta_data( $socket ); 

  // Get the response
  $response = '';
  while (!feof($socket) && (!$info['timed_out']))
  {
    $response .= fgets($socket, 256);
    $info = stream_get_meta_data($socket);
  }
  
  // Record the fact that the socket timed out.
  if ($info['timed_out'])
    send_to_log(2,"Socket timed out while attempting a HTTP POST to '$socket:$port'");
  
  // Close the socket
  fclose($socket);
    
  // Return result;
  send_to_log(8,"HTTP POST Response",explode("\n",$response));
  return $response;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
