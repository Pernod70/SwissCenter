<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

//-------------------------------------------------------------------------------------------------
// This procedure loads the language definitions into the session (and also updates the system 
// preference for the default language);
//-------------------------------------------------------------------------------------------------

function load_lang_strings ( $lang = 'en-gb' )
{
  $lang_file = SC_LOCATION."lang/$lang/$lang.txt";
  $keys      = array();
  
  if (file_exists($lang_file))
  {
    foreach (explode("\n",str_replace("\r",null,file_get_contents($lang_file))) as $line)
      if ( strlen($line) > 0 && $line[0] != '#')
      {
      	$ex = explode('=',$line,2);
        $keys[strtoupper(trim($ex[0]))] = ltrim($ex[1]);
      }      
      
    send_to_log("Loaded $lang language file");
    
    if ( isset($_SESSION["language"]) && is_array($_SESSION["language"]))
      $_SESSION["language"] = array_merge( (array)$_SESSION["language"] , (array)$keys );
    else 
      $_SESSION["language"] = $keys;      
  }
}

function load_lang ($current_lang = '')
{
  // First load english so that we at least have a string for every token.
  load_lang_strings('en');
  
  // If a language name was not given then try to work out which language to use
  if ($current_lang == '')
  {
    // Determine language to load from the browser identification (or the last used). 
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
    {
  	  $langs_allowed = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']); 
      $current_lang = $langs_allowed[0];
    }
    else
    {
      $current_lang = get_sys_pref('DEFAULT_LANGUAGE','en-gb');
    }
  }

  // Now overlay the general language file (eg: 'fr') over the english 
  if (strpos($current_lang,'-') !== false && substr($current_lang,0,strpos($current_lang,'-')) != 'en')
    load_lang_strings(substr($current_lang,0,strpos($current_lang,'-')));
      
  // And finally, overlay with any regional variations (eg: 'fr-be')
  load_lang_strings($current_lang);

  // Store this as the system default language
  set_sys_pref('DEFAULT_LANGUAGE',$current_lang);
}

//-------------------------------------------------------------------------------------------------
// This procedure stores the STYLE information in the current session. 
//-------------------------------------------------------------------------------------------------

function str( $key )
{
  if (!isset($_SESSION["language"]) )
    load_lang();
    
  if (! isset($_SESSION["language"][strtoupper($key)]) )
  {
    return '['.strtoupper($key).']';
  }
  else
  {
    $string = $_SESSION["language"][strtoupper($key)];
    $txt    = '';
    $i      = 1;
    
    # These are the html tags that we will allow in our language files:
    $replace = array( '#<#' => '&lt;', '#>#' => '&gt;', '#\[(/?)(br|em|p|b|i|li|ul|ol)]#i' => '<\1\2>' );
    $string = preg_replace( array_keys($replace), array_values($replace), $string);

    # perform substitutions 
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
  
    return $txt.$string;
  }
}

//-------------------------------------------------------------------------------------------------
// Returns the URL needed to perform a search on the wikipedia internet site in the user's current
// language. The $search_terms string contains the text to search for.
//-------------------------------------------------------------------------------------------------

function lang_wikipedia_search( $search_terms )
{
  // Determine the appropriate wikipedia address for the current language
  $lang = get_sys_pref('DEFAULT_LANGUAGE','en-gb');
  if ( strpos($lang,'-') !== false)
    $lang = substr($lang,0,strpos($lang,'-'));

  return '/wikipedia_proxy.php?wiki='.urlencode($lang.'.wikipedia.org').'&url='.urlencode('/w/index.php').'&search='.urlencode($search_terms);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
