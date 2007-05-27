<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/../base/swisscenter_configuration.php'));

  /**
   * Outputs a config page that allows the user to either export their settings to an XML file
   * or upload an existing XML file to import previously saved settings.
   *
   */

  function impexp_display( $message = '')
  {
    echo '<h1>'.str('EXPORT_SETTINGS').'</h1>';
    echo '<p>'.str('EXPORT_PROMPT');

    form_start('export_config.php', 150, 'conn');
    form_submit(str('EXPORT_SETTINGS_SUBMIT'),2,'left',240);
    form_end();       

    echo '<h1>'.str('IMPORT_SETTINGS').'</h1>';
    
    if (is_array($message))
    {
      echo '<center><table width="90%" cellspacing=0 cellpadding=0 border=1><tr><td>';
      message('!'.str('IMPORT_FAILED'));
      echo('<ul>');
      foreach ($message as $msg)
        echo '<li>'.$msg;      
      echo '</ul></td></tr></table></center>';
    }
    else
      message($message);
      
    echo '<p>'.str('IMPORT_PROMPT');

    form_start('index.php', 150, 'conn');
    form_hidden('section', 'IMPEXP');
    form_hidden('action', 'IMPORT');
    form_upload('filename',str('IMPORT_FILE'),40);
    form_submit(str('UPLOAD_FILE'));
    form_end();       
  }
  
  /**
   * Imports the swisscenter settings from an XML file
   *
   */
  
  function impexp_import()
  {
    if (empty($_FILES["filename"]["name"]))
      impexp_display('!'.str('UPLOAD_FAILED'));
    else 
    {
      $swiss = new Swisscenter_Configuration( $_FILES["filename"]["tmp_name"] );
      $errors = $swiss->import_all();
      
      if (count($errors) ==0)
        impexp_display(str('IMPORT_SUCCESS'));
      else 
        impexp_display($errors);
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>