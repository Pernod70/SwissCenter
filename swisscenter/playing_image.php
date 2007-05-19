<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));
  require_once( realpath(dirname(__FILE__).'/base/stylelib.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/screen.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));

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
  
 /**************************************************************************************************
   Main page output
   *************************************************************************************************/
 
  $image     = new CImage();
  $artfile   = new CImage();  
  $prev_info = array();
  $this_info = array();
  $next_info = array();
  $tracks    = get_tracklist();

  // Work out where in the playlist we are depending on what support is available.
  if (support_now_playing())
    $idx       = $_REQUEST["idx"];
  else 
   $idx = $_SESSION["LAST_RESPONSE_IDX"];
  
  // Get current, prev and next track details (as appropriate)
  if ($idx > 0)
    $prev_info = $tracks[$idx-1];

  $this_info   = $tracks[$idx];

  if ($idx < count($tracks)-1)
    $next_info = $tracks[$idx+1];
    
  send_to_log(8,'Previous track:',$prev_info);
  send_to_log(8,'Current track:',$this_info);
  send_to_log(8,'Next track:',$next_info);
  
  // Load the image and scale it to the appropriate size.  
  $image->load_from_file(style_img('NOW_BACKGROUND',true) );
  $image->resize( convert_x(1000,SCREEN_COORDS), convert_y(1000,SCREEN_COORDS), 0, false);

  #----------
  # Album Art
  #---------- 

  $art_fsp    = file_albumart($this_info["DIRNAME"].$this_info["FILENAME"]);
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
  wrap($image, nvl($this_info["TITLE"],file_noext($this_info["FILENAME"])), $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);

  if (!empty($this_info["ARTIST"]))
  {
    $image->text(str('ARTIST'), $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $this_info["ARTIST"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($this_info["ALBUM"]))
  {
    $image->text(str('ALBUM'),  $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $this_info["ALBUM"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
  }
  if (!empty($this_info["YEAR"]))
  {
    $image->text(str('YEAR'),  $text_x, $text_y, $label_text_col, $label_text_size);
    wrap($image, $this_info["YEAR"], $text_x + $indent, $text_y+=($detail_text_size*2.5), $text_width, $detail_text_col, $detail_text_size);
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
  if ( count($prev_info) >0)
  {  
    $x = $image->get_text_width(str('MUSIC_PLAY_PREV').': ',$detail_text_size);
    $y = $time_text_y;
    $prevsong = nvl($prev_info["TITLE"],file_noext($prev_info["FILENAME"])).(!empty($prev_info["ARTIST"]) ? ' - '.$prev_info["ARTIST"] : '');
  	wrap($image, str('MUSIC_PLAY_PREV').': ', $time_text_x, $time_text_y, $time_text_width, $title_text_col,  $detail_text_size);  	
  	wrap($image, $prevsong, $time_text_x+$x, $y, $time_text_width-$x, $detail_text_col,  $detail_text_size);
  }

  // Next track details
  if ( count($next_info) >0)
  {  
    $x = $image->get_text_width(str('MUSIC_PLAY_NEXT').': ',$detail_text_size);
    $y = $time_text_y;
  	$nextsong = nvl($next_info["TITLE"],file_noext($next_info["FILENAME"])).(!empty($next_info["ARTIST"]) ? ' - '.$next_info["ARTIST"] : '');  	
  	wrap($image, str('MUSIC_PLAY_NEXT').': ', $time_text_x, $time_text_y, $time_text_width, $title_text_col,  $detail_text_size);  	
  	wrap($image, $nextsong, $time_text_x+$x, $y, $time_text_width-$x, $detail_text_col,  $detail_text_size);
  }   
  
  // Time for this track
  if ($this_info["LENGTH"]>0)
  {
    $image->text(str('TRACK_LENGTH'), $time_text_x, convert_y(900,SCREEN_COORDS), $title_text_col, $detail_text_size);    
    $image->text(hhmmss($this_info["LENGTH"]), $time_text_x, convert_y(940,SCREEN_COORDS), $detail_text_col, $detail_text_size);        
  }
  
  // Total so far
  $pos=($idx+1).' / '.count($tracks);
  $total_label  = convert_x(925,SCREEN_COORDS) - $image->get_text_width(str('TRACKS'),$detail_text_size);
  $total_detail = convert_x(925,SCREEN_COORDS) - $image->get_text_width($pos,$detail_text_size);

  $image->text(str('TRACKS'), $total_label, convert_y(900,SCREEN_COORDS), $title_text_col, $detail_text_size);    
  $image->text( $pos , $total_detail, convert_y(940,SCREEN_COORDS), $detail_text_col, $detail_text_size);    
 
  // Output picture
  $image->output('jpeg');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
