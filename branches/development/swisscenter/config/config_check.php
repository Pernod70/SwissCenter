<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/sched.php'));
require_once( realpath(dirname(__FILE__).'/../base/install_checks.php'));

// ----------------------------------------------------------------------------------
// A class to output the test results onto the page in a pretty format!
// ----------------------------------------------------------------------------------

class test_results
{
  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------

  var $sections;
  var $web_results;
  var $cli_results;
  
  #-------------------------------------------------------------------------------------------------
  # Constructor
  #-------------------------------------------------------------------------------------------------

  function test_results()
  {    
    $cli = explode("\n",str_replace("\r",'',run_foreground('config/cli_test.php')));
    $cli = unserialize(implode("\n",array_splice($cli,array_search("",$cli)+1)));
    
    $this->sections = array ();
    $this->web_results = get_check_results();
    $this->cli_results = $cli;
  }

  #-------------------------------------------------------------------------------------------------
  # Add a section
  #-------------------------------------------------------------------------------------------------

  function add_section( $heading )
  {
    $this->sections[] = array( "heading" => $heading
                             , "passed" => array()
                             , "failed_cli" => array() 
                             , "failed_web" => array() 
                             );
    return count($this->sections)-1;
  }
  
  #-------------------------------------------------------------------------------------------------
  # Add details about a specific test
  #-------------------------------------------------------------------------------------------------

  function add_test( $section, $key, $lang_string_stub)
  {
    if ( key_exists($section,$this->sections) )
    {      
      if (!$this->cli_results[$key])
        $this->sections[$section]["failed_cli"][] = str('FAIL_'.$lang_string_stub);
      elseif (!$this->web_results[$key])
        $this->sections[$section]["failed_web"][] = str('FAIL_'.$lang_string_stub);
      else 
        $this->sections[$section]["passed"][] = str('PASS_'.$lang_string_stub);
    }
  }
  
  #-------------------------------------------------------------------------------------------------
  # Display the results
  #-------------------------------------------------------------------------------------------------

  function display()
  {
    echo '<h1>'.str('INSTALL_TEST_TITLE').'</h1>
          <p><center>';
    foreach ($this->sections as $section)
    {
      $failures = (count($section["failed_cli"]) + count($section["failed_web"]) > 0);
      
      // Show the heading for this section (complete with pass/fail icon)
      echo '<p><table border=1 noshade width="90%" cellspacing=0 cellpadding=8 bgcolor="#ffffff">
            <tr>
              <td width="100%"><b>'.$section["heading"].'</b></td>
              <td width="40" valign=top align=center><img src="'.($failures ? 'fail.png' : 'pass.png').'"></td>
            </tr>
            <tr><td colspan=2>';
      
      if (count($section["passed"]) > 0)
      {
        echo '<p>Successful<ul>';
        foreach ($section["passed"] as $msg)
          echo '<li>'.$msg;
        echo '</ul>';
      }
      
      if (count($section["failed_web"]) > 0)
      {
        echo '<p>Failed (web)<ul>';
        foreach ($section["failed_web"] as $msg)
          echo '<li>'.$msg;
        echo '</ul>';
      }

      if (count($section["failed_cli"]) > 0)
      {
        echo '<p>Failed (cli)<ul>';
        foreach ($section["failed_cli"] as $msg)
          echo '<li>'.$msg;
        echo '</ul>';
      }

      // End the display for this section
      echo '</td>
            </tr>
            </table>';
    }
    echo '</center>';
  }

}

// ----------------------------------------------------------------------------------
// Display test results
// ----------------------------------------------------------------------------------

function check_display()
{
  load_lang();
  $test_page = new test_results();
  
  $php = $test_page->add_section("PHP");
  $test_page->add_test($php, "PHP ini file","PHP_INI");
  $test_page->add_test($php, "PHP required mods","PHP_REQ_MODS");
  $test_page->add_test($php, "PHP suggested mods","PHP_EXTRA_MODS");
  $test_page->add_test($php, "PHP version","PHP_VERSION");

  $mysql = $test_page->add_section("MySQL");
  $test_page->add_test($mysql, "MYSQL version","MYSQL_VERSION");
  $test_page->add_test($mysql, "MYSQL connect","MYSQL_CONNECT");
 
  $swiss = $test_page->add_section("SwissCenter");
  $test_page->add_test($swiss, "SWISS ini file","SWISS_INI");
  $test_page->add_test($swiss, "SWISS media locs","SWISS_LOCS");
  $test_page->add_test($swiss, "SWISS write log","SWISS_LOG");

  $server = $test_page->add_section("Webserver");
  $test_page->add_test($server, "SERVER scheduler","SERVER_SCHED");
  
  $test_page->display();
}


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
