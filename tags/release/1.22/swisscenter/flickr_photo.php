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

    // Update page history
    $back_url = flickr_page_params();
    
    if ( count($exif["exif"]) == 0 )
    {
      page_inform(2,$back_url,str('FLICKR_PHOTOS'),str('EXIF_NONE'));
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
      page_header(str('FLICKR_PHOTOS'), $photo["owner"]["username"].' : '.$photo["title"]);

      $info->display();

      // Make sure the "back" button goes to the correct page:
      page_footer($back_url);
    }

  }
  
/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  if ( isset($_REQUEST["full"]) )
  {
    $background_image = '/thumb.php?type=jpg&src='.rawurlencode($_REQUEST["full"]).'&x='.convert_x(1000,SCREEN_COORDS).'&y='.convert_y(1000,SCREEN_COORDS);
    header('Content-type: text/html; '.charset());
    echo '<html>
          <head>'.$meta.'
          <meta SYABAS-FULLSCREEN>
          <meta SYABAS-PHOTOTITLE=0>
          <meta SYABAS-BACKGROUND="'.$background_image.'">
          <meta syabas-keyoption="caps"><meta myibox-pip="0,0,0,0,0"><meta http-equiv="content-type" content="text/html;charset=Windows-1252">
          <meta name="generator" content="lyra-box UI">
          <meta http-equiv="Content-Type" content="text/html; '.charset().'">
          <title>'.$title.'</title>
          <style>
            body {font-family: arial; font-size: 14px; background-repeat: no-repeat; color: '.style_value("PAGE_TEXT_COLOUR",'#FFFFFF').';}
            td { color: '.style_value("PAGE_TEXT_COLOUR",'#FFFFFF').';}
            a {color:'.style_value("PAGE_LINKS_COLOUR",'#FFFFFF').'; text-decoration: none;}
          </style>
          </head>
          <body  onLoadSet="1"
                 background="'.  $background_image .'"
                 FOCUSCOLOR="'.  style_value("PAGE_FOCUS_COLOUR",'#FFFFFF').'"
                 FOCUSTEXT="'.   style_value("PAGE_FOCUS_TEXT",'#FFFFFF').'"
                 text="'.        style_value("PAGE_TEXT_COLOUR",'#FFFFFF').'"
                 vlink="'.       style_value("PAGE_LINKS_COLOUR",'#FFFFFF').'"
                 bgcolor="'.     style_value("PAGE_BACKGROUND_COLOUR",'#FFFFFF').'"
                 TOPMARGIN="0" LEFTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">';
    echo '<table width="'.convert_x(1000).'" border="0" cellpadding="0" cellspacing="0">
          <tr><td width="'.convert_x(900).'" valign="top" align="left">';

    // Update page history
    $back_url = flickr_page_params();

    // Make sure the "back" button goes to the correct page:
    page_footer($back_url);
  }
  elseif ( isset($_REQUEST["exif"]) )
  {
    display_flickr_photoexif( $_REQUEST["photo_id"] );
  }
  else
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    // Update page history
    $back_url = flickr_page_params();
    $this_url = url_remove_param(current_url(), 'del');
  
    $photo_id = $_REQUEST["photo_id"];
  
    // Get information about a photo. The calling user must have permission to view the photo.
    $photo = $flickr->photos_getInfo($photo_id);
  
    // Page headings
    page_header(str('FLICKR_PHOTOS'), utf8_decode($photo["owner"]["username"]).' : '.utf8_decode($photo["title"]));
  
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(400).'" align="left">
          <a name="photo" href="'.url_add_param($this_url, 'full', flickr_get_photo_size($photo)).'">'
          .img_gen(flickr_get_photo_size($photo),400,650).'</a>
          </td><td width="'.convert_x(20).'"></td>
          <td valign="center">';
    echo  font_tags(32).utf8_decode($photo["description"]);            
    echo '</td></table>';
    
    // Output ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('EXIF_VIEW'),'url' => url_add_param($this_url, 'exif', 'Y'));
      
    // Make sure the "back" button goes to the correct page:
    page_footer($back_url, $buttons);  
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
