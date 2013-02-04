<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));
  require_once( realpath(dirname(__FILE__).'/base/stylelib.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/ext/exif/exif_reader.php'));

  //------------------------------------------------------------------------------------------------
  // Output multiple lines of text
  //------------------------------------------------------------------------------------------------

  function wrap (&$image, $text, $x, &$y, $width, $font_colour, $font_size)
  {
    $fudge_factor = 15.2/$font_size;
    $y+=($font_size);

    while (mb_strlen($text)>0)
    {
      $output = shorten($text, $width, 1, $fudge_factor, false);
      $image->text($output, $x, $y, $font_colour, $font_size);
      $text = mb_substr($text,mb_strlen($output));
      $y += ($font_size*1.5);
    }
    $y+=($font_size);
  }

  # ------------------------------------------------------------------------------------------------
  # Variable declarations and Style parameters which control the positioning, colour and size of all
  # elements of the "Now Playing" image
  # ------------------------------------------------------------------------------------------------

  $image     = new CImage();
  $pic       = new CImage();
  $info      = array_pop(db_toarray("select * from photos where file_id=".$_REQUEST["photo_id"]));

  $title     = file_noext($info["FILENAME"]);
  $pic_width = 350;
  $text_x    = $pic_width * 1.1;
  $text_y    = 80;

  $title_text_col   = hexdec(style_value('NOW_TITLE_TEXT_COL','#000000'));
  $title_text_size  = style_value('NOW_TITLE_TEXT_SIZE','18');
  $detail_text_col  = hexdec(style_value('NOW_DETAIL_TEXT_COL','#000000'));
  $detail_text_size = style_value('NOW_DETAIL_TEXT_SIZE','14');

  $text_width       = 400;
  $detail_text_size = 12;

  # ------------------------------------------------------------------------------------------------
  # Build the "Now Playing" image
  # ------------------------------------------------------------------------------------------------

  $image->load_from_file(style_img(strtoupper(get_screen_type()).'_BACKGROUND',true) );

  // Get actual photo, resize it and position it on the background & add the title
  $pic->load_from_file( file_albumart($info["DIRNAME"].$info["FILENAME"]) );
  $pic->resize( ($pic_width * 0.9), 420, false, true );
  $image->copy($pic,15 + ($pic_width - $pic->get_width())/2 ,60);
  wrap( $image, $title, 25, ($y=20), $image->get_width() , $title_text_col, $title_text_size);

  // ISO
  if (!empty($info["EXIF_ISO"]))
  {
    $image->text(str('EXIF_ISO'),  $text_x, $text_y, $title_text_col, $detail_text_size);
    wrap($image, $info["EXIF_ISO"], $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

  // Exposure
  $exposure = sprintf('%s - F%s - %s', $pic['EXIF_EXPOSURE_TIME'], $pic['EXIF_FNUMBER'], $pic['EXIF_FOCAL_LENGTH']);
  if ($exposure != ' - F - ')
  {
    $image->text(str('EXIF_EXPOSURE'),  $text_x, $text_y, $title_text_col, $detail_text_size);
    wrap($image, $exposure, $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

  // Make & Model
  if (!empty($info["EXIF_MAKE"]) || !empty($info["EXIF_MODEL"]))
  {
    if (strpos( strtolower($info['EXIF_MODEL']),strtolower($info['EXIF_MAKE'])) !== false)
      $make_model = $info["EXIF_MODEL"];
    else
      $make_model = $info["EXIF_MAKE"].' '.$info["EXIF_MODEL"];

    $image->text(str('EXIF_MODEL'),  $text_x, $text_y, $title_text_col, $detail_text_size);
    wrap($image, $make_model, $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

  // White Balance
  if (!empty($info["EXIF_WHITE_BALANCE"]))
  {
    $image->text(str('EXIF_WHITE_BALANCE'),  $text_x, $text_y, $title_text_col, $detail_text_size);
    wrap($image, exif_val('WhiteBalance',$info["EXIF_WHITE_BALANCE"]), $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

  // Light Source
  if (!empty($info["EXIF_LIGHT_SOURCE"]))
  {
    $image->text(str('EXIF_LIGHT_SOURCE'),  $text_x, $text_y, $title_text_col, $detail_text_size);
    wrap($image, exif_val('LightSource',$info["EXIF_LIGHT_SOURCE"]), $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

    // Exposure Program
  if (!empty($info["EXIF_EXPOSURE_PROG"]))
  {
    $image->text(str('EXIF_EXPOSE_PROG'),  $text_x, $text_y, $title_text_col, $detail_text_size);
    wrap($image, exif_val('ExpProg',$info["EXIF_EXPOSURE_PROG"]), $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

    /*
    $flash = explode(',',exif_val('Flash',$pic['EXIF_FLASH']));
    $info->add_item(str('EXIF_METER_MODE')     ,exif_val('MeterMode',$pic['EXIF_METER_MODE']));
    $info->add_item(str('EXIF_SCENCE_CAPTURE') ,exif_val('SceneCaptureType',$pic['EXIF_CAPTURE_TYPE']));
    $info->add_item(str('EXIF_FLASH')          ,$flash[0]);
*/
  // Output picture
  $image->output('jpeg');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
