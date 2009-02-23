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
  $extra_info  = (db_value("select download_info from categories where cat_id = $location[CAT_ID]") == 'Y');

  send_to_log(5,"Media update detected.",array("Type"=>$type,"Path"=>$path,"Changed"=>$changed));

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
    $file_added = process_media_file( $dir.'/', $file, $location["LOCATION_ID"], $location["NETWORK_SHARE"], $table, $file_exts, true );

    // If a file was added then we might need to download extra information from the internet via a parser.
    if ( $file_added )
    {
      // Update video details from the Internet if enabled
      require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));

      if ( $extra_info && $location["MEDIA_TYPE"] == MEDIA_TYPE_VIDEO && is_movie_check_enabled() )
      {
        $info = db_row("select * from movies where concat(dirname,filename) = '$path'");
        extra_get_movie_details( $info["FILE_ID"], $path, $info["TITLE"]);
      }

      if ( $extra_info && $location["MEDIA_TYPE"] == MEDIA_TYPE_TV && is_tv_check_enabled() )
      {
        $info = db_row("select * from tv where concat(dirname,filename) = '$path'");
        extra_get_tv_details($info["FILE_ID"], $path, $info["PROGRAMME"], $info["SERIES"], $info["EPISODE"], $info["TITLE"]);
      }
    }
    else
    {
      send_to_log(5,"This file is not a valid media file");
    }
  }


  send_to_log(5,"Update complete");
  echo "Update Complete";
