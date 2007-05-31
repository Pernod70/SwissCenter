<?
/**************************************************************************************************
   SWISScenter Source                                                              Didier Moens
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function misc_display( $message = '')
  {
  	$fontwidth_multiplier = (!empty($_REQUEST["fontwidth_multiplier"]) ? $_REQUEST["fontwidth_multiplier"] : get_sys_pref("FONTWIDTH_MULTIPLIER",1.0));
 
    echo "<h1>".str('MISC_CONFIG_TITLE')."</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','MISC');
    form_hidden('action','UPDATE');
    form_input('fontwidth_multiplier',str('FONTWIDTH_MULTIPLIER'),3,'', $fontwidth_multiplier);
    form_label(str('FONTWIDTH_MULTIPLIER_PROMPT'));    
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function misc_update()
  {
    $fontwidth_multiplier = $_REQUEST["fontwidth_multiplier"];

    if (! form_mask($fontwidth_multiplier,'[0-9]'))
      cache_display("!".str('MISC_FONTWIDTH_MULTIPLIER_ERROR_NOT_NUMBER'));
    elseif ( $fontwidth_multiplier < 1.0 )
      cache_display("!".str('MISC_FONTWIDTH_MULTIPLIER_ERROR_SMALL'));
    else
    {
      set_sys_pref('FONTWIDTH_MULTIPLIER',$fontwidth_multiplier);
      misc_display(str('SAVE_SETTINGS_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
