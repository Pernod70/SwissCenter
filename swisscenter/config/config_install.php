<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  /**
   * Displays a form to the user to prompt them for the details required to create a new
   * swisscenter database
   *
   * @param string $message - optional feedback message
   */
  
  function install_display($message = '')
  {
    $host = (!empty($_REQUEST["host"])     ? $_REQUEST["host"]     : (defined('DB_HOST')     ? DB_HOST : 'localhost'));
    $name = (!empty($_REQUEST["username"]) ? $_REQUEST["username"] : (defined('DB_USERNAME') ? DB_USERNAME : 'swisscenter'));
    $pass = (!empty($_REQUEST["password"]) ? $_REQUEST["password"] : (defined('DB_PASSWORD') ? DB_PASSWORD : 'swisscenter'));
    $db   = (!empty($_REQUEST["dbname"])   ? $_REQUEST["dbname"]   : (defined('DB_DATABASE') ? DB_DATABASE : 'swiss'));
    
    echo "<h1>".str('CONFIG_DB_CREATE')."</h1>";

    if ( test_db() == 'OK' )
      message("!".str('DROP_DATABASE'));
    
    message($message);
    form_start('index.php');
    form_hidden('section','INSTALL');
    form_hidden('action','RUN');

    echo '<p>'.str('CONFIG_DB_CREATE_TXT').'<p>';
    
    form_input('host',str('MACHINE_NAME'),15,'',$host);
    form_input('dbname',str('DATABASE_NAME'),15,'',$db);
    form_input('username',str('USERNAME'),15,'',$name);
    form_input('password',str('PASSWORD'),15,'',$pass);
             
    form_input('root_password',str('CONFIG_DB_ROOT_PW'),15,'',$_REQUEST["root_password"], true);
    form_label(str('CONFIG_DB_ROOT_PW_TXT'));
    form_submit(str('CONFIG_DB_CREATE'));
    form_end();
  }
  
/**
 * Creates a swisscenter database
 *
 */
  
  function install_run()
  {
    set_time_limit(86400);
    
    define('DB_HOST',     (!empty($_REQUEST["host"])     ? $_REQUEST["host"]     : 'localhost'));
    define('DB_USERNAME', (!empty($_REQUEST["username"]) ? $_REQUEST["username"] : 'swisscenter'));
    define('DB_PASSWORD', (!empty($_REQUEST["password"]) ? $_REQUEST["password"] : 'swisscenter'));
    define('DB_DATABASE', (!empty($_REQUEST["dbname"])   ? $_REQUEST["dbname"]   : 'swiss'));

    $pass=un_magic_quote($_REQUEST["root_password"]);
    $db_stat = test_db(DB_HOST,'root',$pass,DB_DATABASE);

    if ($db_stat == 'OK')     
      db_root_sqlcommand($pass,"Drop database ".DB_DATABASE);
    
    if     ( db_root_sqlcommand($pass,"Create database ".DB_DATABASE) == false)
      install_display('!'.str('DB_CREATE_DB_ERR'));
    elseif ( db_root_sqlcommand($pass,"Grant all on ".DB_DATABASE.".* to ".DB_USERNAME."@'".DB_HOST."' identified by '".DB_PASSWORD."'") == false)
      install_display('!'.str('DB_CREATE_USER_ERR'));
    else 
    {
      // Fix for MySQL 4.1 and above (the authentication method changed and PHP 4.x uses an older uncompatible MySQL client).
      @db_root_sqlcommand($pass,"set password for ".DB_USERNAME."@'".DB_HOST."' = OLD_PASSWORD('".DB_PASSWORD."')");  
            
      // Open the setup.sql file
      if ( file_exists('../setup.sql') )
      {
        // Run the setup file and all database update files
        db_sqlfile('../setup.sql');
        apply_database_patches();

        // Write an ini file with the database parameters in it
        write_ini ( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE );

        // Default cache, playlists location and limit (default is no limit)
        set_sys_pref('CACHE_DIR',SC_LOCATION.'cache');
        set_sys_pref('PLAYLISTS',SC_LOCATION.'playlists');
        set_sys_pref('CACHE_MAXSIZE_MB','20');

        // Display the config page
        header('Location: index.php');
      }
      else
      {
        install_display('!'.str('MISSING_SETUP_SQL'));
      }
    }
  }
 
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
