<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  ob_start();

  include_once('../base/mysql.php');
  include_once('../base/file.php');
  include_once('../base/html_form.php');
  include_once('../base/server.php');
  include_once('../base/prefs.php');
  include_once('../base/rating.php');
  include_once('../base/categories.php');
  include_once('../base/db_abstract.php');
  include_once('common.php');
  
  //
  // Display a database error
  //
  
  function db_error()
  {
    return '!Unable to access the database - are your <i>Database Config</i> settings correct?';
  }
  
  //
  // Write config file to disk.
  //
  
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
  
  //*************************************************************************************************
  // INSTALL section
  //*************************************************************************************************
  
  //
  // Install form - get MySQL root password from user
  //
  
  function install_display($message = '')
  {
    $host = (!empty($_REQUEST["host"])     ? $_REQUEST["host"]     : (defined('DB_HOST')     ? DB_HOST : 'localhost'));
    $name = (!empty($_REQUEST["username"]) ? $_REQUEST["username"] : (defined('DB_USERNAME') ? DB_USERNAME : 'swisscenter'));
    $pass = (!empty($_REQUEST["password"]) ? $_REQUEST["password"] : (defined('DB_PASSWORD') ? DB_PASSWORD : 'swisscenter'));
    $db   = (!empty($_REQUEST["dbname"])   ? $_REQUEST["dbname"]   : (defined('DB_DATABASE') ? DB_DATABASE : 'swiss'));
    
    echo "<h1>Create Database</h1>";
     
    message($message);
    form_start('index.php');
    form_hidden('section','INSTALL');
    form_hidden('action','RUN');

    echo '<p>This screen allows you to create and populate the SwissCenter database. The parameters
             that will be used to build the database are shown below.<p>';
    
    form_input('host','Machine Name',15,'',$host);
    form_input('dbname','Database Name',15,'',$db);
    form_input('username','Username',15,'',$name);
    form_input('password','Password',15,'',$pass);
             
    form_input('root_password','"Root" Password',15,'',$_REQUEST["password"]);
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
      install_display('!Unable to create database - Is your MySQL "Root" password correct?');
    elseif ( db_root_sqlcommand($pass,"Grant all on ".DB_DATABASE.".* to ".DB_USERNAME."@'".DB_HOST."' identified by '".DB_PASSWORD."'") == false)
      install_display('!Unable to create user - Is your MySQL "Root" password correct?');
    else 
    {
      // Fix for MySQL 4.1 and above (the authentication method changed and PHP 4.x uses an older uncompatible MySQL client).
      @db_root_sqlcommand($pass,"set password for ".DB_USERNAME."@'".DB_HOST."' = OLD_PASSWORD('".DB_PASSWORD."')");  
            
      // Open the setup.sql file
      if ( file_exists('../setup.sql') )
      {
        // Run the setup file and all database update files
        db_sqlfile('../setup.sql');
        foreach (dir_to_array('../database','update_[0-9.]*.sql') as $file)
          db_sqlfile($file);            

        // Write an ini file with the database parameters in it
        write_ini ( DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DATABASE );
        
        // If the media_search.php script is not scheduled, then schedule it now!
        $sched = syscall('at');
        if ( strstr($sched,os_path(SC_LOCATION.'media_search.php')) === false)
          run_background('media_search.php','M,T,W,Th,F,S,Su','12:00');  

        // Store default cache settings
        set_sys_pref('CACHE_MAXSIZE_MB',10); // Default 10 Mb cache size

        if (!file_exists(SC_LOCATION.'/cache'))
          mkdir(SC_LOCATION.'/cache');

        if (file_exists(SC_LOCATION.'/cache') && is_dir(SC_LOCATION.'/cache'))
          set_sys_pref('CACHE_DIR',SC_LOCATION.'/cache');
          
        // Display the config page
        header('Location: index.php');
      }
      else
      {
        install_display('!The "Setup.sql" file in the ShowCenter directory is missing.');
      }
    }
  }
   
  //*************************************************************************************************
  // USERS section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function users_display($modify_msg = '', $add_msg = '', $edit_id = 0)
  {
    $data = db_toarray("select user_id, u.Name 'Name', u.Pin, c.name 'Max Certificate Viewable' from users u, certificates c where u.maxcert=c.cert_id order by u.name asc");
    
    
    echo "<h1>User Management</h1>";
    message($modify_msg);
    form_start("index.php", 150, "users");
    form_hidden("section", "USERS");
    form_hidden("action", "MODIFY");
    form_select_table("user_id", $data, array("class"=>"form_select_tab","width"=>"100%"), "user_id",
                      array("NAME"=>"",
                            "MAX CERTIFICATE VIEWABLE"=>"select cert_id,name from certificates order by rank asc",
                            "PIN"=>"*")
                      , $edit_id, "users");
    form_submit("Remove Selected Users", 1 ,"center");
    form_end();
    
    echo "<p><h1>Add New User</h1>";
    message($add_msg);
    form_start("index.php", 150);
    form_hidden("section", "USERS");
    form_hidden("action", "NEW");
    form_input("name", "Name", 70, '', $_REQUEST["name"]);
    form_label('Please enter a name for the user as it should appear on the SwissCenter.');
    form_list_dynamic("cert", "Maximum certificate", "select cert_id,name from certificates order by rank asc", $_REQUEST["cert"]);
    form_label('Please enter the <em>maximum</em> certificate that this user should be allowed to view. Items that have a higher
                certificate than the one you specify here will <em>not</em> be displayed to this user.');
    form_input('pin','PIN',5,10);
    form_label('You may optionally protect this user with a PIN number (which may be up to 10 digits long)');
    form_submit("Add New User", 2);
    form_end();
  }
  
  function users_new()
  {
    $name = $_REQUEST["name"];
    $cert = $_REQUEST["cert"];
    $pin  = $_REQUEST["pin"];
    
    if(empty($name))
    {
      users_display("", "!Please enter a name below");
    }
    elseif (empty($cert))
    {
      users_display("","!Please enter the maximum certificate that this user may view");
    }
    else
    {
      $user_count = db_value("select count(*) from users where name='".db_escape_str($name)."'");
      
      if($user_count > 0)
      {
        users_display("", "!That user already exists, please try another name");
      }
      else
      {
        $data = array("name"=>$name, "maxcert"=>$cert,'pin'=>$pin);

        if(db_insert_row("users", $data) === false)
          users_display(db_error());
        else
          users_display("", "New User Added");
      }
    }
  }
  
  function users_modify()
  {
    $selected = form_select_table_vals("user_id");
    $edit_id = form_select_table_edit("user_id", "users");
    $update_data = form_select_table_update("user_id", "users");
    
    if(!empty($edit_id))
    {
      users_display("", "", $edit_id);
    }
    elseif(!empty($update_data))
    {
      $user_id = $update_data["USER_ID"];
      $name = $update_data["NAME"];
      $max_cert = $update_data["MAX_CERTIFICATE_VIEWABLE"];
      $pin = $update_data["PIN"];
      
      if(empty($name))
      {
        user_display("!Please enter a user name");
      }
      else
      {
        $sql = "update users set name='".db_escape_str($name)."'";
        if(empty($pin))
          $sql = $sql.",pin=NULL";
        else
          $sql = $sql.",pin='".db_escape_str($pin)."'";
        
        $sql = $sql.",maxcert=$max_cert where user_id=$user_id";
        
        db_sqlcommand($sql);
        users_display("The selected user has been modified");
      }
    }
    elseif(!empty($selected))
    {
      $message = "The selected users have been deleted";
      
      foreach($selected as $selected_item)
      {
        if($selected_item != 1)
        {
          db_sqlcommand("delete from users where user_id=$selected_item");
        }
        else
          $message = "!The default user cannot be deleted";
      }
      
      users_display($message);
    }
    else
      users_display();
  }
  
  //*************************************************************************************************
  // DIRS section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function dirs_display($delete = '', $new = '', $edit = 0)
  {
    // Ensure that on Linux/Unix systems there is a "media" directory present for symbolic links to go in.
    if (!is_windows() && !file_exists(SC_LOCATION.'media'))
      mkdir(SC_LOCATION.'media');
    
    $data = db_toarray("select location_id,media_name 'Type', cat_name 'Category', cert.name 'Certificate', ml.name 'Directory'  from media_locations ml, media_types mt, categories cat, certificates cert where ml.unrated=cert.cert_id and mt.media_id = ml.media_type and ml.cat_id = cat.cat_id order by 2,3,4");
    
    // Try to determine sensible default values for "Category" and "Certification".
    if (empty($_REQUEST["cat"]))
      $_REQUEST["cat"] = db_value("select cat_id from categories where cat_name='General'");
    if (empty($_REQUEST["cert"]))
      $_REQUEST["cert"] = db_value("select cert_id from certificates where rank = ".db_value("select min(rank) from certificates")." limit 1");
     
    echo "<h1>Current Media Locations</h1>";
    message($delete);
    form_start('index.php', 150, 'dirs');
    form_hidden('section','DIRS');
    form_hidden('action','MODIFY');
    form_select_table('loc_id',$data,array('class'=>'form_select_tab','width'=>'100%'),'location_id',
                      array('DIRECTORY'=>'','TYPE'=>'select media_id,media_name from media_types order by 2',
                            'CATEGORY'=>'select cat_id,cat_name from categories order by cat_name',
                            'CERTIFICATE'=>'select cert_id,name from certificates order by rank asc'), $edit, 'dirs');
    form_submit('Remove Selected Locations',1,'center');
    form_end();
  
    echo '<p><h1>Add A New Location<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','DIRS');
    form_hidden('action','NEW');
    form_input('location','Location',70,'',un_magic_quote($_REQUEST['location']));
    form_label('Please specify the fully qualified directory path where the media can be found. Eg:
                On Windows this might be <em>"C:\Documents and Settings\Robert\My Documents\My Music"</em> or on 
                a Linux system it might be <em>"/home/Robert/Music"</em>');
    form_list_dynamic('type','Media Type',"select media_id,media_name from media_types order by 2",$_REQUEST['type']);
    form_label('Please select the type of media that this location should be searched for.');
    form_list_dynamic('cat', 'Category',"select cat_id,cat_name from categories order by cat_name", $_REQUEST['cat']);
    form_label('Specifying a category helps you to sort your media files into logical groups such as "Audio Books" and "Language
                learning" as well as "General". For example, using this feature will allow you to play a random selection of music
                without a chapter from an audio book being added to the playlist.'); 
    form_list_dynamic('cert', 'Unrated Certificate', 'select cert_id,name from certificates order by rank asc', $_REQUEST['cert']);
    form_label('You should specify a default certificate that will be applied to each file within this media location that
                does not have a certificate explicitly assigned to it.');
    form_submit('Add Location',2);
    form_end();
  }
   
  //
  // Delete an existing location
  //
  
  function dirs_modify()
  {
    $selected = form_select_table_vals('loc_id');     // Get the selected items
    $edit = form_select_table_edit('loc_id', 'dirs');         // Get the id of the edited row
    $update = form_select_table_update('loc_id', 'dirs');     // Get the updates from an edit
    
    if(!empty($edit))
    {
      // There was an edit, display the dirs with the table in edit mode on the selected row
      dirs_display('', '', $edit);
    }
    elseif(!empty($update))
    {
      // Update the row given in the database and redisplay the dirs
    // Process the directory passed in
      $dir = db_escape_str(rtrim(str_replace('\\','/',un_magic_quote($update["DIRECTORY"])),'/'));
      $type_id = $update["TYPE"];
      $cat_id  = $update["CATEGORY"];
      $id      = $update["LOC_ID"];
      $cert    = $update["CERTIFICATE"];
  
      if (empty($type_id))
        dirs_display('',"!Please select the media type below.");
      elseif (empty($cat_id))
        dirs_display('',"!Please select thea category.");
      elseif (empty($cert))
        dirs_display('',"!Please select the default certificate for unrated media.");
      elseif (empty($dir))
        dirs_display('',"!Please select the media location below.");     
      elseif (!file_exists($dir))
        dirs_display("!I'm sorry, the directory you specified does not exist");
      elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
        dirs_display("!Please enter a fully qualified directory path.");
      else
      {
        db_sqlcommand("update media_locations set name='$dir',media_type=$type_id,cat_id=$cat_id,unrated=$cert where location_id=$id");
        
        if (! is_windows() )
        {
          unlink(SC_LOCATION.'media/'.$id);
          symlink($dir,SC_LOCATION.'media/'.$id);
        }

        dirs_display('Updated media location information.');
      }
    }
    elseif($_REQUEST["submit_action"] == " Remove Selected Locations ")
    {
      // Delete the selected directories
      foreach ($selected as $id)
      {
        if (! is_windows() )
          unlink(SC_LOCATION.'media/'.$id);

        db_sqlcommand("delete from maa using mp3s m, mp3_albumart maa where m.file_id = maa.file_id and m.location_id=".$id);
        db_sqlcommand("delete from media_locations where location_id=".$id);
        db_sqlcommand("delete from mp3s where location_id=$id");
        db_sqlcommand("delete from movies where location_id=$id");
        db_sqlcommand("delete from photos where location_id=$id");
      }

      dirs_display('The selected directories have been removed.');
    }
    else
      dirs_display();
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
    elseif (empty($_REQUEST["cat"]))
      dirs_display('',"!Please select thea category.");
    elseif (empty($_REQUEST["cert"]))
      dirs_display('',"!Please select the default certificate for unrated media.");
    elseif (empty($_REQUEST["location"]))
      dirs_display('',"!Please select the media location below.");
    elseif (!file_exists($dir))
      dirs_display('',"!I'm sorry, the directory you specified does not exist");
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      dirs_display('',"!Please enter a fully qualified directory path.");
    else 
    {
      if ( db_insert_row('media_locations',array('name'=>$dir,'media_type'=>$_REQUEST["type"],'cat_id'=>$_REQUEST["cat"],'unrated'=>$_REQUEST["cert"])) === false)
      {
        dirs_display(db_error());
      }
      else
      {
        $id = db_value("select location_id from media_locations where name='$dir' and media_type=".$_REQUEST["type"]);
        
        if (! is_windows() )
          symlink($dir,SC_LOCATION.'media/'.$id);
        
        dirs_display('','Media Location Added');
      }
    }
  }
  
  //*************************************************************************************************
  // ART section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function art_display($delete = '', $new = '', $opt = '', $edit_id = '')
  {
    $list = array('Enabled'=>'YES','Disabled'=>'NO');
    $data = db_toarray("select filename, filename 'Name' from art_files order by 1");
    
    echo "<h1>Album/Film Art files</h1>";
    echo('<em>Album/Film Art</em> files are the names of image files that the SwissCenter should look for when it is
                    displaying a browse function (Browse Movies, Browse Music by folder, etc) or when you have selected an
                    album or film.
                 <p>If any of the files you specify here are found within a directory, then the first one to be found will 
                    be displayed on the Swisscenter interface.');

    echo '<p><h1>Update/Remove Filenames<p>';
    message($delete);
    form_start('index.php', 150, 'art');
    form_hidden('section','ART');
    form_hidden('action','MODIFY');
    form_select_table('filename',$data,array('class'=>'form_select_tab','width'=>'100%'),'filename',
                      array('NAME'=>''), $edit_id, 'art');
    form_submit('Remove Selected files',1,'center');
    form_end();
  
    echo '<p><h1>Add a filename<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','ART');
    form_hidden('action','NEW');
    form_input('name','Filename',60,'',un_magic_quote($_REQUEST['name']));
    form_label('Please specify the name of any image file that should be used as album/film art if it is found 
                to be within a media directory.');
    form_submit('Add filename',2);
    form_end();
    
    echo '<p><h1>Options<p>';
    message($opt);
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'ART');
    form_hidden('action', 'OPTIONS');

    form_radio_static('id3','ID3-Tag Covers',$list,get_sys_pref('use_id3_art','YES'),false,true);
    form_label('If Album Art is present within the ID3-Tag of a music file, then it will be extracted and stored in the
                database as the preferred art to use on the SwissCenter interface. However, as this can cause the database
                to grow quite large you may disable this feature here if you wish.');
    form_submit('Store Settings', 2);
    form_end();
    }   
    
  //
  // Stores the albumart options
  //
  
  function art_options()
  {
    set_sys_pref('USE_ID3_ART',$_REQUEST["id3"]);
    art_display('','','Options Stored');
  }
    
  //
  // Delete an existing location
  //
  
  function art_modify()
  {
    $selected = form_select_table_vals('filename');
    $edit_id = form_select_table_edit('filename', 'art');
    $update_data = form_select_table_update('filename', 'art');
    
    if(!empty($edit_id))
    {
      art_display('', '', '', $edit_id);
    }
    else if(!empty($update_data))
    {
      
      $name = $update_data["NAME"];
      $oldname = $update_data["FILENAME"];
      
      if (empty($name))
        art_display("!Please enter a filename");
      elseif ( strpos($name,"'") !== false || strpos($name,'"') !== false)
        art_display("!Filenames must not contain quote characters.");
      elseif ( strpos($name,"/") !== false || strpos($name,"\\") !== false)
        art_display("!Filenames cannot contain directory references");
      elseif ( !in_array(strtolower(file_ext($name)), array('jpg','jpeg','gif','png')) )
        art_display("!Only files ending in JPG, JPEG, GIF or PNG are valid filenames");
      else
      {
        db_sqlcommand("update art_files set filename='".db_escape_str($name)."' where filename='".db_escape_str($oldname)."'");
        art_display('Art information updated');
      }
    }
    else if(!empty($selected))
    {
      foreach ($selected as $id)
        db_sqlcommand("delete from art_files where filename='".$id."'");

      art_display('The selected files have been removed.');
    }
    else
      art_display();
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
    $opts = array( array('Program'=>'PHP','Location'=>PHP_LOCATION),
                   array('Program'=>'Swisscenter','Location'=>SC_LOCATION),
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
    
    if ( internet_available() )
      echo '<h2>Internet</h2><p>Internet Connection detected - Internet specific features enabled';  
  }
  
  //*************************************************************************************************
  // SCHED section
  //*************************************************************************************************
  
  //
  // Display current config
  //
  
  function sched_display( $message = '')
  {   
    if (is_windows())
      sched_display_win( $message);
    else
      sched_display_linux( $message);
  }
   
  function sched_display_win( $message = '')
  {
    $at_hrs ='12';
    $at_mins='00';
  
    // Get the current schedule information
    $sched = syscall('at');
    foreach(explode("\n",$sched) as $line)
      if (strpos($line,os_path(SC_LOCATION.'media_search.php')) && strpos($line,'Each '))
      {
         $at_days = explode(' ',trim(substr($line,17,19)));
         $at_hrs  = trim(substr($line,36,2));
         $at_mins = trim(substr($line,39,2));
      }

    echo "<h1>Scheduled Tasks</h1>";
    message($message);

    echo '<p><b>Automatic Media Refresh</b>
          <p>A search for new media will be performed at the given time for each of the days
             specified below. It is recommended that you search for new media daily at a time
             when you are unlikely to be using your PC.

          <p align=center>
             <form name="" enctype="multipart/form-data" action="index.php" method="post">
               <input type=hidden name="section" value="SCHED">
               <input type=hidden name="action" value="UPDATE_WIN">
               <table width="400" class="form_select_tab" border=0 >
               <tr>
                 <th style="text-align=center;">Time</th>
                 <th style="text-align=center;">Mon</th>
                 <th style="text-align=center;">Tue</th>
                 <th style="text-align=center;">Wed</th>
                 <th style="text-align=center;">Thu</th>
                 <th style="text-align=center;">Fri</th>
                 <th style="text-align=center;">Sat</th>
                 <th style="text-align=center;">Sun</th>
               </tr>
               <tr>
                 <td style="text-align=center;"><input size="1" name="hr" value="'.$at_hrs.'">
                                               :<input size="1" name="mi" value="'.$at_mins.'"> </td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="M" '. (in_array('M',$at_days) ? 'checked' : '').'></td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="T" '. (in_array('T',$at_days) ? 'checked' : '').'></td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="W" '. (in_array('W',$at_days) ? 'checked' : '').'></td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="Th" '.(in_array('Th',$at_days) ? 'checked' : '').'></td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="F" '. (in_array('F',$at_days) ? 'checked' : '').'></td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="S" '. (in_array('S',$at_days) ? 'checked' : '').'></td>
                 <td style="text-align=center;"><input type="checkbox" name="day[]" value="Su" '.(in_array('Su',$at_days) ? 'checked' : '').'></td>
               </tr>
               </tr>
               </table><br>
                 <input type="submit" value="Update Schedule">
             </form>
             ';
  }
  
  //
  // Update the schedule 
  //

  function sched_display_linux( $message = '')
  {
    $cron = split(" ",syscall('crontab -l | grep "'.SC_LOCATION.'media_search.php" | head -1 | awk \'{ print $1" "$2" "$3" "$4" "$5 }\''));

    echo "<h1>Scheduled Tasks</h1>";
    message($message);

    echo '<p><b>Automatic Media Refresh</b>
          <p>A search for new media will be performed according to the schedule you define in the form below.
          To ensure that your media is available whenever you want it, we recommend that you schedule media
          refreshes between once a day and once an hour.
          
          <p align=center>
            <form name="" enctype="multipart/form-data" action="index.php" method="post">
               <input type=hidden name="section" value="SCHED">
               <input type=hidden name="action" value="UPDATE_LINUX">
               
               <table class="form_select_tab" border=0 >
               <tr>
                 <th height="25"></th>
                 <th width="75">&nbsp;Value</th>
                 <th width="60">&nbsp;Range</th>
                 <th width="50">&nbsp;Notes</th>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">Month: &nbsp;</th>
                 <td>&nbsp;<input size="6" name="month" value="'.$cron[3].'"></td>
                 <td>&nbsp;1-12 </td>
                 <td>&nbsp;1 = January, 2 = February, etc </td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">Date: &nbsp;</th>
                 <td>&nbsp;<input size="6" name="date" value="'.$cron[2].'"></td>
                 <td>&nbsp;1-31 </td>
                 <td>&nbsp;The day of the month </td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">Hour: &nbsp;</th>
                 <td>&nbsp;<input size="6" name="hour" value="'.$cron[1].'"></td>
                 <td>&nbsp;0-23 </td>
                 <td>&nbsp;The hour of the day </td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">Minute: &nbsp;</th>
                 <td>&nbsp;<input size="6" name="minute" value="'.$cron[0].'"></td>
                 <td>&nbsp;0-59 </td>
                 <td>&nbsp;The number of minutes past the hour</td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">Weekday: &nbsp;</th>
                 <td>&nbsp;<input size="6" name="day" value="'.$cron[4].'"></td>
                 <td>&nbsp;1-7 </td>
                 <td>&nbsp;1 = Monday, 2 = Tuesday, etc </td>
               </tr>
               </table><br>
          
               <input type="submit" value="Update Schedule">
             </form>

          <p><b>Notes</b>
          <p>
          <ul>
          <li>\'*\' denotes all possible values.
          <li>Multiple values can be specified using commas (1,5).
          <li>A range can be specified using a hyphen (1-5).
          <li>Both may be specified together (1-4,7).
          </ul>
          ';
  }

  function sched_update_win()
  {   
    $hrs  = $_REQUEST["hr"];
    $mins = $_REQUEST["mi"];
    $days = $_REQUEST["day"];
    
    if ($hrs <0 || $hrs > 23 || !is_numeric($hrs))
      sched_display('!Please enter a valid hour (between 0 and 23)');
    elseif ($mins <0 || $mins > 59 || !is_numeric($mins))
      sched_display('!Please enter a valid minute (between 0 and 59)');
    else
    {
      // Find and remove old schedule entry
      $sched = syscall('at');
      foreach(explode("\n",$sched) as $line)
        if (strpos($line,os_path(SC_LOCATION.'media_search.php')) && strpos($line,'Each '))
         syscall('at '.substr(ltrim($line),0,strpos(ltrim($line),' ')).' /delete');

      if (count($days)>0)
      {
        run_background('media_search.php',implode(',',$days), $hrs.':'.$mins);  
        sched_display('Automatic Media Refresh - Schedule Updated');        
      }
      else
        sched_display('Automatic Media Refresh - No longer Active');
    }
  }

  function sched_update_linux()
  {   
    $hrs    = $_REQUEST["hour"];
    $mins   = $_REQUEST["minute"];
    $dates  = $_REQUEST["date"];
    $months = $_REQUEST["month"];
    $days   = $_REQUEST["day"];
    
    if ( preg_match("/[^-,*0123456789]/",($hrs.$mins.$dates.$months.$days)) != 0)
      sched_display('!Valid characters are any of "0123456789-,*"');
    elseif ($hrs == '' || $mins == '' || $dates == '' || $months == '' || $days == '')
      sched_display('!You must specify a value for every field');
    else
    {
      // Find and replace old crontab entry
      syscall('crontab -l | grep -v "'.SC_LOCATION.'media_search.php" | grep -v "^#" > /tmp/swisscron');
      syscall("echo '$mins $hrs $dates $months $days ".'"'.os_path(PHP_LOCATION).'" "'.SC_LOCATION.'media_search.php"\' >> /tmp/swisscron');
      syscall("crontab /tmp/swisscron");

      // Was it successfully added?
      $cron = split(" ",syscall('crontab -l | grep "'.SC_LOCATION.'media_search.php" | awk \'{ print $1" "$2" "$3" "$4" "$5 }\''));
      if (count($cron)>0)
        sched_display('Automatic Media Refresh - Schedule Updated');        
      else
        sched_display('Automatic Media Refresh - No longer Active');
    }
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
    form_input('dir','Cache Directory',60,'', $dir );
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
      set_sys_pref('CACHE_DIR',$dir);
      set_sys_pref('CACHE_MAXSIZE_MB',$size);
      cache_display('Cache configuration updated');
    }
  }

  //*************************************************************************************************
  // Category section
  //*************************************************************************************************
  function category_display($del_message = '', $add_message = '', $edit_id = 0)
  {
    $cat = $_REQUEST["cat"];
    
    if(empty($cat))
    {
      // Get a list of all of the cats from the database and display them
      $data = db_toarray("select cat_id,cat_name 'Category' from categories order by Category");
      
      echo "<h1>Current Categories</h1>";
      message($del_message);
      form_start('index.php', 150, 'cats');
      form_hidden('section', 'CATEGORY');
      form_hidden('action', 'MODIFY');
      form_select_table('cat_ids', $data, array('class'=>'form_select_tab','width'=>'100%'), 'cat_id',
                        array('CATEGORY'=>''), $edit_id, 'cats');
      form_submit('Remove Selected Categories', 1, 'center');
      form_end();
      
      echo "<p><h1>Add A New Category</h1>";
      message($add_message);
      form_start('index.php');
      form_hidden('section', 'CATEGORY');
      form_hidden('action', 'ADD');
      form_input('cat_name', 'Name', 70, 100, un_magic_quote($_REQUEST["cat_name"]));
      form_submit('Add Category', 2, 'left');
      form_end();
    }
  }
  
  function category_add()
  {
    $cat = rtrim(un_magic_quote($_REQUEST["cat_name"]));
    
    if(empty($cat))
      category_display('', '!Please enter a category name');
    else
    {
      $exists = db_value("select count(*) from categories where cat_name='" . db_escape_str($cat) . "'");
      
      if($exists != 0)
        category_display('', '!Category name already exists');
      else
      {
        if(db_insert_row('categories', array('cat_name'=>$cat)) === false)
          category_display('', db_error());
        else
          category_display('', 'Category added');
      }
    }
  }
  
  function category_modify()
  {
    $selected_ids = form_select_table_vals('cat_ids');
    $edit_id = form_select_table_edit('cat_ids', 'cats');
    $update_data = form_select_table_update('cat_ids', 'cats');
    $default_cat = db_value('select cat_name from categories where cat_id=1');
    
    if(!empty($edit_id))
    {
      category_display('', '', $edit_id);
    }
    elseif(!empty($update_data))
    {
      $category_name = db_escape_str($update_data["CATEGORY"]);
      $id = $update_data["CAT_IDS"];
      
      if(empty($category_name))
        category_display("!Please enter a category name");
      else
      {
        db_sqlcommand("update categories set cat_name='$category_name' where cat_id=$id");
        category_display('Category information updated');
      }
    }
    elseif(!empty($selected_ids))
    {
      $message = 'The selected categories have been removed.';

      foreach($selected_ids as $cat_id)
      {
        if($cat_id != 1)
        {
          // Ensure that the existing media_locations are updated with no category
          db_sqlcommand("update media_locations set cat_id=1 where cat_id=$cat_id");
          db_sqlcommand("delete from categories where cat_id=$cat_id");
        }
        else
          $message = "!The '$default_cat' category cannot be removed";
      }
  
      category_display($message);
    }
    else
      category_display();
  }
  

  //*************************************************************************************************
  // Connection options 
  //*************************************************************************************************
  
  function connect_display($message = '')
  {
    $list = array('Enabled'=>'YES','Disabled'=>'NO');
    
    echo "<h1>Connection Options</h1>";
    message($message);
    
    echo '<p>There are a number of features within the SwissCenter interface that make use of an 
             internet connection either to retrieve information (eg: Weather Forecasts) and or
             to periodically poll for information (eg: New Updates).';

    form_start('index.php', 150, 'conn');
    form_hidden('section', 'CONNECT');
    form_hidden('action', 'MODIFY');

    form_radio_static('radio','Internet Radio',$list,get_sys_pref('radio_enabled','YES'),false,true);
    form_label('Connections to individual Internet Radio stations are made directly from the ShowCenter box when 
                requested by the user. If your ShowCenter does not have direct access to the internet then you should
                disable this feature.');

    form_radio_static('weather','Weather Forecasts',$list,get_sys_pref('weather_enabled','YES'),false,true);
    form_label('Information on the current weather conditions (or 5 day forecast) is downloaded from 
               <a href="http://www.weather.com">The Weather Channel</a> on demand.');
    
    form_radio_static('update','Update Check',$list,get_sys_pref('updates_enabled','YES'),false,true);
    form_label('A daily check is made to the <a href="http://www.swisscenter.co.uk">SwissCenter.co.uk</a> website to
                determine if a new version of the SwissCenter is available. If it is, then an icon will appear
                on the main menu to indicate this.');

    form_radio_static('messages','New Messages',$list,get_sys_pref('messages_enabled','YES'),false,true);
    form_label('A daily check is made for important messages regarding the SwissCenter interface. If new messages
                are available then an icon will appear on the main menu to indicate that they are available for
                you to view.');

    form_submit('Store Settings', 2);
    form_end();
  }
  
  //
  // Saves the new parameters
  //
  
  function connect_modify()
  {
    set_sys_pref('radio_enabled',$_REQUEST["radio"]);
    set_sys_pref('weather_enabled',$_REQUEST["weather"]);
    set_sys_pref('updates_enabled',$_REQUEST["update"]);
    set_sys_pref('messages_enabled',$_REQUEST["messages"]);
    connect_display('Settings Saved');
  }
  
  //*************************************************************************************************
  // MOVIE section
  //*************************************************************************************************
  
  function movie_display( $message = '')
  {
    $per_page    = get_user_pref('PC_PAGINATION',20);
    $page        = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);
    $start       = ($page-1)*$per_page;    
    $where       = '';

    // Extra filters on the media (for categories and search).
    if (!empty($_REQUEST["cat_id"]) )
      $where .= "and ml.cat_id = $_REQUEST[cat_id] ";
   
    if (!empty($_REQUEST["search"]) )
      $where .= "and m.title like '%$_REQUEST[search]%' ";
      
    // If the user has changed category, then shunt them back to page 1.
    if ($_REQUEST["last_where"] != $where)
      $start = 0;
    
    // SQL to fetch matching rows
    $movie_count = db_value("select count(*) from movies m, media_locations ml where ml.location_id = m.location_id ".$where);
    $movie_list  = db_toarray("select m.* from movies m, media_locations ml where ml.location_id = m.location_id ".$where." order by title limit $start,$per_page");        
    
    echo "<h1>Movie Details</h1>";
    message($message);
    
    echo '<form enctype="multipart/form-data" action="" method="post">';

    echo '<table width="100%"><tr><td width="50%">
          <input type=hidden name="section" value="MOVIE">
          <input type=hidden name="action" value="DISPLAY">
          <input type=hidden name="last_where" value="'.$where.'">
          Category : 
          '.form_list_dynamic_html("cat_id","select cat_id,cat_name from categories",$_REQUEST["cat_id"],true,true,'- All Categories -').'
          </form>
          </td><td width"50% align="right">
          Search : 
          <input name="search" value="'.$_REQUEST["search"].'" size=10>
          </td></tr></table>';
    
    echo '<input type=hidden name="section" value="MOVIE">';
    echo '<input type=hidden name="action" value="UPDATE">';

    echo '<p><table class="form_select_tab" width="100%"><tr>
            <th width="3%">&nbsp;</th>
            <th width="34%"> Title </th>
            <th width="21%"> Actors </th>
            <th width="21%"> Directors </th>
            <th width="21%"> Genres </th>
          </tr></table>';

    foreach ($movie_list as $movie)
    {
      $actors    = db_col_to_list("select actor_name from actors a,actors_in_movie aim where a.actor_id=aim.actor_id and movie_id=$movie[FILE_ID] order by 1");
      $directors = db_col_to_list("select director_name from directors d, directors_of_movie dom where d.director_id = dom.director_id and movie_id=$movie[FILE_ID] order by 1");
      $genres    = db_col_to_list("select genre_name from genres g, genres_of_movie gom where g.genre_id = gom.genre_id and movie_id=$movie[FILE_ID] order by 1");
      $cert      = db_value("select name from certificates where cert_id=".nvl($movie["CERTIFICATE"],-1));

      echo '<table class="form_select_tab" width="100%"><tr>
            <td valign="top" width="3%"><input type="checkbox" name="movie[]" value="'.$movie["FILE_ID"].'"></input><td>
            <td valign="top" width="34%">
               <b>'.$movie["TITLE"].'</b><br>
               Certificate : '.nvl($cert).'<br>
               Year : '.nvl($movie["YEAR"]).'<br>
             </td>
             <td valign="top" width="21%">'.nvl(implode("<br>",$actors)).'</td>
             <td valign="top" width="21%">'.nvl(implode("<br>",$directors)).'</td>
             <td valign="top" width="21%">'.nvl(implode("<br>",$genres)).'</td>
            </tr></table>';  	
    }
    
    echo '<table width="100%"><tr><td align="center">
          <input type="Submit" name="subaction" value="Edit Details"> &nbsp; 
          <input type="Submit" name="subaction" value="Clear Details"> &nbsp; 
          </td></tr></table>';
    
    paginate('?search='.$_REQUEST["search"].'&cat_id='.$_REQUEST["cat_id"].'&section=MOVIE&action=DISPLAY&page=',$movie_count,$per_page,$page);
    
    echo '</form>';
  }
  
  function movie_update()
  {
    if ($_REQUEST["subaction"] == 'Clear Details')
      movie_clear_details();
    elseif ($_REQUEST["subaction"] == 'Edit Details')
      movie_update_form();
    elseif (empty($_REQUEST["subaction"])) 
      movie_display();
    else
      echo 'Unknown value recieved for "subaction" parameter : '.$_REQUEST["subaction"];
  }
  
  function movie_clear_details()
  {
    $cleared = false;
    foreach ($_REQUEST["movie"] as $value)
    {
      db_sqlcommand('delete from actors_in_movie where movie_id = '.$value);
      db_sqlcommand('delete from directors_of_movie where movie_id = '.$value);
      db_sqlcommand('delete from genres_of_movie where movie_id = '.$value);
      db_sqlcommand('update movies set year=null,certificate=null where file_id = '.$value);
      scdb_remove_orphans();
      $cleared = true;
    }
    if ($cleared)
      movie_display('Details cleared.');
    else 
      movie_display();
  }

  function movie_update_form()
  {
    $movie_list = $_REQUEST["movie"];
    if (count($movie_list) == 0)
      movie_display("!You did not selected any movies to update.");
    elseif (count($movie_list) == 1)
      movie_update_form_single();
    else
      movie_update_form_multiple($movie_list);
  }
  
  function movie_update_form_single()
  {
    // Get actor/director/genre lists
    $movie_id    = $_REQUEST["movie"][0];
    $details     = db_toarray("select * from movies where file_id=".$movie_id);
    $actors      = db_toarray("select actor_name name, actor_id id from actors order by 1");
    $directors   = db_toarray("select director_name name, director_id id from directors order by 1");
    $genres      = db_toarray("select genre_name name, genre_id id from genres order by 1");
    
    // Because we can't use subqueries for the above lists, we now need to determine which
    // rows should be shown as selected.
    $a_select = db_col_to_list("select actor_id from actors_in_movie where movie_id=".$movie_id);
    $d_select = db_col_to_list("select director_id from directors_of_movie where movie_id=".$movie_id);
    $g_select = db_col_to_list("select genre_id from genres_of_movie where movie_id=".$movie_id);

    // Display movies that will be affected.
    echo '<h1>Update <em>'.$details[0]["TITLE"].'</em></h1>
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="MOVIE">
          <input type=hidden name="action" value="UPDATE_SINGLE">
          <input type=hidden name="movie[]" value="'.$movie_id.'">
          <table class="form_select_tab" width="100%" cellspacing=4><tr><td colspan="3" align="center">&nbsp<br>
          Please enter the details that you would like to <em>add</em> to each of the movies listed above
          <br>(holding down the CTRL key will allow you to select multiple entries in the lists)
          <br>&nbsp;</td></tr><tr>
          <th width="33%">Actors</th>
          <th width="33%">Directors</th>
          <th width="33%">Genres</th>   
          </tr><tr>
          <td><select name="actors[]" multiple size="8">
          '.list_option_elements($actors, $a_select).'
          </select></td><td><select name="directors[]" multiple size="8">
          '.list_option_elements($directors,$d_select).'
          </select></td><td><select name="genres[]" multiple size="8">
          '.list_option_elements($genres,$g_select).'
          </select></td></tr></tr><tr><td colspan="3" align="center">&nbsp<br>
          You may enter new Actors, Directors or Genres that are not listed into the boxes below. 
          <br>To add more than one new entry, separate each entry with a comma.
          <br>&nbsp;</td></tr><tr>
          <td width="33%"><input name="actor_new" size=25></td>
          <td width="33%"><input name="director_new" size=25></td>
          <td width="33%"><input name="genre_new" size=25></td>
          </tr><tr>
            <th>Certificate</th>
            <th>Year</th>
          </tr><tr>
            <td>
            '.form_list_dynamic_html("rating",get_cert_list_sql(),$details[0]["CERTIFICATE"],true).'
            </td>
            <td><input name="year" size="6"></td>
          </tr></table>
          <p align="center"><input type="submit" value="Add/Update Details">
          </form>';    
  }
  
  function movie_update_form_multiple( $movie_list )
  {
    $actors    = db_toarray("select actor_name name from actors order by 1");
    $directors = db_toarray("select director_name name from directors order by 1");
    $genres    = db_toarray("select genre_name name from genres order by 1");

    // Display movies that will be affected.
    echo '<h1>Update Movies</h1>
         <center>The following movies will be updated :<p>';
         array_to_table(db_toarray("select title from movies where file_id in (".implode(',',$movie_list).")"),'50%');      
      
    echo '</center>
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="MOVIE">
          <input type=hidden name="action" value="UPDATE_MULTIPLE">';

    foreach ($movie_list as $movie_id)
      echo '<input type=hidden name="movie[]" value="'.$movie_id.'">';
            
    echo '<table class="form_select_tab" width="100%" cellspacing=4><tr><td colspan="3" align="center">
          Please enter the details that you would like to <em>add</em> to each of the movies listed above
          <br>(holding down the CTRL key will allow you to select multiple entries in the lists)
          </td></tr><tr>
          <th width="33%">Actors</th>
          <th width="33%">Directors</th>
          <th width="33%">Genres</th>   
          </tr><tr>
          <td><select name="actors[]" multiple size="8">
          '.list_option_elements($actors).'
          </select></td><td><select name="directors[]" multiple size="8">
          '.list_option_elements($directors).'
          </select></td><td><select name="genres[]" multiple size="8">
          '.list_option_elements($genres).'
          </select></td></tr></tr><tr><td colspan="3" align="center">
          You may enter new Actors, Directors or Genres that are not listed into the boxes below. 
          <br>To add more than one new entry, separate each entry with a comma.
          </td></tr><tr>
          <td width="33%"><input name="actor_new" size=25></td>
          <td width="33%"><input name="director_new" size=25></td>
          <td width="33%"><input name="genre_new" size=25></td>
          </tr><tr>
            <th>Certificate</th>
            <th>Year</th>
          </tr><tr>
            <td>
            '.form_list_dynamic_html("rating",get_cert_list_sql(),'',true).'
            </td>
            <td><input name="year" size="6"></td>
          </tr></table>
          <p align="center"><input type="submit" value="Add/Update Details">
          </form>';    
  }

  function movie_update_single()
  {
    // Clear the existing details for this movie, as they will be reinserted by
    // calling the update_multiple function.
    $movie_id = $_REQUEST["movie"][0];
    db_sqlcommand("delete from actors_in_movie where movie_id=".$movie_id);
    db_sqlcommand("delete from directors_of_movie where movie_id=".$movie_id);
    db_sqlcommand("delete from genres_of_movie where movie_id=".$movie_id);
    movie_update_multiple();
  }
  
  function movie_update_multiple()
  {
    $movie_list = $_REQUEST["movie"];
    $columns    = array();
    
    if (!empty($_REQUEST["year"]))
      $columns["YEAR"] = $_REQUEST["year"];
    if (!empty($_REQUEST["rating"]))
      $columns["CERTIFICATE"] = $_REQUEST["rating"];

    // Update the MOVIES table?
    if (count($columns)>0)
    {
      $columns["DETAILS_AVAILABLE"] = 'Y';
      scdb_set_movie_attribs($movie_list, $columns);
    }
   
    // Add Actors/Genres/Directors?
    if (count($_REQUEST["actors"]) >0)
      scdb_add_actors($movie_list,$_REQUEST["actors"]);
    if (!empty($_REQUEST["actor_new"]))
      scdb_add_actors($movie_list, explode(',',$_REQUEST["actor_new"]));

    if (count($_REQUEST["directors"]) >0)
      scdb_add_directors($movie_list,$_REQUEST["directors"]);
    if (!empty($_REQUEST["director_new"]))
      scdb_add_directors($movie_list, explode(',',$_REQUEST["director_new"]));

    if (count($_REQUEST["genres"]) >0)
      scdb_add_genres($movie_list,$_REQUEST["genres"]);   
    if (!empty($_REQUEST["genre_new"]))
      scdb_add_genres($movie_list, explode(',',$_REQUEST["genre_new"]));

    scdb_remove_orphans();
    movie_display("Changes Made");
   }

  //*************************************************************************************************
  // Extra Movie Information options
  //*************************************************************************************************

  function movie_info( $message = "")
  {
    $list = array('Enabled'=>'YES','Disabled'=>'NO');
    
    if (!empty($_REQUEST["downloads"]))
    {
      set_sys_pref('movie_check_enabled',$_REQUEST["downloads"]);
      $message = 'Settings Saved';
    }
    
    if (!empty($_REQUEST["refresh"]))
    {
      db_sqlcommand('update movies set year = null, certificate = null, match_pc = null, details_available = null, synopsis = null');
      db_sqlcommand('delete from directors_of_movie');
      db_sqlcommand('delete from actors_in_movie');
      db_sqlcommand('delete from genres_of_movie');
      $message = 'All extra movie information scheduled for refresh.';
    }
    
    echo "<h1>Extra Movie Info</h1>";
    message($message);
    
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'MOVIE');
    form_hidden('action', 'INFO');
    echo '<p><b>Downloading extra movie information</b>
          <p>When new video items are discovered during a "new media search", the SwissCenter will use the filename
                of the video file to search for and download additional movie information (eg: Actors, Directors, etc)
                from the online movie rental site <a href=""http://www.lovefilm.com">www.lovefilem.com</a>.';
    form_radio_static('downloads','Status',$list,get_sys_pref('movie_check_enabled','YES'),false,true);
    form_submit('Save Changes',2);
    form_end();

    form_start('index.php', 150, 'conn');
    form_hidden('section', 'MOVIE');
    form_hidden('action', 'INFO');
    form_hidden('refresh','YES');
    echo '<p><b>Refreshing all extra movie information</b>
          <p>If you encounter a situation where you need to refresh all the movie information that was downloaded from
             the internet, for what ever reason, then you may do so by clicking on the button below.
          <p><span class="stdformlabel">However, please be aware that if you have manually entered information using the "Movie Details" page
             then this information will be lost.</span>';
    form_submit('Refresh All Extra Information',2);
    form_end();
  }
  
  //*************************************************************************************************
  // Media Search
  //*************************************************************************************************

  function media_search()
  {
    run_background('media_search.php');
    echo "<h1>Search For New Media</h1>";
    message('Searching for new media');
    echo '<p>The database refresh has been started in the background and you may continue to use the 
             system as normal. However please be aware that not all media files will be available 
             for playback until the refresh is complete. ';
  }
   
  //*************************************************************************************************
  // Privacy Policy
  //*************************************************************************************************

  function privacy_display()
  {
    ?>
    <h1>Privacy Policy</h1>
     <p><b>Data Collection</b>
     <p>Information relating to the media files on your machine is collected and stored in a MySQL database located on
        the PC on which the SwissCenter software is installed. This information is used for the sole purpose of 
        providing you with an interface to your media files on such devices as the Pinnacle Showcenter. At no point is 
        this of any other information transmitted in any form to the authors of the SwissCenter, or to any other third party.
     <p>There is one exception to the above statemnet :- To obtain extra information regarding video/movies files (such as actors, 
        directors, etc) the filename is submitted as part of a search query to the www.lovefilm.com website. If
        you would prefer that this informationis not transmitted then you should disable the "Movie Info Download" feature on
        the "Extra Movie Info" page.
     <p><b>Unsolicited Email/Messages</b>
     <p><ul>
        <li>We will never send you unsolicited email.
        <li>Messages downloaded to the SwissCenter interface will relate only to the capabilities and operation of the 
            interface. If you wish you may prevent even these messages from being downloaded by disabling the "Messages" 
            feature in "Connectivity" section.
        </ul>
    <?    
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
     menu_heading("Configuration");
     menu_item('Create Database','section=INSTALL&action=DISPLAY','menu_bgr2.png');
     if ($db_stat == 'OK')
     {
       menu_item('Categories','section=CATEGORY&action=DISPLAY');
       menu_item('Media Locations','section=DIRS&action=DISPLAY');
       menu_item('User Management','section=USERS&action=DISPLAY');
       menu_item('Scheduled Tasks','section=SCHED&action=DISPLAY');
       menu_item('Connectivity','section=CONNECT&action=DISPLAY');
       menu_item('Album/Film Art','section=ART&action=DISPLAY');
       menu_item('Playlists Location','section=PLAYLISTS&action=DISPLAY');
       menu_item('Image Cache','section=CACHE&action=DISPLAY');
       menu_heading();
       menu_heading();
       menu_heading("Media Management");
       menu_item('Search For New Media','section=MEDIA&action=SEARCH','menu_bgr.png');
       menu_item('Movie Details','section=MOVIE&action=DISPLAY','menu_bgr.png');
       menu_item('Extra Movie Info','section=MOVIE&action=INFO','menu_bgr.png');
       menu_heading();
       menu_heading();
       menu_heading("Information");
       menu_item('Privacy Policy','section=PRIVACY&action=DISPLAY','menu_bgr2.png');
       menu_item('Support Info','section=SUPPORT&action=DISPLAY','menu_bgr2.png');
     }
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
   
   
   if (!is_server_iis() && $_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"] && false)
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
     // The user has not specified an action, so try to work out which page they should be viewing
    if ($db_stat != 'OK')
    {
      // No database - this must be their first visit. Prompt for them to create a database
      install_display();
    }
    else
    {
      // Database is OK - Remove any old databsae update scripts that are still hanging around
      // and then display all the categories that they have defined.
      
      $sched = syscall('at');
      foreach(explode("\n",$sched) as $line)
        if (strstr($line,'db_update.php'))
           syscall('at '.substr(ltrim($line),0,strpos(ltrim($line),' ')).' /delete');

      category_display();  
    }
   }
 }

 
//*************************************************************************************************
// Get the database parameters from the ini file as they are needed throughout the script, and 
// then execute the template file
//*************************************************************************************************

  if ($_REQUEST["section"]!='INSTALL' && file_exists('swisscenter.ini'))
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
