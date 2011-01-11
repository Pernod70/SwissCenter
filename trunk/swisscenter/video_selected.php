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
  require_once( realpath(dirname(__FILE__).'/base/youtube.php'));

  $menu = new menu();
  $info = new infotab();

  // Displays the details for a single movie (identified by the movie ID) including the title, year, certificate
  // and synopsis (where available).

  function movie_details ($movie, $num_menu_items)
  {
    $info      = array_pop(db_toarray("select * from movies where file_id=$movie"));
    $directors = db_toarray("select d.director_name from directors_of_movie dom, directors d where dom.director_id = d.director_id and dom.movie_id=$movie");
    $actors    = db_toarray("select a.actor_name from actors_in_movie aim, actors a where aim.actor_id = a.actor_id and aim.movie_id=$movie");
    $genres    = db_toarray("select g.genre_name from genres_of_movie gom, genres g where gom.genre_id = g.genre_id and gom.movie_id=$movie");
    $synlen   = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

    // Synopsis
    if ( !is_null($info["SYNOPSIS"]) )
    {
      $text = shorten($info["SYNOPSIS"],$synlen,1,32);
      if (strlen($text) != strlen($info["SYNOPSIS"]))
        $text = $text.' <a href="/video_synopsis.php?media_type='.MEDIA_TYPE_VIDEO.'&file_id='.$movie.'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('MORE')).'</a>';
    }
    else
      $text = str('NO_SYNOPSIS_AVAILABLE');

    echo '<p>'.font_tags(32).$text.'</font>';
  }

  // Function that checks to see if the supplied SQL contains a filter for the specified type.
  // If it doesn't, then we can output a menu item which allows the user to filter on that type.

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
  $this_url      = url_set_param(current_url(),'add','N');
  $cert_img      = '';

  //
  // A single movie has been matched/selected by the user, so display as much information as possible
  // on the screen, along with commands to "Play Now" or "Add to Playlist".
  //

  if ($num_rows == 1 || $num_unique == 1)
  {
    // Single match, so get the details from the database and display them
    if ( ($data = db_toarray("select media.*, ml.name, ml.network_share, ".get_cert_name_sql()." certificate_name from $sql_table $predicate")) === false)
      page_error( str('DATABASE_ERROR'));

    // Form star rating
    $img_rating = '';
    if ( !is_null($data[0]["EXTERNAL_RATING_PC"]) )
    {
      $user_rating = nvl($data[0]["EXTERNAL_RATING_PC"]/10,0);
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

    // Random fanart image
    $themes = db_toarray('select processed_image, show_banner, show_image from themes where media_type='.MEDIA_TYPE_VIDEO.' and title="'.db_escape_str($data[0]["TITLE"]).'" and use_synopsis=1 and processed_image is not NULL');
    $theme = $themes[mt_rand(0,count($themes)-1)];

    if ( file_exists($theme['PROCESSED_IMAGE']) )
      $background = $theme['PROCESSED_IMAGE'];
    else
      $background = -1;

    if (!empty($data[0]["YEAR"]))
      page_header( $data[0]["TITLE"].' ('.$data[0]["YEAR"].')', $media_logos.'&nbsp;&nbsp;'.$img_rating,'',1,false,'',$background );
    else
      page_header( $data[0]["TITLE"], $media_logos.'&nbsp;&nbsp;'.$img_rating,'',1,false,'',$background );

    // Is DVD image?
    $is_dvd = in_array(file_ext($data[0]["FILENAME"]), media_exts_dvd());

    // Play now
    if ( $is_dvd )
    {
      // If VIDEO_TS folder then pass folder name
      if ( strtoupper($data[0]["FILENAME"]) == 'VIDEO_TS.IFO' )
        $file = rtrim($data[0]["DIRNAME"],'/');
      else
        $file = $data[0]["DIRNAME"].$data[0]["FILENAME"];
      $file = str_replace($data[0]["NAME"], "", $file);
      // Can't use gen_playlist as the NMT does something different with zcd=2.
      $menu->add_item( str('PLAY_NOW') , 'href="file:///opt/sybhttpd/localhost.drives/'.rawurlencode($data[0]["NETWORK_SHARE"].$file).'" zcd="2" ' );
      send_to_log(2,'href for dvd: href="file:///opt/sybhttpd/localhost.drives/'.rawurlencode($data[0]["NETWORK_SHARE"].$file).'" zcd="2" ');
    }
    else
    {
      $menu->add_item( str('PLAY_NOW'), play_sql_list(MEDIA_TYPE_VIDEO,"select distinct $select_fields from $sql_table $predicate order by title, filename"));

      // Resume playing
      if ( support_resume() && file_exists( bookmark_file($data[0]["DIRNAME"].$data[0]["FILENAME"])) )
        $menu->add_item( str('RESUME_PLAYING'), resume_file(MEDIA_TYPE_VIDEO,$data[0]["FILE_ID"]), true);

      // Add to your current playlist
      if (pl_enabled())
        $menu->add_item( str('ADD_PLAYLIST'), 'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table $predicate order by title, filename"),true);
    }

    // Movie trailer
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
    {
      $back_url = url_remove_params(current_url(), array('add','p_del'));
      $menu->add_item( str('SEARCH_WIKIPEDIA'), lang_wikipedia_search( ucwords(strip_title($data[0]["TITLE"])), $back_url ), true);
    }

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
    page_header( str('MANY_ITEMS',$num_unique),'' );

    if ( ($data = db_toarray("select file_id, dirname from $sql_table $predicate group by dirname")) === false )
      page_error( str('DATABASE_ERROR') );

    if ( count($data)==1)
      $folder_img = file_albumart($data[0]["DIRNAME"]);

    search_distinct_info($info, str('TITLE'), 'title', $sql_table, $predicate);
    search_distinct_info($info, str('YEAR'), 'year',$sql_table, $predicate);
    search_distinct_info($info, str('CERTIFICATE'), get_cert_name_sql(),$sql_table, $predicate);
    $menu->add_item( str('PLAY_NOW'), play_sql_list(MEDIA_TYPE_VIDEO,"select distinct $select_fields from $sql_table $predicate order by title, filename"));

    if (pl_enabled())
      $menu->add_item( str('ADD_PLAYLIST'), 'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table $predicate order by title, filename"),true);

    check_filters( array('title','year','certificate','genre_name','actor_name','director_name'), $sql_table_all, $predicate, $menu);

    $info->display();
  }

  // Delete media (limited to a small number of files)
  if (is_user_admin() && $num_rows<=8 )
    $menu->add_item( str('DELETE_MEDIA'), 'video_delete.php?del='.implode(',',$file_ids).'&media_type='.MEDIA_TYPE_VIDEO,true);

    // Certificate? Get the appropriate image.
  $scheme    = get_rating_scheme_name();
    if (!empty($data[0]["CERTIFICATE"]))
    $cert_img  = img_gen(SC_LOCATION.'images/ratings/'.$scheme.'/'.get_cert_name( get_nearest_cert_in_scheme($data[0]["CERTIFICATE"], $scheme)).'.gif', 280, 100);

  // Is there a picture for us to display?
  if ( !empty($folder_img) )
  {
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(280).'" align="left">
              '.img_gen($folder_img,280,550).'<br><center>'.$cert_img.'</center>
              </td><td width="'.convert_x(20).'"></td>
              <td valign="top">';

                    // Movie synopsis
                    movie_details($data[0]["FILE_ID"],$menu->num_items());

                    // Running Time
              if (!is_null($playtime))
                echo   '<p>'.font_tags(32).str('RUNNING_TIME').': '.hhmmss($playtime).'</font>';
                $menu->display(1, 480);
    echo '    </td></table>';
  }
  else
  {
    $menu->display();
  }

  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_param($this_url,'shuffle','on') );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_param($this_url,'shuffle','off') );

  page_footer( url_add_params( search_picker_most_recent(), array("p_del"=>"y","del"=>"y") ), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
