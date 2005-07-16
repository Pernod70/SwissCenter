<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/image.php");

  //------------------------------------------------------------------------------------------------
  // Output multiple lines of text
  //------------------------------------------------------------------------------------------------

  function wrap (&$image, $text, $x, &$y, $width, $font_colour, $font_size)
  {
    $fudge_factor = 15.2/$font_size;
    $y+=($font_size);
    
    while (strlen($text)>0)
    {
      $output = shorten($text, $width, $fudge_factor, 1, false);
      $image->text($output, $x, $y, $font_colour);
      $text = substr($text,strlen($output));
      $y += ($font_size*1.5);
    }    
    $y+=($font_size);
  }
  
  # ------------------------------------------------------------------------------------------------
  # Variable declarations and Style parameters which control the positioning, colour and size of all
  # elements of the "Now Playing" image
  # ------------------------------------------------------------------------------------------------

  $image     = new CImage();
  $artfile   = new CImage();
  $info      = db_toarray("select * from mp3s where file_id=".$_REQUEST["music_id"]);
 
  $text_width                    = style_value('NOW_TEXT_WIDTH','360');
  $title_text_col                = hexdec(style_value('NOW_TITLE_TEXT_COL','#000000'));
  $title_text_size               = style_value('NOW_TITLE_TEXT_SIZE','18');
  $detail_text_col               = hexdec(style_value('NOW_DETAIL_TEXT_COL','#000000'));
  $detail_text_size              = style_value('NOW_DETAIL_TEXT_SIZE','14');
  list($text_x, $text_y)         = explode(',',style_value('NOW_TEXT_XY','242,105'));
  list($art_left,$art_top)       = explode(',',style_value('NOW_ART_TOPLEFT_XY','23,105'));
  list($art_right,$art_bottom)   = explode(',',style_value('NOW_ART_BOTTOMRIGHT_XY','221,303'));  
  $art_bg_colour                 = style_value('NOW_ART_BACKGROUND_COL','#000000');

  # ------------------------------------------------------------------------------------------------
  # Build the "Now Playing" image
  # ------------------------------------------------------------------------------------------------

  $image->load_from_file(style_img(strtoupper(get_screen_type()).'_PLAYING',true) );
  
  // Album Art
  $art_fsp   = file_albumart($info[0]["DIRNAME"].$info[0]["FILENAME"]);

  if ($art_fsp == '')
    $artfile->load_from_file( style_img('IMG_ART_MISSING',true) );
  elseif ( file_ext($art_fsp) == 'sql' )
    $artfile->load_from_database( substr($art_fsp,0,-4) );
  else
    $artfile->load_from_file( $art_fsp );

  $artfile->resize( ($art_right-$art_left),($art_bottom-$art_top), $art_bg_colour );
  $image->copy($artfile,$art_left,$art_top);
  
  //Track Information
  if (!empty($info[0]["TITLE"]))
  {
    $image->text('Track',  $text_x, $text_y, $title_text_col, $title_text_size);
    wrap($image, $info[0]["TITLE"], $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($info[0]["ARTIST"]))
  {
    $image->text('Artist', $text_x, $text_y, $title_text_col, $title_text_size);
    wrap($image, $info[0]["ARTIST"], $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($info[0]["ALBUM"]))
  {
    $image->text('Album',  $text_x, $text_y, $title_text_col, $title_text_size);
    wrap($image, $info[0]["ALBUM"], $text_x+20, $text_y+=($detail_text_size), $text_width, $detail_text_col, $detail_text_size);
  }

  // Output picture
  $image->output('jpeg');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
