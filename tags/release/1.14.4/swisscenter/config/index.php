<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  ob_start();

  require_once( realpath(dirname(__FILE__).'/../base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/../base/file.php'));
  require_once( realpath(dirname(__FILE__).'/../base/html_form.php'));
  require_once( realpath(dirname(__FILE__).'/../base/server.php'));
  require_once( realpath(dirname(__FILE__).'/../base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/../base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/../base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/../base/db_abstract.php'));
  require_once( realpath(dirname(__FILE__).'/common.php'));

  // ----------------------------------------------------------------------------------
  // Display a database error
  // ----------------------------------------------------------------------------------
  
  function db_error()
  {
    return '!'.str('CONFIG_DB_ERROR');
  }
  
  // ----------------------------------------------------------------------------------
  // Write config file to disk.
  // ----------------------------------------------------------------------------------
  
  function write_ini ( $host, $user, $pass, $name )
  {
    $str = ";************************************************************************* ".newline().
           "; SwissCenter Configuration                                                ".newline().
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
  
  // ----------------------------------------------------------------------------------
  // Create and amanager the menu (static menu)
  // ----------------------------------------------------------------------------------

  function display_menu()
  {
    $db_stat = test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE); 

    echo '<table width="160">';
    menu_heading(str('CONFIGURATION'));
    menu_item( str('CONFIG_DB_CREATE')         ,'section=INSTALL&action=DISPLAY','menu_bgr2.png');
    if ($db_stat == 'OK')
	{
	  menu_item( str('CATEGORIES')             ,'section=CATEGORY&action=DISPLAY');
	  menu_item( str('MEDIA_LOCATIONS')        ,'section=DIRS&action=DISPLAY');
	  menu_item( str('USERS_ADD_TITLE')        ,'section=USERS&action=DISPLAY');
	  menu_item( str('SCHEDULE_TITLE')         ,'section=SCHED&action=DISPLAY');
	  menu_item( str('CONNECT_TITLE')          ,'section=CONNECT&action=DISPLAY');
	  menu_item( str('ART_FILES_TITLE')        ,'section=ART&action=DISPLAY');
	  menu_item( str('PLAYLISTS')              ,'section=PLAYLISTS&action=DISPLAY');
	  menu_item( str('CACHE_CONFIG_TITLE')     ,'section=CACHE&action=DISPLAY');

	  menu_heading();
	  menu_heading();
	  
	  menu_heading(str('MEDIA_MANAGEMENT'));
	  menu_item( str('ORG_TITLE')              ,'section=MOVIE&action=DISPLAY','menu_bgr.png');
	  menu_item( str('MOVIE_OPTIONS')      ,'section=MOVIE&action=INFO','menu_bgr.png');
	  
	  menu_heading();
	  menu_heading();
	  
	  menu_heading(str("INFORMATION"));
	  menu_item( str('PRIVACY_POLICY')         ,'section=PRIVACY&action=DISPLAY','menu_bgr2.png');
	  menu_item( str('SUPPORT_TITLE')          ,'section=SUPPORT&action=DISPLAY','menu_bgr2.png');
	}
    echo '</table>';
  }
 
  // ----------------------------------------------------------------------------------
  // Calls the correct function for displaying content on the page.
  // ----------------------------------------------------------------------------------
 
  function display_content()
  {
  	// Check to see if the database connection is OK
    $db_stat = test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE); 
    if ($db_stat == 'OK')
    {
      session_start();
      include_once('../base/settings.php');
    }
   
   
    if (!is_server_iis() && $_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"] && false)
    {
      echo '<br><h1>'.str('ACCESS_DENIED').'</h1><p align="center">'.str('REMOTE_ACCESS_ERROR').'</p>';
    }
    elseif (!empty($_REQUEST["section"]))
    {
      $func = (strtoupper($_REQUEST["section"]).'_'.strtoupper($_REQUEST["action"]));
      include_once('config_'.strtolower($_REQUEST["section"]).'.php');
      @$func();
    }
    else 
    {
      // The user has not specified an action, so try to work out which page they should be viewing
      if ($db_stat != 'OK')
      {
        // No database - this must be their first visit. Prompt for them to create a database
        include_once('config_install.php');
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

        include_once('config_category.php');
        category_display();  
      }
    }
  }

  // ----------------------------------------------------------------------------------
  // Get the database parameters from the ini file as they are needed throughout the script, and 
  // then execute the template file
  // ----------------------------------------------------------------------------------

  if ($_REQUEST["section"]!='INSTALL' && file_exists('swisscenter.ini'))
  {
    foreach( parse_ini_file('swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }

  $page_title = str('CONFIG_TITLE');
  $page_width = '750px';
  include("config_template.php");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
