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
    $pc_screen_size_opts  = array( "800x450"           => "800x450",
                                   "1024x768"          => "1024x768",
                                   "1280x1024"         => "1280x1024",
                                   "720x480 (NTSC)"    => "624x416",
                                   "720x576 (PAL)"     => "624x496",
                                   "1280x720 (720p)"   => "1280x720",
                                   "1920x1080 (1080p)" => "1920x1080");

    echo "<h1>".str('MISC_CONFIG_TITLE')."</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','MISC');
    form_hidden('action','UPDATE');
    form_input('fontname',str('TTF_FONT'),30,'',get_sys_pref('TTF_FONT'));
    form_label(str('TTF_FONT_PROMPT'));
    form_input('fontwidth_multiplier',str('FONTWIDTH_MULTIPLIER'),3,'', $fontwidth_multiplier);
    form_label(str('FONTWIDTH_MULTIPLIER_PROMPT'));
    form_list_static('pc_screen_size',str('PC_SCREEN_SIZE'), $pc_screen_size_opts, get_sys_pref('PC_SCREEN_SIZE','800x450'), false, false, false);
    form_label(str('PC_SCREEN_SIZE_PROMPT'));
    form_input('date_format',str('DATE_FORMAT'),15,'',get_sys_pref('DATE_FORMAT','%d%b%y'));
    form_label(str('DATE_FORMAT_TEST', db_value("select date_format(now(),'".get_sys_pref('DATE_FORMAT','%d%b%y')."')")));
    form_label(str('DATE_FORMAT_PROMPT'));
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function misc_update()
  {
    $img = new CImage();
    $msg = '';

    $fontwidth_multiplier = $_REQUEST["fontwidth_multiplier"];
    $pc_screen_size       = $_REQUEST["pc_screen_size"];
    $fontname             = $_REQUEST["fontname"];
    $date_format          = $_REQUEST["date_format"];

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
      set_sys_pref('PC_SCREEN_SIZE',$pc_screen_size);
      set_sys_pref('DATE_FORMAT',$date_format);
      unset($_SESSION["device"]);
      misc_display(str('SAVE_SETTINGS_OK').$msg);
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
