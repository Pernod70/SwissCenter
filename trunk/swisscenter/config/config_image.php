<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
 require_once( realpath(dirname(__FILE__).'/../base/image.php'));

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function image_display( $message = '')
  {
    $resize_vals  = array( str('IMAGE_RESIZE')=>'RESIZE',str('IMAGE_RESAMPLE')=>'RESAMPLE');
    $option_vals  = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');

    echo "<h1>".str('CONFIG_IMAGE_OPTIONS')."</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','IMAGE');
    form_hidden('action','UPDATE');
    form_radio_static('resize',str('IMAGE_RESIZE_TYPE'),$resize_vals, get_sys_pref('IMAGE_RESIZING','RESAMPLE'),false,true);
    form_label(str('IMAGE_RESIZE_PROMPT'));
    form_radio_static('precache',str('CACHE_PRECACHE'),$option_vals, get_sys_pref('CACHE_PRECACHE_IMAGES','NO'),false,true);
    form_label(str('CACHE_PRECACHE_PROMPT'));
    form_input('fontname',str('TTF_FONT'),30,'',get_sys_pref('TTF_FONT',0));
    form_label(str('TTF_FONT_PROMPT'));
    form_submit(str('SAVE_SETTINGS'));
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Saves the new parameters
  // ----------------------------------------------------------------------------------

  function image_update()
  {
    $img = new CImage();
    $fontname = $_REQUEST["fontname"];
    if (strtoupper(substr($fontname,strlen($fontname)-4)) != '.TTF'
       || $img->text('Test',0,0,0,14,$sfont)===FALSE) {
         $msg = str('FAIL_PHP_FONT_SET');
    } else {
      set_sys_pref('TTF_FONT',$fontname);
      $msg = '';
    }
    set_sys_pref('IMAGE_RESIZING',$_REQUEST["resize"]);
    set_sys_pref('CACHE_PRECACHE_IMAGES',$_REQUEST["precache"]);
    image_display(str('SAVE_SETTINGS_OK').$msg);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
