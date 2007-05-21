<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/page.php'));
  require_once( realpath(dirname(__FILE__).'/image.php'));
  require_once( realpath(dirname(__FILE__).'/stylelib.php'));
  require_once( realpath(dirname(__FILE__).'/users.php'));
  require_once( realpath(dirname(__FILE__).'/screen.php'));
  require_once( realpath(dirname(__FILE__).'/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/file.php'));

  //------------------------------------------------------------------------------------------------
  // Output multiple lines of text
  //------------------------------------------------------------------------------------------------

  function wrap (&$image, $text, $x, &$y, $width, $font_colour, $font_size)
  {
    // $image->line($x,$y,$x+$width,$y,$font_colour);
    $line_spacing = $font_size * 1.4;
    
    while (strlen($text)>0)
    {
      $output = shorten($text, $width, 1, $font_size, false);
      $image->text($output, $x, $y, $font_colour, $font_size);
      $text = substr($text,strlen($output));
      $y += $line_spacing;
    }  
      
    $y += $font_size;
  }
  
  function now_playing_image( $current_track, $previous_track = '', $next_track = '' )
  {
    $image     = new CImage();  
    $artfile   = new CImage();  
  
    send_to_log(8,'Previous track:',$previous_track);
    send_to_log(8,'Current track:',$current_track);
    send_to_log(8,'Next track:',$next_track);
    
    // Load the image and scale it to the appropriate size.  
    $image->load_from_file(style_img('NOW_BACKGROUND',true) );
    $image->resize( convert_x(1000,SCREEN_COORDS), convert_y(1000,SCREEN_COORDS), 0, false);
  
    #----------
    # Album Art
    #---------- 
  
    $art_fsp    = file_albumart($current_track["DIRNAME"].$current_track["FILENAME"]);
    $art_x      = convert_x(70,SCREEN_COORDS);
    $art_y      = convert_y(200,SCREEN_COORDS);
    $art_w      = convert_x(280,SCREEN_COORDS);
    $art_h      = convert_y(400,SCREEN_COORDS);
    $border_col = hexdec(style_value('NOW_ART_BORDER','#FFFFFF'));
  
    if ($art_fsp == '')
      $artfile->load_from_file( style_img('NOW_NO_ALBUMART',true) );
    elseif ( file_ext($art_fsp) == 'sql' )
      $artfile->load_from_database( substr($art_fsp,0,-4) );
    else
      $artfile->load_from_file( $art_fsp );
      
    // Resize album art and then overlay onto the background image.  
    $artfile->resize( $art_w, $art_h, $art_bg_colour, true, '', $border_col );
    $image->copy($artfile, $art_x, $art_y);
  
    #-----------
    # Page Title
    #-----------
  
    $title_text_size  = font_size( 36, SCREEN_COORDS);
    $title_text_col   = hexdec(style_value('NOW_TITLE_COLOUR','#000000'));
    $title_x          = convert_x(75,SCREEN_COORDS);
    $title_y          = convert_y(120,SCREEN_COORDS);
    $line_y           = convert_y(150,SCREEN_COORDS);
    
    $image->text(str('NOW_PLAYING'),$title_x, $title_y, $title_text_col, $title_text_size);
    $image->rectangle($title_x, $line_y, convert_x(850,SCREEN_COORDS),convert_y(2,SCREEN_COORDS),$title_text_col);
    
    # -----------------
    # Track Information
    # -----------------
    
    $label_text_size  = font_size( 25, SCREEN_COORDS);
    $detail_text_size = font_size( 20, SCREEN_COORDS);  
    $label_text_col   = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
    $detail_text_col  = hexdec(style_value('NOW_DETAIL_COLOUR','#000000'));
  
    $text_x           = convert_x(400,SCREEN_COORDS);
    $text_y           = convert_y(250,SCREEN_COORDS);
    $text_width       = convert_x(450,SCREEN_COORDS);
    $indent           = convert_x(30,SCREEN_COORDS); 
  
    $image->text(str('TRACK_NAME'),  $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, nvl($current_track["TITLE"],file_noext($current_track["FILENAME"])), $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  
    if (!empty($current_track["ARTIST"]))
    {
      $image->text(str('ARTIST'), $text_x, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["ARTIST"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
    }
    if (!empty($current_track["ALBUM"]))
    {
      $image->text(str('ALBUM'),  $text_x, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["ALBUM"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
    }
    if (!empty($current_track["YEAR"]))
    {
      $image->text(str('YEAR'),  $text_x, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["YEAR"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
    }
    
    # ------------------------
    # Playing time Information
    # ------------------------
    
    $time_text_width		 = convert_x(840,SCREEN_COORDS);
    $time_text_x         = convert_x(80,SCREEN_COORDS);
    $time_text_y         = max($text_y,convert_y(680,SCREEN_COORDS));
    $image->rectangle($title_x, $time_text_y , convert_x(850,SCREEN_COORDS), convert_y(2,SCREEN_COORDS), $title_text_col);
    $time_text_y        += convert_y(60,SCREEN_COORDS);
   
    // Previous track details
    if ( count($previous_track) >0)
    {  
      $x = $image->get_text_width(str('MUSIC_PLAY_PREV').': ',$detail_text_size);
      $y = $time_text_y;
      $prevsong = nvl($previous_track["TITLE"],file_noext($previous_track["FILENAME"])).(!empty($previous_track["ARTIST"]) ? ' - '.$previous_track["ARTIST"] : '');
    	wrap($image, str('MUSIC_PLAY_PREV').': ', $time_text_x, $time_text_y, $time_text_width, $title_text_col,  $detail_text_size);  	
    	wrap($image, $prevsong, $time_text_x+$x, $y, $time_text_width-$x, $detail_text_col,  $detail_text_size);
    }
  
    // Next track details
    if ( count($next_track) >0)
    {  
      $x = $image->get_text_width(str('MUSIC_PLAY_NEXT').': ',$detail_text_size);
      $y = $time_text_y;
    	$nextsong = nvl($next_track["TITLE"],file_noext($next_track["FILENAME"])).(!empty($next_track["ARTIST"]) ? ' - '.$next_track["ARTIST"] : '');  	
    	wrap($image, str('MUSIC_PLAY_NEXT').': ', $time_text_x, $time_text_y, $time_text_width, $title_text_col,  $detail_text_size);  	
    	wrap($image, $nextsong, $time_text_x+$x, $y, $time_text_width-$x, $detail_text_col,  $detail_text_size);
    }   
    
    // Time for this track
    if ($current_track["LENGTH"]>0)
    {
      $image->text(str('TRACK_LENGTH'), $time_text_x, convert_y(900,SCREEN_COORDS), $title_text_col, $detail_text_size);    
      $image->text(hhmmss($current_track["LENGTH"]), $time_text_x, convert_y(940,SCREEN_COORDS), $detail_text_col, $detail_text_size);        
    }
    
    // Total so far
    $pos=($idx+1).' / '.count($tracks);
    $total_label  = convert_x(925,SCREEN_COORDS) - $image->get_text_width(str('TRACKS'),$detail_text_size);
    $total_detail = convert_x(925,SCREEN_COORDS) - $image->get_text_width($pos,$detail_text_size);
  
    $image->text(str('TRACKS'), $total_label, convert_y(900,SCREEN_COORDS), $title_text_col, $detail_text_size);    
    $image->text( $pos , $total_detail, convert_y(940,SCREEN_COORDS), $detail_text_col, $detail_text_size);    
   
    // return finished image
    return $image;
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
