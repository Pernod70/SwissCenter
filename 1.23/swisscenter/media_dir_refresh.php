<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));

  // Set unlimited timeout/memory for the media refresh - otherwise the showcenter will display
  //  an error after 30 seconds.
  set_time_limit(0);
  ini_set('memory_limit',-1);

  if (empty( $_REQUEST["go"]) )
    // Output a message to the user
    page_inform(0, url_add_param(current_url(),'go','true'), str('REFRESH_DIR'), str('REFRESH_DIR_TEXT'));

  else
  {
    // Perform the media search. 
    $dir             = urldecode( $_REQUEST["dir"]);
    $rtn             = urldecode( $_REQUEST["return_url"]);
    $media_type      = $_REQUEST["media_type"];
    $table           = db_value("select media_table from media_types where  media_id=$media_type");
    $filetypes       = media_exts( $media_type );
    $media_locations = db_toarray("select * from media_locations where media_type=".$media_type);
  
    foreach ($media_locations as $row)
      process_media_directory( str_suffix($row["NAME"],'/').$dir , $row["LOCATION_ID"], $row["NETWORK_SHARE"], $table, $filetypes);
    
    header('Location: '.$rtn);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
