<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/videobash.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = current_url();

  $type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : 'videos';
  $cat  = isset($_REQUEST["cat"])  ? $_REQUEST["cat"]  : 'all';
  $sort = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : 'mr';
  $when = isset($_REQUEST["when"]) ? $_REQUEST["when"] : '';

  $subtitle = ucwords($cat);

  if ($sort == 'mr')
    $subtitle .= ' / '.str('MOST_RECENT');
  elseif ($sort == 'mv')
    $subtitle .= ' / '.str('MOST_VIEWED');
  elseif ($sort == 'tr')
    $subtitle .= ' / '.str('TOP_RATED');

  if ($when == '')
    $subtitle .= ' / '.str('ALL_TIME');
  elseif ($when == 'd')
    $subtitle .= ' / '.str('TODAY');
  elseif ($when == 'w')
    $subtitle .= ' / '.str('THIS_WEEK');
  elseif ($when == 'm')
    $subtitle .= ' / '.str('THIS_MONTH');

  $videobash = new VideoBash();
  $items = $videobash->getItems($type, $cat, $sort, $when);

  if ( count($items[1]) == 0 )
  {
    page_inform(2, page_hist_previous(), str('VIDEOBASH'), str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $entry_list = array();
    $playlist = array();

    // Add entries from selected feed
    foreach ($items[3] as $idx=>$item)
    {
      $url = url_add_params('videobash_selected.php', array('url'=>$items[2][$idx], 'img'=>$items[1][$idx]));
      $entry_list[] = array('thumb' => $items[1][$idx],
                            'text'  => $item,
                            'url'   => $url);

      // Playlist used for slideshow.
      $full_image = preg_replace('/_\d+x\d+/', '', $items[1][$idx]);
      $playlist[] = array('TITLE'=>$item, 'FILENAME'=>$full_image);
    }

    // Page headings
    page_header(str('VIDEOBASH'), $subtitle);

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs(current_url(), $entry_list, $page);

    // Output ABC buttons
    $buttons = array();

    // Photo slideshow
    if ($type == 'photos')
      $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => play_array_list(MEDIA_TYPE_PHOTO, $playlist));

    // Sort parameter
    if ( $sort == 'mr' )
      $buttons[] = array('text'=>str('MOST_VIEWED'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'mv')));
    elseif ( $sort == 'mv' )
      $buttons[] = array('text'=>str('TOP_RATED'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'tr')));
    elseif ( $sort == 'tr' )
      $buttons[] = array('text'=>str('MOST_RECENT'), 'url'=>url_add_params($this_url, array('hist'=>PAGE_HISTORY_REPLACE, 'sort'=>'mr')));

    // When parameter
    if ( $when == '' )
      $buttons[] = array('text' => str('THIS_MONTH'), 'url'=>url_add_params($this_url, array('page'=>0, 'when'=>'m', 'hist'=>PAGE_HISTORY_REPLACE)));
    if ( $when == 'm' )
      $buttons[] = array('text' => str('THIS_WEEK'), 'url'=>url_add_params($this_url, array('page'=>0, 'when'=>'w', 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( $when == 'w' )
      $buttons[] = array('text' => str('TODAY'), 'url'=>url_add_params($this_url, array('page'=>0, 'when'=>'t', 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( $when == 't' )
      $buttons[] = array('text' => str('ALL_TIME'), 'url'=>url_add_params($this_url, array('page'=>0, 'when'=>'', 'hist'=>PAGE_HISTORY_REPLACE)));

    // Only add the change view type button if less than 3 have been defined
    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)));

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>