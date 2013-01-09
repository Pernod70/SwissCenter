<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/xml_sidecar.php'));
  require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));

  // Log details of the page request
  send_to_log(1,"------------------------------------------------------------------------------");
  send_to_log(1,"Page Requested : ".current_url()." by client (".client_ip().")");

  $type        = $_REQUEST["Type"];
  $path        = str_replace('\\','/',un_magic_quote(rawurldecode($_REQUEST["Path"])));
  $oldpath     = isset($_REQUEST["OldPath"]) ? str_replace('\\','/',un_magic_quote(rawurldecode($_REQUEST["OldPath"]))) : '';
  $changed     = $_REQUEST["ChangedDate"];
  $isDirectory = $_REQUEST["IsDirectory"];
  $isFile      = ($isDirectory == 'Yes' ? false : true);

  $dir         = dirname($path);
  $file        = basename($path);

  $location    = db_row("select * from media_locations where '".db_escape_str($dir)."/' like concat(name,'/%')");
  $table       = db_value("select media_table from media_types where media_id = $location[MEDIA_TYPE]");
  $file_exts   = media_exts( $location["MEDIA_TYPE"] );
  $extra_info  = (db_value("select download_info from categories where cat_id = $location[CAT_ID]") == 'Y');

  send_to_log(5,"SwissMonitor update detected:", array("Type"      => $type,
                                                       "Path"      => $path,
                                                       "OldPath"   => $oldpath,
                                                       "Changed"   => $changed,
                                                       "Directory" => $isDirectory));

  switch ( $type )
  {
    case "Deleted":
      if ( $isFile )
      {
        // Remove file from database
        db_sqlcommand("delete from $table where dirname='".db_escape_str($dir)."/' and filename='".db_escape_str($file)."'");

        $response = array("status"  => "OK",
                          "message" => "File deleted from the database.",
                          "retry"   => "false");
      }
      else
      {
        // Remove files in folder from database
        db_sqlcommand("delete from $table where dirname like '".db_escape_str($path)."/%'");

        $response = array("status"  => "OK",
                          "message" => "Folder deleted from the database.",
                          "retry"   => "false");
      }
      break;

    case "Renamed":
      if ( $isFile )
      {
        // Remove old file from database
        db_sqlcommand("delete from $table where dirname='".db_escape_str(dirname($oldpath))."/' and filename='".db_escape_str(basename($oldpath))."'");
      }
      else
      {
        // Remove files in old folder from database
        db_sqlcommand("delete from $table where dirname like '".db_escape_str($oldpath)."/%'");
      }

    case "Created":
    case "Changed":
      if ( $isFile )
      {
        // Add new file to database
        $file_added = process_media_file( $dir.'/', $file, $location["LOCATION_ID"], $location["NETWORK_SHARE"], $table, $file_exts, true );

        if ( $file_added )
          $response = array("status"  => "OK",
                            "message" => "File added to the database.",
                            "retry"   => "false");
        else
          $response = array("status"  => "OK",
                            "message" => "Not a valid media file for this location.",
                            "retry"   => "false");
      }
      else
      {
        $status = get_sys_pref('MEDIA_SCAN_STATUS');
        if ( empty($status) || $status == str('MEDIA_SCAN_STATUS_COMPLETE') )
        {
          // Add new folder to database
          set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_RUNNING'));
          process_media_directory( $path.'/', $location["LOCATION_ID"], $location["NETWORK_SHARE"], $table, $file_exts );
          set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_COMPLETE'));

          $response = array("status"  => "OK",
                            "message" => "Folder added to the database.",
                            "retry"   => "false");
        }
        else
        {
          // Cannot scan directory whilst media search is running
          $response = array("status"  => "Fail",
                            "message" => "Media search in progress.",
                            "retry"   => "true");
        }
      }
      break;

    default:

      $response = array("status"  => "Failed",
                        "message" => "Unknown Type from SwissMonitor.",
                        "retry"   => "false");
      break;
  }

  // If a file was added then we might need to download extra information from the internet via a parser.
  if ( $file_added && $extra_info )
  {
    // Update video details from the Internet if enabled
    if ( $location["MEDIA_TYPE"] == MEDIA_TYPE_VIDEO && is_movie_check_enabled() )
    {
      $info = db_row("select * from movies where concat(dirname,filename) = '".db_escape_str($path)."'");
      ParserMovieLookup($info["FILE_ID"], $path, array('TITLE' => $info["TITLE"]));
      // Export to XML
      if ( get_sys_pref('movie_xml_save','NO') == 'YES' )
        export_video_to_xml($info["FILE_ID"]);
    }

    if ( $location["MEDIA_TYPE"] == MEDIA_TYPE_TV && is_tv_check_enabled() )
    {
      $info = db_row("select * from tv where concat(dirname,filename) = '".db_escape_str($path)."'");
      ParserTvLookup($info["FILE_ID"], $path, array('PROGRAMME' => $info["PROGRAMME"],
                                                    'SERIES'    => $info["SERIES"],
                                                    'EPISODE'   => $info["EPISODE"],
                                                    'TITLE'     => $info["TITLE"]));
      // Export to XML
      if ( get_sys_pref('tv_xml_save','NO') == 'YES' )
        export_tv_to_xml($info["FILE_ID"]);
    }
  }

  // SwissMonitor expects a response in the form:
  //
  //  <?xml version="1.0" encoding="utf-8"? >
  //  <result xmlns="http://www.swisscenter.co.uk/schemas/2009/03/SwissMonitor">
  //    <status>OK|Failed</status>
  //    <message>Some funky fatal error.</message>
  //    <retry>true|false</retry>
  //  </result>

  send_to_log(5,"SwissMonitor response:", $response);
  header('Content-Type: text/xml');
  echo '<?xml version="1.0" encoding="utf-8"?>
        <result xmlns="http://www.swisscenter.co.uk/schemas/2009/03/SwissMonitor">
          <status>'.$response["status"].'</status>
          <message>'.$response["message"].'</message>
          <retry>'.$response["retry"].'</retry>
        </result>';
?>