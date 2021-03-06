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
    page_inform(2, page_hist_previous(), str('YOUPORN'), str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $entry_list = array();

    // Add entries from selected feed
    foreach ($items[3] as $idx=>$item)
    {
      $url = url_add_params('youporn_video_selected.php', array('url'=>rawurlencode($items[1][$idx]), 'img'=>rawurlencode($items[2][$idx])));
      $entry_list[] = array('thumb' => $items[2][$idx],
                            'text'  => $item,
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
      if ( $sort == 'time' )
        $buttons[] = array('text'=>str('SORT_VIEWS'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'views')));
      elseif ( $sort == 'views' )
        $buttons[] = array('text'=>str('SORT_RATING'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'rating')));
      elseif ( $sort == 'rating' )
        $buttons[] = array('text'=>str('SORT_DURATION'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'duration' )));
      elseif ( $sort == 'duration' )
        $buttons[] = array('text'=>str('SORT_DATE'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'time')));
    }

    // Time parameter
    if ( in_array($type, array('top_rated', 'most_viewed')) )
    {
      if ( $time == 'today' )
        $buttons[] = array('text' => str('YESTERDAY'), 'url'=>url_add_params($this_url, array('time'=>'yesterday', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'yesterday' )
        $buttons[] = array('text' => str('THIS_WEEK'), 'url'=>url_add_params($this_url, array('time'=>'week', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'week' || $time == '' )
        $buttons[] = array('text' => str('THIS_MONTH'), 'url'=>url_add_params($this_url, array('time'=>'month', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'month' )
        $buttons[] = array('text' => str('THIS_YEAR'), 'url'=>url_add_params($this_url, array('time'=>'year', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'year' )
        $buttons[] = array('text' => str('ALL_TIME'), 'url'=>url_add_params($this_url, array('time'=>'all', 'hist'=>PAGE_HISTORY_REPLACE)));
      elseif ( $time == 'all' )
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
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
