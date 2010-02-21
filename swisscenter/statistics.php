<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));
  require_once( realpath(dirname(__FILE__).'/ext/lastfm/datafeeds.php'));

  function stats_video( $type )
  {
    switch ($type)
    {
      case 'GENRE':
        $data = db_toarray('select count(distinct m.title) NUMBER_OF_TITLES, g.genre_name GENRE
                                   from genres_of_movie gom, genres g, movies m
                                   where gom.genre_id = g.genre_id and gom.movie_id = m.file_id group by genre
                                   order by 1 desc');
        break;

      case 'CATEGORY':
        $data = db_toarray('select count(distinct m.title) NUMBER_OF_TITLES, c.cat_name CATEGORY
                                   from categories c, media_locations ml, movies m
                                   where c.cat_id = ml.cat_id and m.location_id = ml.location_id group by c.cat_name
                                   order by 1 desc');
        break;

      case 'TOP20':
        $data = db_toarray('select TOTAL_VIEWINGS, TITLE
                                   from movies m, viewings v where v.media_type = '.MEDIA_TYPE_VIDEO.' and m.file_id = v.media_id and v.user_id = '.get_current_user_id().'
                                   order by total_viewings desc limit 20');
        break;
    }
    return $data;
  }

  function stats_tv( $type )
  {
    switch ($type)
    {
      case 'PROGRAMME':
        $data = db_toarray('select count(episode) NUMBER_OF_TV_SHOWS, PROGRAMME from tv group by programme');
        break;

      case 'GENRE':
        $data = db_toarray('select NUMBER_OF_TV_SHOWS, GENRE
                                   from (select g.genre_name GENRE, count(got.tv_id) NUMBER_OF_TV_SHOWS
                                         from genres_of_tv got, genres g where got.genre_id = g.genre_id group by genre) as T2
                                   order by 1 desc');
        break;

      case 'CATEGORY':
        $data = db_toarray('select count(t.episode) NUMBER_OF_TV_SHOWS, c.cat_name CATEGORY
                                   from categories c, media_locations ml, tv t
                                   where c.cat_id = ml.cat_id and t.location_id = ml.location_id group by c.cat_name
                                   order by 1 desc');
        break;

      case 'TOP20':
        $data = db_toarray('select TOTAL_VIEWINGS, TITLE, PROGRAMME
                                   from tv t, viewings v where v.media_type = '.MEDIA_TYPE_TV.' and t.file_id = v.media_id and v.user_id = '.get_current_user_id().'
                                   order by total_viewings desc limit 20');
        break;
    }
    return $data;
  }

  function stats_music( $type )
  {
    switch ($type)
    {
      case 'ARTIST':
        $data = db_toarray('select count(file_id) NUMBER_OF_TRACKS, ARTIST from mp3s group by artist');
        break;

      case 'GENRE':
        $data = db_toarray('select count(file_id) NUMBER_OF_TRACKS, GENRE from mp3s group by genre
                                   order by 1 desc');
        break;

      case 'CATEGORY':
        $data = db_toarray('select count(m.file_id) NUMBER_OF_TRACKS, c.cat_name CATEGORY
                                   from categories c, media_locations ml, mp3s m
                                   where c.cat_id = ml.cat_id and m.location_id = ml.location_id group by c.cat_name
                                   order by 1 desc');
        break;

      case 'TOP20':
        $data = db_toarray('select TOTAL_VIEWINGS, TITLE, ARTIST, ALBUM
                                   from mp3s m, viewings v where v.media_type = '.MEDIA_TYPE_MUSIC.' and m.file_id = v.media_id and v.user_id = '.get_current_user_id().'
                                   order by total_viewings desc limit 20');
        break;
    }
    return $data;
  }

  function stats_photos( $type )
  {
    switch ($type)
    {
      case 'PHOTO_ALBUM':
        $data = db_toarray('select NUMBER_OF_PHOTOS, PHOTO_ALBUM
                                   from (select pa.title PHOTO_ALBUM, photos.dirname, COUNT(photos.file_id) NUMBER_OF_PHOTOS
                                         from photo_albums pa left join photos on photos.dirname = pa.dirname
                                         group by pa.title) as T1 where NUMBER_OF_PHOTOS > 0
                                   order by 1 desc');
        break;

      case 'CATEGORY':
        $data = db_toarray('select count(p.file_id) NUMBER_OF_PHOTOS, c.cat_name CATEGORY
                                   from categories c, media_locations ml, photos p
                                   where c.cat_id = ml.cat_id and p.location_id = ml.location_id group by c.cat_name
                                   order by 1 desc');
        break;

      case 'TOP20':
        $data = db_toarray('select p.filename TITLE, pa.title PHOTO_ALBUM, TOTAL_VIEWINGS
                                   from photos p, photo_albums pa, viewings v
                                   where v.media_type = '.MEDIA_TYPE_PHOTO.' and p.file_id = v.media_id and pa.dirname = p.dirname and v.user_id = '.get_current_user_id().'
                                   order by total_viewings desc limit 20');
        break;
    }
    return $data;
  }

  function stats_lastfm( $type )
  {
    $user = get_user_pref('LASTFM_USERNAME');
    $data = array();

    switch ($type)
    {
      case 'RECENT_TRACKS':
        $recent = lastfm_user_getRecentTracks($user, 1, 10);

        foreach ($recent["recenttracks"]["track"] as $track)
        {
          $image = $track["image"][0]["#text"];
          $title = utf8_decode($track["name"]);
          $artist = utf8_decode($track["artist"]["#text"]);
          $album = utf8_decode($track["album"]["#text"]);

          $data[] = array(img_gen($image,30,30), $title, $artist, $album);
        }
        break;

      case 'TOP_ALBUMS':
        $top = lastfm_user_getTopAlbums($user, $overall);

        foreach ($top["topalbums"]["album"] as $album)
        {
          $image = $album["image"][0]["#text"];
          $title = utf8_decode($album["name"]);
          $artist = utf8_decode($album["artist"]["name"]);
          $count = $album["playcount"];

          $data[] = array($count, img_gen($image,30,30), $title, $artist);
        }
        break;

      case 'TOP_ARTISTS':
        $top = lastfm_user_getTopArtists($user, $overall);

        echo '<table align="center">';
        foreach ($top["topartists"]["artist"] as $artist)
        {
          $image = $artist["image"][0]["#text"];
          $title = utf8_decode($artist["name"]);
          $count = $artist["playcount"];

          $data[] = array($count, img_gen($image,30,30), $title);
        }
        echo '</table>';
        break;

      case 'TOP_TRACKS':
        $top = lastfm_user_getTopTracks($user, $overall);

        foreach ($top["toptracks"]["track"] as $id=>$track)
        {
          $image = $track["image"][0]["#text"];
          $title = utf8_decode($track["name"]);
          $artist = utf8_decode($track["artist"]["name"]);
          $count = $track["playcount"];

          $data[] = array($count, img_gen($image,30,30), $title, $artist);
        }
        break;
    }
    return $data;
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $page    = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1;
  $current = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : 'VIDEO';
  $tabs    = array('VIDEO', 'TVSERIES', 'MUSIC', 'PHOTOS', 'LASTFM');

  $tab_strip = '';
  foreach ($tabs as $key=>$tab)
    $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$tab.'">'.
                  ($tab == $current ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';

  // Output Title
  page_header( str('STATISTICS') );

  echo '<center>'.font_tags(28).$tab_strip.'</center>';

  $info = new infotab();
  $menu = new menu();
  $menu->set_menu_type(MENU_TYPE_LIST);

  switch ($current)
  {
    case 'VIDEO':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'GENRE';
      $tabs = array('GENRE', 'CATEGORY', 'TOP20');

      $num_videos = db_value("select count(filename) from movies");
      $num_titles = db_value("select count(distinct title) from movies");

      $info->add_item( str('NUMBER_OF_VIDEOS'), $num_videos );
      $info->add_item( str('NUMBER_OF_TITLES'), $num_titles );

      $tab_strip = '';
      foreach ($tabs as $key=>$tab)
        $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$current.'&tab2='.$tab.'">'.
                      ($tab == $tab2 ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';
      echo '<center>'.font_tags(28).$tab_strip.'</center>';

      $data = stats_video($tab2);
      break;

    case 'TVSERIES':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'PROGRAMME';
      $tabs = array('PROGRAMME', 'GENRE', 'CATEGORY', 'TOP20');

      $num_programme = db_value('select count(distinct programme) from tv');
      $num_epsiode   = db_value('select count(distinct series,filename) from tv');

      $info->add_item( str('NUMBER_OF_TV_SHOWS'), $num_programme );
      $info->add_item( str('NUMBER_OF_TV_EPISODES'), $num_epsiode );

      $tab_strip = '';
      foreach ($tabs as $key=>$tab)
        $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$current.'&tab2='.$tab.'">'.
                      ($tab == $tab2 ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';
      echo '<center>'.font_tags(28).$tab_strip.'</center>';

      $data = stats_tv($tab2);
      break;

    case 'MUSIC':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'ARTIST';
      $tabs = array('ARTIST', 'GENRE', 'CATEGORY', 'TOP20');

      $num_tracks = db_value("select count(file_id) from mp3s");
      $num_artist = db_value("select count(distinct artist) from mp3s");
      $num_albums = db_value("select count(distinct album) from mp3s");

      $info->add_item( str('NUMBER_OF_TRACKS'), $num_tracks );
      $info->add_item( str('NUMBER_OF_ARTISTS'), $num_artist );
      $info->add_item( str('NUMBER_OF_ALBUMS'), $num_albums );

      $tab_strip = '';
      foreach ($tabs as $key=>$tab)
        $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$current.'&tab2='.$tab.'">'.
                      ($tab == $tab2 ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';
      echo '<center>'.font_tags(28).$tab_strip.'</center>';

      $data = stats_music($tab2);
      break;

    case 'PHOTOS':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'PHOTO_ALBUM';
      $tabs = array('PHOTO_ALBUM', 'CATEGORY', 'TOP20');

      $num_albums = db_value("select count(distinct dirname) from photos");
      $num_photos = db_value("select count(file_id) from photos");

      $info->add_item( str('NUMBER_OF_PHOTO_ALBUMS'), $num_albums );
      $info->add_item( str('NUMBER_OF_PHOTOS'), $num_photos );

      $tab_strip = '';
      foreach ($tabs as $key=>$tab)
        $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$current.'&tab2='.$tab.'">'.
                      ($tab == $tab2 ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';
      echo '<center>'.font_tags(28).$tab_strip.'</center>';

      $data = stats_photos($tab2);
      break;

    case 'LASTFM':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'RECENT_TRACKS';
      $tabs = array('RECENT_TRACKS', 'TOP_ALBUMS', 'TOP_ARTISTS', 'TOP_TRACKS');

      $tab_strip = '';
      foreach ($tabs as $key=>$tab)
        $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$current.'&tab2='.$tab.'">'.
                      ($tab == $tab2 ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';
      echo '<center>'.font_tags(28).$tab_strip.'</center>';

      $data = stats_lastfm($tab2);
      break;
  }

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  $info->display();

  foreach ($data as $item)
    $menu->add_table_item( $item );

  $menu->display_page($page);

  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
