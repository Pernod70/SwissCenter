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
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));
  require_once( realpath(dirname(__FILE__).'/base/xml_sidecar.php'));
  require_once( realpath(dirname(__FILE__).'/video_obtain_info.php'));

  $menu = new menu();

  /**
   * Displays the synopsis for a single tv episode (identified by the file_id).
   *
   * @param int $tv file_id
   * @param real $width fraction of screen width to use
   */
  function tv_details ($tv, $num_menu_items, $width)
  {
    $synopsis = db_value("select synopsis from tv where file_id=$tv");
    $synlen   = $_SESSION["device"]["browser_x_res"] * (9-$num_menu_items) * $width;

    // Synopsis
    if ( !is_null($synopsis) )
    {
      $text = shorten($synopsis,$synlen,1,FONTSIZE_BODY);
      if (strlen($text) != strlen($synopsis))
        $text = $text.' <a href="/video_synopsis.php?media_type='.MEDIA_TYPE_TV.'&file_id='.$tv.'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('MORE')).'</a>';
    }
    else
      $text = str('NO_SYNOPSIS_AVAILABLE');

    echo font_tags(FONTSIZE_BODY).$text.'</font>';
  }

  function running_time ($runtime)
  {
    if (!is_null($runtime))
      echo font_tags(FONTSIZE_BODY).str('RUNNING_TIME').': '.hhmmss($runtime).'</font>';
  }

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

  function media_logos( $audio_codec, $video_codec, $resolution, $channels )
  {
    // Media logos
    $media_logos = '';
    if (stristr($resolution, '1920x') )    { $media_logos .= img_gen(style_img('MEDIA_1080',true), 120,40); }
    if (stristr($resolution, '1280x') )    { $media_logos .= img_gen(style_img('MEDIA_720',true), 120,40); }
    if (stristr($video_codec, 'h264') )    { $media_logos .= img_gen(style_img('MEDIA_H264',true), 80,40); }
    if (stristr($video_codec, 'mpeg-1') )  { $media_logos .= img_gen(style_img('MEDIA_MPEG',true), 80,40); }
    if (stristr($video_codec, 'mpeg-2') )  { $media_logos .= img_gen(style_img('MEDIA_MPEG',true), 80,40); }
    if (stristr($video_codec, 'divx') )    { $media_logos .= img_gen(style_img('MEDIA_DIVX',true), 80,40); }
    if (stristr($video_codec, 'xvid') )    { $media_logos .= img_gen(style_img('MEDIA_XVID',true), 80,40); }
    if (stristr($video_codec, 'quick') )   { $media_logos .= img_gen(style_img('MEDIA_MP4V',true), 80,40); }
    if (stristr($audio_codec, 'dts') )     { $media_logos .= img_gen(style_img('MEDIA_DTS',true), 65,40); }
    if (stristr($audio_codec, 'mpeg') )    { $media_logos .= img_gen(style_img('MEDIA_MP3',true), 65,40); }
    if (stristr($audio_codec, 'ac3') )     { $media_logos .= img_gen(style_img('MEDIA_DOLBY',true), 65,40); }
    if (stristr($audio_codec, 'windows') ) { $media_logos .= img_gen(style_img('MEDIA_WINDOWS',true), 65,40); }
    if ($channels == 1) { $media_logos .= img_gen(style_img('MEDIA_MONO',true), 55,40); }
    if ($channels == 2) { $media_logos .= img_gen(style_img('MEDIA_STEREO',true), 55,40); }
    if ($channels >= 5) { $media_logos .= img_gen(style_img('MEDIA_SURROUND',true), 55,40); }
    return $media_logos;
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.
  $sql_table     = "tv media".get_rating_join().' where 1=1 ';
  $predicate     = get_rating_filter().category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV).filter_get_predicate();
  $select_fields = "file_id, dirname, filename, title, year, length";
  $file_id       = $_REQUEST["file_id"];
  $this_url      = url_remove_params(current_url(), array('lookup', 'viewed'));
  $cert_img      = '';

  // Clean the current url in the history
  page_hist_current_update($this_url, $predicate);

  // Single match, so get the details from the database
  if ( ($data = db_row("select media.*, ".get_cert_name_sql()." certificate_name from $sql_table and file_id=$file_id")) === false)
    page_error( str('DATABASE_ERROR'));

  // Perform lookup if requested
  if (isset($_REQUEST["lookup"]) && strtoupper($_REQUEST["lookup"]) == 'Y')
  {
    // Clear old details first
    purge_tv_details($file_id);

    // Lookup tv show using current database values
    $lookup = ParserTVLookup($file_id, $data["DIRNAME"].$data["FILENAME"]
                                     , array('PROGRAMME' => $data["PROGRAMME"],
                                             'SERIES'    => $data["SERIES"],
                                             'EPISODE'   => $data["EPISODE"],
                                             'TITLE'     => $data["TITLE"]));
    // Export to XML
    if ( $lookup && get_sys_pref('tv_xml_save','NO') == 'YES' )
      export_tv_to_xml($file_id);

    $data = db_row("select media.*, ".get_cert_name_sql()." certificate_name from $sql_table and file_id=$file_id");
  }

  // Set viewed status
  if (isset($_REQUEST["viewed"]))
  {
    store_request_details(MEDIA_TYPE_TV, $data["FILE_ID"], ($_REQUEST["viewed"] == 1 ? true : false));
  }

  // Random fanart image
  $themes = db_toarray('select processed_image, show_banner, show_image from themes where media_type='.MEDIA_TYPE_TV.' and title="'.db_escape_str($data["PROGRAMME"]).'" and use_synopsis=1 and processed_image is not NULL');
  $theme = $themes[mt_rand(0,count($themes)-1)];

  // Set banner image
  if ( !empty($theme) && !$theme['SHOW_BANNER'] )
    $banner_img = false;
  else
  {
    // Random banner image
    $banner_imgs = dir_to_array($data['DIRNAME'].'banners/','banner_*.*');
    $banner_img = $banner_imgs[mt_rand(0,count($banner_imgs)-1)];
  }

  // Set episode and certificate image
  if ( !empty($theme) && !$theme['SHOW_IMAGE'] )
  {
    $folder_img = SC_LOCATION.'images/dot.gif';
  }
  else
  {
    // Display thumbnail
    $folder_img = file_albumart($data["DIRNAME"].$data["FILENAME"]);
    // Certificate? Get the appropriate image.
    if (!empty($data["CERTIFICATE"]))
      $cert_img = img_gen(SC_LOCATION.'images/ratings/'.get_rating_scheme_name().'/'.get_cert_name( get_nearest_cert_in_scheme($data["CERTIFICATE"])).'.gif',280,100);
  }

  // Set background image
  if ( !empty($theme) && file_exists($theme['PROCESSED_IMAGE']) )
    $background = $theme['PROCESSED_IMAGE'];
  else
    $background = -1;

  page_header( $data["PROGRAMME"], $data["TITLE"].(empty($data["YEAR"]) ? '' : ' ('.$data["YEAR"].')') ,'<meta SYABAS-PLAYERMODE="video">',1,false,'',$background,
               ( get_sys_pref('tv_use_banners','YES') == 'YES' && file_exists($banner_img) ? $banner_img : false ), 'PAGE_TEXT_BACKGROUND' );

  // Read bookmark file
  $bookmark_filename = bookmark_file($data["DIRNAME"].$data["FILENAME"]);
  if (!support_resume() && file_exists($bookmark_filename))
    $pc = (int)trim(file_get_contents($bookmark_filename));
  else
    $pc = 0;
  $percent_played = ($pc !== 0 && $pc < 99) ? ' ('.$pc.'%)' : '';

  // Play now
  $menu->add_item( str('PLAY_NOW').$percent_played, play_file( MEDIA_TYPE_TV, $data["FILE_ID"]));

  // Resume playing
  if ( support_resume() && file_exists( $bookmark_filename ))
    $menu->add_item( str('RESUME_PLAYING').$percent_played, resume_file(MEDIA_TYPE_TV,$file_id), true);

  // Add to your current playlist
  if (pl_enabled())
    $menu->add_item( str('ADD_PLAYLIST') ,'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table and file_id=$file_id"),true);

  // Add a link to search wikipedia
  if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES' )
    $menu->add_item( str('SEARCH_WIKIPEDIA'), lang_wikipedia_search( ucwords(strip_title($data["PROGRAMME"])), url_add_param($this_url, 'hist', PAGE_HISTORY_DELETE) ), true);

  // Link to full cast & directors
  if ($data["DETAILS_AVAILABLE"] == 'Y')
    $menu->add_item( str('VIDEO_INFO'), 'video_info.php?tv='.$file_id, true);

  // Delete media (limited to a small number of files)
  if (is_user_admin())
    $menu->add_item( str('DELETE_MEDIA'), 'video_delete.php?del='.$file_id.'&media_type='.MEDIA_TYPE_TV, true);

  // Column 1: Image
  echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="10" border="0">
          <tr>
            <td valign="middle">
              <table '.($theme['SHOW_IMAGE'] ? style_background('PAGE_TEXT_BACKGROUND') : '').' cellpadding="10" cellspacing="0" border="0">
                <tr>
                  <td><center>'.img_gen($folder_img,280,550,false,false,false,array(),false).'<br>'.$cert_img.'</center></td>
                </tr>
              </table>
            </td>';
  // Column 2: Details and menu
  echo '    <td valign="top">
              <table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0">
                <tr>
                  <td>';
                  // Episode synopsis
                  tv_details($data["FILE_ID"],$menu->num_items(),0.625);
  echo '          <br>';
                  // Running Time
                  running_time($data["LENGTH"]);
  echo '          <center>'.media_logos($data["AUDIO_CODEC"], $data["VIDEO_CODEC"], $data["RESOLUTION"], $data["AUDIO_CHANNELS"]).'&nbsp;'.star_rating($data["EXTERNAL_RATING_PC"]).'</center>
                  </td>
                </tr>
              </table>';
              $menu->display(1, 480);
  echo '    </td>
          </tr>
        </table>';

  // Buttons for Next and Previous episodes
  $prev = db_row("select file_id, series, episode from tv media ".get_rating_join()." where programme = '".db_escape_str($data["PROGRAMME"])."'".
                 " and concat(lpad(series,2,0),lpad(episode,3,0)) < concat(lpad(".$data["SERIES"].',2,0),lpad('.$data["EPISODE"].',3,0)) '.$predicate.
                 " order by series desc,episode desc limit 1");
  $next = db_row("select file_id, series, episode from tv media ".get_rating_join()." where programme = '".db_escape_str($data["PROGRAMME"])."'".
                 " and concat(lpad(series,2,0),lpad(episode,3,0)) > concat(lpad(".$data["SERIES"].',2,0),lpad('.$data["EPISODE"].',3,0)) '.$predicate.
                 " order by series asc, episode asc limit 1");

  // Output ABC buttons
  $buttons = array();
  if ( is_array($prev) )
    $buttons[0] = array('text'=>str('EP_PREV', $prev['SERIES'].'x'.$prev['EPISODE']), 'url'=> url_add_params('/tv_episode_selected.php', array('file_id'=>$prev['FILE_ID'], 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( is_array($next) )
    $buttons[1] = array('text'=>str('EP_NEXT', $next['SERIES'].'x'.$next['EPISODE']), 'url'=> url_add_params('/tv_episode_selected.php', array('file_id'=>$next['FILE_ID'], 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( internet_available() )
    $buttons[2] = array('text'=>str('LOOKUP_TV'), 'url'=> url_add_params('/tv_episode_selected.php', array('file_id'=>$file_id, 'lookup'=>'Y', 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( is_user_admin() )
  {
    if ( viewings_count(MEDIA_TYPE_TV, $data["FILE_ID"]) == 0 )
      $buttons[3] = array('text'=>str('VIEWED_SET'), 'url'=> url_add_params('/tv_episode_selected.php', array('file_id'=>$file_id, 'viewed'=>1, 'hist'=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[3] = array('text'=>str('VIEWED_RESET'), 'url'=> url_add_params('/tv_episode_selected.php', array('file_id'=>$file_id, 'viewed'=>0, 'hist'=>PAGE_HISTORY_REPLACE)) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous(), $buttons, 0, true, 'PAGE_TEXT_BACKGROUND' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
