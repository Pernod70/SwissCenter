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
// Makes an array contain unique values for the given key within in the nested array.
//

function arrayUnique( $array, $key ) 
{ 
  $rArray = array();
  if (is_array($array) && sizeof($array) > 0)
  {
    $keys   = array();
    for ($i = 0; $i < sizeof($array); $i++)
    {
      if (!in_array($array[$i][$key],$keys))
      {
        $rArray[] = $array[$i];
        $keys[]   = $$array[$i][$key];
      }
    }
  } 
  return $rArray;
}

//
// Truncates a string to the given number of characters, and adds an ellipse to 
// indicate it has been shortened
//

function shorten( $text, $trunc, $font_size = 1, $lines = 1 )
{
  if(empty($text))
    return $text;
    
  $char_widths = array(   "A" => 16,  "B" => 12,  "C" => 15,  "D" => 14,  "E" => 14,  "F" => 14,  "G" => 15,  "H" => 15,
                          "I" => 4,   "J" => 10,  "K" => 14,  "L" => 11,  "M" => 16,  "N" => 15,  "O" => 16,  "P" => 14,
                          "Q" => 16,  "R" => 15,  "S" => 12,  "T" => 15,  "U" => 14,  "V" => 16,  "W" => 20,  "X" => 16,
                          "Y" => 15,  "Z" => 15,  "[" => 6,   "\\" => 7,  "]" => 6,   "^" => 9,   "_" => 12,  "`" => 6,
                          "a" => 11,  "b" => 11,  "c" => 11,  "d" => 11,  "e" => 11,  "f" => 6,   "g" => 11,  "h" => 11,
                          "i" => 4,   "j" => 5,   "k" => 10,  "l" => 4,   "m" => 16,  "n" => 11,  "o" => 11,  "p" => 11,
                          "q" => 11,  "r" => 7,   "s" => 9,   "t" => 6,   "u" => 11,  "v" => 11,  "w" => 16,  "x" => 12,
                          "y" => 11,  "z" => 11,  "{" => 7,   "|" => 4,   "}" => 7,   "~" => 11,  "!" => 4,   "\"" => 7,
                          "#" => 10,  "$" => 11,  "%" => 16,  "&" => 15,  "'" => 4,   "/" => 7,   ")" => 6,   "(" => 6,
                          "*" => 7,   "+" => 12,  "," => 4,   "-" => 8,   "." => 4,   "0" => 11,  "1" => 7,   "2" => 11,
                          "3" => 11,  "4" => 11,  "5" => 11,  "6" => 11,  "7" => 11,  "8" => 11,  "9" => 11,  ":" => 4,
                          ";" => 4,   "=" => 12,  ">" => 12,  "?" => 11,  "@" => 19,  "<" => 12,  " " => 7,  );

  $len = 0;
  $short_string = "";
  $max_len = (int)((($trunc / $font_size) * $lines) - (12 * $font_size));

  for($index = 0; $index < strlen($text); $index++)
  {
    $current_char = $text[$index];

    if(!array_key_exists($current_char, $char_widths))
      $char_len = 7;
    else
      $char_len = $char_widths[$current_char];

    if(($len + $char_len) < $max_len)
    {
      $len += $char_len;
      $short_string .= $current_char;
    }
    else
    {
      $short_string .= "...";
      break;
    }
  }

  return $short_string;
}

//
// Returns true if the page request came from a showcenter box (rather than a web browser 
// on a PC).
//

function is_showcenter()
{
  if (strpos($_SERVER["HTTP_USER_AGENT"],'Syabas') !== false)
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
  if(is_server_apache() || is_server_iis())
    return "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
  else 
    return $_SERVER["SCRIPT_NAME"].(empty($_SERVER["QUERY_STRING"]) ? "" : "?".$_SERVER["QUERY_STRING"]);
}

//
// Returns the webserver type
//

function get_server_type()
{
  $server_type = "UNKNOWN";
  
  if(strpos($_SERVER["SERVER_SOFTWARE"], "Apache") === 0)
  {
    $server_type = "APACHE";
  }
  else if(strpos($_SERVER["SERVER_SOFTWARE"], "IIS") === 0)
  {
    $server_type = "IIS";
  }
  else
  {
    $server_type = "SIMESE";
  }
  
  return $server_type;
}

function is_server_iis()
{
  return get_server_type() == "IIS";
}

function is_server_apache()
{
  return get_server_type() == "APACHE";
}

function is_server_simese()
{
  return get_server_type() == "SIMESE";
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
