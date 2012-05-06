<?php
/**************************************************************************************************
  SwissCenter Source
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  $menu = new menu();
  $current = current_url();

  page_header( filter_text());
  switch ($_REQUEST["option"])
  {
    case 'viewed':
      if (empty($_REQUEST["value"]))
      {
        $menu->add_item( str('FILTER_VIEWED_NONE'),        url_set_param($current,'value','none'));
        $menu->add_item( str('FILTER_VIEWED_NOTFINISHED'), url_set_param($current,'value','notcomplete'));
        $menu->add_item( str('FILTER_VIEWED_PLAYED'),      url_set_param($current,'value','started'));
        $menu->add_item( str('FILTER_VIEWED_FINISHED'),    url_set_param($current,'value','complete'));
      }
      else
      {
        filter_set("viewed:".$_REQUEST["value"]);
        // Remove filter pages from the history stack
        page_hist_pop();
        page_hist_pop();
        header('Location: '.page_hist_previous());
      }
      break;

    case 'popular':
      if (empty($_REQUEST["value"]))
      {
        $menu->add_item( str('FILTER_MOST_POPULAR'), url_set_param($current,"value",'most'));
        $menu->add_item( str('FILTER_LEAST_POPULAR'), url_set_param($current,"value",'least'));
      }
      else
      {
        filter_set("popular:".$_REQUEST["value"]);
        // Remove filter pages from the history stack
        page_hist_pop();
        page_hist_pop();
        header('Location: '.page_hist_previous());
      }
      break;

    case 'date':
      if (empty($_REQUEST["value"]))
      {
        foreach (explode(',',get_sys_pref("RECENTLY_ADDED_DAYS",'7,14,30,90')) as $val)
          $menu->add_item( str('RECENTLY_ADDED_DAYS',$val), url_set_param($current,"value",$val));
      }
      else
      {
        switch ( get_sys_pref("RECENT_DATE_TYPE", "ADDED") )
        {
          case 'ADDED':
            filter_set(str('RECENTLY_ADDED'), " and media.discovered > ('".db_datestr()."' - interval $_REQUEST[value] day)" );
            break;

          case 'CREATED':
            filter_set(str('RECENTLY_CREATED'), " and media.timestamp > ('".db_datestr()."' - interval $_REQUEST[value] day)" );
            break;

          case 'ADDED_OR_CREATED':
            filter_set(str('RECENTLY_ADDED_OR_CREATED'), " and (media.discovered > ('".db_datestr()."' - interval $_REQUEST[value] day) or ".
                                                              " media.timestamp > ('".db_datestr()."' - interval $_REQUEST[value] day))" );
            break;
        }
        // Remove filter pages from the history stack
        page_hist_pop();
        page_hist_pop();
        header('Location: '.page_hist_previous());
      }
      break;

    case 'none':
      filter_set();
      // Remove filter page from the history stack
      page_hist_pop();
      header('Location: '.page_hist_previous());
      break;

    default:
      $menu->add_item( str('FILTER_VIEWED'), url_set_param($current,'option','viewed'), true);
//      $menu->add_item( str('FILTER_POPULAR'), url_set_param($current,'option','popular'), true);
      $menu->add_item( str('RECENTLY_'.get_sys_pref("RECENT_DATE_TYPE", "ADDED")), url_set_param($current,'option','date'), true);
      $menu->add_item( str('REMOVE_FILTER'), url_set_param($current,'option','none'));
  }

  $menu->display();
  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
