<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  function display_flickr_photoexif( $photo_id )
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    // Retrieves a list of EXIF/TIFF/GPS tags for a given photo. The calling user must have permission to view the photo.
    $exif = $flickr->photos_getExif($photo_id);
    $exif_tags = array('Make', 'Model', 'Date and Time', 'Exposure', 'Aperture', 'Focal Length', 'ISO Speed', 'Metering Mode', 'Flash');

    if ( count($exif["exif"]) == 0 )
    {
      page_inform(2,page_hist_previous(),str('FLICKR_PHOTOS'),str('EXIF_NONE'));
    }
    else
    {
      foreach ($exif["exif"] as $tag)
        if ( in_array($tag["label"], $exif_tags) )
          $exif_tag[$tag["label"]] = (isset($tag["clean"]) ? $tag["clean"] : $tag["raw"]);

      $info = new infotab();

      // Stop the make from appearing twice (such as "Canon Canon EOS 10D").
      if (!empty($exif_tag['Make']) && strpos( strtolower($exif_tag['Model']),strtolower($exif_tag['Make'])) !== false)
        $info->add_item(str('EXIF_MODEL'), $exif_tag['Model']);
      else
        $info->add_item(str('EXIF_MODEL'), $exif_tag['Make'].' '.$exif_tag['Model']);

      // Exposure details
      $info->add_item(str('EXIF_EXPOSURE'), sprintf('%s - f%s - %s', $exif_tag['Exposure']
                                                                   , $exif_tag['Aperture']
                                                                   , $exif_tag['Focal Length']));
      $info->add_item(str('EXIF_ISO'), $exif_tag['ISO Speed']);
  //  $info->add_item(str('EXIF_WHITE_BALANCE')  ,exif_val('WhiteBalance',$pic['EXIF_WHITE_BALANCE']));
  //  $info->add_item(str('EXIF_LIGHT_SOURCE')   ,exif_val('LightSource',$pic['EXIF_LIGHT_SOURCE']));
  //  $info->add_item(str('EXIF_EXPOSE_PROG')    ,exif_val('ExpProg',$pic['EXIF_EXPOSURE_PROG']));
      $info->add_item(str('EXIF_METER_MODE'), $exif_tag['Metering Mode']);
  //    $info->add_item(str('EXIF_SCENCE_CAPTURE') ,exif_val('SceneCaptureType',$pic['EXIF_CAPTURE_TYPE']));
      $info->add_item(str('EXIF_FLASH'), $exif_tag['Flash']);

      // Get information about a photo. The calling user must have permission to view the photo.
      $photo = $flickr->photos_getInfo($photo_id);

      // Page headings
      page_header(str('FLICKR_PHOTOS'), $photo["photo"]["owner"]["username"].' : '.$photo["photo"]["title"]);

      $info->display();

      // Make sure the "back" button goes to the correct page:
      page_footer(page_hist_previous());
    }

  }

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  if ( isset($_REQUEST["full"]) )
  {
    page_header( $_REQUEST["title"], '', '', 1, true, '', $_REQUEST["full"] );

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous());
  }
  elseif ( isset($_REQUEST["exif"]) )
  {
    display_flickr_photoexif( $_REQUEST["photo_id"] );
  }
  else
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    $curent_url = current_url();

    $photo_id = $_REQUEST["photo_id"];

    // Get information about a photo. The calling user must have permission to view the photo.
    $photo = $flickr->photos_getInfo($photo_id);

    // Page headings
    page_header(str('FLICKR_PHOTOS'), utf8_decode($photo["photo"]["owner"]["username"]).' : '.utf8_decode($photo["photo"]["title"]));

    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(400).'" align="left">
          <a name="photo" href="'.url_add_params($curent_url, array('full' => flickr_get_photo_size($photo_id),
                                                                    'title' => $photo["photo"]["title"])).'">'
          .img_gen(flickr_get_photo_size($photo_id),400,650).'</a>
          </td><td width="'.convert_x(20).'"></td>
          <td valign="center">';
    echo  font_tags(FONTSIZE_BODY).utf8_decode($photo["photo"]["description"]);
    echo '</td></table>';

    // Output ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('EXIF_VIEW'),'url' => url_add_param($curent_url, 'exif', 'Y'));

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
