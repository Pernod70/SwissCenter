<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));

  $menu = new menu();

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  $cert_img = '';
  $media_type = $_REQUEST["media_type"];
  switch ($media_type)
  {
    case MEDIA_TYPE_TV    : $media_table = 'tv'    ; break;
    case MEDIA_TYPE_VIDEO :
    default               : $media_table = 'movies'; break;
  }

  if (isset($_REQUEST["del"]))
  {
    // Get the file details from the database
    if ( ($data = db_toarray("select dirname, filename, title, year, certificate from $media_table where file_id in (".$_REQUEST["del"].")")) === false)
      page_error( str('DATABASE_ERROR'));

    if (!empty($data[0]["YEAR"]))
      page_header( $data[0]["TITLE"].' ('.$data[0]["YEAR"].')' ,'');
    else
      page_header( $data[0]["TITLE"] );

    // Delete options
    $menu->add_item( str('YES'), 'video_delete.php?delete='.$_REQUEST["del"].'&media_type='.$_REQUEST["media_type"]);
    $menu->add_item( str('NO'), page_hist_previous());

    // Display thumbnail
    $folder_img = file_albumart($data[0]["DIRNAME"].$data[0]["FILENAME"]);
  }

  elseif (isset($_REQUEST["delete"]))
  {
    // Get the file details from the database
    if ( ($data = db_toarray("select file_id, dirname, filename from $media_table where file_id in (".$_REQUEST["delete"].")")) === false)
      page_error( str('DATABASE_ERROR'));

    foreach ($data as $row)
    {
      // Delete the media file (including image and subtitles)
      foreach( find_in_dir_all_exts( $row["DIRNAME"], file_noext($row["FILENAME"]) ) as $file)
      {
        send_to_log(8, "Deleting file: $file");
        unlink($file);
      }

      // Remove media from database
      db_sqlcommand("delete from $media_table where file_id=".$row['FILE_ID']);
    }
    // Remove associated themes

    switch ($media_type)
    {
      case MEDIA_TYPE_TV    : remove_orphaned_tv_info(); break;
      case MEDIA_TYPE_VIDEO : remove_orphaned_movie_info(); break;
    }

    page_inform(2, page_hist_previous(), '', str('MEDIA_DELETED'));
  }

  // Certificate? Get the appropriate image.
  $scheme = get_rating_scheme_name();
  if (!empty($data[0]["CERTIFICATE"]))
    $cert_img  = img_gen(SC_LOCATION.'images/ratings/'.$scheme.'/'.get_cert_name( get_nearest_cert_in_scheme($data[0]["CERTIFICATE"], $scheme)).'.gif', 280, 100);

  // Is there a picture for us to display?
  if (! empty($folder_img) )
  {
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(280).'" align="left">
              '.img_gen($folder_img,280,550).'<br><center>'.$cert_img.'</center>
              </td><td width="'.convert_x(20).'"></td>
              <td valign="top">';
              echo '<p>'.font_tags(FONTSIZE_BODY).str('CONFIRM_DELETE').'</font>';
              echo '<ul>';
              foreach ($data as $row)
              {
                // Display files to be deleted (including image and subtitles)
                foreach( find_in_dir_all_exts( $row["DIRNAME"], file_noext($row["FILENAME"]) ) as $file)
                  echo '<li>'.font_tags(FONTSIZE_BODY).basename($file).'</font>';
              }
              echo '</ul></p>';
              $menu->display(1, 480);
    echo '    </td></table>';
  }
  else
  {
    $menu->display();
  }

  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
