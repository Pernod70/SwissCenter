<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/config_check.php'));

// ----------------------------------------------------------------------------------
// Display test results
// ----------------------------------------------------------------------------------

function check_files_display()
{
  $file_tests = new test_summary( str('INSTALL_TEST_TITLE') );
  $file_tests->set_fail_icon('question.png');

  # ----------------------
  # SwissCenter configuration Tests
  # ----------------------

  $swiss = $file_tests->add_section("File Verification",1);

  // Only verify installation files if not a developer
//  if (get_sys_pref('IS_DEVELOPMENT','NO') == 'NO')
    $file_tests->add_test( $swiss, check_swiss_files(), str("PASS_SWISS_FILES"), str("FAIL_SWISS_FILES", format_filelist_html(SC_LOCATION."filelist_missing.txt")) );

  # ----------------------
  # Display test results
  # ----------------------

  $file_tests->display();
}

/**
 * Returns formatted HTML of files with links to SVN to download from.
 *
 * @param array $filelist
 * @return string
 */
function format_filelist_html( $filelist )
{
  $filelist = unserialize(file_get_contents($filelist));
  $revision = svn_current_revision();
  $filelist_html = '';
  foreach ($filelist as $file)
    $filelist_html .= '<br><a href="http://tools.assembla.com/swiss/export/'.$revision.'/trunk/swisscenter/'.$file["filename"].'" target="_blank">'.$file["filename"].'</a>
                           <a href="http://tools.assembla.com/swiss/changeset/'.$file["revision"].'" target="_blank">['.$file["revision"].']</a> ('.str($file["error"]).')';
  return $filelist_html;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
