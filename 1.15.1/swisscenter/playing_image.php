<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));
  require_once( realpath(dirname(__FILE__).'/base/stylelib.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/screen.php'));

  //------------------------------------------------------------------------------------------------
  // Output multiple lines of text
  //------------------------------------------------------------------------------------------------

  function wrap (&$image, $text, $x, &$y, $width, $font_colour, $font_size)
  {
    // $image->line($x,$y,$x+$width,$y,$font_colour);
    $line_spacing = $font_size * 1.7;
    
    while (strlen($text)>0)
    {
      $output = shorten($text, $width, 1, $font_size, false);
      $image->text($output, $x, $y, $font_colour);
      $text = substr($text,strlen($output));
      $y += $line_spacing;
    }  
      
    $y += $font_size;
  }
  
 /**************************************************************************************************
   Main page output
   *************************************************************************************************/
 
  $image            = new CImage();
  $artfile          = new CImage();
  $info             = db_toarray("select * from mp3s where file_id=".get_user_pref('LAST_PLAYED_ID'));

  // Load the image and scale it to the appropriate size.  
  $image->load_from_file(style_img('NOW_BACKGROUND',true) );
  $image->resize( convert_x(1000,SCREEN_COORDS), convert_y(1000,SCREEN_COORDS), 0, false);
  
  #----------
  # Album Art
  #----------

  $art_fsp   = file_albumart($info[0]["DIRNAME"].$info[0]["FILENAME"]);
  $art_x     = convert_x(70,SCREEN_COORDS);
  $art_y     = convert_y(200,SCREEN_COORDS);
  $art_w     = convert_x(250,SCREEN_COORDS);
  $art_h     = convert_y(400,SCREEN_COORDS);

  if ($art_fsp == '')
    $artfile->load_from_file( style_img('NOW_NO_ALBUMART',true) );
  elseif ( file_ext($art_fsp) == 'sql' )
    $artfile->load_from_database( substr($art_fsp,0,-4) );
  else
    $artfile->load_from_file( $art_fsp );
    
  // Resize album art and then overlay onto the background image.  
  $artfile->resize( $art_w, $art_h, $art_bg_colour );
  $image->copy($artfile, $art_x, $art_y);

  #-----------
  # Page Title
  #-----------

  $title_text_size  = font_size( 40, SCREEN_COORDS);
  $title_text_col   = hexdec(style_value('NOW_TITLE_COLOUR','#000000'));
  $title_x          = convert_x(70,SCREEN_COORDS);
  $title_y          = convert_y(120,SCREEN_COORDS);
  
  $image->text(str('NOW_PLAYING'),$title_x, $title_y, $title_text_col, $title_text_size);
  
  # -----------------
  # Track Information
  # -----------------
  
  $label_text_size  = font_size( 20, SCREEN_COORDS);
  $detail_text_size = font_size( 14, SCREEN_COORDS);  
  $label_text_col   = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
  $detail_text_col  = hexdec(style_value('NOW_DETAIL_COLOUR','#000000'));

  $text_x           = convert_x(400,SCREEN_COORDS);
  $text_y           = convert_y(250,SCREEN_COORDS);
  $text_width       = convert_x(450,SCREEN_COORDS);
  $indent           = convert_x(30,SCREEN_COORDS);
  
  if (!empty($info[0]["TITLE"]))
  {
    $image->text(str('TRACK_NAME'),  $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $info[0]["TITLE"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($info[0]["ARTIST"]))
  {
    $image->text(str('ARTIST'), $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $info[0]["ARTIST"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($info[0]["ALBUM"]))
  {
    $image->text(str('ALBUM'),  $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $info[0]["ALBUM"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($info[0]["YEAR"]))
  {
    $image->text(str('YEAR'),  $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $info[0]["YEAR"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  }

  // Output picture
  $image->output('jpeg');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
