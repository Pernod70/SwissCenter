<?
/**************************************************************************************************
   SWISScenter Source                                                     
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/page.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));

function apply_database_patches()
{
  $files = dir_to_array( SC_LOCATION.'database', 'patch_[0-9]*.sql');
  $current_version = get_sys_pref('DATABASE_PATCH',0);
  sort($files);
  
  foreach ($files as $file)
  {
    $patch = str_replace('patch_','',file_noext($file));
    if ( $patch > $current_version )
    {
      send_to_log(5, "Applying database patch [$patch]");
      db_sqlfile($file);
      set_sys_pref('DATABASE_PATCH',$patch);
    }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>