<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/page.php'));
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
           "DB_DATABASE=$name ".newline();
  
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
    $menu = new config_menu();
        
    $menu->add_menu(str('CONFIGURATION'));
    $menu->add_item(str('CONFIG_OVERVIEW')          ,'');
    $menu->add_item(str('CONFIG_DB_CREATE')         ,'section=INSTALL&action=DISPLAY');
    
    if ($db_stat == 'OK')
  	{
      $menu->add_menu(str('CONFIGURATION'));
      $menu->add_item(str('CACHE_CONFIG_TITLE')     ,'section=CACHE&action=DISPLAY');
  	  $menu->add_item(str('CATEGORIES')             ,'section=CATEGORY&action=DISPLAY');
  	  $menu->add_item(str('BROWSE_OPTIONS')         ,'section=BROWSE&action=DISPLAY');
	    $menu->add_item(str('CONNECT_TITLE')          ,'section=CONNECT&action=DISPLAY');
  	  $menu->add_item(str('USERS_ADD_TITLE')        ,'section=USERS&action=DISPLAY');
  	  $menu->add_item(str('SCHEDULE_TITLE')         ,'section=SCHED&action=DISPLAY');
  
      $menu->add_menu(str('MEDIA_MANAGEMENT'));
  	  $menu->add_item(str('MEDIA_LOCATIONS')        ,'section=DIRS&action=DISPLAY');
  	  $menu->add_item(str('CONFIG_AUDIO_OPTIONS')   ,'section=AUDIO&action=DISPLAY');
  	  $menu->add_item(str('CONFIG_IMAGE_OPTIONS')   ,'section=IMAGE&action=DISPLAY');
  	  $menu->add_item(str('CONFIG_RADIO_OPTIONS')   ,'section=RADIO&action=DISPLAY');
  	  $menu->add_item(str('PLAYLISTS')              ,'section=PLAYLISTS&action=DISPLAY');
  	  $menu->add_item(str('MOVIE_OPTIONS')          ,'section=MOVIE&action=INFO');
  	  $menu->add_item(str('ART_FILES_TITLE')        ,'section=ART&action=DISPLAY');
  	  $menu->add_item(str('ORG_TITLE')              ,'section=MOVIE&action=DISPLAY');

      $menu->add_menu(str('INFORMATION'));
  	  $menu->add_item(str('PRIVACY_POLICY')         ,'section=PRIVACY&action=DISPLAY');
  	  $menu->add_item(str('SUPPORT_TITLE')          ,'section=SUPPORT&action=DISPLAY');
  	}
  	
  	$menu->display();
    $menu_id = $_SESSION["config_menu"];
  	echo '<script>showHide("submenu'.$menu_id.'");</script>';
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
      $func();
    }
    else 
    {
      // The user has not specified an action, so run installation tests and display the results.
      include_once('config_check.php');
      check_display();  
    }
  }
  
  /**
   * Identify which menu the user is currently on by looking at the "menu" parameter
   * passed into the page. If no menu parameter was passed, then use the last value
   * that was stored in the session.
   * 
   * Finally, if the user has never been to a config page in this session, then 
   * select the first menu.
   */

  // Identify which menu the user is currently on. 
  if (empty($_REQUEST["section"]) )
    $_SESSION["config_menu"] = 1;
  elseif (!empty($_REQUEST["menu"]))
    $_SESSION["config_menu"] = $_REQUEST["menu"];

  /**
   * Get the database parameters from the ini file as they are needed throughout the script, 
   * and then execute the template file
   */
  
  if ($_REQUEST["section"]!='INSTALL' && file_exists('swisscenter.ini'))
  {
    foreach( parse_ini_file('swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }

  $page_title = '<a href="/"><img border=0 align="right" hspace=8 src="/images/close.gif"></a>'.str('CONFIG_TITLE');
  $page_width = '750px';
  include("config_template.php");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
