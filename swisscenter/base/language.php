<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/../ext/xml/xmlbuilder.php'));

/**
 * This procedure loads the specified language definitions into the database.
 *
 * @param string $lang
 */
function load_lang_xml ( $lang = 'en-GB' )
{
  $lang_file = SC_LOCATION."lang/$lang/$lang.xml";
  $checksum  = md5_file($lang_file);

  /// Proceed only if the language file has changed
  if (file_exists($lang_file) && $checksum !== get_sys_pref("LANG_CHKSUM_$lang"))
  {
    @set_magic_quotes_runtime(0);

    send_to_log(5,"Updating language translations from: $lang.xml");

    // Read and process XML file
    $data = file_get_contents($lang_file);

    if ($data !== false)
    {
      // Parse the language XML file
      preg_match_all('/<string>.*<id>(.*)<\/id>.*<text>(.*)<\/text>.*<version>(.*)<\/version>.*<\/string>/Uis', htmlspecialchars_decode($data), $matches);

      if (count($matches[0]) == 0)
        send_to_log(2,'- Parsing '.$lang_file.' failed to find any language strings!');
      else
      {
        // Ensure language exists in database
        $lang_id = db_value("select lang_id from translate_languages where ietf_tag = '$lang'");
        if (!$lang_id)
        {
          $lang_name = preg_get('/fullname="(.*)"/', htmlspecialchars_decode($data));
          $lang_id   = db_insert_row('translate_languages', array('ietf_tag'=>$lang, 'name'=>$lang_name));
        }

        // When importing base language 'en' mark all translation keys as unverified
        db_sqlcommand("update translate_keys set verified = '".($lang=='en' ? 'N' : 'Y')."'");

        // Import the translation into database
        foreach ($matches[1] as $index=>$id)
        {
          // Does the key already exist?
          $key_id = db_value("select key_id from translate_keys where text_id = '".trim($id)."'");
          if (!$key_id)
          {
            // Only add keys when importing base language 'en'
            if ($lang=='en')
            {
              send_to_log(6,'- Adding language key ['.trim($id).']');
              $key_id = db_insert_row('translate_keys', array('TEXT_ID'=>trim($id), 'VERIFIED'=>'Y'));
            }
            else
              send_to_log(2,'- Invalid language key ['.trim($id).'] ignored in '.basename($lang_file));
          }
          else
          {
            // Mark existing key as verified
            db_update_row('translate_keys', $key_id, array('VERIFIED'=>'Y'), 'key_id');
          }
          // If the key is valid then insert/update the translation
          if ($key_id)
          {
            if (db_value('select count(text) from translate_text where key_id='.$key_id.' and lang_id='.$lang_id) > 0)
              db_sqlcommand("update translate_text set text='".db_escape_str($matches[2][$index])."', version='".db_escape_str($matches[3][$index])."' where key_id=$key_id and lang_id=$lang_id");
            else
            {
              if (empty($matches[2][$index]))
                send_to_log(2,'- Invalid language text for key ['.trim($id).']');
              else
              {
                send_to_log(6,'- Adding language text for key ['.trim($id).']');
                db_insert_row('translate_text', array('KEY_ID'=>$key_id, 'LANG_ID'=>$lang_id, 'TEXT'=>$matches[2][$index] ,'VERSION'=>$matches[3][$index]));
              }
            }
          }
        }

        // Delete any translation keys that are not verified
        $keys = db_toarray("select text_id from translate_keys where verified = 'N'");
        foreach ($keys as $key)
          send_to_log(6,'- Removed language key ['.$key['TEXT_ID'].']');
        db_sqlcommand("delete from translate_keys where verified = 'N'");

        send_to_log(6,"Loaded $lang.xml language file into database");

        // Save the checksum for this language.
        set_sys_pref("LANG_CHKSUM_$lang", $checksum);
      }
    }
    else
      send_to_log(2,'Unable to read the language file: '.$lang_file);
  }
}

/**
 * Determine which language to use.
 */
function current_language()
{
  // If the user is logged in then use their preferred language
  if (($user_id = get_current_user_id()) !== false)
    $current_lang = get_user_pref('LANGUAGE','',$user_id);

  // If a language name was not given then try to work out which language to use
  if (empty($current_lang))
  {
    // Determine language to load from the browser identification (or the last used).
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
      $current_lang = array_shift(explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']));
    else
      $current_lang = get_sys_pref('DEFAULT_LANGUAGE','en-GB');
  }

  // Store this as the system default language
  set_sys_pref('DEFAULT_LANGUAGE',$current_lang);

  return $current_lang;
}

/**
 * Import all language definitions into the database.
 */
function load_translations()
{
  // Import all language translations, base 'en' must be first
  load_lang_xml('en');
  foreach (explode("\n", str_replace("\r", null, file_get_contents(SC_LOCATION.'lang/languages.txt'))) as $line)
  {
    $lang = explode(',',$line);
    if (!is_null($lang[0]) && strlen($lang[0])>0 && $lang[1]!=='en')
      load_lang_xml($lang[1]);
  }
}

/**
 * Returns the requested language string identified by $key. If $key does not exist then it is added
 * to the base language file.
 *
 * @param string $key
 * @return string
 */
function str( $key )
{
  $num_args = @func_num_args();
  $key = strtoupper($key);

  $lang = isset($_SESSION["language"]) ? $_SESSION["language"] : current_language();
  $base = 'en';

  $translation = db_row("select t1.text text_base, t2.text text_lang, t3.text text_region
                         from translate_keys k
                         left join translate_text t1 on t1.key_id=k.key_id and t1.lang_id=(select lang_id from translate_languages where ietf_tag='$base')
                         left join translate_text t2 on t2.key_id=k.key_id and t2.lang_id=(select lang_id from translate_languages where ietf_tag='".substr($lang, 0, 2)."')
                         left join translate_text t3 on t3.key_id=k.key_id and t3.lang_id=(select lang_id from translate_languages where ietf_tag='$lang')
                         where k.text_id='$key'");

  if (empty($translation['TEXT_BASE']))
  {
    $txt = '['.$key.']';

    if ($num_args>1)
      for ($i=1;$i<$num_args;$i++)
        $txt.= ' ['.@func_get_arg($i).']';

    // Automatically add any unknown strings to the database
    if (!db_value("select key_id from translate_keys where text_id='".$key."'"))
      db_insert_row('translate_keys', array('TEXT_ID'=>$key));

    return $txt;
  }
  else
  {
    $string = $translation['TEXT_BASE'];
    if (!empty($translation['TEXT_LANG'])) $string = $translation['TEXT_LANG'];
    if (!empty($translation['TEXT_REGION'])) $string = $translation['TEXT_REGION'];

    $txt    = '';
    $i      = 1;

    # These are the html tags that we will allow in our language files:
    $replace = array( '#<#' => '&lt;', '#>#' => '&gt;', '#\[(/?)(br|em|p|b|i|li|ul|ol)]#i' => '<\1\2>' );
    $string = preg_replace( array_keys($replace), array_values($replace), $string);

    # perform substitutions
    while (($pos=mb_strpos($string,'%')) !== false)
    {
      $txt .= mb_substr($string,0,$pos);

      if (mb_substr($string,$pos+1,1) == 's')
      {
        $txt.= @func_get_arg($i++);
        $string = mb_substr($string,$pos+2);
      }
      else
      {
        $txt.='%';
        $string = mb_substr($string,$pos+1);
      }
    }
    return $txt.$string;
  }
}

/**
 * Returns the URL needed to perform a search on the wikipedia internet site in the user's current
 * language. The $search_terms string contains the text to search for.
 *
 * @param string $search_terms
 * @return string
 */
function lang_wikipedia_search( $search_terms, $back_url = '' )
{
  // Determine the appropriate wikipedia address for the current language
  $lang = get_sys_pref('WIKIPEDIA_LANGUAGE','');
  if ( empty($lang) )
  {
    $lang = get_sys_pref('DEFAULT_LANGUAGE','en-GB');
    if ( mb_strpos($lang,'-') !== false)
      $lang = mb_substr($lang,0,strpos($lang,'-'));
  }
  return '/wikipedia_proxy.php?wiki='.urlencode($lang.'.wikipedia.org').'&url='.urlencode('/w/index.php').'&search='.urlencode($search_terms).'&back_url='.urlencode($back_url);
}

/**
 * Save the language definitions to XML file.
 *
 * @param string $lang
 */
function save_lang_xml( $lang_tag )
{
  $lang_file = SC_LOCATION."lang/$lang_tag/$lang_tag.xml";

  $fullname = db_value("select name from translate_languages where ietf_tag='$lang_tag'");
  $translations = db_toarray("select tk.text_id id, tt.text text, tt.version version
                              from translate_keys tk
                              left join translate_text tt on tt.key_id=tk.key_id and tt.lang_id=(select lang_id from translate_languages where ietf_tag='$lang_tag')
                              order by id");

  $xml = new XmlBuilder();
  $xml->Push('swisscenter');
  $xml->Push('languages');
  $xml->Push('language', array('name'     => $lang_tag,
                               'fullname' => $fullname));
  foreach ($translations as $translation)
  {
    if (!empty($translation['TEXT']))
    {
      $xml->Push('string');
      $xml->Element('id', $translation['ID']);
      $xml->Element('text', $translation['TEXT']);
      $xml->Element('version', $translation['VERSION']);
      $xml->Pop('string');
    }
  }
  $xml->Pop('language');
  $xml->Pop('languages');
  $xml->Pop('swisscenter');

  if (($fsp = fopen($lang_file, 'wb')) !== false)
  {
    fwrite($fsp, $xml->getXml());
    fclose($fsp);

    // Save the checksum for this language.
    $checksum = md5_file($lang_file);
    set_sys_pref("LANG_CHKSUM_$lang_tag", $checksum);

    send_to_log(6,"Saved $lang_file language file");
  }
  else
    send_to_log(6,"Failed to save language file: $lang_file");
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
