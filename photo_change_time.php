<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/language.php'));

  if (isset($_REQUEST["time"]))
  {
    set_user_pref('PHOTO_PLAY_TIME',$_REQUEST["time"]);
    page_hist_pop();
    header('Location: '.page_hist_previous());
  }
  else
  {
    page_header( str('VIEW_PHOTO'),str('PHOTO_CHANGE_TIME'),'',1,false,'',MEDIA_TYPE_PHOTO );

    echo '<p>';
    $menu = new menu();

    $time = get_sys_pref('PHOTO_DELAY_SECONDS', '0,5,10,15,30,60,90');
    foreach (explode(',', $time) as $delay)
      $menu->add_item( str('SECONDS',trim($delay)), 'photo_change_time.php?time='.trim($delay));

    $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));
    page_footer( page_hist_previous() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
