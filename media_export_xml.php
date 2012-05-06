<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/xml_sidecar.php'));

  //===========================================================================================
  // Main script logic
  //===========================================================================================

  $export_xml = get_sys_pref('EXPORT_XML');
  delete_sys_pref('EXPORT_XML');

  switch ($export_xml)
  {
    case 'VIDEO':
      if ( get_sys_pref('movie_xml_save','NO') == 'YES' )
      {
        send_to_log(4,'Exporting all video information to XML');

        // Only export videos information where the details_available column is 'Y'.
        $data = db_toarray("select file_id from movies where details_available = 'Y' order by dirname,title ");

        // Process each video file
        foreach ($data as $row)
          export_video_to_xml( $row["FILE_ID"] );

        send_to_log(4,'Export of all video information to XML complete');
      }
      break;

    case 'TV':
      if ( get_sys_pref('tv_xml_save','NO') == 'YES' )
      {
        send_to_log(4,'Exporting all tv information to XML');

        // Only export tv information where the details_available column is 'Y'.
        $data = db_toarray("select file_id from tv where details_available = 'Y' order by dirname,programme ");

        // Process each tv file
        foreach ($data as $row)
          export_tv_to_xml( $row["FILE_ID"] );

        send_to_log(4,'Export of all tv information to XML complete');
      }
      break;
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
