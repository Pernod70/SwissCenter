<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $current = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : 'VIDEO';
  $tabs    = array('VIDEO', 'TVSERIES', 'MUSIC', 'PHOTOS');

  $tab_strip = '';
  foreach ($tabs as $key=>$tab)
    $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="statistics.php?tab='.$tab.'">'.
                  ($tab == $current ? font_tags(30, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(30).str($tab)).'</font></a>';

  // Output Title
  page_header( str('STATISTICS'), $tab_strip );

  $info = new infotab();

  switch ($current)
  {
    case 'VIDEO':
      $num_videos = db_value("select count(filename) from movies");
      $num_titles = db_value("select count(distinct title) from movies");

      $info->add_item( str('NUMBER_OF_VIDEOS'), $num_videos );
      $info->add_item( str('NUMBER_OF_TITLES'), $num_titles );
      break;

    case 'TVSERIES':
      $num_programme = db_value('select count(distinct programme) from tv');
      $num_epsiode   = db_value('select count(distinct series,filename) from tv');

      $info->add_item( str('NUMBER_OF_TV_SHOWS'), $num_programme );
      $info->add_item( str('NUMBER_OF_TV_EPISODES'), $num_epsiode );
      break;

    case 'MUSIC':
      $num_tracks = db_value("select count(file_id) from mp3s");
      $num_artist = db_value("select count(distinct artist) from mp3s");
      $num_albums = db_value("select count(distinct album) from mp3s");

      $info->add_item( str('NUMBER_OF_TRACKS'), $num_tracks );
      $info->add_item( str('NUMBER_OF_ARTISTS'), $num_artist );
      $info->add_item( str('NUMBER_OF_ALBUMS'), $num_albums );
      break;

    case 'PHOTOS':
      $num_albums = db_value("select count(distinct dirname) from photos");
      $num_photos = db_value("select count(file_id) from photos");

      $info->add_item( str('NUMBER_OF_PHOTO_ALBUMS'), $num_albums );
      $info->add_item( str('NUMBER_OF_PHOTOS'), $num_photos );
      break;
  }

  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  $info->display();

  page_footer( 'config.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
