<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  include_once('common.php5');
  include_once('mysql.php5');
  include_once('html_form.php5');  

  // Create and amanager the menu (static menu)

  function display_menu()
  {
    echo '<table width="160">';
    menu_item('Shell Commands','section=CMD&action=DISPLAY');
    menu_item('Messages','section=MESG&action=DISPLAY');
    menu_item('Release Code','section=RELEASE&action=DISPLAY');
    echo '</table>';
  }
 
  // Calls the correct function for displaying content on the page.
 
  function display_content()
  {
    if (!empty($_REQUEST["section"]))
    {
      $func = (strtoupper($_REQUEST["section"]).'_'.strtoupper($_REQUEST["action"]));
      include_once('config_'.strtolower($_REQUEST["section"]).'.php5');
      $func();
    }
    else 
    {
      // The user has not specified an action, so run installation tests and display the results.
      include_once('config_cmd.php5');
     cmd_display();
    } 
  }
  
  // Get the database parameters from the ini file as they are needed throughout the script, and 
  // then execute the template file

  if ($_REQUEST["section"]!='INSTALL' && file_exists('swisscenter.ini'))
  {
    foreach( parse_ini_file('swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }

  // Show the page

  $page_title = 'SwissCenter ONLINE Configuration Utility';
  $page_width = '750px';
  include("config_template.php5");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
