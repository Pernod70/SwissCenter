<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/youporn.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $back_url = page_hist_back_url();
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = current_url();

  $type = $_REQUEST["type"];
  $url  = ( $type == 'category' ? $_REQUEST["cat"] : $_REQUEST["type"] );
  $time = isset($_REQUEST["time"]) ? $_REQUEST["time"] : '';
  $sort = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : '';
  if ( $type == 'category' )
    $subtitle = ucfirst(preg_get('/\d+\/(.*)\//',$_REQUEST["cat"]));
  elseif ( $type == 'browse' )
    $subtitle = str('NEW_VIDEOS');
  else
    $subtitle = str(strtoupper($_REQUEST["type"]));

  $youporn = new YouPorn();
  $youporn_url = '/'.$url.(empty($time) ? '' : '/'.$time).(empty($sort) ? '' : '/'.$sort);
  $items = $youporn->getItems($youporn_url);

  if ( count($items[0]) == 0 )
  {
    page_inform(2, $back_url, str('YOUPORN'), str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $entry_list = array();

    // Add entries from selected feed
    foreach ($items[3] as $idx=>$item)
    {
      $url = url_add_params('youporn_video_selected.php', array('url'=>$items[2][$idx], 'img'=>$items[1][$idx]));
      $entry_list[] = array('thumb' => $items[1][$idx],
                            'text'  => utf8_decode($item),
                            'url'   => $url);
    }

    // Page headings
    page_header(str('YOUPORN'), $subtitle);

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $entry_list, $page);

    // Output ABC buttons
    $buttons = array();

    // Sort parameter
    if ( in_array($type, array('browse', 'category')) )
    {
      if ( $sort == 'hybrid' )
        $buttons[] = array('text'=>str('SORT_VIEWS'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'views')));
      elseif ( $sort == 'views' )
        $buttons[] = array('text'=>str('SORT_RATING'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'rating')));
      elseif ( $sort == 'rating' )
        $buttons[] = array('text'=>str('SORT_DURATION'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'duration' )));
      elseif ( $sort == 'duration' )
        $buttons[] = array('text'=>str('SORT_DATE'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'time')));
      elseif ( $sort == 'time' )
        $buttons[] = array('text'=>str('SORT_NAME'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'title')));
      elseif ( $sort == 'title' )
        $buttons[] = array('text'=>str('SORT_HYBRID'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'hybrid')));
    }

    // Time parameter
    if ( in_array($type, array('top_rated', 'most_viewed')) )
    {
      if ( $time == 'today' )
        $buttons[] = array('text' => str('YESTERDAY'), 'url'=>url_add_params($this_url, array('time'=>'yesterday', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'yesterday' )
        $buttons[] = array('text' => str('THIS_WEEK'), 'url'=>url_add_params($this_url, array('time'=>'this_week', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'this_week' || $time == '' )
        $buttons[] = array('text' => str('THIS_MONTH'), 'url'=>url_add_params($this_url, array('time'=>'this_month', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'this_month' )
        $buttons[] = array('text' => str('THIS_YEAR'), 'url'=>url_add_params($this_url, array('time'=>'this_year', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'this_year' )
        $buttons[] = array('text' => str('ALL_TIME'), 'url'=>url_add_params($this_url, array('time'=>'all_time', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'all_time' )
        $buttons[] = array('text' => str('TODAY'), 'url'=>url_add_params($this_url, array('time'=>'today', 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    // Only add the change view type button if less than 3 have been defined
    if ( count($buttons) < 3 )
    {
      if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
        $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
        $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)));
    }

    // Make sure the "back" button goes to the correct page:
    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
