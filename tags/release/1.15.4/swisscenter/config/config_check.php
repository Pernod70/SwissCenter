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
    $this->sections    = array ();
    $this->cli_results = array();
    
    // Get the results of all the installation tests when run via the webserver
    $this->web_results = get_check_results();
    
    // Simese hangs when you attempt to run a PHP script in the foreground, but we don't really need to
    // run the tests under the CLI version of PHP as that's how simese operates normally anyway.
    if (! is_server_simese())
    {
      // Now run the same tests again via the command line.
      // This will reveal any differences between the webserver install (which might be using mod_php) 
      // and the command line version - for instance, they might be on totally difference versions.
      $cli = run_foreground('config/cli_test.php');
      if ($cli !== false)
      {
        $cli = explode("\n",str_replace("\r",'',$cli));
        $cli = unserialize(implode("\n",$cli));
        $this->cli_results = $cli;
      }
    }
  }

  #-------------------------------------------------------------------------------------------------
  # Add a new section and returns the identifier for it (used when adding tests).
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
  # Returns the result of a test (true for pass, false for fail)
  #-------------------------------------------------------------------------------------------------

  function test_result( $key )
  {
    if ( key_exists($key,$this->web_results) )
    {      
      if (!$this->web_results[$key] || (count($this->cli_results)>0 && !$this->cli_results[$key]) )
        return false;
      else 
        return true;
    }
    else 
      return false;
  }
  
  #-------------------------------------------------------------------------------------------------
  # Add details about a specific test
  #-------------------------------------------------------------------------------------------------

  function add_test( $section, $key, $pass, $fail)
  {
    if ( key_exists($section,$this->sections) )
    {      
      if (!$this->web_results[$key])
        $this->sections[$section]["failed_web"][] = $fail;
      elseif (is_array($this->cli_results) && key_exists($key,$this->cli_results) && !$this->cli_results[$key])
        $this->sections[$section]["failed_cli"][] = $fail;
      else 
        $this->sections[$section]["passed"][] = $pass;
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
            </tr>';
      
      if ( $failures )
      {      
        echo '<tr><td colspan=2>';
        
        if (count($section["passed"]) > 0)
        {
          echo '<p>'.str('INSTALL_TEST_SUCCESS').'<font color="#006600"><ul>';
          foreach ($section["passed"] as $msg)
            echo '<li>'.$msg;
          echo '</ul></font>';
        }
        
        if (count($section["failed_web"]) > 0)
        {
          echo '<p>'.str('INSTALL_TEST_FAIL_WEB').'<font color="#660000"><ul>';
          foreach ($section["failed_web"] as $msg)
            echo '<li>'.$msg;
          echo '</ul></font>';
        }
  
        if (count($section["failed_cli"]) > 0)
        {
          echo '<p>'.str('INSTALL_TEST_FAIL_CLI').'<font color="#660000"><ul>';
          foreach ($section["failed_cli"] as $msg)
            echo '<li>'.$msg;
          echo '</ul></font>';
        }

        echo '</td>';
      }

      // End the display for this section
      echo '</tr>
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
    
  # ----------------------
  # PHP Tests
  # ----------------------
  
  $php = $test_page->add_section("PHP");
  
  if (! is_server_simese() || version_compare(simese_version(),'1.31','<') )
    $test_page->add_test( $php, "PHP cli", str("PASS_PHP_CLI"), str("FAIL_PHP_CLI" ) );    

  $test_page->add_test( $php, "PHP version", str("PASS_PHP_VERSION"), str("FAIL_PHP_VERSION",phpversion()) );
  $test_page->add_test( $php, "PHP ini file", str("PASS_PHP_INI"), str("FAIL_PHP_INI"));
  $test_page->add_test( $php, "PHP required mods", str("PASS_PHP_REQ_MODS"), str("FAIL_PHP_REQ_MODS", implode(', ',get_required_modules_list())) );
  $test_page->add_test( $php, "PHP suggested mods", str("PASS_PHP_EXTRA_MODS"), str("FAIL_PHP_EXTRA_MODS", implode(', ',get_suggested_modules_list())) );
  $test_page->add_test( $php, "PHP fonts", str("PASS_PHP_FONTS"), str("FAIL_PHP_FONTS") );

  # ----------------------
  # MySQL Tests
  # ----------------------
  
  # If there is no swisscenter.ini file present, then we will not be able to connect to the database
  # because that is where the connection details are stored.
  
  if ( $test_page->test_result('SWISS ini file'))
  {                         
    $mysql = $test_page->add_section("MySQL");
  
    $test_page->add_test( $mysql, "MYSQL connect", str("PASS_MYSQL_CONNECT"), str("FAIL_MYSQL_CONNECT"));  
    $test_page->add_test( $mysql, "MYSQL version", str("PASS_MYSQL_VERSION"), str("FAIL_MYSQL_VERSION"));
    $test_page->add_test( $mysql, "MYSQL database", str("PASS_MYSQL_DB"), str("FAIL_MYSQL_DB"));
  }
                      
  # ----------------------
  # Webserver Tests
  # ----------------------

  $server = $test_page->add_section("Webserver");

  $test_page->add_test( $server, "SERVER scheduler", str("PASS_SERVER_SCHED"), str("FAIL_SERVER_SCHED"));
                           
  # Display test results
  $test_page->display();

  # ----------------------
  # SwissCenter configuration Tests
  # ----------------------
                           
  $swiss = $test_page->add_section("SwissCenter");

  // It only makes sense to check for root installations on UNIX.
  if (is_unix())
    $test_page->add_test( $swiss, "SWISS root install", str("PASS_SWISS_ROOT_INS"), str("ROOT_INSTALL_TEXT"));

  $test_page->add_test( $swiss, "SWISS write root", str("PASS_SWISS_RW_FILES"), str("MISSING_PERMS_TEXT"));
  $test_page->add_test( $swiss, "SWISS ini file", str("PASS_SWISS_INI"), str("FAIL_SWISS_INI"));
  $test_page->add_test( $swiss, "SWISS write log", str("PASS_SWISS_LOG"), str("FAIL_SWISS_LOG", logfile_location()) );
                      
  if ( $test_page->test_result('MYSQL database'))
    $test_page->add_test( $swiss, "SWISS media locs", str("PASS_SWISS_LOCS"), str("FAIL_SWISS_LOCS"));

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
