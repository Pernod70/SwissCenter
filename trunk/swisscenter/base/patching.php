<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));

function apply_database_patches()
{
  $database_patch   = get_sys_pref('DATABASE_PATCH',0);
  $database_version = get_sys_pref('DATABASE_VERSION',1);

  $files = dir_to_array( SC_LOCATION.'database', 'patch_'.($database_version[0]-1).'[0-9]{3}.sql');
  sort($files);

  $errors = 0;
  foreach ($files as $file)
  {
    $patch = str_replace('patch_','',file_noext($file));
    if ( $patch > $database_patch )
    {
      send_to_log(5, "Applying database patch [$patch]");
      // Reset the timeout counter for each patch being applied
      set_time_limit(60);
      db_sqlcommand('START TRANSACTION');
      $errors = db_sqlfile($file);
      if ( $errors == 0)
      {
        db_sqlcommand('COMMIT');
        set_sys_pref('DATABASE_PATCH',$patch);
      }
      else
      {
        db_sqlcommand('ROLLBACK');
        send_to_log(1, "Failed to apply database patch [$patch]");
        break;
      }
    }
  }
  return ($errors == 0 ? true : false);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
