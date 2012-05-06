<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function ext_files_display( $message = '')
  {
    echo "<h1>".str('EXTERNAL_FILES')."</h1>";
    message($message);

    form_start('index.php');
    form_hidden('section','EXT_FILES');
    form_hidden('action','UPDATE');
    form_input('wget',str('WGET_PATH'),80,'',wget_location());
    form_label(str('WGET_PATH_PROMPT'));
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function ext_files_update()
  {
    $wget = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["wget"])),'/');

    if ( !is_file($wget) )
      ext_files_display("!".str('WGET_PATH_NOT_FOUND'));
    else
    {
      set_sys_pref('WGET_PATH',$wget);
      ext_files_display(str('SAVE_SETTINGS_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
