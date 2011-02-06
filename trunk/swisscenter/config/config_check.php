<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/sched.php'));
require_once( realpath(dirname(__FILE__).'/../base/install_checks.php'));

// ----------------------------------------------------------------------------------
// A class to output the test results onto the page in a pretty format!
// ----------------------------------------------------------------------------------

class test_summary
{
  var $title;
  var $sections;

  var $pass_img = 'pass.png';
  var $fail_img = 'fail.png';

  function test_summary( $title )
  {
    $this->sections = array();
    $this->title    = $title;
  }

  function set_pass_icon( $icon )
  { $this->pass_img = $icon; }

  function set_fail_icon( $icon )
  { $this->fail_img = $icon; }

  #-------------------------------------------------------------------------------------------------
  # Add a new section and returns the identifier for it (used when adding tests).
  #-------------------------------------------------------------------------------------------------

  function add_section( $heading, $position )
  {
    $this->sections[$position] = array( "heading" => $heading
                                      , "passed"  => array()
                                      , "failed"  => array()  );
    return $position;
  }


  #-------------------------------------------------------------------------------------------------
  # Add details about a specific test
  #-------------------------------------------------------------------------------------------------

  function add_test( $section, $result, $pass, $fail)
  {
    if ( key_exists($section,$this->sections) )
    {
      if ($result)
        $this->sections[$section]["passed"][] = $pass;
      else
        $this->sections[$section]["failed"][] = $fail;
    }
    else
      echo '<li>'.$section.' - '.$key;
  }

  #-------------------------------------------------------------------------------------------------
  # Display the results
  #-------------------------------------------------------------------------------------------------

  function display()
  {
    ksort($this->sections);

    echo '<h1>'.$this->title.'</h1>
          <p><center>';
    foreach ($this->sections as $section)
    {
      $failures = (count($section["failed"]) > 0);

      // Show the heading for this section (complete with pass/fail icon)
      echo '<p><table border=1 noshade width="90%" cellspacing=0 cellpadding=8 bgcolor="#ffffff">
            <tr>
              <td width="100%"><b>'.$section["heading"].'</b></td>
              <td width="40" valign=top align=center><img src="/images/'.($failures ? $this->fail_img : $this->pass_img).'"></td>
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

        if (count($section["failed"]) > 0)
        {
          echo '<p>'.str('INSTALL_TEST_FAIL_WEB').'<font color="#660000"><ul>';
          foreach ($section["failed"] as $msg)
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
  $core_tests      = new test_summary( str('INSTALL_TEST_TITLE') );
  $component_tests = new test_summary( str('EXTRAS_TEST_TITLE') );
  $component_tests->set_fail_icon('question.png');

  # ----------------------
  # SwissCenter configuration Tests
  # ----------------------

  if (get_sys_pref("SVN_REVISION") == "")
    $version = swisscenter_version();
  else
    $version = 'SVN Revision ['.get_sys_pref("SVN_REVISION").']';

  $swiss = $core_tests->add_section("SwissCenter : ".$version,4);

  // It only makes sense to check for root installations on UNIX.
  if (is_unix())
    $core_tests->add_test( $swiss, check_not_root_install(), str("PASS_SWISS_ROOT_INS"), str("ROOT_INSTALL_TEXT"));

  $core_tests->add_test( $swiss, check_database_patch(), str("PASS_DATABASE_PATCH", get_sys_pref('database_patch')), str("FAIL_DATABASE_PATCH", get_sys_pref('database_patch')) );

  # ----------------------
  # PHP Tests
  # ----------------------

  $php = $core_tests->add_section("PHP : v".phpversion(),2);

  $core_tests->add_test( $php, check_php_version(), str("PASS_PHP_VERSION"), str("FAIL_PHP_VERSION",phpversion()) );
  $core_tests->add_test( $php, check_php_required_modules(), str("PASS_PHP_REQ_MODS"), str("FAIL_PHP_REQ_MODS", implode(', ',get_required_modules_list())) );
  $core_tests->add_test( $php, check_php_suggested_modules(), str("PASS_PHP_EXTRA_MODS"), str("FAIL_PHP_EXTRA_MODS", implode(', ',get_suggested_modules_list())) );
  $core_tests->add_test( $php, check_php_ttf(), str("PASS_PHP_FONTS"), str("FAIL_PHP_FONTS") );

  # ----------------------
  # MySQL Tests
  # ----------------------

  # If there is no swisscenter.ini file present, then we will not be able to connect to the database
  # because that is where the connection details are stored.

  if ( check_swiss_ini_file() )
  {
    $mysql = $core_tests->add_section("MySQL : v".mysql_version(),3);

    $core_tests->add_test( $mysql, check_mysql_connect(), str("PASS_MYSQL_CONNECT"), str("FAIL_MYSQL_CONNECT"));
    $core_tests->add_test( $mysql, check_mysql_version(), str("PASS_MYSQL_VERSION"), str("FAIL_MYSQL_VERSION"));
    $core_tests->add_test( $mysql, check_mysql_database_exists(), str("PASS_MYSQL_DB"), str("FAIL_MYSQL_DB"));
  }

  # ----------------------
  # Webserver Tests
  # ----------------------

  if (is_server_simese())
    $ws_version = "Simese v".simese_version();
  elseif (is_server_lighttpd())
    $ws_version = "Lighttpd v".lighttpd_version();
  else
    $ws_version = "Apache v".apache_version();

  $server = $core_tests->add_section("Webserver : ".$ws_version,1);
  $core_tests->add_test( $server, check_server_scheduler(), str("PASS_SERVER_SCHED"), str("FAIL_SERVER_SCHED"));

  # ----------------------
  # Music IP
  # ----------------------

  $musicip = $component_tests->add_section("MusicIP ".musicip_version(),1);

  $component_tests->add_test( $musicip, musicip_available(TRUE), str('PASS_MUSICIP_TEST'),str('FAIL_MUSICIP_TEST').'<p>'.str('MIP_DESC','<a href="http://www.musicip.com">www.musicip.com</a>'),FALSE);

  if ( musicip_available() )
    $component_tests->add_test( $musicip, (musicip_mixable_percent() >=50),str('PASS_MIP_MIXABLE'),str('FAIL_MIP_MIXABLE'));

  # ----------------------
  # SwissMonitor Service
  # ----------------------

  if (is_windows())
  {
    $monitor = $component_tests->add_section("SwissMonitor",4);
    $installed = win_service_installed("SwissMonitorService");
    $component_tests->add_test( $monitor, $installed , str('PASS_SWISSMONITOR_INSTALLED'),str('FAIL_SWISSMONITOR_INSTALLED').'<p>'.str('SWISSMONITOR_DESCRIPTION'));

    if ($installed)
      $component_tests->add_test( $monitor, (win_service_status("SwissMonitorService") == SERVICE_STARTED),str('PASS_SWISSMONITOR_RUNNING'),str('FAIL_SWISSMONITOR_RUNNING'));
    else
      $component_tests->add_test( $monitor, win_dotnet2_installed(), str('PASS_DOTNET2_INSTALLED'), str('FAIL_DOTNET2_INSTALLED'));
  }

  # ----------------------
  # Display test results
  # ----------------------

  $core_tests->display();
  $component_tests->display();
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
