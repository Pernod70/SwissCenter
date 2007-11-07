<?php
/**************************************************************************************************
   SWISScenter Source                                                              Didier Moens
 *************************************************************************************************/
 require_once( realpath(dirname(__FILE__).'/../base/image.php'));

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
    form_input('fontname',str('TTF_FONT'),30,'',get_sys_pref('TTF_FONT'));
    form_label(str('TTF_FONT_PROMPT'));
    form_input('fontwidth_multiplier',str('FONTWIDTH_MULTIPLIER'),3,'', $fontwidth_multiplier);
    form_label(str('FONTWIDTH_MULTIPLIER_PROMPT'));    
    form_submit(str('SAVE_SETTINGS'));
    form_end();
    
    echo "<h1>".str('SUPPORT_CLIENTS_TITLE')."</h1><p>";
    array_to_table( db_toarray("select device_type,concat(browser_x_res,'x',browser_y_res) from clients order by 1,2"), 'Device,Resolution');
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function misc_update()
  {
    $img = new CImage();
    $msg = '';

    $fontwidth_multiplier = $_REQUEST["fontwidth_multiplier"];
    $fontname             = $_REQUEST["fontname"];

    if (! form_mask($fontwidth_multiplier,'[0-9]*'))
      misc_display("!".str('MISC_FONTWIDTH_MULTIPLIER_NOT_NUMBER'));
    elseif ( $fontwidth_multiplier < 0.5 )
      misc_display("!".str('MISC_FONTWIDTH_MULTIPLIER_TOO_SMALL'));
    else
    {
      // Check to see if the font specified can be successfully used.
      if ( $img->text('Test',0,0,0,14,$fontname) === FALSE) 
        $msg = str('FAIL_PHP_FONT_SET');
      else 
        set_sys_pref('TTF_FONT',$fontname);

      set_sys_pref('FONTWIDTH_MULTIPLIER',$fontwidth_multiplier);
      misc_display(str('SAVE_SETTINGS_OK').$msg);
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
