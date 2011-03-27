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
        $menu->add_item( str('FILTER_VIEWED_NONE'),        url_set_param($current,'value','none'), true);
        $menu->add_item( str('FILTER_VIEWED_NOTFINISHED'), url_set_param($current,'value','notcomplete'), true);
        $menu->add_item( str('FILTER_VIEWED_PLAYED'),      url_set_param($current,'value','started'), true);
        $menu->add_item( str('FILTER_VIEWED_FINISHED'),    url_set_param($current,'value','complete'), true);
      }
      else
      {
        filter_set("viewed:".$_REQUEST["value"]);
        header('Location: '.urldecode($_REQUEST["return"]));
      }
      break;

    case 'popular':
      if (empty($_REQUEST["value"]))
      {
        $menu->add_item( str('FILTER_MOST_POPULAR'), url_set_param($current,"value",'most'), true);
        $menu->add_item( str('FILTER_LEAST_POPULAR'), url_set_param($current,"value",'least'), true);
      }
      else
      {
        filter_set();
        $_SESSION["filter"] = "$_REQUEST[option] : $_REQUEST[value]";
        header('Location: '.urldecode($_REQUEST["return"]));
      }
      break;

    case 'date':
      if (empty($_REQUEST["value"]))
      {
        foreach (explode(',',get_sys_pref("RECENTLY_ADDED_DAYS",'7,14,30,90')) as $val)
          $menu->add_item( str('RECENTLY_ADDED_DAYS',$val), url_set_param($current,"value",$val), true);
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
        header('Location: '.urldecode($_REQUEST["return"]));
      }
      break;

    case 'none':
      filter_set();
      header('Location: '.urldecode($_REQUEST["return"]));
      break;

    default:
      $menu->add_item( str('FILTER_VIEWED'), url_set_param($current,'option','viewed'), true);
//      $menu->add_item( str('FILTER_POPULAR'), url_set_param($current,'option','popular'), true);
      $menu->add_item( str('RECENTLY_'.get_sys_pref("RECENT_DATE_TYPE", "ADDED")), url_set_param($current,'option','date'), true);
      $menu->add_item( str('REMOVE_FILTER'), url_set_param($current,'option','none'), true);
  }

  $menu->display();
  page_footer(urldecode($_REQUEST["return"]));

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
