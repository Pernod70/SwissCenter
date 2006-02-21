<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function support_display()
  {
    echo "<h1>".str('SUPPORT_TITLE')."</h1><p>".str('SUPPORT_TXT','<a href=\"www.swisscenter.co.uk\">www.swisscenter.co.uk</a>');
    
    echo "<h2>".str('SUPPORT_PROG_TITLE')."</h2>";
    $opts = array( array('Program'=>'PHP','Location'=>PHP_LOCATION),
                   array('Program'=>'Swisscenter','Location'=>SC_LOCATION),
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
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>