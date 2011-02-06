<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/image.php'));
  require_once( realpath(dirname(__FILE__).'/stylelib.php'));
  require_once( realpath(dirname(__FILE__).'/users.php'));
  require_once( realpath(dirname(__FILE__).'/screen.php'));
  require_once( realpath(dirname(__FILE__).'/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/file.php'));
  require_once( realpath(dirname(__FILE__).'/fanart.php'));

  //------------------------------------------------------------------------------------------------
  // Output multiple lines of text
  //------------------------------------------------------------------------------------------------

  function wrap (&$image, $text, $x, &$y, $width, $font_colour, $font_size)
  {
    // $image->line($x,$y,$x+$width,$y,$font_colour);
    $line_spacing = $font_size * 1.4;

    while (strlen($text)>0)
    {
      $output = shorten($text, $width, 1, $font_size.'px', false);
      $image->text($output, $x, $y, $font_colour, $font_size);
      $text = substr($text,strlen($output));
      $y += $line_spacing;
    }

    $y += $font_size;
  }

  //------------------------------------------------------------------------------------------------
  // Adds a bar of images at the specified co-ordinates.
  //------------------------------------------------------------------------------------------------

  function image_rand_pics( &$dest_img, $x, $y, $width, $height, $border_col, $images)
  {
    $chosen_photos = array();
    $total_width = convert_x($width,BROWSER_SCREEN_COORDS);

    $count = 0;
    while ($count < count($images))
    {
      $artist_photo = new CImage();
      $count++;

      $pic_num = mt_rand(0,count($images)-1);
      $pic_fsp = download_and_cache_image($images[$pic_num]);
      if ($pic_fsp !== false)
      {
        array_splice($images,$pic_num,1);
        if ( @$artist_photo->load_from_file( $pic_fsp) !== false)
        {
          @$artist_photo->resize_to_height( convert_y($height,BROWSER_SCREEN_COORDS), '', $border_col );
          if ($total_width - $artist_photo->get_width() - convert_x(10,BROWSER_SCREEN_COORDS) > 0)
          {
            $chosen_photos[] = $artist_photo;
            $total_width = $total_width - $artist_photo->get_width() - convert_x(10,BROWSER_SCREEN_COORDS);
          }
          else
            break;
        }
      }
    }

    if (count($chosen_photos)>0)
    {
      send_to_log(8,"Displaying ".count($chosen_photos)." of ".(count($images)+count($chosen_photos))." images in a row");
      $spacing = floor(convert_x(10,BROWSER_SCREEN_COORDS) + $total_width/(count($chosen_photos)));
      $start_x = convert_x($x,BROWSER_SCREEN_COORDS) + floor($spacing/2);
      foreach ($chosen_photos as $artist_photo)
      {
        @$dest_img->copy($artist_photo, $start_x, convert_y($y,BROWSER_SCREEN_COORDS));
        $start_x = $start_x + $artist_photo->get_width() + $spacing;
      }
    }
  }

  //------------------------------------------------------------------------------------------------
  // Outputs a "Now playing this station" screen for internet radio.
  //------------------------------------------------------------------------------------------------

  function station_playing_image( $station_name, $now_playing, $logo='' )
  {
    if ( is_file(SC_LOCATION.'images/iradio/'.$logo) )
    {
      $image     = new CImage();
      $artfile   = new CImage();

      // Load the image and scale it to the appropriate size.
      $image->load_from_file(style_img('NOW_BACKGROUND',true) );
      $image->resize( convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS), 0, false);

      #-------------
      # Station Logo
      #-------------

      $art_x      = convert_x(70,BROWSER_SCREEN_COORDS);
      $art_y      = convert_y(200,BROWSER_SCREEN_COORDS);
      $art_w      = convert_x(280,BROWSER_SCREEN_COORDS);
      $art_h      = convert_y(400,BROWSER_SCREEN_COORDS);
      $border_col = hexdec(style_value('NOW_ART_BORDER','#FFFFFF'));

      $artfile->load_from_file(SC_LOCATION.'images/iradio/'.$logo);

      // Resize logo and then overlay onto the background image.
      $artfile->resize( $art_w, $art_h, $art_bg_colour, true, '', $border_col );
      $image->copy($artfile, $art_x, $art_y);

      #-----------
      # Page Title
      #-----------

      $title_text_size  = font_size( 36, BROWSER_SCREEN_COORDS);
      $title_text_col   = hexdec(style_value('NOW_TITLE_COLOUR','#000000'));
      $title_x          = convert_x(75,BROWSER_SCREEN_COORDS);
      $title_y          = convert_y(120,BROWSER_SCREEN_COORDS);
      $line_y           = convert_y(150,BROWSER_SCREEN_COORDS);

      $image->text(str('NOW_PLAYING'),$title_x, $title_y, $title_text_col, $title_text_size);
      $image->rectangle($title_x, $line_y, convert_x(850,BROWSER_SCREEN_COORDS),convert_y(2,BROWSER_SCREEN_COORDS),$title_text_col);

      # -----------------
      # Track Information
      # -----------------

      $label_text_size  = font_size( 25, BROWSER_SCREEN_COORDS);
      $detail_text_size = font_size( 22, BROWSER_SCREEN_COORDS);
      $label_text_col   = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
      $detail_text_col  = hexdec(style_value('NOW_DETAIL_COLOUR','#000000'));

      $text_x           = convert_x(400,BROWSER_SCREEN_COORDS);
      $text_y           = convert_y(225,BROWSER_SCREEN_COORDS);
      $text_width       = convert_x(255,BROWSER_SCREEN_COORDS);
      $indent           = convert_x(30,BROWSER_SCREEN_COORDS);

      // Extract year from now playing details
      preg_match('/\((\d\d\d\d)\)/', $now_playing, $year);
      $now_playing = preg_replace('/(\(\d\d\d\d\))/', '', $now_playing);

      // Separate different tracks
      $track = explode('<>', $now_playing);

      if (preg_match('/ - (.*)/', $track[0], $details))
      {
        $image->text(str('TRACK_NAME'),  $text_x, $text_y, $label_text_col, $label_text_size);
        $text_y+=($detail_text_size*2.5);
        wrap($image, $details[1], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
      }
      if (preg_match('/(.*) - /', $track[0], $details))
      {
        $image->text(str('ARTIST'), $text_x, $text_y, $label_text_col, $label_text_size);
        $text_y+=($detail_text_size*2.5);
        wrap($image, $details[1], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
      }
      if (!empty($details[1]))
      {
      if (!empty($year[1]))
      {
        $image->text(str('YEAR'),  $text_x, $text_y, $label_text_col, $label_text_size);
        $text_y+=($detail_text_size*2.5);
        wrap($image, $year[1], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
      }
      }
      # ------------------------
      # Previous two tracks
      # ------------------------

      $label_text_col      = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
      $detail_text_col     = hexdec(style_value('NOW_DETAIL_COLOUR','#000000'));
      $time_text_width	   = convert_x(860,BROWSER_SCREEN_COORDS);
      $time_text_x         = convert_x(80,BROWSER_SCREEN_COORDS);
      $time_text_y         = max($text_y,convert_y(640,BROWSER_SCREEN_COORDS));
      $image->rectangle($time_text_x, $time_text_y , convert_x(850,BROWSER_SCREEN_COORDS), convert_y(2,BROWSER_SCREEN_COORDS), $title_text_col);
      $time_text_y         = convert_y(80,BROWSER_SCREEN_COORDS);

      // Previous track details

      if($track[1] != $track[0])
      {
        $x = $image->get_text_width(str('MUSIC_PLAY_PREV').': ',$detail_text_size);
        $y = $time_text_y;
        $image->text (str('MUSIC_PLAY_PREV').': ', $time_text_x, convert_y(700 + $adjust_y,BROWSER_SCREEN_COORDS), $title_text_col,  $detail_text_size);
        $image->text ($track[1], $time_text_x+$x, convert_y(700 + $adjust_y,BROWSER_SCREEN_COORDS), $detail_text_col,  $detail_text_size);
      }

      // Details track before previous

      if($track[2] != $track[1])
      {
        $x = $image->get_text_width(str('MUSIC_BEFORE_PREV').': ',$detail_text_size);
        $y = $time_text_y;
        $image->text (str('MUSIC_BEFORE_PREV').': ', $time_text_x, convert_y(750 + $adjust_y,BROWSER_SCREEN_COORDS), $title_text_col,  $detail_text_size);
        $image->text ($track[2], $time_text_x+$x, convert_y(750 + $adjust_y,BROWSER_SCREEN_COORDS), $detail_text_col,  $detail_text_size);
       }
      # ------------------------
      # Channel Name
      # ------------------------

      $label_text_size  = font_size( 25, BROWSER_SCREEN_COORDS);
      $detail_text_size = font_size( 22, BROWSER_SCREEN_COORDS);
      $label_text_col   = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
      $detail_text_col  = hexdec(style_value('NOW_DETAIL_COLOUR','#FFFFFF'));

      $image->text(str('CHANNEL'), $time_text_x, convert_y(905 + $adjust_y,BROWSER_SCREEN_COORDS), $title_text_col, $detail_text_size);
      $image->text( $station_name, $time_text_x, convert_y(945 + $adjust_y,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);

    }
    else
    {
      $title_text_size  = font_size( 24, BROWSER_SCREEN_COORDS);
      $title_text_col   = hexdec(style_value('RADIO_TITLE_COLOUR','#000000'));
      $title_x          = convert_x(75,BROWSER_SCREEN_COORDS);
      $title_y          = convert_y(120,BROWSER_SCREEN_COORDS);

      $track = explode('<>', $now_playing);

      $image = new CImage();
      $image->load_from_file( style_img('RADIO_BACKGROUND',true));
      $image->resize( convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS), 0, false);
      wrap( $image,$station_name, $title_x, $title_y, convert_x(500,BROWSER_SCREEN_COORDS), $title_text_col, $title_text_size);
      $title_y+=($title_text_size*2.5);
      wrap( $image,$track[0], $title_x, $title_y, convert_x(500,BROWSER_SCREEN_COORDS), $title_text_col, $title_text_size);
    }
    return $image;
  }

  //------------------------------------------------------------------------------------------------
  // Outputs a "Now Playing" image
  //------------------------------------------------------------------------------------------------

  function now_playing_image( $current_track, $previous_track = '', $next_track = '', $tracks = '', $photos = '' )
  {
    $image     = new CImage();
    $artfile   = new CImage();

    send_to_log(8,'Previous track:',$previous_track);
    send_to_log(8,'Current track:',$current_track);
    send_to_log(8,'Next track:',$next_track);

    // Load the image and scale it to the appropriate size.
    $image->load_from_file(style_img('NOW_BACKGROUND',true) );
    $image->resize( convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS), 0, false);

    #----------
    # Album Art
    #----------

    if ( isset($current_track["ALBUMART"]) && !empty($current_track["ALBUMART"]))
      if ( is_remote_file($current_track["ALBUMART"]) )
        $art_fsp = download_and_cache_image($current_track["ALBUMART"]);
      else
        $art_fsp = $current_track["ALBUMART"];
    else
      $art_fsp = file_albumart($current_track["DIRNAME"].$current_track["FILENAME"]);

    $art_x      = convert_x(70,BROWSER_SCREEN_COORDS);
    $art_y      = convert_y(180,BROWSER_SCREEN_COORDS);
    $art_w      = convert_x(280,BROWSER_SCREEN_COORDS);
    $art_h      = convert_y(400,BROWSER_SCREEN_COORDS);
    $border_col = hexdec(style_value('NOW_ART_BORDER','#FFFFFF'));
    $art_bg_colour = 0;

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

    $title_text_size  = font_size( 36, BROWSER_SCREEN_COORDS);
    $title_text_col   = hexdec(style_value('NOW_TITLE_COLOUR','#000000'));
    $title_x          = convert_x(75,BROWSER_SCREEN_COORDS);
    $title_y          = convert_y(120,BROWSER_SCREEN_COORDS);
    $line_y           = convert_y(150,BROWSER_SCREEN_COORDS);

    $image->text(str('NOW_PLAYING'),$title_x, $title_y, $title_text_col, $title_text_size);
    $image->rectangle($title_x, $line_y, convert_x(850,BROWSER_SCREEN_COORDS),convert_y(2,BROWSER_SCREEN_COORDS),$title_text_col);

    # -----------------
    # Track Information
    # -----------------

    $label_text_size  = font_size( 22, BROWSER_SCREEN_COORDS);
    $detail_text_size = font_size( 22, BROWSER_SCREEN_COORDS);
    $label_text_col   = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
    $detail_text_col  = hexdec(style_value('NOW_DETAIL_COLOUR','#000000'));

    $text_x           = convert_x(400,BROWSER_SCREEN_COORDS);
    $text_y           = convert_y(250,BROWSER_SCREEN_COORDS);
    $text_width       = convert_x(450,BROWSER_SCREEN_COORDS);
    $indent           = convert_x(30,BROWSER_SCREEN_COORDS);

    $image->text(str('TRACK_NAME'),  $text_x, $text_y, $label_text_col, $label_text_size);
    $text_y+=($detail_text_size*2.5);
    wrap($image, nvl($current_track["TITLE"],file_noext($current_track["FILENAME"])), $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);

    if (!empty($current_track["ARTIST"]))
    {
      $image->text(str('ARTIST'), $text_x, $text_y, $label_text_col, $label_text_size);
      $text_y+=($detail_text_size*2.5);
      wrap($image, $current_track["ARTIST"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (!empty($current_track["ALBUM"]))
    {
      $image->text(str('ALBUM'),  $text_x, $text_y, $label_text_col, $label_text_size);
      $text_y+=($detail_text_size*2.5);
      wrap($image, $current_track["ALBUM"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (!empty($current_track["YEAR"]))
    {
      $image->text(str('YEAR'),  $text_x, $text_y, $label_text_col, $label_text_size);
      $text_y+=($detail_text_size*2.5);
      wrap($image, $current_track["YEAR"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }

    # ------------------------
    # Prev/Next track
    # ------------------------

    $time_text_width     = convert_x(840,BROWSER_SCREEN_COORDS);
    $time_text_x         = convert_x(80,BROWSER_SCREEN_COORDS);
    $time_text_y         = max($text_y,convert_y(680,BROWSER_SCREEN_COORDS));
    $image->rectangle($title_x, $time_text_y , convert_x(850,BROWSER_SCREEN_COORDS), convert_y(2,BROWSER_SCREEN_COORDS), $title_text_col);
    $time_text_y        += convert_y(60,BROWSER_SCREEN_COORDS);

    // Previous track details
    if ( is_array($previous_track))
    {
      $x = $image->get_text_width(str('MUSIC_PLAY_PREV').': ',$detail_text_size);
      $y = $time_text_y;
      $prevsong = nvl($previous_track["TITLE"],file_noext($previous_track["FILENAME"])).(!empty($previous_track["ARTIST"]) ? ' - '.$previous_track["ARTIST"] : '');
      wrap($image, str('MUSIC_PLAY_PREV').': ', $time_text_x, $time_text_y, $time_text_width, $title_text_col,  $detail_text_size);
      wrap($image, $prevsong, $time_text_x+$x, $y, $time_text_width-$x, $detail_text_col,  $detail_text_size);
    }

    // Next track details
    if ( is_array($next_track))
    {
      $x = $image->get_text_width(str('MUSIC_PLAY_NEXT').': ',$detail_text_size);
      $y = $time_text_y;
      $nextsong = nvl($next_track["TITLE"],file_noext($next_track["FILENAME"])).(!empty($next_track["ARTIST"]) ? ' - '.$next_track["ARTIST"] : '');
      wrap($image, str('MUSIC_PLAY_NEXT').': ', $time_text_x, $time_text_y, $time_text_width, $title_text_col,  $detail_text_size);
      wrap($image, $nextsong, $time_text_x+$x, $y, $time_text_width-$x, $detail_text_col,  $detail_text_size);
    }

    # ------------------------
    # Photos
    # ------------------------

    if ( !is_array($previous_track) && !is_array($next_track) && is_array($photos))
      image_rand_pics($image, 75, convert_tolog_y($time_text_y,BROWSER_SCREEN_COORDS)-20, 850, 120, $border_col, $photos);

    # ------------------------
    # Playing time Information
    # ------------------------

    // Adjust position of details according to player
    if (get_player_make() == 'NGR')
      $adjust_y = -65;
    else
      $adjust_y = 0;

    // Time for this track
    if ($current_track["LENGTH"]>0)
    {
      $image->text(str('TRACK_LENGTH'), $time_text_x, convert_y(900 + $adjust_y,BROWSER_SCREEN_COORDS), $title_text_col, $detail_text_size);
      $image->text(hhmmss($current_track["LENGTH"]), $time_text_x, convert_y(940 + $adjust_y,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);
    }

    // Total so far
    if ($tracks != '')
    {
      $total_label  = convert_x(925,BROWSER_SCREEN_COORDS) - $image->get_text_width(str('TRACKS'),$detail_text_size);
      $total_detail = convert_x(925,BROWSER_SCREEN_COORDS) - $image->get_text_width($tracks,$detail_text_size);

      $image->text(str('TRACKS'), $total_label, convert_y(900 + $adjust_y,BROWSER_SCREEN_COORDS), $title_text_col, $detail_text_size);
      $image->text( $tracks , $total_detail, convert_y(940 + $adjust_y,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);
    }

    // return finished image
    return $image;
  }

  function now_playing_image_fanart( $current_track, $previous_track = '', $next_track = '', $tracks = '', $progress = false )
  {
    $image     = new CImage();
    $frame     = new CImage();
    $artfile   = new CImage();

    send_to_log(8,'Previous track:',$previous_track);
    send_to_log(8,'Current track:',$current_track);
    send_to_log(8,'Next track:',$next_track);

    // Background image
    switch ( internet_available() ? get_sys_pref('NOW_PLAYING_FANART','LASTFM') : false )
    {
      case 'GOOGLE':
        $fanart_img = get_google_artist_image( $current_track["ARTIST"] );
        break;
      case 'LASTFM':
        $fanart_img = get_lastfm_artist_image( $current_track["ARTIST"] );
        break;
      default:
        $fanart_img = false;
    }

    // Random fanart image for current artist
    if ( !$fanart_img )
    {
      $fanart_imgs = dir_to_array(SC_LOCATION.'fanart/artists/'.strtolower($current_track["ARTIST"]).'/', '.*', 5);
      $fanart_img  = count($fanart_imgs)==0 ? style_img('RADIO_BACKGROUND',true) : $fanart_imgs[mt_rand(0,count($fanart_imgs)-1)];
    }

    // Load the image and scale it to the appropriate size.
    $fanart_img_cache = cache_filename($fanart_img, convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS));
    if ( !file_exists($fanart_img_cache) )
      precache( $fanart_img, convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS) );
    $image->load_from_file($fanart_img_cache);

    # ---------------
    # Details overlay
    # ---------------

    $frame->load_from_file( SC_LOCATION.'images/overlay_medium.png' );
    $frame->resize( convert_x(850,BROWSER_SCREEN_COORDS), convert_y(150,BROWSER_SCREEN_COORDS), 0, false );
    $image->copy($frame, convert_x(100,BROWSER_SCREEN_COORDS), convert_y(750,BROWSER_SCREEN_COORDS));

    #----------
    # Album Art
    #----------

    if ( isset($current_track["ALBUMART"]) && !empty($current_track["ALBUMART"]))
      if ( is_remote_file($current_track["ALBUMART"]) )
        $art_fsp = download_and_cache_image($current_track["ALBUMART"]);
      else
        $art_fsp = $current_track["ALBUMART"];
    else
      $art_fsp   = file_albumart($current_track["DIRNAME"].$current_track["FILENAME"]);

    $art_x      = convert_x(50,BROWSER_SCREEN_COORDS);
    $art_y      = convert_y(725,BROWSER_SCREEN_COORDS);
    $art_w      = convert_x(200,BROWSER_SCREEN_COORDS);
    $art_h      = convert_y(200,BROWSER_SCREEN_COORDS);
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

    # -----------------
    # Track Information
    # -----------------

    $detail_text_size = font_size( 18, BROWSER_SCREEN_COORDS);
    $title_text_col   = hexdec(style_value('NOW_TITLE_COLOUR','#000000'));
    $label_text_col   = hexdec(style_value('NOW_LABEL_COLOUR','#000000'));
    $detail_text_col  = hexdec(style_value('NOW_DETAIL_COLOUR','#000000'));

    $text_x           = convert_x(250,BROWSER_SCREEN_COORDS);
    $text_y           = convert_y(775,BROWSER_SCREEN_COORDS);
    $text_width       = convert_x(650,BROWSER_SCREEN_COORDS);

    $title  = empty($current_track["TITLE"]) ? file_noext($current_track["FILENAME"]) : $current_track["TITLE"];
    $artist = empty($current_track["ARTIST"]) ? '' : ' - '.$current_track["ARTIST"];
    $year   = empty($current_track["YEAR"]) ? '' : ' ('.$current_track["YEAR"].')';
    $album  = empty($current_track["ALBUM"]) ? '' : $current_track["ALBUM"];

    $image->text($title.$artist.$year, $text_x, $text_y, $detail_text_col, $detail_text_size);

    $text_y+=($detail_text_size*1.5);
    $image->text($album, $text_x, $text_y, $detail_text_col, $detail_text_size);

    # ------------------------
    # Progress bar
    # ------------------------

    $text_y+=($detail_text_size*0.3);
    $image->rectangle(convert_x(250,BROWSER_SCREEN_COORDS), $text_y, $progress * convert_x(550,BROWSER_SCREEN_COORDS), $detail_text_size*1.2, $title_text_col, true);
    $image->rectangle(convert_x(250,BROWSER_SCREEN_COORDS), $text_y, convert_x(550,BROWSER_SCREEN_COORDS), $detail_text_size*1.2, $label_text_col, false);
    $text_y+=($detail_text_size*1.2);

    # ------------------------
    # Playing time Information
    # ------------------------

    // Time for this track
    if ($current_track["LENGTH"]>0)
    {
      $image->text(hhmmss($current_track["LENGTH"]), convert_x(810,BROWSER_SCREEN_COORDS), $text_y, $detail_text_col, $detail_text_size);
    }

    # ------------------------
    # Prev/Next track
    # ------------------------

    // Previous track details
    $text_y+=($detail_text_size*1.5);
    if ( is_array($previous_track))
    {
      $x = $image->get_text_width('|<< : ',$detail_text_size);
      $prevsong = nvl($previous_track["TITLE"],file_noext($previous_track["FILENAME"])).(!empty($previous_track["ARTIST"]) ? ' - '.$previous_track["ARTIST"] : '');
      $image->text('|<< : ', $text_x, $text_y, $detail_text_col, $detail_text_size);
      $image->text($prevsong, $text_x+$x, $text_y, $detail_text_col, $detail_text_size);
    }

    // Next track details
    $text_y+=($detail_text_size*1.5);
    if ( is_array($next_track))
    {
      $x = $image->get_text_width('>>| : ',$detail_text_size);
      $nextsong = nvl($next_track["TITLE"],file_noext($next_track["FILENAME"])).(!empty($next_track["ARTIST"]) ? ' - '.$next_track["ARTIST"] : '');
      $image->text('>>| : ', $text_x, $text_y, $detail_text_col, $detail_text_size);
      $image->text($nextsong, $text_x+$x, $text_y, $detail_text_col, $detail_text_size);
    }

    // Total so far
    if ($tracks != '')
    {
      $total_detail = convert_x(940,BROWSER_SCREEN_COORDS) - $image->get_text_width($tracks,$detail_text_size);
      $image->text( $tracks, $total_detail, convert_y(895,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);
    }

    // return finished image
    return $image;
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
