<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/youtube.php'));

  $menu = new menu();
  $info = new infotab();

  /**
   * Displays the synopsis (where available) for a single movie (identified by the movie ID).
   *
   * @param $movie
   * @param $num_menu_items
   */
  function movie_details ($movie, $num_menu_items)
  {
    $synopsis = db_value("select synopsis from movies where file_id=$movie");
    $synlen   = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

    // Synopsis
    if ( !is_null($synopsis) )
    {
      $text = shorten($synopsis,$synlen,1,FONTSIZE_BODY);
      if (strlen($text) != strlen($synopsis))
        $text = $text.' <a href="/video_synopsis.php?media_type='.MEDIA_TYPE_VIDEO.'&file_id='.$movie.'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('MORE')).'</a>';
    }
    else
      $text = str('NO_SYNOPSIS_AVAILABLE');

    echo font_tags(FONTSIZE_BODY).$text.'</font>';
  }

  /**
   * Displays the running time for a movie.
   *
   * @param $runtime
   */
  function running_time($runtime)
  {
    if (!is_null($runtime))
      echo font_tags(FONTSIZE_BODY).str('RUNNING_TIME').': '.hhmmss($runtime).'</font>';
  }

  /**
   * Function that checks to see if the supplied SQL contains a filter for the specified type.
   * If it doesn't, then we can output a menu item which allows the user to filter on that type.
   *
   * @param $filter_list
   * @param $sql_table
   * @param $predicate
   * @param $menu
   */
  function check_filters ($filter_list, $sql_table, $predicate, &$menu )
  {
    foreach ($filter_list as $filter)
    {
      $num_rows  = db_value("select count(distinct $filter) from $sql_table $predicate");
      if ($num_rows>1)
      {
        switch ($filter)
        {
          case 'title'         : $menu->add_item( str('REFINE_TITLE')       ,"video_search.php?sort=title",true); break;
          case 'year'          : $menu->add_item( str('REFINE_YEAR')        ,"video_search.php?sort=year",true);   break;
          case 'certificate'   : $menu->add_item( str('REFINE_CERTIFICATE') ,"video_search.php?sort=certificate",true);  break;
          case 'genre_name'    : $menu->add_item( str('REFINE_GENRE') 	    ,"video_search.php?sort=genre",true);  break;
          case 'actor_name'    : $menu->add_item( str('REFINE_ACTOR')       ,"video_search.php?sort=actor",true);  break;
          case 'director_name' : $menu->add_item( str('REFINE_DIRECTOR')    ,"video_search.php?sort=director",true);  break;
        }
      }
    }
  }

  /**
   * Displays the rating for a movie as a string of stars.
   *
   * @param $rating
   * @return string - HTML
   */
  function star_rating( $rating )
  {
    // Form star rating
    $img_rating = '';
    if ( !is_null($rating) )
    {
      $user_rating = nvl($rating/10,0);
      for ($i = 1; $i<=10; $i++)
      {
        if ( $user_rating >= $i )
          $img_rating .= img_gen(style_img('STAR',true),25,40);
        elseif ( $i-1 >= $user_rating )
          $img_rating .= img_gen(style_img('STAR_0',true),25,40);
        else
          $img_rating .= img_gen(style_img('STAR_'.(number_format($user_rating,1)-floor($user_rating))*10,true),25,40);
      }
    }
    return $img_rating;
  }

  /**
   * Displays audio/video codec logos for a movie based upon it's metadata.
   *
   * @param $metadata
   * @return string - HTML
   */
  function media_logos( $metadata )
  {
    // Media logos
    $media_logos = '';
    if (stristr($metadata["RESOLUTION"], '1920x') )    { $media_logos .= img_gen(style_img('MEDIA_1080',true), 120,40); }
    if (stristr($metadata["RESOLUTION"], '1280x') )    { $media_logos .= img_gen(style_img('MEDIA_720',true), 120,40); }
    if (stristr($metadata["VIDEO_CODEC"], 'h264') )    { $media_logos .= img_gen(style_img('MEDIA_H264',true), 80,40); }
    if (stristr($metadata["VIDEO_CODEC"], 'mpeg-1') )  { $media_logos .= img_gen(style_img('MEDIA_MPEG',true), 80,40); }
    if (stristr($metadata["VIDEO_CODEC"], 'mpeg-2') )  { $media_logos .= img_gen(style_img('MEDIA_MPEG',true), 80,40); }
    if (stristr($metadata["VIDEO_CODEC"], 'divx') )    { $media_logos .= img_gen(style_img('MEDIA_DIVX',true), 80,40); }
    if (stristr($metadata["VIDEO_CODEC"], 'xvid') )    { $media_logos .= img_gen(style_img('MEDIA_XVID',true), 80,40); }
    if (stristr($metadata["VIDEO_CODEC"], 'quick') )   { $media_logos .= img_gen(style_img('MEDIA_MP4V',true), 80,40); }
    if (stristr($metadata["AUDIO_CODEC"], 'dts') )     { $media_logos .= img_gen(style_img('MEDIA_DTS',true), 65,40); }
    if (stristr($metadata["AUDIO_CODEC"], 'mpeg') )    { $media_logos .= img_gen(style_img('MEDIA_MP3',true), 65,40); }
    if (stristr($metadata["AUDIO_CODEC"], 'ac3') )     { $media_logos .= img_gen(style_img('MEDIA_DOLBY',true), 65,40); }
    if (stristr($metadata["AUDIO_CODEC"], 'windows') ) { $media_logos .= img_gen(style_img('MEDIA_WINDOWS',true), 65,40); }
    if ($metadata["AUDIO_CHANNELS"] == 1) { $media_logos .= img_gen(style_img('MEDIA_MONO',true), 55,40); }
    if ($metadata["AUDIO_CHANNELS"] == 2) { $media_logos .= img_gen(style_img('MEDIA_STEREO',true), 55,40); }
    if ($metadata["AUDIO_CHANNELS"] >= 5) { $media_logos .= img_gen(style_img('MEDIA_SURROUND',true), 55,40); }
    return $media_logos;
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.
  $sql_table_all = 'movies media '.
                   'left outer join directors_of_movie dom on media.file_id = dom.movie_id '.
                   'left outer join genres_of_movie gom on media.file_id = gom.movie_id '.
                   'left outer join actors_in_movie aim on media.file_id = aim.movie_id '.
                   'left outer join actors a on aim.actor_id = a.actor_id '.
                   'left outer join directors d on dom.director_id = d.director_id '.
                   'left outer join genres g on gom.genre_id = g.genre_id'.
                    get_rating_join().' where 1=1 ';
  $select_fields = "file_id, dirname, filename, title, year, length";
  $predicate     = search_process_passed_params();
  $sql_table     = "movies media ";
  // Only join tables that are actually required
  if (strpos($predicate,'director_name like') > 0)
    $sql_table  .= 'left outer join directors_of_movie dom on media.file_id = dom.movie_id '.
                   'left outer join directors d on dom.director_id = d.director_id ';
  if (strpos($predicate,'actor_name like') > 0)
    $sql_table  .= 'left outer join actors_in_movie aim on media.file_id = aim.movie_id '.
                   'left outer join actors a on aim.actor_id = a.actor_id ';
  if (strpos($predicate,'genre_name like') > 0)
    $sql_table  .= 'left outer join genres_of_movie gom on media.file_id = gom.movie_id '.
                   'left outer join genres g on gom.genre_id = g.genre_id ';
  $sql_table    .= get_rating_join().' where 1=1 ';
  $file_ids      = db_col_to_list("select distinct media.file_id from $sql_table $predicate");
  $playtime      = db_value("select sum(length) from movies where file_id in (".implode(',',$file_ids).")");
  $num_unique    = db_value("select count(distinct synopsis) from movies where file_id in (".implode(',',$file_ids).")");
  $num_rows      = count($file_ids);
  $this_url      = url_remove_params(current_url(), array('shuffle', 'viewed'));
  $cert_img      = '';
  $name          = un_magic_quote(rawurldecode($_REQUEST["name"]));
  $type          = un_magic_quote($_REQUEST["type"]);

  // Clean the current url in the history
  page_hist_current_update($this_url, $predicate);

  // Set viewed status
  if (isset($_REQUEST["viewed"]))
  {
    foreach ($file_ids as $file_id)
      store_request_details(MEDIA_TYPE_VIDEO, $file_id, ($_REQUEST["viewed"] == 1 ? true : false));
  }

  //
  // A single movie has been matched/selected by the user, so display as much information as possible
  // on the screen, along with commands to "Play Now" or "Add to Playlist".
  //

  if ($num_rows == 1 || $num_unique == 1)
  {
    // Single match, so get the details from the database and display them
    if ( ($data = db_toarray("select media.*, ml.name, ml.network_share, ".get_cert_name_sql()." certificate_name from $sql_table $predicate order by filename")) === false)
      page_error( str('DATABASE_ERROR'));

    // Random fanart image
    $themes = db_toarray('select processed_image, show_banner, show_image from themes where media_type='.MEDIA_TYPE_VIDEO.' and title="'.db_escape_str($data[0]["TITLE"]).'" and use_synopsis=1 and processed_image is not NULL');
    $theme = $themes[mt_rand(0,count($themes)-1)];

    if ( file_exists($theme['PROCESSED_IMAGE']) )
      $background = $theme['PROCESSED_IMAGE'];
    else
      $background = -1;

    // Display page header
    if (!empty($data[0]["YEAR"]))
      page_header( $data[0]["TITLE"].' ('.$data[0]["YEAR"].')', '', '<meta SYABAS-PLAYERMODE="video">', 1, false, '', $background, false, 'PAGE_TEXT_BACKGROUND' );
    else
      page_header( $data[0]["TITLE"],'', '<meta SYABAS-PLAYERMODE="video">', 1, false, '', $background, false, 'PAGE_TEXT_BACKGROUND' );

    // Is DVD image?
    $is_dvd = in_array(file_ext($data[0]["FILENAME"]), media_exts_dvd());

    // Add Play now menu item
    if ( $is_dvd )
    {
      // Can't use gen_playlist as the NMT does something different with zcd=2.
      $nmt_path = make_network_share_path($data[0]["DIRNAME"].$data[0]["FILENAME"], $data[0]["NAME"], $data[0]["NETWORK_SHARE"]);
      $menu->add_item( str('PLAY_NOW') , 'href="'.$nmt_path.'" zcd="2" ' );
      send_to_log(2,'href for dvd: href="'.$nmt_path.'" zcd="2" ');
    }
    else
    {
      // Read bookmark file
      $bookmark_filename = bookmark_file($data[0]["DIRNAME"].$data[0]["FILENAME"]);
      if (!support_resume() && file_exists($bookmark_filename))
        $pc = (int)trim(file_get_contents($bookmark_filename));
      else
        $pc = 0;
      $percent_played = ($pc !== 0 && $pc < 99) ? ' ('.$pc.'%)' : '';

      // Play now
      $menu->add_item( str('PLAY_NOW').$percent_played, play_sql_list(MEDIA_TYPE_VIDEO,"select distinct $select_fields from $sql_table $predicate order by title, filename"));

      // Resume playing
      if ( support_resume() && file_exists( bookmark_file($data[0]["DIRNAME"].$data[0]["FILENAME"])) )
        $menu->add_item( str('RESUME_PLAYING').$percent_played, resume_file(MEDIA_TYPE_VIDEO,$data[0]["FILE_ID"]), true);

      // Add to your current playlist
      if (pl_enabled())
        $menu->add_item( str('ADD_PLAYLIST'), 'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table $predicate order by title, filename"),true);
    }

    // Add Movie trailer menu item
    if (!empty($data[0]["TRAILER"]))
    {
      if (strpos($data[0]["TRAILER"],'youtube.com') > 0)
        $menu->add_item( str('PLAY_TRAILER'), 'href="stream_url.php?'.current_session().'&youtube_id='.get_youtube_video_id($data[0]["TRAILER"]).'&ext=.mp4" vod ');
      elseif (is_remote_file($data[0]["TRAILER"]))
        $menu->add_item( str('PLAY_TRAILER'), 'href="'.url_add_params('stream_url.php', array('user_agent' => rawurlencode('QuickTime/7.6'),
                                                                                              'url' => rawurlencode($data[0]["TRAILER"]),
                                                                                              'ext' => '.'.file_ext($data[0]["TRAILER"]))).'" vod ');
      else
        $menu->add_item( str('PLAY_TRAILER'), "href='".server_address().make_url_path($data[0]["TRAILER"])."' vod" );
    }

    // Add a link to search wikipedia
    if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES' )
      $menu->add_item( str('SEARCH_WIKIPEDIA'), lang_wikipedia_search( ucwords(strip_title($data[0]["TITLE"])), url_add_param($this_url, 'hist', PAGE_HISTORY_DELETE) ), true);

    // Link to full cast & directors
    if ($data[0]["DETAILS_AVAILABLE"] == 'Y')
      $menu->add_item( str('VIDEO_INFO'), 'video_info.php?movie='.$data[0]["FILE_ID"],true);

    // Display thumbnail (DVD Video image will be in parent folder)
    if ( strtoupper($data[0]["FILENAME"]) == 'VIDEO_TS.IFO' )
      $folder_img = file_albumart(rtrim($data[0]["DIRNAME"],'/').".dvd");
    else
      $folder_img = file_albumart($data[0]["DIRNAME"].$data[0]["FILENAME"]);
  }

  //
  // There are multiple movies which match the criteria entered by the user. Therefore, we should
  // display the information that is common to all movies, and provide links to refine the search
  // further.
  //
  else
  {
    // More than one track matches, so output filter details and menu options to add new filters
    page_header( $name, str('MANY_ITEMS',$num_unique) );

    if ( ($data = db_toarray("select file_id, dirname from $sql_table $predicate group by dirname")) === false )
      page_error( str('DATABASE_ERROR') );

    // Browse by actor or director, so check for an image to display
    if ( $type == 'actor_name' || $type == 'director_name' )
    {
      // Random person image
      $images = dir_to_array(SC_LOCATION.'fanart/actors/'.filename_safe(strtolower($name)), '.jpg', 5);
      if (!empty($images))
      {
        $folder_img = $images[mt_rand(0,count($images)-1)];
      }
    }
    // Browse by genre, so check for appropriate image to display
    if ( $type == 'genre_name' )
    {
      $genre_img = SC_LOCATION.'images/genres/'.$name.'.png';
      if ( file_exists($genre_img) )
        $folder_img = $genre_img;
    }
    elseif ( count($data) == 1 )
    {
      $folder_img = file_albumart($data[0]["DIRNAME"]);
    }

    // Display common details
    search_distinct_info($info, str('TITLE'), 'title', $sql_table, $predicate);
    search_distinct_info($info, str('YEAR'), 'year',$sql_table, $predicate);
    search_distinct_info($info, str('CERTIFICATE'), get_cert_name_sql(),$sql_table, $predicate);
    $info->display();

    // Add Play now menu item
    $menu->add_item( str('PLAY_NOW'), play_sql_list(MEDIA_TYPE_VIDEO,"select distinct $select_fields from $sql_table $predicate order by title, filename"));

    if (pl_enabled())
      $menu->add_item( str('ADD_PLAYLIST'), 'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table $predicate order by title, filename"),true);

    // Add Refine by ... menu items
    check_filters( array('title','year','certificate','external_rating_pc','genre_name','actor_name','director_name'), $sql_table_all, $predicate, $menu);
  }

  // Delete media (limited to a small number of files)
  if (is_user_admin() && $num_rows<=8 )
    $menu->add_item( str('DELETE_MEDIA'), 'video_delete.php?del='.implode(',',$file_ids).'&media_type='.MEDIA_TYPE_VIDEO,true);

  // Set thumbnail and certificate image
  if ( !empty($theme) && !$theme['SHOW_IMAGE'] )
  {
    $folder_img = SC_LOCATION.'images/dot.gif';
  }
  else
  {
    // Certificate? Get the appropriate image.
    if (!empty($data[0]["CERTIFICATE"]))
      $cert_img = img_gen(SC_LOCATION.'images/ratings/'.get_rating_scheme_name().'/'.get_cert_name( get_nearest_cert_in_scheme($data[0]["CERTIFICATE"])).'.gif',280,100,false,false,false,array(),false);
  }

  echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
          <tr>';

  // Is there a picture for us to display?
  if ( !empty($folder_img) )
  {
    // Column 1: Image
    echo '    <td valign="middle">
                <table '.style_background('PAGE_TEXT_BACKGROUND').' cellpadding="10" cellspacing="0" border="0">
                  <tr>
                    <td><center>'.img_gen($folder_img,280,550,false,false,false,array(),false).'<br>'.$cert_img.'</center></td>
                  </tr>
                </table>
              </td>';
    // Column 2: Gap
    echo '    <td width="'.convert_x(10).'"></td>';
  }

  // Is a single movie selected?
  if ($num_rows == 1 || $num_unique == 1)
  {
    // Column 3: Details and menu
    echo '    <td valign="top">
                <table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0">
                  <tr>
                    <td>';
                    // Movie synopsis
                    movie_details($data[0]["FILE_ID"],$menu->num_items());
    echo '          <br>';
                    // Running Time
                    running_time($playtime);
    echo '          <center>'.media_logos($data[0]).'&nbsp;'.star_rating($data[0]["EXTERNAL_RATING_PC"]).'</center>
                    </td>
                  </tr>
                </table>';
                $menu->display(1, 480);
    echo '    </td>';
  }
  else
  {
    echo '    <td>';
              $menu->display(1, 480);
    echo '    </td>';
  }

  echo '
            </tr>
          </table>';

  // Count viewed items
  $viewed_count = 0;
  foreach ($file_ids as $file_id)
    $viewed_count += viewings_count( MEDIA_TYPE_VIDEO, $file_id );

  // Buttons for Next and Previous videos
  $order = strtoupper(preg_get('/sort=([a-z]+)/', page_hist_previous('url')));
  $prev = db_row("select file_id,title from $sql_table".page_hist_previous('sql').
                 " and $order < '".db_escape_str($data[0][$order])."'".
                 " and title != '".db_escape_str($data[0]["TITLE"])."'".
                 " order by $order desc limit 1");
  if ( !is_array($prev) ) // Return last video
    $prev = db_row("select file_id,title from $sql_table".page_hist_previous('sql').
                   " order by $order desc limit 1");

  $next = db_row("select file_id, title from $sql_table".page_hist_previous('sql').
                 " and $order > '".db_escape_str($data[0][$order])."'".
                 " and title != '".db_escape_str($data[0]["TITLE"])."'".
                 " order by $order asc limit 1");
  if ( !is_array($next) ) // Return first video
    $next = db_row("select file_id,title from $sql_table".page_hist_previous('sql').
                   " order by $order asc limit 1");

  // Output ABC buttons
  $buttons = array();
  if ( is_array($prev) )
    $buttons[0] = array('text'=>str('PREVIOUS').': '.$prev["TITLE"], 'url'=> url_add_params($this_url, array('type'=>'title', 'name'=>rawurlencode($prev["TITLE"]), 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( is_array($next) )
    $buttons[1] = array('text'=>str('NEXT').': '.$next["TITLE"], 'url'=> url_add_params($this_url, array('type'=>'title', 'name'=>rawurlencode($next["TITLE"]), 'hist'=>PAGE_HISTORY_REPLACE)) );
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[2] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_params($this_url, array('shuffle'=>'on', 'hist'=>PAGE_HISTORY_REPLACE)) );
  else
    $buttons[2] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_params($this_url, array('shuffle'=>'off', 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( is_user_admin() )
  {
    if ( $viewed_count == 0 )
      $buttons[3] = array('text'=>str('VIEWED_SET'), 'url'=> url_set_params($this_url, array('viewed'=>1, 'hist'=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[3] = array('text'=>str('VIEWED_RESET'), 'url'=> url_set_params($this_url, array('viewed'=>0, 'hist'=>PAGE_HISTORY_REPLACE)) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous(), $buttons, 0, true, 'PAGE_TEXT_BACKGROUND' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
