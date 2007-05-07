<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function cache_display( $message = '')
  {
    $option_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    $dir          = (!empty($_REQUEST["dir"])  ? $_REQUEST["dir"]  : get_sys_pref("CACHE_DIR"));
    $size         = (!empty($_REQUEST["size"]) ? $_REQUEST["size"] : get_sys_pref("CACHE_MAXSIZE_MB",50));

    echo "<h1>".str('CACHE_CONFIG_TITLE')."</h1>";
    message($message);
    form_start('index.php',160);
    form_hidden('section','CACHE');
    form_hidden('action','UPDATE');
    form_input('dir',str('CACHE_DIR'),40,'', os_path(un_magic_quote($dir)) );
    form_label(str('CACHE_DIR_PROMPT'));
    form_input('size',str('CACHE_SIZE'),3,'', $size);
    form_label(str('CACHE_SIZE_PROMPT'));    
    form_radio_static('style',str('CACHE_STYLE'),$option_vals, get_sys_pref('CACHE_STYLE_DETAILS','YES'),false,true);
    form_label(str('CACHE_STYLE_PROMPT'));    
    form_radio_static('lang',str('CACHE_LANG'),$option_vals, get_sys_pref('CACHE_LANGUAGE_STRINGS','YES'),false,true);
    form_label(str('CACHE_LANG_PROMPT'));    
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function cache_update()
  {
    $dir = rtrim(str_replace('\\','/',stripslashes($_REQUEST["dir"])),'/');
    $size = $_REQUEST["size"];

    if (empty($dir))
      cache_display("!".str('CACHE_ERROR_MISSING'));
    elseif ($size == '')
      cache_display("!".str('CACHE_ERROR_SIZE'));
    elseif (! form_mask($size,'[0-9]'))
      cache_display("!".str('CACHE_ERROR_NOT_NUMBER'));
    elseif ( $size <0 )
      cache_display("!".str('CACHE_ERROR_SMALL'));
    elseif (!file_exists($dir))
      cache_display("!".str('CACHE_ERROR_NOT_EXIST'));
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      cache_display("!".str('CACHE_ERROR_FULL_DIR'));
    else
    {
      set_sys_pref('CACHE_STYLE_DETAILS',$_REQUEST["style"]);
      set_sys_pref('CACHE_LANGUAGE_STRINGS',$_REQUEST["lang"]);
      set_sys_pref('CACHE_DIR',$dir);
      set_sys_pref('CACHE_MAXSIZE_MB',$size);
      cache_display(str('SAVE_SETTINGS_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
