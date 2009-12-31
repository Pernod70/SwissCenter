<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/language.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $back_url = search_hist_most_recent();

  if (isset($_REQUEST["music"]))
  {
  	$_SESSION['background_music'] = urldecode($_REQUEST["music"]);
  	header('Location: '.$back_url["url"]);
  }
  else
  {
    page_header( str('VIEW_PHOTO'),str('PHOTOS_MUSIC_CHANGE'),'',1,false,'',MEDIA_TYPE_PHOTO );

    echo '<p>';
    $menu = new menu();

    $menu->add_item( str('PHOTOS_MUSIC_NONE'),        'photo_change_music.php?music=');
    $menu->add_item( str('PHOTOS_MUSIC_CURRENT'),     'photo_change_music.php?music='.urlencode('*'));

    $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));
    page_footer( $back_url["url"] );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
