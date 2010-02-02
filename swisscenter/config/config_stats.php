<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// ----------------------------------------------------------------------------------
// Displays media statistics
// ----------------------------------------------------------------------------------

function stats_display()
{
  $tab = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : 'TOP10';
  $url = '/config/index.php?section=STATS&action=DISPLAY&tab=';

  echo "<h1>".str('STATISTICS')."</h1>";

  // Display statistics
  form_start('index.php', 200, 'stats');

  form_tabs(array('VIDEO', 'TVSERIES', 'MUSIC', 'PHOTOS'), $url, $tab);

  switch ($tab)
  {
    case 'VIDEO':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'GENRE';
      $url = '/config/index.php?section=STATS&action=DISPLAY&tab='.$tab.'&tab2=';
      form_tabs(array('GENRE', 'CATEGORY', 'TOP20'), $url, $tab2);

      $num_videos = db_value("select count(filename) from movies;");
      $num_titles = db_value("select count(distinct title) from movies;");

      array_to_table( array( array('Summary'=>str('NUMBER_OF_VIDEOS', ' = '.$num_videos)),
                             array('Summary'=>str('NUMBER_OF_TITLES', ' = '.$num_titles)) ) );
      echo '<p>';

      stats_video($tab2);
      break;

    case 'TVSERIES':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'PROGRAMME';
      $url = '/config/index.php?section=STATS&action=DISPLAY&tab='.$tab.'&tab2=';
      form_tabs(array('PROGRAMME', 'GENRE', 'CATEGORY', 'TOP20'), $url, $tab2);

      $num_programme = db_value('select count(distinct programme) from tv');
      $num_epsiode   = db_value('select count(distinct series,filename) from tv');

      array_to_table( array( array('Summary'=>str('NUMBER_OF_TV_SHOWS', ' = '.$num_programme)),
                             array('Summary'=>str('NUMBER_OF_TV_EPISODES', ' = '.$num_epsiode)) ) );
      echo '<p>';

      stats_tv($tab2);
      break;

    case 'MUSIC':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'ARTIST';
      $url = '/config/index.php?section=STATS&action=DISPLAY&tab='.$tab.'&tab2=';
      form_tabs(array('ARTIST', 'GENRE', 'CATEGORY', 'TOP20'), $url, $tab2);

      $num_tracks = db_value("select count(file_id) from mp3s");
      $num_artist = db_value("select count(distinct artist) from mp3s");
      $num_albums = db_value("select count(distinct album) from mp3s");

      array_to_table( array( array('Summary'=>str('NUMBER_OF_TRACKS', ' = '.$num_tracks)),
                             array('Summary'=>str('NUMBER_OF_ARTISTS', ' = '.$num_artist)),
                             array('Summary'=>str('NUMBER_OF_ALBUMS', ' = '.$num_albums)) ) );
      echo '<p>';

      stats_music($tab2);
      break;

    case 'PHOTOS':
      $tab2 = isset($_REQUEST["tab2"]) ? $_REQUEST["tab2"] : 'PHOTO_ALBUM';
      $url = '/config/index.php?section=STATS&action=DISPLAY&tab='.$tab.'&tab2=';
      form_tabs(array('PHOTO_ALBUM', 'CATEGORY', 'TOP20'), $url, $tab2);

      $num_albums = db_value("select count(distinct dirname) from photos");
      $num_photos = db_value("select count(file_id) from photos");

      array_to_table( array( array('Summary'=>str('NUMBER_OF_PHOTO_ALBUMS', ' = '.$num_albums)),
                             array('Summary'=>str('NUMBER_OF_PHOTOS', ' = '.$num_photos)) ) );
      echo '<p>';

      stats_photos($tab2);
      break;
  }
  form_end();
}

function stats_video( $type )
{
  switch ($type)
  {
    case 'GENRE':
      array_to_table(db_toarray('select g.genre_name GENRE, count(distinct m.title) NUMBER_OF_TITLES, count(gom.movie_id) NUMBER_OF_VIDEOS
                                 from genres_of_movie gom, genres g, movies m
                                 where gom.genre_id = g.genre_id and gom.movie_id = m.file_id group by genre'),
                     str('GENRE').','.str('NUMBER_OF_TITLES').','.str('NUMBER_OF_VIDEOS'));
      break;

    case 'CATEGORY':
      array_to_table(db_toarray('select c.cat_name CATEGORY, count(distinct m.title) NUMBER_OF_TITLES, count(m.file_id) NUMBER_OF_VIDEOS
                                 from categories c, media_locations ml, movies m
                                 where c.cat_id = ml.cat_id and m.location_id = ml.location_id group by c.cat_name'),
                     str('CATEGORY').','.str('NUMBER_OF_TITLES').','.str('NUMBER_OF_VIDEOS'));
      break;

    case 'TOP20':
      array_to_table(db_toarray('select distinct(TITLE), TOTAL_VIEWINGS
                                 from movies m, viewings v where v.media_type = '.MEDIA_TYPE_VIDEO.' and m.file_id = v.media_id
                                 order by total_viewings desc limit 20'),
                     str('TITLE').','.str('VIEWED'));
      break;
  }
}

function stats_tv( $type )
{
  switch ($type)
  {
    case 'PROGRAMME':
      array_to_table(db_toarray('select PROGRAMME, SERIES, count(episode) NUMBER_OF_TV_SHOWS from tv group by programme, series'),
                     str('PROGRAMME').','.str('SERIES').','.str('NUMBER_OF_TV_EPISODES'));
      break;

    case 'GENRE':
      array_to_table(db_toarray('select GENRE, NUMBER_OF_TV_SHOWS
                                 from (select g.genre_name GENRE, count(got.tv_id) NUMBER_OF_TV_SHOWS
                                       from genres_of_tv got, genres g where got.genre_id = g.genre_id group by genre) as T2'),
                     str('GENRE').','.str('NUMBER_OF_TV_EPISODES'));
      break;

    case 'CATEGORY':
      array_to_table(db_toarray('select c.cat_name CATEGORY, count(distinct t.programme) NUMBER_OF_TITLES, count(t.episode) NUMBER_OF_TV_SHOWS
                                 from categories c, media_locations ml, tv t
                                 where c.cat_id = ml.cat_id and t.location_id = ml.location_id group by c.cat_name'),
                     str('CATEGORY').','.str('NUMBER_OF_TV_SHOWS').','.str('NUMBER_OF_TV_EPISODES'));
      break;

    case 'TOP20':
      array_to_table(db_toarray('select TITLE, PROGRAMME, SERIES, TOTAL_VIEWINGS
                                 from tv t, viewings v where v.media_type = '.MEDIA_TYPE_TV.' and t.file_id = v.media_id
                                 order by total_viewings desc limit 20'),
                     str('TITLE').','.str('PROGRAMME').','.str('SERIES').','.str('VIEWED'));
      break;
  }
}

function stats_music( $type )
{
  switch ($type)
  {
    case 'ARTIST':
      array_to_table(db_toarray('select ARTIST, count(distinct album) NUMBER_OF_ALBUMS, count(file_id) NUMBER_OF_TRACKS from mp3s group by artist'),
                     str('ARTIST').','.str('NUMBER_OF_ALBUMS').','.str('NUMBER_OF_TRACKS'));
      break;

    case 'GENRE':
      array_to_table(db_toarray('select GENRE, count(distinct album) NUMBER_OF_ALBUMS, count(file_id) NUMBER_OF_TRACKS from mp3s group by genre'),
                     str('GENRE').','.str('NUMBER_OF_ALBUMS').','.str('NUMBER_OF_TRACKS'));
      break;

    case 'CATEGORY':
      array_to_table(db_toarray('select c.cat_name CATEGORY, count(distinct m.album) NUMBER_OF_ALBUMS, count(m.file_id) NUMBER_OF_TRACKS
                                 from categories c, media_locations ml, mp3s m
                                 where c.cat_id = ml.cat_id and m.location_id = ml.location_id group by c.cat_name'),
                     str('CATEGORY').','.str('NUMBER_OF_ALBUMS').','.str('NUMBER_OF_TRACKS'));
      break;

    case 'TOP20':
      array_to_table(db_toarray('select TITLE, ARTIST, ALBUM, TOTAL_VIEWINGS
                                 from mp3s m, viewings v where v.media_type = '.MEDIA_TYPE_MUSIC.' and m.file_id = v.media_id
                                 order by total_viewings desc limit 20'),
                     str('TITLE').','.str('ARTIST').','.str('ALBUM').','.str('VIEWED'));
      break;
  }
}

function stats_photos( $type )
{
  switch ($type)
  {
    case 'PHOTO_ALBUM':
      array_to_table(db_toarray('select PHOTO_ALBUM, NUMBER_OF_PHOTOS
                                 from (select pa.title PHOTO_ALBUM, photos.dirname, COUNT(photos.file_id) NUMBER_OF_PHOTOS
                                       from photo_albums pa left join photos on photos.dirname = pa.dirname
                                       group by pa.title) as T1 where NUMBER_OF_PHOTOS > 0'),
                     str('PHOTO_ALBUM').','.str('NUMBER_OF_PHOTOS'));
      break;

    case 'CATEGORY':
      array_to_table(db_toarray('select c.cat_name CATEGORY, count(distinct p.dirname) NUMBER_OF_PHOTO_ALBUMS, count(p.file_id) NUMBER_OF_PHOTOS
                                 from categories c, media_locations ml, photos p
                                 where c.cat_id = ml.cat_id and p.location_id = ml.location_id group by c.cat_name'),
                     str('CATEGORY').','.str('NUMBER_OF_PHOTO_ALBUMS').','.str('NUMBER_OF_PHOTOS'));
      break;

    case 'TOP20':
      array_to_table(db_toarray('select p.filename TITLE, pa.title PHOTO_ALBUM, TOTAL_VIEWINGS
                                 from photos p, photo_albums pa, viewings v
                                 where v.media_type = '.MEDIA_TYPE_PHOTO.' and p.file_id = v.media_id and pa.dirname = p.dirname
                                 order by total_viewings desc limit 20'),
                     str('TITLE').','.str('PHOTO_ALBUM').','.str('VIEWED'));
      break;
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
