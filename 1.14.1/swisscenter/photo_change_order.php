<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/page.php");
  require_once("base/menu.php");
  require_once("base/mysql.php");
  require_once("base/language.php");
  require_once("base/search.php");  
  
  $back_url = search_hist_most_recent();

  if (isset($_REQUEST["order"]))
  {
    // User has selected "Random"? - Sorting by the "dirname" column is a botch (we have to sort by *something*). 
    if ($_REQUEST["order"] == 'dirname')
      $_SESSION["shuffle"] = 'on';
    else 
      $_SESSION["shuffle"] = 'off';
    
  	set_user_pref('PHOTO_PLAY_ORDER',$_REQUEST["order"]);
  	header('Location: '.$back_url["url"]);
  }
  else 
  {
    page_header( str('PHOTO_CHANGE_ORDER'), '');

    echo '<p align="center">'.str('SELECT_OPTION');
    $menu = new menu();

    $menu->add_item( str('PHOTO_ORDER_NAME'),        'photo_change_order.php?order=filename');
    $menu->add_item( str('PHOTO_ORDER_DATE_TAKEN'),  'photo_change_order.php?order=date_created');
    $menu->add_item( str('PHOTO_ORDER_DATE_DISK'),   'photo_change_order.php?order=date_modified');
    $menu->add_item( str('PHOTO_ORDER_DATE_RANDOM'), 'photo_change_order.php?order=dirname');

    $menu->display();
    page_footer( $back_url["url"] );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
