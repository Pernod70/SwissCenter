<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));

  /**
   * Displays a message to the user, and auto-refreshes with the current progress.
   */
  
  function show_progress()
  {
    $menu        = new menu();
    $status      = get_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_COMPLETE'));
    $overall     = db_value("select avg(percent_scanned) from media_locations");
    $refresh_url = '/do_refresh.php?type=show';

    // Stop refreshing if the search is complete
    if ($status != str('MEDIA_SCAN_STATUS_COMPLETE'))
      page_header( str('SETUP_SEARCH_NEW_MEDIA'), '','<meta http-equiv="refresh" content="5;URL='.$refresh_url.'">');
    else
      page_header( str('SETUP_SEARCH_NEW_MEDIA'));

    // Display a message tot he user
    echo str('REFRESH_RUNNING');    

    // and then the current status
    echo '<p>&nbsp;<br><center><font color="'.style_value("PAGE_TITLE_COLOUR",'#FFFFFF').'">'.str('MEDIA_SCAN_STATUS').' :</font> '.$status;
    if ($status == str('MEDIA_SCAN_STATUS_RUNNING'))
      echo ' ('.(int)$overall.'%)';
    
    // And then finally a menu option to nagivate away from this page.
    echo '</center><p>';
    $menu->add_item(str('CONTINUE'),'/');
    $menu->display();
    page_footer( 'config.php' );        
  }
  
  /**
   * Refresh all categories and all media types
   *
   */
  
  function do_refresh_all()
  {
    set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_PENDING'));
    media_refresh_now();
    show_progress();
  }
  
  /**
   * Refresh all media locations defined for a particular category
   *
   * @param integer $spec - the category ID to refresh
   */
  
  function do_refresh_cat( $spec )
  {
    set_sys_pref('MEDIA_SCAN_MEDIA_TYPE','');
    set_sys_pref('MEDIA_SCAN_CATEGORY',$spec);
    do_refresh_all();
  }
  
  /**
   * Refresh all media locations for a particular media type
   *
   * @param ineteger $spec - the media type to refresh
   */
  
  function do_refresh_type( $spec )
  {
    set_sys_pref('MEDIA_SCAN_MEDIA_TYPE',$spec);
    set_sys_pref('MEDIA_SCAN_CATEGORY','');
    do_refresh_all();
  }
  
  /**
   * Choose the method to refresh: all, by type or by category
   *
   */

  function choose_main_opt()
  {
    $menu = new menu();
    page_header( str('SETUP_SEARCH_NEW_MEDIA'));
    $menu->add_item(str('SETUP_SEARCH_ALL'),'/do_refresh.php?type=all');
    $menu->add_item(str('SETUP_SEARCH_TYPE'),'/do_refresh.php?type=media_type',true);
    $menu->add_item(str('SETUP_SEARCH_CATEGORY'),'/do_refresh.php?type=category',true);
    echo '<center>'.str('SELECT_OPTION').'</center><p>';
    $menu->display();
    page_footer( 'config.php' );      
  }
  
  /**
   * Choose the category to refresh
   *
   */
  
  function choose_category()
  {
    $menu = new menu();
    page_header( str('SETUP_SEARCH_NEW_MEDIA'));          
    echo '<center>'.str('SELECT_CATEGORY').'</center><p>';
    foreach (db_toarray("select * from categories order by cat_name") as $cat)
    {
      $menu->add_item($cat["CAT_NAME"],'/do_refresh.php?type=category&spec='.$cat["CAT_ID"]);
    }
    $menu->display_page( nvl($_REQUEST["page"],1) );        
    page_footer( '/do_refresh.php' );          
  }
  
  /**
   * Choose the media type to refresh
   *
   */
  
  function choose_type()
  {
    $menu = new menu();
    page_header( str('SETUP_SEARCH_NEW_MEDIA'));          
    echo '<center>'.str('SETUP_SEARCH_TYPE_TITLE').'</center><p>';
    $menu->add_item( str('MUSIC') ,'/do_refresh.php?type=media_type&spec='.MEDIA_TYPE_MUSIC);
    $menu->add_item( str('PHOTOS') ,'/do_refresh.php?type=media_type&spec='.MEDIA_TYPE_PHOTO);
    $menu->add_item( str('TVSERIES') ,'/do_refresh.php?type=media_type&spec='.MEDIA_TYPE_TV);
    $menu->add_item( str('VIDEO') ,'/do_refresh.php?type=media_type&spec='.MEDIA_TYPE_VIDEO);
    $menu->display();
    page_footer( '/do_refresh.php' );              
  }

  /**
   * Main Logic
   * 
   */
  
  $type   = $_REQUEST["type"];
  $spec   = $_REQUEST["spec"];
  $status = get_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_COMPLETE'));

  if ( $type == 'show' || $status != str('MEDIA_SCAN_STATUS_COMPLETE') )
    show_progress();
  elseif ( $type == 'all' )
    do_refresh_all();
  elseif ( $type == 'media_type' && empty($spec) )
    choose_type();
  elseif ( $type == 'media_type' )
    do_refresh_type($spec);
  elseif ( $type == 'category' && empty($spec) )
    choose_category();
  elseif ( $type == 'category' )
    do_refresh_cat($spec);
  else
    choose_main_opt();  

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
