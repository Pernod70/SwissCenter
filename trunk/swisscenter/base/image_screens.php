<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/image.php'));
  require_once( realpath(dirname(__FILE__).'/stylelib.php'));
  require_once( realpath(dirname(__FILE__).'/screen.php'));
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
      $pic_url = is_array($images[$pic_num]) ? $images[$pic_num]["remote"] : $images[$pic_num];
      $pic_fsp = download_and_cache_image($pic_url);
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
  // Outputs a "Now Playing" image
  //------------------------------------------------------------------------------------------------

  function now_playing_image( $current_track, $previous_track = '', $next_track = '', $tracks = '', $photos = '' )
  {
    $image     = new CImage();
    $artfile   = new CImage();

    send_to_log(8,'Previous track:',$previous_track);
    send_to_log(8,'Current track:',$current_track);
    send_to_log(8,'Next track:',$next_track);

    #------------------------------
    # Radio Station with no Details
    #------------------------------

    if (isset($current_track["STATION"]) && !isset($current_track["ALBUMART"]) && empty($current_track["TITLE"]))
    {
      $title_text_size  = font_size( 24, BROWSER_SCREEN_COORDS);
      $title_text_col   = hexdec(style_value('RADIO_TITLE_COLOUR','#000000'));
      $title_x          = convert_x(75,BROWSER_SCREEN_COORDS);
      $title_y          = convert_y(120,BROWSER_SCREEN_COORDS);

      $image->load_from_file( style_img('RADIO_BACKGROUND',true));
      $image->resize( convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS), 0, false);
      wrap( $image,$current_track["STATION"], $title_x, $title_y, convert_x(500,BROWSER_SCREEN_COORDS), $title_text_col, $title_text_size);

      return $image;
    }

    // Load the image and scale it to the appropriate size.
    $image->load_from_file(style_img('NOW_BACKGROUND',true) );
    $image->resize( convert_x(1000,BROWSER_SCREEN_COORDS), convert_y(1000,BROWSER_SCREEN_COORDS), 0, false);

    #----------
    # Album Art
    #----------

    if ( isset($current_track["ALBUMART"]) && !empty($current_track["ALBUMART"]))
    {
      if ( is_remote_file($current_track["ALBUMART"]) )
        $art_fsp = download_and_cache_image($current_track["ALBUMART"]);
      else
        $art_fsp = $current_track["ALBUMART"];
    }
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
    $artfile->resize( $art_w, $art_h, $art_bg_colour, true, '' );
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
    $text_width       = convert_x(350,BROWSER_SCREEN_COORDS);
    $indent           = $image->get_text_width(str('COMPOSER').': ',$detail_text_size); // Longest word

    if (isset($current_track["TITLE"]) && !empty($current_track["TITLE"]))
    {
      $width = $image->get_text_width(str('TITLE').': ',$detail_text_size);
      $image->text(str('TITLE').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, nvl($current_track["TITLE"],file_noext($current_track["FILENAME"])), $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["ARTIST"]) && !empty($current_track["ARTIST"]))
    {
      $width = $image->get_text_width(str('ARTIST').': ',$detail_text_size);
      $image->text(str('ARTIST').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["ARTIST"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["BAND"]) && !empty($current_track["BAND"]) && $current_track["BAND"] !== $current_track["ARTIST"])
    {
      $width = $image->get_text_width(str('BAND').': ',$detail_text_size);
      $image->text(str('BAND').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["BAND"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["COMPOSER"]) && !empty($current_track["COMPOSER"]))
    {
      $width = $image->get_text_width(str('COMPOSER').': ',$detail_text_size);
      $image->text(str('COMPOSER').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["COMPOSER"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["ALBUM"]) && !empty($current_track["ALBUM"]))
    {
      $width = $image->get_text_width(str('ALBUM').': ',$detail_text_size);
      $image->text(str('ALBUM').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["ALBUM"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["GENRE"]) && !empty($current_track["GENRE"]))
    {
      $width = $image->get_text_width(str('GENRE').': ',$detail_text_size);
      $image->text(str('GENRE').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["GENRE"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["YEAR"]) && !empty($current_track["YEAR"]))
    {
      $width = $image->get_text_width(str('YEAR').': ',$detail_text_size);
      $image->text(str('YEAR').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["YEAR"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["PUBLISHER"]) && !empty($current_track["PUBLISHER"]))
    {
      $width = $image->get_text_width(str('PUBLISHER').': ',$detail_text_size);
      $image->text(str('PUBLISHER').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, $current_track["PUBLISHER"], $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }
    if (isset($current_track["STATION"]) && isset($current_track["LENGTH"]) && $current_track["LENGTH"] > 0)
    {
      $width = $image->get_text_width(str('TRACK_LENGTH').': ',$detail_text_size);
      $image->text(str('TRACK_LENGTH').':', $text_x + $indent - $width, $text_y, $label_text_col, $label_text_size);
      wrap($image, hhmmss($current_track["LENGTH"]), $text_x + $indent, $text_y, $text_width, $detail_text_col, $detail_text_size);
    }

    $text_x = convert_x(80,BROWSER_SCREEN_COORDS);

    if (isset($current_track["STATION"]) && !empty($current_track["STATION"]))
    {
      # -------------------------
      # Radio Station Information
      # -------------------------

      $width = $image->get_text_width(str('CHANNEL').': ',$detail_text_size);
      $image->text(str('CHANNEL').':', $text_x, convert_y(620,BROWSER_SCREEN_COORDS), $label_text_col, $label_text_size);
      $image->text($current_track["STATION"], $text_x, convert_y(660,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);
    }
    else
    {
      # ------------------------
      # Playing time Information
      # ------------------------

      // Time for this track
      if (isset($current_track["LENGTH"]) && $current_track["LENGTH"] > 0)
      {
        $image->text(str('TRACK_LENGTH'), $text_x, convert_y(620,BROWSER_SCREEN_COORDS), $title_text_col, $detail_text_size);
        $image->text(hhmmss($current_track["LENGTH"]), $text_x, convert_y(660,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);
      }

      // Total so far
      if ($tracks != '')
      {
        $total_label_x  = convert_x(925,BROWSER_SCREEN_COORDS) - $image->get_text_width(str('TRACKS'),$detail_text_size);
        $total_detail_x = convert_x(925,BROWSER_SCREEN_COORDS) - $image->get_text_width($tracks,$detail_text_size);

        $image->text(str('TRACKS'), $total_label_x, convert_y(620,BROWSER_SCREEN_COORDS), $title_text_col, $detail_text_size);
        $image->text( $tracks , $total_detail_x, convert_y(660,BROWSER_SCREEN_COORDS), $detail_text_col, $detail_text_size);
      }
    }

    $text_width		  = convert_x(750,BROWSER_SCREEN_COORDS);
    $text_y         = convert_y(680,BROWSER_SCREEN_COORDS);
    $image->rectangle($text_x, $text_y , convert_x(850,BROWSER_SCREEN_COORDS), convert_y(2,BROWSER_SCREEN_COORDS), $title_text_col);
    $text_y        += convert_y(40,BROWSER_SCREEN_COORDS);

    # ------------------------
    # Prev/Next track
    # ------------------------

    $indent = $image->get_text_width(str('MUSIC_PLAY_PREV').': ',$detail_text_size);
    $indent = max($indent, $image->get_text_width(str('MUSIC_PLAY_NEXT').': ',$detail_text_size));

    // Just played track details
    if ( is_array($previous_track) && count($previous_track) > 0)
    {
      $width = $image->get_text_width(str('MUSIC_PLAY_PREV').': ',$detail_text_size);
      $prevsong = nvl($previous_track[0]["TITLE"],file_noext($previous_track[0]["FILENAME"])).(!empty($previous_track[0]["ARTIST"]) ? ' - '.$previous_track[0]["ARTIST"] : '');
      $image->text(str('MUSIC_PLAY_PREV').':', $text_x + $indent - $width, $text_y, $title_text_col, $detail_text_size);
      wrap($image, $prevsong, $text_x + $indent, $text_y, $text_width - $width, $detail_text_col, $detail_text_size);
    }

    // Before that track details
    if ( is_array($previous_track) && count($previous_track) > 1)
    {
      $width = $image->get_text_width(str('MUSIC_BEFORE_PREV').': ',$detail_text_size);
      $prevsong = nvl($previous_track[1]["TITLE"],file_noext($previous_track[1]["FILENAME"])).(!empty($previous_track[1]["ARTIST"]) ? ' - '.$previous_track[1]["ARTIST"] : '');
      $image->text(str('MUSIC_BEFORE_PREV').':', $text_x + $indent - $width, $text_y, $title_text_col, $detail_text_size);
      wrap($image, $prevsong, $text_x + $indent, $text_y, $text_width - $width, $detail_text_col, $detail_text_size);
    }

    // Next track details
    if ( is_array($next_track) && count($next_track) > 0)
    {
      $width = $image->get_text_width(str('MUSIC_PLAY_NEXT').': ',$detail_text_size);
      $nextsong = nvl($next_track[0]["TITLE"],file_noext($next_track[0]["FILENAME"])).(!empty($next_track[0]["ARTIST"]) ? ' - '.$next_track[0]["ARTIST"] : '');
      $image->text(str('MUSIC_PLAY_NEXT').':', $text_x + $indent - $width, $text_y, $title_text_col, $detail_text_size);
      wrap($image, $nextsong, $text_x + $indent, $text_y, $text_width - $width, $detail_text_col, $detail_text_size);
    }

    # ------------------------
    # Photos
    # ------------------------

    if ( is_array($photos) )
      image_rand_pics($image, 75, convert_tolog_y($text_y,BROWSER_SCREEN_COORDS)-20, 850, 120, $border_col, $photos);

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
    switch ( internet_available() ? get_sys_pref('NOW_PLAYING_FANART','DISCOGS') : false )
    {
      case 'GOOGLE':
        $fanart_img = get_google_artist_image( $current_track["ARTIST"] );
        break;
      case 'DISCOGS':
      case 'LASTFM':
        $fanart_img = get_discogs_artist_image( $current_track["ARTIST_SORT"] );
        break;
      default:
        $fanart_img = false;
    }

    // Random fanart image for current artist
    if ( !$fanart_img )
    {
      $fanart_imgs = dir_to_array(SC_LOCATION.'fanart/artists/'.filename_safe(strtolower($current_track["ARTIST"])).'/', '.*', 5);
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
    {
      if ( is_remote_file($current_track["ALBUMART"]) )
        $art_fsp = download_and_cache_image($current_track["ALBUMART"]);
      else
        $art_fsp = $current_track["ALBUMART"];
    }
    else
      $art_fsp   = file_albumart($current_track["DIRNAME"].$current_track["FILENAME"]);

    $art_x      = convert_x(50,BROWSER_SCREEN_COORDS);
    $art_y      = convert_y(725,BROWSER_SCREEN_COORDS);
    $art_w      = convert_x(200,BROWSER_SCREEN_COORDS);
    $art_h      = convert_y(200,BROWSER_SCREEN_COORDS);
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

    if ( $progress !== false )
    {
      $text_y+=($detail_text_size*0.3);
      $image->rectangle(convert_x(250,BROWSER_SCREEN_COORDS), $text_y, $progress * convert_x(550,BROWSER_SCREEN_COORDS), $detail_text_size*1.2, $title_text_col, true);
      $image->rectangle(convert_x(250,BROWSER_SCREEN_COORDS), $text_y, convert_x(550,BROWSER_SCREEN_COORDS), $detail_text_size*1.2, $label_text_col, false);
      $text_y+=($detail_text_size*1.2);
    }

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
    if ( is_array($previous_track) && isset($previous_track[0]) )
    {
      $x = $image->get_text_width('|<< : ',$detail_text_size);
      $prevsong = nvl($previous_track[0]["TITLE"],file_noext($previous_track[0]["FILENAME"])).(!empty($previous_track[0]["ARTIST"]) ? ' - '.$previous_track[0]["ARTIST"] : '');
      $image->text('|<< : ', $text_x, $text_y, $detail_text_col, $detail_text_size);
      $image->text($prevsong, $text_x+$x, $text_y, $detail_text_col, $detail_text_size);
    }

    // Next track details
    $text_y+=($detail_text_size*1.5);
    if ( is_array($next_track) && isset($next_track[0]) )
    {
      $x = $image->get_text_width('>>| : ',$detail_text_size);
      $nextsong = nvl($next_track[0]["TITLE"],file_noext($next_track[0]["FILENAME"])).(!empty($next_track[0]["ARTIST"]) ? ' - '.$next_track[0]["ARTIST"] : '');
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
    $image->last_modified = time();
    return $image;
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
