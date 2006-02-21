<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("file.php");

//
// Returns the outcome of a system command
//

function syscall($command)
{
  if ($proc = popen("($command)","r"))
  {
    while (!feof($proc)) 
      $result .= fgets($proc, 1000);
      
    pclose($proc);
    return $result; 
  }
}
  
//
// Runs a job in the background.
//

function run_background ( $command, $days = '' )
{
  if ( substr(PHP_OS,0,3)=='WIN' )
  {
    $soon = date('H:i',time()+70);
    if (!empty($days)) 
      $soon.= ' /every:'.$days;
      
    // Windows, so use the "Start" command to run it in another process.
    // Change recommended by Marco : http://www.swisscenter.co.uk/component/option,com_simpleboard/Itemid,42/func,view/id,29/catid,10/
    exec('at '.$soon.' CMD /C """"'.os_path($_SESSION["opts"]["php_location"]).'" "'.os_path($_SESSION["opts"]["sc_location"].$command).'""""');

  }
  else
  {
    $log = (is_null($logfile) ? '/dev/null' : os_path($_SESSION["opts"]["sc_location"].$logfile));

    // UNIX, so run with '&' to force it to the background.
    exec( '"'.os_path($_SESSION["opts"]["php_location"]).'" "'.os_path($_SESSION["opts"]["sc_location"].$command).'" > "'.$log.'" &' );
  }
}

// Returns whether the search string is in the array (case-insensitive)

function in_array_ci($search, $array)
{
  return preg_grep('/^'.preg_quote(strtolower($search), '/').'$/i', $array);
}

// Adds the given character onto the end of the given string, if it is not already present.
function str_suffix( $string, $char)
{
  if (empty($string))
    return '';
 elseif ( $string[strlen($string)-1] == $char)
    return $string;
  else
    return $string.$char;
}

// Returns the null device (/dev/null in UNIX, :null in windows.
function os_null()
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return ':null';
  else
    return '/dev/null';
}

// Returns the last value in an array without removing it
function array_last( &$array )
{
  return $array[count($array)-1];
}

// Checks to see if the PHP option "Magic Quotes" is turned on, and if it is then this
// function strips the slashes from the input.

function un_magic_quote( $text )
{
  if ( get_magic_quotes_gpc() == 1)
    return stripslashes($text);
  else
    return $text;
}

// this function will return the value of a given variable which is stored in a text string
// of the following format (eg: an SQL statement): ^.*variable='value'.* variable='value'...

function var_in_string( $string, $var)
{
  $results = array ();
  preg_match("/".$var."\s*=\s*'([^']*)'/",$string,$results);
  return $results[1];
}

// Takes an amount in seconds, and returns a string reading "x Hours, Y Minutes, Z seconds"

function hhmmss( $secs )
{
  $str = '';

  if ($secs > 86400)
  {
    $str = floor($secs/86400).'d : ';
    $secs = $secs % 86400;
  }

  if ($secs > 3600)
  {
    $str = floor($secs/3600).'h : ';
    $secs = $secs % 3600;
  }

  if ($secs > 60)
  {
    $str .= floor($secs/60).'m : ';
    $secs = $secs % 60;
  }

  return $str.'1s';
}

//
// Gets the details of the image, and adjusts the $X and $Y parameters to reflect the
// true image size if the image was resized to fit within the $Xx$Y rectangle, whilst
// maintaining the aspect ratio.
//

function image_resized_xy( $filename, &$x, &$y )
{
  $imagedata = getimagesize($filename);

  if ($x && ($imagedata[0] < $imagedata[1]))
  {
    $x = floor(($y / $imagedata[1]) * $imagedata[0]);
  }
  else
  {
    $y = floor(($x / $imagedata[0]) * $imagedata[1]);
  }

}

//
// Sets the status of the "New Media" indicator light on the showcenter box
//

function media_indicator( $status )
{
  if ($status != 'ON' && $status !='OFF' && $status !='BLINK')
    echo "Status wrong for new media indicator - should be ON, OFF or BLINK";
  else
  {
  	$boxes = db_col_to_list("select ip_address from clients where box_id is not null");
  	if (count($boxes)>0)
  	{
      foreach($boxes as $ip)
        $dummy = @file_get_contents('http://'.$ip.':2020/LED_indicate.cgi?%7FStatusLED='.$status);
  	}
  }
}

//
// Sorts an array of arrays based on the given key in the nested array.
//

function array_sort( &$array, $key ) 
{ 
  if (is_array($array) && sizeof($array) > 0)
  {
    for ($i = 0; $i < sizeof($array); $i++)
      $sort_values[$i] = $array[$i][$key]; 

    asort ($sort_values); 
    reset ($sort_values); 

    while (list ($arr_key, $arr_val) = each ($sort_values))
      $sorted_arr[] = $array[$arr_key]; 

    $array = $sorted_arr; 
  } 
}

//
// Truncates a string to the given number of characters, and adds an ellipse to 
// indicate it has been shortened
//

function shorten( $text, $trunc )
{
  if (strlen($text) > $trunc)
    return substr($text,0,$trunc).'...';
  else
    return $text;
}

//
// Returns true if the page request came from a showcenter box (rather than a web browser 
// on a PC).
//

function is_showcenter()
{
  if (strpos($_ENV["HTTP_USER_AGENT"],'Syabas') !== false)
    return true;
  else
    return false;
}

//
// Adds the given paramter/value pair to the given URL
//

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
    return preg_replace('/([?&]'.$param.'=)[^&]*/','\1'.$value,$url); 
  }

}

//
// Returns true if SwissCenter is running on a MS Windows OS
//

function is_windows()
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return true;
  else
    return false;
}

//
// Returns the full URL (SCRIPT_NAME + QUERY_STRING) of the current page
//

function current_url()
{
  return $_SERVER["SCRIPT_NAME"].(empty($_SERVER["QUERY_STRING"]) ? '' : '?'.$_SERVER["QUERY_STRING"]);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
