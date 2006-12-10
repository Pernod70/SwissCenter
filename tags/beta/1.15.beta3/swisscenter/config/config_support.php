<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function support_display( $log_mode = "", $debug_message = "")
  {
    // If we haven't passed through the stat of the LOG_MODE then take it from the ini file
    if ($log_mode == "")
      $log_mode = strtoupper(LOG_MODE);
    
    // Build list of debugging levels.
    $list = array( str('DEBUG_LEVEL',1,str('DEBUG_MINIMUM')) => 1 
                 , str('DEBUG_LEVEL',2) => 2
                 , str('DEBUG_LEVEL',3) => 3
                 , str('DEBUG_LEVEL',4) => 4
                 , str('DEBUG_LEVEL',5,str('DEBUG_RECOMMENDED')) => 5
                 , str('DEBUG_LEVEL',6) => 6
                 , str('DEBUG_LEVEL',7) => 7
                 , str('DEBUG_LEVEL',8) => 8
                 , str('DEBUG_LEVEL',9,str('DEBUG_MAXIMUM')) => 9
                 );    

    echo "<h1>".str('SUPPORT_TITLE')."</h1><p>".str('SUPPORT_TXT','<a href=\"www.swisscenter.co.uk\">www.swisscenter.co.uk</a>');
    
    echo "<h2>".str('DEBUG_MODE')."</h2>";
    message($debug_message);    
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'SUPPORT');
    form_hidden('action', 'SET_DEBUG');
    form_list_static('debug',str('DEBUG_MODE'), $list ,( (int)$log_mode>1 ? $log_mode:5),false,false);
    form_label(str('DEBUG_MODE_PROMPT'));
    form_submit(str('SAVE_SETTINGS'), 2);
    form_end();
    
    echo "<h2>".str('SUPPORT_PROG_TITLE')."</h2>";
    $opts = array( array('Program'=>'PHP (CLI Version)','Location'=>os_path(php_cli_location()) ),
                   array('Program'=>'PHP ini file','Location'=>os_path(php_ini_location()) ),
                   array('Program'=>'Swisscenter','Location'=>os_path(SC_LOCATION)),
                 );
    array_to_table($opts, str('SUPPORT_PROG_HEADINGS'));    
  
    echo "<h2>".str('SUPPORT_CLIENTS_TITLE')."</h2>";
    array_to_table(db_toarray('select ip_address, agent_string from clients order by ip_address')
                  ,str('SUPPORT_CLINTS_TABLE'));

    echo "<h2>".str('SUPPORT_DB_TITLE')."</h2>";
    echo '<table width="100%"><tr><td valign=top>';
      array_to_table( array( array('Connection Details'=>'Host = '.DB_HOST), 
                      array('Connection Details'=>'Database = '.DB_DATABASE),
                      array('Connection Details'=>'Username = '.DB_USERNAME), 
                      array('Connection Details'=>'Password = '.DB_PASSWORD) 
                    ),str('SUPPORT_DB_CONN_TABLE'));
      echo '<br>';
      array_to_table(db_toarray('show databases'),str('SUPPORT_DB_DB_LIST'));
    echo '</td><td valign=top>';
      $data = db_toarray('show tables');
      for ($i = 0; $i<count($data); $i++)
        $data[$i]['ROWS'] = db_value('select count(*) from '.$data[$i]['TABLES_IN_'.strtoupper(DB_DATABASE)]);
      array_to_table($data,str('SUPPORT_DB_TABLE_LIST'));
    echo '</td></tr></table>';
  
    echo "<h2>".str('SUPPORT_SYSPREF_TITLE')."</h2>";
    array_to_table(db_toarray('select * from system_prefs order by 1')
                  ,str('SUPPORT_SYSPREF_TABLE'));
  
    echo "<h2>".str('SUPPORT_USRPREF_TITLE')."</h2>";
    array_to_table(db_toarray('select u.name "User", up.name,up.value 
                                 from users u,user_prefs up 
                                where u.user_id = up.user_id order by 1,2')
                  ,str('SUPPORT_USRPREF_TABLE'));  
  }
  
  function support_set_debug ()
  {
    update_ini("swisscenter.ini","LOG_MODE",$_REQUEST["debug"]);
    support_display( $_REQUEST["debug"], str('SAVE_SETTINGS_OK'));
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>