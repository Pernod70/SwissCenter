<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  ob_start();

  include_once('../base/mysql.php');
  include_once('../base/file.php');
  include_once('../base/html_form.php');
  include_once('common.php');
  
  //
  // Display a database error
  //
  
  function db_error()
  {
    return '!Unable to access the database - are your <i>Database Config</i> settings correct?';
  }
  
  //*************************************************************************************************
  // INSTALL section
  //*************************************************************************************************
  
  //
  // Install form - get MySQL root password from user
  //
  
  function install_display($message = '')
  {
    echo '<h1>Create Database</h1>
          <p>This screen allows you to create and populate the SwissCenter database in MySQL. The parameters
             that will be used to build the database are shown below. 
    
         <p><table align="center" width="300" class="form_select_tab">
            <tr><th> Paramter Name </th><th> Value </th></tr>
            <tr><td> Machine Name  </td><td> localhost   </td></tr>
            <tr><td> Database Name </td><td> swiss       </td></tr>
            <tr><td> Username      </td><td> swisscenter </td></tr>
            <tr><td> Password      </td><td> swisscenter </td></tr>
            </table>
             
          <p>Please enter the <em>"Root"</em> password for your MySQL installation into the field below and click on
             <em>"Create database"</em> to continue.';
  
    message($message);
    form_start('index.php');
    form_hidden('section','INSTALL');
    form_hidden('action','RUN');
    form_input('password','"Root" Password',15,'',$_REQUEST["password"]);
    form_label('Unless you entered a value for the <em>root</em> password during the installation of MySQL, then it is 
                probably not set and you should leave this field blank.
                <p><em>Please Note: Creating the database can take several minutes to complete. Please be patient. </em>');
    form_submit('Create Database');
    form_end();
  }
  
  //
  // Install form - get MySQL root password from user
  //
  
  function install_run()
  {
    $pass=un_magic_quote($_REQUEST["password"]);
    $db_stat = test_db('localhost','root',$pass,'swiss');
    
    if (!defined('DB_HOST'))
    {
      define('DB_HOST','localhost');
      define('DB_USERNAME','swisscenter');
      define('DB_PASSWORD','swisscenter');
      define('DB_DATABASE','swiss');
    }

    if     ( db_root_sqlcommand($pass,"Create database swiss") == false && $db_stat != 'OK')
      install_display('!Unable to create database - Is your MySQL "Root" password correct?');
    elseif ( db_root_sqlcommand($pass,"Grant all on swiss.* to swisscenter@'localhost' identified by 'swisscenter'") == false)
      install_display('!Unable to create user - Is your MySQL "Root" password correct?');
    else 
    {
      // Fix for MySQL 4.1 and above (the authentication method changed and PHP 4.x uses an older uncompatible MySQL client).
      if (substr(db_value("select version()"),0,3) >= 4.1)
        db_root_sqlcommand($pass,"set password for swisscenter@'localhost' = OLD_PASSWORD('swisscenter')");  
            
      // Open the setup.sql file
      if ( file_exists('../setup.sql') )
      {
        // Run the setup file and all database update files
        db_sqlfile('../setup.sql');
        foreach (dir_to_array('../database','update_[0-9.]*.sql') as $file)
          db_sqlfile($file);            

        write_ini ( 'localhost', 'swisscenter', 'swisscenter', 'swiss' );
        header('Location: index.php');
      }
      else
      {
        install_display('!The "Setup.sql" file in the ShowCenter directory is missing.');
      }
    }
  }
  
  //*************************************************************************************************
  // DATABASE section
  //*************************************************************************************************
  
  // Write config file to disk.
  function write_ini ( $host, $user, $pass, $name )
  {
     $str = ";************************************************************************* ".newline().
            "; This is the configuration file for SWISS-Center by Robert Taylor         ".newline().
            ";************************************************************************* ".newline().
            "".newline().
            "DB_HOST=$host ".newline().
            "DB_USERNAME=$user ".newline().
            "DB_PASSWORD=$pass ".newline().
            "DB_DATABASE=$name ".newline().
            "LOGFILE=".(defined('LOGFILE') ? LOGFILE : '');
  
    // Try to make a MySQL connection using the details given
    $db_stat = test_db($host,$user,$pass,$name); 
    
    if ($db_stat != 'OK')
      return false;
    else
    {
      // Try to write the settings to the swisscenter.ini file
      touch('swisscenter.ini');
      if (! $handle = @fopen('swisscenter.ini', 'w') )
        return false;
      else 
      {   
         $success = (fwrite($handle, $str));
         fclose($handle);
         return ($success !== false);
      }
    }       
  }
  
  //
  // Display current config
  //
  
  function db_display( $message = '')
  {
    $host = (!empty($_REQUEST["host"])     ? $_REQUEST["host"]     : (defined('DB_HOST')     ? DB_HOST : 'localhost'));
    $name = (!empty($_REQUEST["username"]) ? $_REQUEST["username"] : (defined('DB_USERNAME') ? DB_USERNAME : 'swisscenter'));
    $pass = (!empty($_REQUEST["password"]) ? $_REQUEST["password"] : (defined('DB_PASSWORD') ? DB_PASSWORD : 'swisscenter'));
    $db   = (!empty($_REQUEST["dbname"])   ? $_REQUEST["dbname"]   : (defined('DB_DATABASE') ? DB_DATABASE : 'swiss'));
    
    
    echo "<h1>Database Configuration</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','DB');
    form_hidden('action','UPDATE');
    form_input('host','Machine Name',15,'',$host);
    form_label('This should be set to "localhost" if MySQL and Apache are installed on the same machine');
    form_input('dbname','Database Name',15,'',$db);
    form_input('username','Username',15,'',$name);
    form_input('password','Password',15,'',$pass);
    form_submit();
    form_end();
  }
   
  //
  // Save new parameters
  //
  
  function db_update()
  {
    if (write_ini ( $_REQUEST["host"], $_REQUEST["username"], $_REQUEST["password"], $_REQUEST["dbname"] ))
      db_display('Settings written to the configuration file.');
    else 
      db_display('!Unable to write to configuration file.');
  }
  
  //*************************************************************************************************
  // USERS section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function users_display()
  {
    echo "<h1>User Management</h1>";
    echo "<p>I'm sorry but the ability to create multiple users of the SwissCenter, each with their own preferences 
             and access rights is a feature that is currently still in development.
          <p>This section of the configuration will become active as soon as the feature is released and your SwissCenter
             has been updated.";
  }
  
  //*************************************************************************************************
  // DIRS section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function dirs_display($delete = '', $new = '')
  {
    // Ensure that on Linux/Unix systems there is a "media" directory present for symbolic links to go in.
    if (!is_windows() && !file_exists($_SESSION["opts"]["sc_location"].'media'))
      mkdir($_SESSION["opts"]["sc_location"].'media');
    
    $data = db_toarray("select location_id, name 'Directory' ,media_name 'Type' from media_locations ml, media_types mt where mt.media_id = ml.media_type order by name");
     
    echo "<h1>Current Media Locations</h1>";
    message($delete);
    form_start('index.php');
    form_hidden('section','DIRS');
    form_hidden('action','DELETE');
    form_select_table('loc_id',$data,array('class'=>'form_select_tab','width'=>'100%'),'location_id');
    form_submit('Remove Selected Locations',1,'center');
    form_end();
  
    echo '<p><h1>Add A New Location<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','DIRS');
    form_hidden('action','NEW');
    form_list_dynamic('type','Media Type',"select media_id,media_name from media_types order by 2",$_REQUEST['type']);
    form_input('location','Location',70,'',un_magic_quote($_REQUEST['location']));
    form_label('Please specify the fully qualified directory path where the media can be found <p>For example: 
                On Windows this might be <em>"C:\Documents and Settings\Robert\My Documents\My Music"</em> or on 
                a LINUX system it might be <em>"/home/Robert/Music"</em>');
    form_submit('Add Location',2);
    form_end();

    // Removes all scheduled calls to the old xxx_db_update.php scripts
    $sched = syscall('at');
    foreach(explode("\n",$sched) as $line)
      if (strstr($line,'db_update.php'))
         syscall('at '.substr(ltrim($line),0,strpos(ltrim($line),' ')).' /delete');

    // If the media_search.php script is not scheduled, then schedule it now!
    if ( strstr($sched,'media_search.php') === false)
        run_background('media_search.php','M,T,W,Th,F,S,Su');  
  }
   
  //
  // Delete an existing location
  //
  
  function dirs_delete()
  {
    $selected = form_select_table_vals('loc_id');
    
    foreach ($selected as $id)
    {
      if (! is_windows() )
        unlink($_SESSION["opts"]["sc_location"].'media/'.$id);

      db_sqlcommand("delete from media_locations where location_id=".$id);
    }
  
    dirs_display('The selected directories have been removed.');
  }
  
  //
  // Add a new location
  //
  
  function dirs_new()
  {
    // Process the directory passed in
    $dir = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["location"])),'/');
    
    if (empty($_REQUEST["type"]))
      dirs_display('',"!Please select the media type below.");
    elseif (empty($_REQUEST["location"]))
      dirs_display('',"!Please select the media location below.");
    elseif (!file_exists($dir))
      dirs_display('',"!I'm sorry, the directory you specified does not exist");
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      dirs_display('',"!Please enter a fully qualified directory path.");
    else 
    {
      if ( db_insert_row('media_locations',array('name'=>$dir,'media_type'=>$_REQUEST["type"])) === false)
      {
        dirs_display(db_error());
      }
      else
      {
        $id = db_value("select location_id from media_locations where name='$dir' and media_type=".$_REQUEST["type"]);
        
        if (! is_windows() )
          symlink($dir,$_SESSION["opts"]["sc_location"].'media/'.$id);
        
        dirs_display('Media Location Added');
      }
    }
  }
  
  //*************************************************************************************************
  // ART section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function art_display($delete = '', $new = '')
  {
    $data = db_toarray("select filename, filename 'Name' from art_files order by 1");
    
    echo "<h1>Album/Film Art files</h1>";
    message($delete);
    echo('<em>Album/Film Art</em> files are the names of image files that the SwissCenter should look for when it is
                    displaying a browse function (Browse Movies, Browse Music by folder, etc) or when you have selected an
                    album or film.
                 <p>If any of the files you specify here are found within a directory, then the first one to be found will 
                    be displayed on the Swisscenter interface.');
    form_start('index.php');
    form_hidden('section','ART');
    form_hidden('action','DELETE');
    form_select_table('filename',$data,array('class'=>'form_select_tab','width'=>'100%'),'filename');
    form_submit('Remove Selected files',1,'center');
    form_end();
  
    echo '<p><h1>Add a filename<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','ART');
    form_hidden('action','NEW');
    form_input('name','Filename',30,'',un_magic_quote($_REQUEST['name']));
    form_label('Please specify the name of any image file that should be used as album/film art if it is found 
                to be within a media directory.');
    form_submit('Add filename',2);
    form_end();
  }
   
  //
  // Delete an existing location
  //
  
  function art_delete()
  {
    $selected = form_select_table_vals('filename');
    
    foreach ($selected as $id)
      db_sqlcommand("delete from art_files where filename='".$id."'");
  
    art_display('The selected files have been removed.');
  }
  
  //
  // Add a new location
  //
  
  function art_new()
  {
    $name = un_magic_quote($_REQUEST["name"]);
    
    if (empty($name))
      art_display('',"!Please enter a filename below..");
    elseif ( strpos($name,"'") !== false || strpos($name,'"') !== false)
      art_display('',"!Filenames must not contain quote characters.");
    elseif ( strpos($name,"/") !== false || strpos($name,"\\") !== false)
      art_display('',"!Filenames cannot contain directory references");
    elseif ( !in_array(strtolower(file_ext($name)), array('jpg','jpeg','gif','png')) )
      art_display('',"!Only files ending in JPG, JPEG, GIF or PNG are valid filenames");
    else 
    {
      if ( db_insert_row('art_files',array('filename'=>$name)) === false)
      {
        art_display(db_error());
      }
      else
      {
        art_display('Art File Added');
      }
    }
  }
  
  //*************************************************************************************************
  // PLAYLISTS section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function playlists_display( $message = '' )
  {
    $dir  = (!empty($_REQUEST["location"]) ? $_REQUEST["location"] : db_value("select value from system_prefs where name='PLAYLISTS'"));
  
    echo '<p><h1>Saved Playlists Location<p>';
    message($message);
    form_start('index.php');
    form_hidden('section','PLAYLISTS');
    form_hidden('action','UPDATE');
    form_input('location','Location',70,'',$dir);
    form_label('Please specify the fully qualified directory where playlists should be stored by the SwissCenter.
                <p>For example: On Windows this might be <em>"C:\Documents and Settings\Robert\My Documents\My Playlists"</em>
                 or on  a LINUX system it might be <em>"/home/Robert/Playlists"</em>');
    form_submit('Submit',2);
    form_end();
  }
  
  //
  // Saves the new parameter
  //
  
  function playlists_update()
  {
    $dir = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["location"])),'/');
    
    if (empty($_REQUEST["location"]))
      playlists_display("!Please select the playlists location below.");
    elseif (!file_exists($dir))
      playlists_display("!I'm sorry, the directory you specified does not exist");
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      playlists_display("!Please enter a fully qualified directory path.");
    else 
    {
      db_sqlcommand("delete from system_prefs where name='PLAYLISTS'");
      if ( db_insert_row('system_prefs',array("name"=>"PLAYLISTS","value"=>$dir)) === false)
      {
        playlists_display(db_error());
      }
      else
      {
        playlists_display('Playlists location updated');
      }
    }
  }
  
  //*************************************************************************************************
  // SUPPORT section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function support_display()
  {
    echo "<h1>Support Information</h1>
          <p>If you are experiencing problems with the Swisscenter then please post a description of
             the problem you are having and any other relevant information (such as your operating
             system) to the <em>Help forum</em> on the <a href=\"www.swisscenter.co.uk\">www.swisscenter.co.uk</a>
             website.
          <p>All or some of the information shown below may be requested to help troubleshoot your problem.";
    
    echo "<h2>Program Locations</h2>";
    $opts = array( array('Program'=>'PHP','Location'=>$_SESSION["opts"]["php_location"]),
                   array('Program'=>'Swisscenter','Location'=>$_SESSION["opts"]["sc_location"]),
                 );
    array_to_table($opts);
  
    echo "<h2>Database Settings</h2>";
    echo '<table width="100%"><tr><td valign=top>';
      array_to_table( array( array('Connection Details'=>'Host = '.DB_HOST), 
                      array('Connection Details'=>'Database = '.DB_DATABASE),
                      array('Connection Details'=>'Username = '.DB_USERNAME), 
                      array('Connection Details'=>'Password = '.DB_PASSWORD) 
                    ));
      echo '<br>';
      array_to_table(db_toarray('show databases'));
    echo '</td><td valign=top>';
      $data = db_toarray('show tables');
      for ($i = 0; $i<count($data); $i++)
        $data[$i]['ROWS'] = db_value('select count(*) from '.$data[$i]['TABLES_IN_'.strtoupper(DB_DATABASE)]);
      array_to_table($data);
    echo '</td></tr></table>';
  
    echo "<h2>Media Locations</h2>";
    array_to_table(db_toarray('select media_name "Type", name "Location" from media_locations ml, media_types mt where ml.media_type = mt.media_id order by 1'));
  
    echo "<h2>Art Files</h2>";
    array_to_table(db_toarray('select * from art_files order by 1'));
  
    echo "<h2>System Preferences</h2>";
    array_to_table(db_toarray('select * from system_prefs order by 1'));
  
    echo "<h2>User Preferences</h2>";
    array_to_table(db_toarray('select u.name "User", up.name,up.value from users u,user_prefs up where u.user_id = up.user_id order by 1,2'));
  
    if ( substr(PHP_OS,0,3)=='WIN' )
    {
      echo "<h2>Schedule</h2>";
      exec('at',$output);
      echo '<table width="100%" class="form_select_tab"><tr><th>Scheduled Jobs</th></tr>';
      for ($i = 2; $i<=count($output); $i++)
        echo '<tr><td>'.$output[$i].'</td></tr>';
      echo '</table>';
    }
    
    if ( $_SESSION["internet"])
      echo '<h2>Internet</h2><p>Internet Connection detected - Internet specific features enabled';  
  }
  
  //*************************************************************************************************
  // CACHE section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function cache_display( $message = '')
  {
    $dir  = (!empty($_REQUEST["dir"])  ? $_REQUEST["dir"]  : db_value("select value from system_prefs where name='CACHE_DIR'"));
    $size = (!empty($_REQUEST["size"]) ? $_REQUEST["size"] : db_value("select value from system_prefs where name='CACHE_MAXSIZE_MB'"));
    
    echo "<h1>Image Cache Configuration</h1>";
    message($message);
    form_start('index.php');
    form_hidden('section','CACHE');
    form_hidden('action','UPDATE');
    form_input('dir','Cache Directory',70,'', $dir );
    form_label('Please specify the directory in which the SwissCenter server should cache image files that have been resized 
                (for example, the folder art for an album, or the thumbnail of a photograph)');
    form_input('size','Maximum Size',1,'', $size);
    form_label('The is the maximum amount of disk space (in Megabytes) that will be used to cache the image files. Once 
                this limit has been reached, previously cached files will be deleted on a least recently used basis.');
    form_submit();
    form_end();
  }
   
  //
  // Saves the new parameters
  //
  
  function cache_update()
  {
    $dir = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["dir"])),'/');
    $size = $_REQUEST["size"];
    
    if (empty($dir))
      cache_display("!Please select the cache location below.");
    elseif ($size == '')
      cache_display("!Please select the cache size below.");
    elseif (! form_mask($size,'[0-9]'))
      cache_display("!The value you entered for the cache size is not a number");
    elseif ( $size <1 )
      cache_display("!You cache size you entered is too small.");
    elseif (!file_exists($dir))
      cache_display("!I'm sorry, the directory you specified does not exist");
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      cache_display("!Please enter a fully qualified directory path.");
    else 
    {
      db_sqlcommand("delete from system_prefs where name='CACHE_DIR'");
      db_sqlcommand("delete from system_prefs where name='CACHE_MAXSIZE_MB'");
      if ( (db_insert_row('system_prefs',array("name"=>"CACHE_DIR","value"=>$dir)) === false) ||
           (db_insert_row('system_prefs',array("name"=>"CACHE_MAXSIZE_MB","value"=>$size)) === false) )
      {
        cache_display(db_error());
      }
      else
      {
        cache_display('Cache configuration updated');
      }
    }
  }

  //*************************************************************************************************
  // Populate main sections of the webpage
  //*************************************************************************************************

  //
  // Create and amanager the menu (static menu)
  //

 function display_menu()
 {
  $db_stat = test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE); 

  echo '<table width="160">';
     menu_item('Create Database','section=INSTALL&action=DISPLAY','menu_bgr2.png');
     menu_item('Database Config','section=DB&action=DISPLAY');
     if ($db_stat == 'OK')
     {
       menu_item('User Management','section=USERS&action=DISPLAY');
       menu_item('Media Locations','section=DIRS&action=DISPLAY');
       menu_item('Album/Film Art','section=ART&action=DISPLAY');
       menu_item('Playlists Location','section=PLAYLISTS&action=DISPLAY');
       menu_item('Image Cache','section=CACHE&action=DISPLAY');
     }
     menu_item('Support Info','section=SUPPORT&action=DISPLAY','menu_bgr2.png');
   echo '</table>';
 }
 
 //
 // Calls the correct function for displaying content on the page.
 //
 
 function display_content()
 {
   $db_stat = test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE); 
   if ($db_stat == 'OK')
   {
     session_start();
     include_once('../base/settings.php');
   }
   
   
   if ($_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"])
   {
     echo '<br><h1>Access Denied</h1>
           <p align="center">Remote access to the <i>\'SwissCenter Configuration Utility\'</i> is disabled for security reasons.';
   }
   elseif (!empty($_REQUEST["section"]))
   {
     $func = (strtoupper($_REQUEST["section"]).'_'.strtoupper($_REQUEST["action"]));
     @$func();
   }
   else 
   {
    if ($db_stat != 'OK')
      install_display();
    else
      dirs_display();  
   }
 }

 
//*************************************************************************************************
// Get the database parameters from the ini file as they are needed throughout the script, and 
// then execute the template file
//*************************************************************************************************

  if (file_exists('swisscenter.ini'))
  {
    foreach( parse_ini_file('swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }

  $page_title = 'SwissCenter Configuration Utility';
  $page_width = '750px';
  include("config_template.php");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
