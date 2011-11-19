<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/language.php'));

  if (isset($_REQUEST["order"]))
  {
    // User has selected "Random"? - Sorting by the "dirname" column is a botch (we have to sort by *something*).
    if ($_REQUEST["order"] == 'dirname')
      $_SESSION["shuffle"] = 'on';
    else
      $_SESSION["shuffle"] = 'off';

    set_user_pref('PHOTO_PLAY_ORDER',$_REQUEST["order"]);
    page_hist_pop();
    header('Location: '.page_hist_previous());
  }
  else
  {
    page_header( str('VIEW_PHOTO'),str('PHOTO_CHANGE_ORDER'),'',1,false,'',MEDIA_TYPE_PHOTO );

    echo '<p>';
    $menu = new menu();

    $menu->add_item( str('PHOTO_ORDER_NAME'),        'photo_change_order.php?order=filename');
    $menu->add_item( str('PHOTO_ORDER_DATE_TAKEN'),  'photo_change_order.php?order=date_created');
    $menu->add_item( str('PHOTO_ORDER_DATE_DISK'),   'photo_change_order.php?order=date_modified');
    $menu->add_item( str('PHOTO_ORDER_DATE_RANDOM'), 'photo_change_order.php?order=dirname');

    $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));
    page_footer( page_hist_previous() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
