<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

include_once('prefs.php');

//-------------------------------------------------------------------------------------------------
// This procedure loads the language definitions into the session (and also updates the system 
// preference for the default language);
//-------------------------------------------------------------------------------------------------

function load_lang ( $lang = 'en')
{
  $lang_file = SC_LOCATION.'lang/'.$lang.'.txt';
  $keys      = array();
  
  if (file_exists($lang_file))
  {
    foreach (explode("\n",str_replace("\r",null,file_get_contents($lang_file))) as $line)
      if ( strlen($line) > 0 && $line[0] != '#')
      {
      	$ex = explode('=',$line,2);
        $keys[strtoupper(trim($ex[0]))] = $ex[1];
      }      

    $_SESSION["language"] = $keys;
    set_sys_pref('DEFAULT_LANGUAGE',$lang);
  }
  else 
    send_to_log("Unable to locate $lang language file");
}

//-------------------------------------------------------------------------------------------------
// This procedure stores the STYLE information in the current session. 
//-------------------------------------------------------------------------------------------------

function str( $key )
{
//  if (! isset($_SESSION["language"]))
    load_lang();
    
  $string = $_SESSION["language"][strtoupper($key)];
  $txt    = '';
  $i      = 1;
  
  while ( strpos($string,'%') !== false)
  {
  	$pos  = strpos($string,'%');
  	$txt .= substr($string,0,$pos);

  	if ($string[strpos($string,'%')+1] == 's')
  	{
  	  $txt.= @func_get_arg($i++);
      $string = substr($string,$pos+2);
  	}
  	else 
  	{
  	  $txt.='%';
      $string = substr($string,$pos+1);
  	} 
  }

  # These are the html tags that we will allow in our language files:
  $replace = array( '#<#'                   => '&lt;'
                  , '#>#'                   => '&gt;'
                  , '#\[[/]?p]#'            => '<p>'
                  , '#\[[/]?br]#'           => '<br>'
                  , '#\[[/]?b]#'            => '<b>'
                  , '#\[[/]?i]#'            => '<i>'
                  , '#\[[/]?em]#'           => '<em>'
                  );

  return '#'.
         preg_replace( array_keys($replace), array_values($replace), $txt.$string).
         '#';
}
   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
