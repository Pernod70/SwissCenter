<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/rss.php'));

  function display_rss_feed($sub_id)
  {
    $menu     = new menu();
    $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $sub_data = rss_get_subscription_details($sub_id);
    $items    = rss_get_subscription_items($sub_id, 'desc');
    $synlen   = $_SESSION["device"]["browser_x_res"];

    if ( strpos(search_picker_most_recent(), 'sub_id') > 0 ) search_picker_pop();
    $back_url = search_picker_most_recent();
    search_picker_push( current_url() );

    page_header( $sub_data["TITLE"], shorten($sub_data["DESCRIPTION"],$synlen) );

    // Build up a menu of subscription items that the user can select from.
    foreach ($items as $item)
    {
      $menu->add_item( $item["TITLE"], url_add_params('/rss_item_selected.php',array('item_id'=>$item["ID"],
                                                                                     'sub_id'=>$sub_id)));
    }

    if ($menu->num_items() > 0)
    {
      if (!empty($sub_data["IMAGE"]) )
        $img = "select image from rss_subscriptions where id=$sub_id.sql";
      else
        $img = style_img('MISSING_RSS_ART',true,false);

      echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
            <tr><td valign=top width="'.convert_x(280).'" align="center"><br>
                '.img_gen($img,280,450).'<br>';
      echo font_tags(FONTSIZE_BODY).str('RSS_LAST_UPDATED').'<br>'.date('Y-m-d H:i',strtotime($sub_data["LAST_UPDATE"])).'</td>';
      echo '    <td width="'.convert_x(20).'"></td>
                <td valign="top">';
                $menu->display_page( $page,1,520 );
      echo '    </td></table>';
    }

    // Define buttons for linked file and url.
    $buttons = array();
    $buttons[] = array('text'=>str('RSS_REFRESH'), 'url'=> 'rss_feeds.php?update_id='.$sub_id);
    page_footer( $back_url, $buttons );
  }

  /**************************************************************************************************
   Main page output
   *************************************************************************************************/

  $rss_feeds = rss_get_subscriptions();

  if ( isset($_REQUEST["update_id"]) )
  {
    page_inform(5,"rss_feeds.php?sub_id=".$_REQUEST["update_id"], str('RSS_FEEDS'), str('RSS_UPDATE'));
    // Store the parameters to the media search (rss subscription id) in the system_prefs table
    // as this is the only way of passing the info to the background process in Simese.
    clear_media_scan_prefs();
    set_sys_pref('MEDIA_SCAN_TYPE','RSS');
    set_sys_pref('MEDIA_SCAN_RSS',$_REQUEST["update_id"]);
    set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_PENDING'));

    // Call the media search in the background.
    media_refresh_now();
  }
  elseif ( !empty($_REQUEST["sub_id"]) )
  {
    display_rss_feed($_REQUEST["sub_id"]);
  }
  elseif ( count($rss_feeds) >0 )
  {
    (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
    search_picker_init( current_url() );
    page_header(str('RSS_FEEDS'));
    display_rss(url_remove_param(current_url(),'page'), $rss_feeds, $page);

    // Define buttons for linked file and url.
    $buttons = array();
    $buttons[] = array('text'=>str('RSS_REFRESH'), 'url'=> 'rss_feeds.php?update_id=0');
    page_footer('index.php?submenu=internet', $buttons);
  }
  else
  {
    page_inform(2,'index.php?submenu=internet',str('RSS_FEEDS'),str('NO_ITEMS_TO_DISPLAY'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
