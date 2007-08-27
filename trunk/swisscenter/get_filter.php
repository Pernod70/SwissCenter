<?php
/**************************************************************************************************
  SwissCenter Source
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  $menu = new menu();
  $current = current_url();
  
  page_header(str('FILTER'));  
  switch ($_REQUEST["option"])
  {
    case 'viewed':
      if (empty($_REQUEST["value"]))
      {
        $menu->add_item( str('FILTER_VIEWED_NONE'), url_set_param($current,'value','none'), true);
        $menu->add_item( str('FILTER_VIEWED_PART'), url_set_param($current,'value','part'), true);
        $menu->add_item( str('FILTER_VIEWED_COMPLETE'), url_set_param($current,'value','all'), true);
      }
      else 
      {
        filter_set( str('FILTER_VIEWED'), '');
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
        $menu->add_item( str('RECENTLY_ADDED_DAYS',7), url_set_param($current,"value",7), true);
        $menu->add_item( str('RECENTLY_ADDED_DAYS',14), url_set_param($current,"value",14), true);
        $menu->add_item( str('RECENTLY_ADDED_DAYS',30), url_set_param($current,"value",30), true);
        $menu->add_item( str('RECENTLY_ADDED_DAYS',90), url_set_param($current,"value",90), true);
      }
      else 
      {
        filter_set(str('RECENTLY_ADDED'), " and media.discovered > ('".db_datestr()."' - interval $_REQUEST[value] day)" );
        header('Location: '.urldecode($_REQUEST["return"]));
      }
      break;
      
    case 'none':
      filter_set();
      header('Location: '.urldecode($_REQUEST["return"]));
      break;      
      
    default:
//      $menu->add_item( str('FILTER_VIEWED'), url_set_param($current,'option','viewed'), true);
//      $menu->add_item( str('FILTER_POPULAR'), url_set_param($current,'option','popular'), true);
      $menu->add_item( str('RECENTLY_ADDED'), url_set_param($current,'option','date'), true);
      $menu->add_item( str('REMOVE_FILTER'), url_set_param($current,'option','none'), true);
  }

  $menu->display();
  page_footer(urldecode($_REQUEST["return"]));  
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
