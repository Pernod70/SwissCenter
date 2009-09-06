<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/../ext/xml/xmlbuilder.php'));

//-------------------------------------------------------------------------------------------------
// This procedure loads the language definitions into the session (and also updates the system
// preference for the default language);
//-------------------------------------------------------------------------------------------------

function load_lang_strings ( $lang = 'en-gb', $session = 'language' )
{
  global $tag, $id, $text, $version, $keys;
  $lang_file = SC_LOCATION."lang/$lang/$lang.xml";
  $keys      = array();

  if (file_exists($lang_file))
  {
    // Create XML parser
    $xmlparser = xml_parser_create();
    if ($xmlparser !== false)
    {
      xml_set_element_handler($xmlparser, "start_tag_lang", "end_tag_lang");
      xml_set_character_data_handler($xmlparser, "tag_contents_lang");

      // Read and process XML file
      $data = file_get_contents($lang_file);
      if ($data !== false)
      {
        $data = preg_replace("/>\s+/", ">", $data);
        $data = preg_replace("/\s+</", "<", $data);
        if (!xml_parse($xmlparser, $data))
          send_to_log(2,'XML parse error: '.xml_error_string(xml_get_error_code($xmlparser)).xml_get_current_line_number($xmlparser));
        else
        {
          send_to_log(6,"Loaded $lang language file into $session");
          if ( isset($_SESSION[$session]) && is_array($_SESSION[$session]))
            $_SESSION[$session] = array_merge( (array)$_SESSION[$session] , (array)$keys );
          else
            $_SESSION[$session] = $keys;
        }
      }
      else
        send_to_log(6,'Unable to read the language file: ',$lang_file);

      xml_parser_free($xmlparser);
    }
    else
      send_to_log(2,'Unable to create an expat XML parser - is the "xml" extension loaded into PHP?');
  }
}

function load_lang ($current_lang = '')
{
  // Loading the language strings can sometimes cause a timeout
  set_user_timeout();

 /**
   * Determine which language to load
   */

  // If the user is logged in then use their preferred language
  if ($current_lang == '' && ($user_id = get_current_user_id()) !== false)
    $current_lang = get_user_pref('LANGUAGE','',$user_id);

  // If a language name was not given then try to work out which language to use
  if ($current_lang == '')
  {
    // Determine language to load from the browser identification (or the last used).
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
      $current_lang = array_shift(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']));
    else
      $current_lang = get_sys_pref('DEFAULT_LANGUAGE','en-gb');
    }

  /**
   * Load the language strings
   */

  $base = 'en';
  list($lang,$region) = explode('-',$current_lang);
  $used_cache = false;

  // Optimization - check the timestamp on all the files (base, language, region) and if none
  //                of them have changed then load the cached language strings.

  $cache_file   = get_sys_pref('cache_dir', SC_LOCATION)."/Lang-$base-$lang-$region.txt";
  $cache_chksum = get_sys_pref("LANG_CHKSUM_$base-$lang-$region");
  $checksum     = md5( @filemtime(SC_LOCATION."lang/$base/$base.xml").'/'
                     . @filemtime(SC_LOCATION."lang/$lang/$lang.xml").'/'
                     . @filemtime(SC_LOCATION."lang/$lang/$lang-$region.xml"));

  send_to_log(8,"Cached language settings", array("Cache file"=>$cache_file,"Cache Checksum"=>$cache_chksum,"New Checksum"=>$checksum));
  if ( $checksum == get_sys_pref("LANG_CHKSUM_$base-$lang-$region") )
  {
    send_to_log(6,"Loading cached language strings",$cache_file);
    $_SESSION['language'] = unserialize( file_get_contents($cache_file));
    $used_cache = ( is_array($_SESSION['language']) );
  }

  if ( $used_cache === false)
  {
    $_SESSION['language'] = array();

    // Load the $base, then overlay the $lang and finally the $region.
    load_lang_strings($base);
    if ($lang != $base)
      load_lang_strings($lang);
    if ( !empty($region) )
      load_lang_strings($lang.'-'.$region);

    // Create the cache file for this language combination and save the checksum for it.
    send_to_log(6,"Writing cached language strings",$cache_file);
    write_binary_file($cache_file, serialize($_SESSION['language']));
    set_sys_pref("LANG_CHKSUM_$base-$lang-$region", $checksum);
  }

  // Store this as the system default language
  set_sys_pref('DEFAULT_LANGUAGE',$current_lang);
}

//-------------------------------------------------------------------------------------------------
// This procedure stores the STYLE information in the current session.
//-------------------------------------------------------------------------------------------------

function str( $key )
{
  $num_args = @func_num_args();

  if (!isset($_SESSION["language"]) )
    load_lang();

  if (!isset($_SESSION["language"][strtoupper($key)]) || $_SESSION["language"][strtoupper($key)]['TEXT'] == '')
  {
    $txt = '['.strtoupper($key).']';

    if ($num_args>1)
      for ($i=1;$i<$num_args;$i++)
        $txt.= ' ['.@func_get_arg($i).']';

    // Automatically add any unknown strings to the base language file (DEVELOPERS ONLY)
    if (get_sys_pref('IS_DEVELOPMENT','NO') == 'YES')
    {
      if (!isset($_SESSION["language_base"][strtoupper($key)]) )
      {
        $_SESSION["language_base"] = array();
        load_lang_strings("en", "language_base");
        $_SESSION["language_base"][strtoupper($key)] = array('TEXT'    => '',
                                                             'VERSION' => swisscenter_version());
        save_lang('en', $_SESSION["language_base"]);
      }
    }

    return $txt;
  }
  else
  {
    $string = $_SESSION["language"][strtoupper($key)]['TEXT'];
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

//-------------------------------------------------------------------------------------------------
// Callback functions to perform parsing of the language files.
//-------------------------------------------------------------------------------------------------

function start_tag_lang($parser, $name, $attribs)
{
  global $tag, $id, $text, $version;
  if ($name == 'STRING') { $text = ''; }
  $tag = $name;
}

function end_tag_lang($parser, $name)
{
  global $tag, $id, $text, $version, $keys;
  if ($name == 'STRING') { $keys[strtoupper(trim($id))] = array('TEXT'    => ltrim($text),
                                                                'VERSION' => $version); }
}

function tag_contents_lang($parser, $data)
{
  global $tag, $id, $text, $version;
  if ($tag == "ID")      { $id = $data; }
  if ($tag == "TEXT")    { $text .= utf8_decode($data); }
  if ($tag == "VERSION") { $version = $data; }
}

//-------------------------------------------------------------------------------------------------
// Save the language definitions to XML file.
//-------------------------------------------------------------------------------------------------

function save_lang( $lang, $language )
{
  $lang_file = SC_LOCATION."lang/$lang/$lang.xml";

  // Order the language array by key
  ksort( $language );

  $xml = new XmlBuilder();
  $xml->Push('swisscenter');
  $xml->Push('languages');
  $xml->Push('language', array('name'     => $lang,
                               'fullname' => utf8_encode(trim($language['LANGUAGE']['TEXT']))));
  foreach ($language as $id=>$text)
  {
    if ($lang=='en' || !empty($text['TEXT']))
    {
      $xml->Push('string');
      $xml->Element('id', strtoupper(trim($id)));
      $xml->Element('text', utf8_encode(trim($text['TEXT'])));
      $xml->Element('version', $text['VERSION']);
      $xml->Pop('string');
    }
  }
  $xml->Pop('language');
  $xml->Pop('languages');
  $xml->Pop('swisscenter');

  if ($fsp = fopen(SC_LOCATION."lang/$lang/$lang.xml", 'wb'))
  {
	  fwrite($fsp, $xml->getXml());
    fclose($fsp);
    send_to_log(6,"Saved $lang_file language file");
  }
  else
    send_to_log(6,"Failed to save language file: $lang_file");
}

//-------------------------------------------------------------------------------------------------
// Converts language ini file to new xml format.
//-------------------------------------------------------------------------------------------------

function language_ini2xml( $lang )
{
  // Where is the SwissCenter installed?
  define('SC_LOCATION', str_replace('\\','/',realpath(dirname(dirname(__FILE__)))).'/' );

  $lang_file = SC_LOCATION."lang/$lang/$lang.txt";
  $language  = array();

  if (file_exists($lang_file))
  {
    foreach (explode("\n",str_replace("\r",null,file_get_contents($lang_file))) as $line)
    {
      if ( strlen($line) > 0 && $line[0] != '#')
      {
      	$ex = explode('=',$line,2);
        $language[strtoupper(trim($ex[0]))] = array('TEXT'=>ltrim($ex[1]), 'VERSION'=>'1.19');
      }
    }

    // Save the language file in xml
    save_lang($lang, $language);
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
