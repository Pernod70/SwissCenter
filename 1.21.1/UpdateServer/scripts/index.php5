<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/

  include_once('common.php5');
  include_once('mysql.php5');
  include_once('html_form.php5');  

  // Create and amanager the menu (static menu)

  function display_menu()
  {
    echo '<table width="160">';
    if ($_ENV["REMOTE_USER"] == 'admin')
    {
      menu_item('Shell Commands','section=CMD&action=DISPLAY');
      menu_item('SQL Commands','section=DB&action=RUNSQL');
      menu_item('Contributors','section=CONTRIB&action=DISPLAY');
    }
    
    if ($_ENV["REMOTE_USER"] == 'pernod' || $_ENV["REMOTE_USER"] == 'admin')
    {
      menu_item('Release Code','section=RELEASE&action=DISPLAY');
      menu_item('Messages','section=MESG&action=DISPLAY');
    }

    menu_item('Upload Style','section=STYLE&action=DISPLAY');
    echo '</table>';
  }
 
  // Calls the correct function for displaying content on the page.
 
  function display_content()
  {
    $section=$_REQUEST["section"];
    $action=$_REQUEST["action"];

    if (!empty($section))
    {
      $func = (strtoupper($section).'_'.strtoupper($action));
      include_once('config_'.strtolower($section).'.php5');
      $func();
    }
    else
    {
      echo '<p><h1>Welcome...</h1>
            <p>Welcome to the SwissCenter online configuration utiliuty. 
               Depending upon your access level, there will be several menu items listed to the left.';
    }
  }
  
  // Show the page

  $page_title = 'SwissCenter ONLINE Configuration Utility';
  $page_width = '750px';
  include("config_template.php5");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
