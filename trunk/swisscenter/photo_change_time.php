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

  if (isset($_REQUEST["time"]))
  {
  	set_user_pref('PHOTO_PLAY_TIME',$_REQUEST["time"]);
  	header('Location: '.$back_url["url"]);
  }
  else 
  {
    page_header( str('PHOTO_CHANGE_ORDER'), '');

    echo '<p align="center">'.str('SELECT_OPTION');
    $menu = new menu();

    $menu->add_item( str('SECONDS',5),        'photo_change_time.php?time=5');
    $menu->add_item( str('SECONDS',10),        'photo_change_time.php?time=10');
    $menu->add_item( str('SECONDS',15),        'photo_change_time.php?time=15');
    $menu->add_item( str('SECONDS',30),        'photo_change_time.php?time=30');
    $menu->add_item( str('SECONDS',60),        'photo_change_time.php?time=60');
    $menu->add_item( str('SECONDS',90),        'photo_change_time.php?time=90');

    $menu->display();
    page_footer( $back_url["url"] );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
