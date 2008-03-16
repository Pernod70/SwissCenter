<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/rss.php'));

  function display_rss_feed($sub_id)
  {
    $menu     = new menu();
    $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
    $sub_data = rss_get_subscription($sub_id);
    $items    = rss_get_subscription_items($sub_id, 'desc');
    $synlen   = ( is_screen_hdtv() ? 1200 : 325) * 4;

    search_picker_init( current_url() );
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
            <tr><td valign=top width="'.convert_x(280).'" align="left">
                '.img_gen($img,280,550).'
                </td><td width="'.convert_x(20).'"></td>
                <td valign="top">';
                $menu->display_page( $page,1,520 );
      echo '    </td></table>';   
    }
    
    page_footer( url_remove_params(search_picker_most_recent(), array('sub_id', 'page') ));
  }
  
  /**************************************************************************************************
   Main page output
   *************************************************************************************************/
       
  $rss_feeds = rss_get_subscriptions();

  if ( isset($_REQUEST["sub_id"]) )
    display_rss_feed($_REQUEST["sub_id"]);
  elseif ( count($rss_feeds) >0 )
  {
    (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
    page_header(str('RSS_FEEDS'));
    echo '<center>'.str('SELECT_RSS_FEED').'</center><p>';
    display_rss(url_remove_param( current_url(), 'page'), $rss_feeds, $page);
    page_footer('index.php');
  }
  else
  {
    page_inform(5,'index.php',str('RSS_FEEDS'),str('NO_ITEMS_TO_DISPLAY'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
