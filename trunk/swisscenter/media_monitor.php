<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/musicip.php'));
  require_once( realpath(dirname(__FILE__).'/base/rss.php'));
  require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));
  require_once( realpath(dirname(__FILE__).'/itunes_import.php'));


  $changed     = $_REQUEST["ChangedDate"];
  $type        = $_REQUEST["Type"];
  $path        = str_replace('\\','/',un_magic_quote($_REQUEST["Path"]));
  $dir         = dirname($path);
  $file        = basename($path);
  $location    = db_row("select * from media_locations where '$dir' like concat(name,'%')");
  $table       = db_value("select media_table from media_types where  media_id = $location[MEDIA_TYPE]");
  $file_exts   = media_exts( $location["MEDIA_TYPE"] );

  send_to_log(5,"Media update detected.",array("Type"=>$type,"Path"=>$path,"Changed"=>$changed));

  if ( is_dir($path))
  {
    // The change applies to a directory.

    if ($type == "Deleted")
    {
      send_to_log(5,"The following directory has been deleted: $path");
      $files = db_col_to_list("select concat(dirname,filename) FILENAME from $table where dirname like '$dir/%'");
      db_sqlcommand("delete from $table where dirname like '$dir/%'");
      send_to_log(5,"The following media files have been removed from the database",$files);
    }
    else
    {
      send_to_log(5,"The following directory has been created/changed: $path");
      send_to_log(5,"This is not handled yet");
    }
  }
  else
  {
    // The change applies to a file

    if ($type == "Deleted")
    {
      send_to_log(5,"The following file has been deleted: $path");
      $files = db_col_to_list("select concat(dirname,filename) FILENAME from $table where dirname='$dir/' and filename='$file'");
      db_sqlcommand("delete from $table where dirname='$dir/' and filename='$file'");
      send_to_log(5,"The following media files have been removed from the database",$path);
    }
    else
    {
      send_to_log(5,"The following file has been created/changed: $path");
      process_media_file( $dir.'/', $file, $location["LOCATION_ID"], $location["NETWORK_SHARE"], $table, $file_exts, true );
      send_to_log(5,"Update complete");
    }

  }

  echo "Update Complete";
