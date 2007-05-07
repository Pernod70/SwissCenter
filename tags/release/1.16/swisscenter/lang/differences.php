<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/page.php'));

  function get_lang_strings ( $fsp )
  {
    $keys = array();
    
    foreach (explode("\n",str_replace("\r",null,file_get_contents($fsp))) as $line)
      if ( strlen($line) > 0 && $line[0] != '#')
      {
        $ex = explode('=',$line,2);
        $keys[strtoupper(trim($ex[0]))] = ltrim($ex[1]);
      }      
    
    return $keys;  
  }  
  
  $lang_dir = SC_LOCATION.'lang/';
  $master = get_lang_strings($lang_dir.'en/en.txt');

  foreach ( dir_to_array($lang_dir, '^[^.]', DIR_TO_ARRAY_SHOW_DIRS) as $dir )
  {
    if ( $dir != 'en')
    {
      $lang_strings = get_lang_strings($lang_dir.$dir.'/'.$dir.'.txt');
      echo "<p><h2>Language : $dir</h2>";
      
      foreach ($master as $k=>$v )
        if (! array_key_exists($k, $lang_strings))
          echo "<br>$k = $v";
    }
  }
  
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
