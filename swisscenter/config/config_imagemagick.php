<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function imagemagick_display( $message = '')
  {
    echo "<h1>".str('IMAGEMAGICK_CONFIG')."</h1>";
    message($message);
    echo '<p>'.str('IMAGEMAGICK_DESC','<a href="http://www.imagemagick.org" target="_blank">www.imagemagick.org</a>');

    form_start('index.php');
    form_hidden('section','IMAGEMAGICK');
    form_hidden('action','UPDATE');
    form_input('im_path',str('IMAGEMAGICK_PATH'),50,'',get_sys_pref('IMAGEMAGICK_PATH'));
    form_label(str('IMAGEMAGICK_PATH_PROMPT'));
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function imagemagick_update()
  {
    $path = rtrim(str_replace('\\','/',$_REQUEST["im_path"]),'/');

    if ( !file_exists($path) )
      imagemagick_display("!".str('IMAGEMAGICK_PATH_NOT_FOUND'));
    else
    {
      set_sys_pref('IMAGEMAGICK_PATH',$path);
      imagemagick_display(str('SAVE_SETTINGS_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
