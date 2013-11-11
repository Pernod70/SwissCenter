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
  foreach ($filelist as $path=>$file)
    if ($file["error"] == 'delete')
      $filelist_html .= '<br>'.$path.' ('.str($file["error"]).')';
    else
      $filelist_html .= '<br><a href="https://www.assembla.com/code/swiss/subversion/nodes/trunk/swisscenter/'.$path.'?_format=raw&rev='.$revision.'" target="_blank">'.$path.'</a>
                             <a href="https://www.assembla.com/code/swiss/subversion/changesets/'.$file["revision"].'" target="_blank">['.$file["revision"].']</a> ('.str($file["error"]).')';
  return $filelist_html;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/