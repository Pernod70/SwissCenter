<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/sched.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/stylelib.php'));
require_once( realpath(dirname(__FILE__).'/urls.php'));

$char_widths = array(   "A" => 096,  "B" => 192,  "C" => 240,  "D" => 224,  "E" => 224,  "F" => 224,  "G" => 240,  "H" => 240,
                        "I" => 064,  "J" => 160,  "K" => 224,  "L" => 176,  "M" => 256,  "N" => 240,  "O" => 256,  "P" => 224,
                        "Q" => 256,  "R" => 240,  "S" => 192,  "T" => 240,  "U" => 224,  "V" => 256,  "W" => 320,  "X" => 256,
                        "Y" => 240,  "Z" => 240,  "[" => 096, "\\" => 112,  "]" => 096,  "^" => 144,  "_" => 192,  "`" => 096,
                        "a" => 176,  "b" => 176,  "c" => 176,  "d" => 176,  "e" => 176,  "f" => 096,  "g" => 176,  "h" => 176,
                        "i" => 064,  "j" => 080,  "k" => 160,  "l" => 064,  "m" => 256,  "n" => 176,  "o" => 176,  "p" => 176,
                        "q" => 176,  "r" => 112,  "s" => 144,  "t" => 096,  "u" => 176,  "v" => 176,  "w" => 256,  "x" => 192,
                        "y" => 176,  "z" => 176,  "{" => 112,  "|" => 064,  "}" => 112,  "~" => 176,  "!" => 064, "\"" => 112,
                        "#" => 160,  "$" => 176,  "%" => 256,  "&" => 240,  "'" => 064,  "/" => 112,  ")" => 096,  "(" => 096,
                        "*" => 112,  "+" => 192,  "," => 064,  "-" => 128,  "." => 064,  "0" => 176,  "1" => 112,  "2" => 176,
                        "3" => 176,  "4" => 176,  "5" => 176,  "6" => 176,  "7" => 176,  "8" => 176,  "9" => 176,  ":" => 064,
                        ";" => 064,  "=" => 192,  ">" => 192,  "?" => 176,  "@" => 304,  "<" => 192,  " " => 112,  );

/**
 * Simple function to search a string using a regular expression and then
 * return the first captured pattern
 *
 * @param string $pattern - Pattern to use when searching
 * @param string $subject - The string to search
 * @return string
 */

function preg_get( $pattern, $subject )
{
  preg_match( $pattern, $subject, $matches);
  return (isset($matches[1]) ? $matches[1] : '');
}

// ----------------------------------------------------------------------------------
// A better alternative to the "shuffle" routine in PHP - this version generates a
// more random shuffle (and can be seeded to always return the same shuffled list).
// ----------------------------------------------------------------------------------

function shuffle_fisherYates(&$array, $seed = false)
{
   if ($seed !== false)
     mt_srand($seed);

   $total = count($array);
   for ($i = 0; $i<$total; $i++)
   {
         $j = @mt_rand(0, ($total-1));
         $temp = $array[$i];
         $array[$i] = $array[$j];
         $array[$j] = $temp;
   }
}

//-------------------------------------------------------------------------------------------------
// Makes the given filepath acceptable to the webserver (\ become /)
//-------------------------------------------------------------------------------------------------

function make_url_path( $fsp )
{
  // On linux/unix systems, we need to modify the path to access the file via the symbolic link
  // rather than trying to access it directly
  if ( is_unix() )
  {
    // We order the list by the longest path first to ensure we always match the most specific first.
    foreach ( db_toarray("select name,concat('media/',location_id) dir from media_locations order by length(name) desc") as $dir)
    {
      $pos = strpos($fsp, $dir["NAME"]);
      if ( $pos == 0 and $pos !== false)
        $fsp = $dir["DIR"].substr($fsp, strlen($dir["NAME"]));
    }
  }

  $parts = split('/',str_replace('\\','/',$fsp));

  // On windows, we should ensure that the drive letter is converted to uppercase
  if ( is_windows() )
    $parts[0] = strtoupper($parts[0]);

  // Simese doesn't like UTF-8 encoded URL's
  for ($i=0; $i<count($parts); $i++)
    $parts[$i] = rawurlencode(is_server_simese() ? $parts[$i] : utf8_encode($parts[$i]));

  return join('/',$parts);
}

//-------------------------------------------------------------------------------------------------
// Makes the path required to access a file via a network share on NMT players
//-------------------------------------------------------------------------------------------------

function make_network_share_path( $fsp, $share_local, $share_nmt )
{
  // If VIDEO_TS folder then pass folder name
  if ( strtoupper(basename($fsp)) == 'VIDEO_TS.IFO' )
    $fsp = dirname($fsp);

  // Remove shared path from file path.
  $fsp = str_replace($share_local, "", $fsp);

  // Create NMT file path of shared file.
  $path = 'file:///opt/sybhttpd/localhost.drives/'.$share_nmt.$fsp;

  return rawurlencode(utf8_encode($path));
}

// ----------------------------------------------------------------------------------
// If the given $text is empty or NULL (from MySQL) then this function returns the $default
// string. Otherwise, it returns the $text passed in.
// ----------------------------------------------------------------------------------

function nvl($text,$default = '&lt;Unknown&gt;')
{
  if (empty($text) || is_null($text))
    return $default;
  else
    return $text;
}

// ----------------------------------------------------------------------------------------
// Removes common parts of filenames that we don't want to search for...
// (eg: file extension, file suffix ("CD1",etc) and non-alphanumeric chars.
// ----------------------------------------------------------------------------------------

function strip_title ($title)
{
  $search  = array ( '/\.[^.]*$/U'
                   , '/\(.*\)/'
                   , '/\[.*\]/'
                   , '/\s[^\w&$]/'
                   , '/[^\w&$]\s/'
                   , '/\sCD[^\w].*/i'
                   , '/ +$/'
                   , '/_/'
                   , '/\./');

  $replace = array ( ''
                   , ' '
                   , ' '
                   , ' '
                   , ' '
                   , ' '
                   , ''
                   , ' '
                   , ' ');

  return preg_replace($search, $replace, $title);
}

// ----------------------------------------------------------------------------------
// Returns the text between two given strings
// ----------------------------------------------------------------------------------

function substr_between_strings( &$string, $startstr, $endstr)
{
  $start  = ( empty($startstr) ? 0 : strpos($string,$startstr));
  $end    = strpos($string,$endstr, $start+strlen($startstr));

  if ($start === false || $end === false)
  {
    return '';
  }
  else
  {
    $text  = strip_tags(substr($string,$start+strlen($startstr),$end-$start-strlen($startstr)));

    if (strpos($text,'>') === false)
      return ltrim(rtrim($text));
    else
      return ltrim(rtrim(substr($text,strpos($text,'>')+1)));
  }
}

// ----------------------------------------------------------------------------------
// Returns all the hyperlinks that are in the given string that match the specified
// regular expression ($search) within the href portion of the link.
// ----------------------------------------------------------------------------------

function get_urls_from_html ($string, $search)
{
  $matches = array();
  preg_match_all ('/<a.*href="(.*'.$search.'[^"]*)"[^>]*>(.*)<\/a>/Ui', $string, $matches);

  for ($i = 0; $i<count($matches[2]); $i++)
    $matches[2][$i] = preg_replace('/<[^>]*>/','',$matches[2][$i]);

  return $matches;
}

// ----------------------------------------------------------------------------------
// Returns the given URL ($url) as a properly formatted URL, using $site as the site
// address if one is not present.
// ----------------------------------------------------------------------------------

function add_site_to_url ( $url, $site )
{
  if ( strpos($url,'http:/') === false)
    return rtrim($site,'/').'/'.ltrim($url,'/');
  else
    return $url;
}

// ----------------------------------------------------------------------------------
// Returns all the hyperlinks is the given string
// ----------------------------------------------------------------------------------

function get_images_from_html ($string)
{
  $matches = array();
  preg_match_all ('/<img.*src="([^"]*)"[^>]*>/i', $string, $matches);
  return $matches;
}

// ----------------------------------------------------------------------------------
// Returns the outcome of a system command
// ----------------------------------------------------------------------------------

function syscall($command)
{
  $result = false;

  if ($proc = popen("($command)","r"))
  {
    while (!feof($proc))
      $result .= fgets($proc, 1000);

    pclose($proc);
  }

  return $result;
}

// ----------------------------------------------------------------------------------
// Returns whether the search string is in the array (case-insensitive)
// ----------------------------------------------------------------------------------

function in_array_ci($search, $array)
{
  return preg_grep('/^'.preg_quote(strtolower($search), '/').'$/i', $array);
}

// ----------------------------------------------------------------------------------
// Adds the given character onto the end of the given string, if it is not already present.
// ----------------------------------------------------------------------------------

function str_suffix( $string, $char)
{
  if (empty($string))
    return '';
 elseif ( $string[strlen($string)-1] == $char)
    return $string;
  else
    return $string.$char;
}

// ----------------------------------------------------------------------------------
// Returns the null device (/dev/null in UNIX, :null in windows.
// ----------------------------------------------------------------------------------

function os_null()
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return ':null';
  else
    return '/dev/null';
}

// ----------------------------------------------------------------------------------
// Returns the last value in an array without removing it
// ----------------------------------------------------------------------------------

function array_last( &$array )
{
  return $array[count($array)-1];
}

// ----------------------------------------------------------------------------------
// Checks to see if the PHP option "Magic Quotes" is turned on, and if it is then this
// function strips the slashes from the input.
// ----------------------------------------------------------------------------------

function un_magic_quote( $text )
{
  if ( get_magic_quotes_gpc() == 1)
  {
    if ( is_array($text) )
    {
      foreach ($text as $key=>$value)
        $text[$key] = stripslashes($value);
    }
    else
      $text = stripslashes($text);
  }
  return $text;
}

// ----------------------------------------------------------------------------------
// this function will return the value of a given variable which is stored in a text string
// of the following format (eg: an SQL statement): ^.*variable='value'.* variable='value'...
// ----------------------------------------------------------------------------------

function var_in_string( $string, $var)
{
  $results = array ();
  preg_match("/".$var."\s*=\s*'([^']*)'/",$string,$results);
  return $results[1];
}

// ----------------------------------------------------------------------------------
// Takes an amount in seconds, and returns a string reading "x Hours, Y Minutes, Z seconds"
// ----------------------------------------------------------------------------------

function hhmmss( $secs )
{
  $str = '';

  if ($secs > 86400)
  {
    $str .= floor($secs/86400).'d : ';
    $secs = $secs % 86400;
  }

  if ($secs > 3600)
  {
    $str .= floor($secs/3600).'h : ';
    $secs = $secs % 3600;
  }

  if ($secs > 60)
  {
    $str .= floor($secs/60).'m : ';
    $secs = $secs % 60;
  }

  return $str.$secs.'s';
}

// ----------------------------------------------------------------------------------
// Gets the details of the image, and adjusts the $X and $Y parameters to reflect the
// true image size if the image was resized to fit within the $Xx$Y rectangle, whilst
// maintaining the aspect ratio.
// ----------------------------------------------------------------------------------

function image_resized_xy( $filename, &$x, &$y )
{
  // Create a new image
  $image = new CImage();

  // Load the image from disk
  if (strtolower(file_ext($filename)) == 'sql')
    $image->load_from_database( substr($filename,0,-4) );
  elseif ( file_exists($filename) || is_remote_file($filename) )
    $image->load_from_file($filename);
  else
    send_to_log(1,'Unable to process image specified : '.$filename);

  $imagedata = array($image->width, $image->height);

  $x = convert_x($x);
  $y = convert_y($y);

  $a = $x / $y;
  $b = $imagedata[0] / $imagedata[1];

  if ($a < $b)
  {
    $y = floor(($x / $imagedata[0]) * $imagedata[1]);
  }
  else
  {
    $x = floor(($y / $imagedata[1]) * $imagedata[0]);
  }

  $x = convert_tolog_x($x);
  $y = convert_tolog_y($y);
}

/**
 * Checks that a socket is open and listening for requests.
 *
 * @param string $address
 * @param integer $port
 * @param integer $timeouts
 * @return boolean
 */

function socket_check( $address, $port, $timeouts = 3)
{
  for ($i=0; $i < $timeouts; $i++)
    if ( $sock = @fsockopen($address, $port, $errno, $errst, 0.5))
    {
      fclose($sock);
      return true;
    }

  return false;
}

// ----------------------------------------------------------------------------------
// Sets the status of the "New Media" indicator light on the showcenter box
// ----------------------------------------------------------------------------------

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
      {
        if (socket_check($ip,2020,1))
          $dummy = @file_get_contents('http://'.$ip.':2020/LED_indicate.cgi?%7FStatusLED='.$status);
      }
    }
  }
}

// ----------------------------------------------------------------------------------
// Sorts an array of arrays based on the given key in the nested array.
// ----------------------------------------------------------------------------------

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

// ----------------------------------------------------------------------------------
// Makes an array contain unique values for the given key within the nested array.
// ----------------------------------------------------------------------------------

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
        $keys[]   = $array[$i][$key];
      }
    }
  }

  return $rArray;
}

// ----------------------------------------------------------------------------------
// Returns the SwissCenter version (the highest of the last version to be updated to
// online or the version of the database).
// ----------------------------------------------------------------------------------

function swisscenter_version()
{
  return max( get_sys_pref('last_update') ,get_sys_pref('database_version'));
}

// ----------------------------------------------------------------------------------
// Attempts to convert a decimal number to a fraction. Firstly, the standard shutter
// speeds are used to determine if one of them is +/- 5% of the value.
// ----------------------------------------------------------------------------------

function dec2frac( $decimal)
{
  // If the number passed in is 0, then exit immediately.
  if ($decimal == 0)
    return '';

  $speeds = array(  '1/8000' => 1/8000,   '1/7500' => 1/7500,   '1/7000' => 1/7000,   '1/6500' => 1/6500,   '1/6000' => 1/6000,
                    '1/5500' => 1/5500,   '1/5000' => 1/5000,   '1/4500' => 1/4500,   '1/4000' => 1/4000,   '1/3500' => 1/3500,
                    '1/3000' => 1/3000,   '1/2500' => 1/2500,   '1/2000' => 1/2000,   '1/1500' => 1/1500,   '1/1000' => 1/1000,
                    '1/750'  => 1/750,    '1/500'  => 1/500,    '1/350'  => 1/350,    '1/250'  => 1/250,    '1/180'  => 1/180,
                    '1/125'  => 1/125,    '1/90'   => 1/90,     '1/60'   => 1/60,     '1/45'   => 1/45,     '1/30'   => 0/30,
                    '1/20'   => 1/20,     '1/15'   => 1/15,     '1/10'   => 1/10,     '1/8'    => 1/8,      '1/6'    => 1/6,
                    '1/4'    => 1/4,      '0"3'    => 0.3,      '0"5'    => 0.5,      '0"7'    => 0.7,      '1"'     => 1,
                    '1"5'    => 1.5,      '2"'     => 2,        '3"'     => 3,        '4"'     => 4,        '6"'     => 096,
                    '8"'     => 128,        '10"'    => 160,       '15"'    => 240,       '20"'    => 320,       '30"'    => 30       ) ;

  // Try to match to the above shutter speeds.
  foreach ($speeds as $key => $val)
    if ($decimal > ($val*0.95) && $decimal < ($val*1.05))
      return $key;

  $decimal = (string)$decimal;

  $num = '';
  $den = 1;
  $dec = false;

  // find least reduced fractional form of number
  for( $i = 0, $ix = strlen( $decimal ); $i < $ix; $i++ )
  {
   // build the denominator as we 'shift' the decimal to the right
   if( $dec ) $den *= 10;

   // find the decimal place/ build the numerator
   if( $decimal{$i} == '.' ) $dec = true;
   else $num .= $decimal{$i};
  }
  $num = (int)$num;

  // whole number, just return it
  if( $den == 1 )
    return $num;

  $num2 = $num;
  $den2 = $den;
  $rem  = 1;

  // Euclid's Algorithm (to find the gcd)
  while( $num2 % $den2 )
  {
   $rem = $num2 % $den2;
   $num2 = $den2;
   $den2 = $rem;
  }

  if( $den2 != $den )
    $rem = $den2;

  // now $rem holds the gcd of the numerator and denominator of our fraction
  return  ($num / $rem ) . "/" . ($den / $rem);
}

/**
 * Returns the date/time in GMT from a NIST time-server. The default timeserver used
 * is time-a.timefreq.bldrdoc.gov, however any of the following are valid:
 *
 *   time-a.timefreq.bldrdoc.gov
 *   time-b.timefreq.bldrdoc.gov
 *   time-c.timefreq.bldrdoc.gov
 *   time-d.timefreq.bldrdoc.gov
 *
 * @param string $timeserver - hostname of timeserver
 * @param integer $socket -socket (13)
 * @return timestamp
 */

function query_time_server ($timeserver = 'time-a.timefreq.bldrdoc.gov', $socket = 13)
{
  $time = false;
  if ($fp = @fsockopen($timeserver,$socket,$errno,$errstr,2))
  {
    fputs($fp,"\n");
    $value = fread($fp,49);
    fclose($fp);

    if ($value !== false && $value > 0)
    {
      $components = explode(' ',$value);
      dump($components);
      list( $h, $min, $s) = explode(':',$components[2]);
      list( $y, $m, $d) = explode('-',$components[1]);
      $time = gmmktime( $h, $min, $s, $m, $d, $y);
    }
  }
  return $time;
}

/**
 * Returns the current time (as a unix timestamp) in the GMT timezone.
 *
 */

function gmt_time()
{
  $offset = get_sys_pref('GMT_OFFSET',false);

  // We only trust the stored offset if was calculated less than 24 hours ago. This is to ensure that DST changes take effect.
  if ( internet_available() && ($offset === false || get_sys_pref_modified_date('GMT_OFFSET') < db_datestr(time()-86400)) )
  {
    // Get the GMT time from a web service
    send_to_log(6,'Attempting to get GMT Standard Time from NIST timeserver');
    $gmt = query_time_server();

    if ($gmt !== false)
    {
      $time = $gmt;
      $offset = time()-$gmt;
      send_to_log(5,'Your local PHP time ('.gmdate('Y.m.d H:i:s').') is '.abs($offset).' seconds '.($offset > 0 ? 'ahead of' : 'behind').' GMT');

      // Store the offset in the database
      set_sys_pref('GMT_OFFSET', $offset);
    }
    else
    {
      // Return PHP time() - UTC Offset + DST
      $time = time();
      send_to_log(2,'Unable to get GMT time from web service, using PHP time',gmdate('Y.m.d H:i:s',$time));
    }
  }
  else
  {
    $time = time() - $offset;
    send_to_log(6,'Using previously stored GMT time',gmdate('Y.m.d H:i:s',$time));
  }

  return $time;
}

/**
 * Sets a variable only if it's value is not null.
 *
 * @param $var - variable to be set
 * @param $value - value to be set
 */

function set_var( &$var, $value )
{
  if (is_null($value))
    return false;
  else
  {
    $var = $value;
    return true;
  }
}

/**
 * Find position of last occurrence of a string in a string.
 * This is similar to the PHP5 function strripos.
 *
 * @param string $string
 * @param string $searchFor
 * @param string $startFrom
 * @return integer
 */

function strrpos_str($string, $searchFor, $startFrom = 0)
{
  $addLen = strlen ($searchFor);
  $endPos = $startFrom - $addLen;
  while (true)
  {
    if (($newPos = strpos ($string, $searchFor, $endPos + $addLen)) === false) break;
    $endPos = $newPos;
  }
  return ($endPos >= 0) ? $endPos : false;
}

/**
 * Returns the MySQL version.
 *
 * @return string
 */

function mysql_version()
{
  if (extension_loaded('mysql') && ($db = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD )) )
  {
    $stmt = mysql_query( 'select version()', $db);
    if ($row = mysql_fetch_array( $stmt, MYSQL_ASSOC ))
      return array_pop($row);
    else
      return false;
  }
  else
    return false;
}

/**
 * Returns the MySQL charset.
 *
 * @return string
 */

function mysql_charset()
{
  if (extension_loaded('mysql') &&  ($db = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD )) )
  {
    $stmt = mysql_query( 'select charset(\'abc\')', $db);
    if ($row = mysql_fetch_array( $stmt, MYSQL_ASSOC ))
      return array_pop($row);
    else
      return false;
  }
  else
    return false;
}

/**
 * Returns the MySQL collation.
 *
 * @return string
 */

function mysql_collation()
{
  if (extension_loaded('mysql') &&  ($db = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD )) )
  {
    $stmt = mysql_query( 'select collation(\'abc\')', $db);
    if ($row = mysql_fetch_array( $stmt, MYSQL_ASSOC ))
      return array_pop($row);
    else
      return false;
  }
  else
    return false;
}

/**
 * Highlight the specified text in a string
 *
 * @param string $text
 * @param string $search
 * @param color $color
 * @return string
 */

function highlight($text, $search, $color='Silver')
{
  if (empty($text) || empty($search))
    return $text;
  else
    return preg_replace('/('.$search.')/i', '<FONT style="BACKGROUND-COLOR: '.$color.'">$1</FONT>', $text);
}

/**
 * Convert url using mms: protocol to rtsp:.
 *
 * @return string
 */

function convert_mms_to_rtsp( $url )
{
  return (get_sys_pref('CONVERT_MMS_TO_RTSP','YES')=='YES' ? str_replace('mms://','rtsp://',$url) : $url);
}

/**
 * Convert special characters to XML entities, decoding HTML entities
 *
 * @param string $string
 * @return string
 */

function xmlspecialchars( $text )
{
  return str_replace('&#039;', '&apos;', htmlspecialchars( html_entity_decode($text, ENT_QUOTES) ));
}

function xmlspecialchars_decode( $text )
{
  return str_replace('&apos;', '\'', html_entity_decode($text, ENT_QUOTES));
}

if(!function_exists('mime_content_type')) {

  function mime_content_type($filename)
  {
    $mime_types = array(

      // images
      'png' => 'image/png',
      'jpe' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'jpg' => 'image/jpeg',
      'gif' => 'image/gif',
      'bmp' => 'image/bmp',
      'tiff' => 'image/tiff',
      'tif' => 'image/tiff',

      // audio
      'ac3' => 'audio/ac3',
      'm4a' => 'audio/mpeg',
      'mp2' => 'audio/mpeg',
      'mp3' => 'audio/mpeg',
      'ogg' => 'audio/ogg',
      'tta' => 'audio/tta',
      'wav' => 'audio/x-wav',
      'wma' => 'audio/x-ms-wma',
      'flac' => 'audio/x-flac',

      // video
      'asf' => 'video/x-ms-asf',
      'avi' => 'video/x-msvideo',
      'm4v' => 'video/x-m4v',
      'mkv' => 'video/x-matroska',
      'mp4' => 'video/mp4',
      'mpe' => 'video/mpeg',
      'mpeg' => 'video/mpeg',
      'mpg' => 'video/mpeg',
      'vob' => 'video/mpeg',
      'wmv' => 'video/x-ms-wmv',
      'qt' => 'video/quicktime',
      'mov' => 'video/quicktime',
      'flv' => 'video/x-flv',

    );

    $ext = strtolower(array_pop(explode('.',$filename)));
    if (array_key_exists($ext, $mime_types)) {
      return $mime_types[$ext];
    }
    elseif (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME);
      $mimetype = finfo_file($finfo, $filename);
      finfo_close($finfo);
      return $mimetype;
    }
    else {
      return 'application/octet-stream';
    }
  }
}

function unicode_decode($str)
{
  return preg_replace('/\%u([0-9a-f]{4})/e', "unicode_value(\\1)", $str);
}

function unicode_value($code)
{
  $value=hexdec($code);
  if($value<0x0080)
    return chr($value);
  elseif($value<0x0800)
    return chr((($value&0x07c0)>>6)|0xc0)
          .chr(($value&0x3f)|0x80);
  else
    return chr((($value&0xf000)>>12)|0xe0)
          .chr((($value&0x0fc0)>>6)|0x80)
          .chr(($value&0x3f)|0x80);
}

/**
 * Check whether a URL exists.
 *
 * @param string $url
 * @return boolean
 */
function url_exists($url)
{
  $hdrs = @get_headers($url);
  return is_array($hdrs) ? preg_match('/^HTTP\\/\\d+\\.\\d+\\s+2\\d\\d\\s+.*$/',$hdrs[0]) : false;
}

/**
 * Given a string to search for ($needle) and an array of possible matches ($haystack) this
 * function will return the index number of the best match and set $accuracy to the value
 * determined (0-100). If no match is found, then this function returns FALSE
 *
 * @param string $needle - title to match against
 * @param array $haystack - Array of values to check against
 * @param integer $accuracy - accuracy level (0-100)
 * @return integer - index into the $haystack array for the best match (or FALSE).
 */

function best_match ( $needle, $haystack, &$accuracy )
{
  $best_match = array("id" => 0, "chars" => 0, "pc" => 0);

  foreach ($haystack as $i=>$item)
  {
    $chars = similar_text(strtolower(trim($needle)),strtolower(trim($item)),$pc);
    $haystack[$i] .= " (".round($pc,2)."%)";

    if ( ($chars > $best_match["chars"] && $pc >= $best_match["pc"]) || $pc > $best_match["pc"])
      $best_match = array("id" => $i, "chars" => $chars, "pc" => $pc);
  }

  // If we are sure that we found a good result, then get the file details.
  if ($best_match["pc"] > 75)
  {
    send_to_log(6,'Possible matches are:',$haystack);
    send_to_log(4,'Best guess: ['.$best_match["id"].'] - '.$haystack[$best_match["id"]]);
    $accuracy = $best_match["pc"];
    return $best_match["id"];
  }
  else
  {
    send_to_log(4,'Multiple Matches found, No match > 75%',$haystack);
    return false;
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
