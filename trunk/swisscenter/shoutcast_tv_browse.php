<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/shoutcasttv.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $back_url = 'shoutcast_tv.php';
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = url_remove_params(current_url(),array('page','del'));
  $genre    = (isset($_REQUEST["genre"]) ? $_REQUEST["genre"] : '');
  $order    = (isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : 'viewers');

  // Get the list of trailers.
  $shoutcast = new SHOUTcastTV();
  $items = $shoutcast->getDirectory();

  if ( count($items) == 0 )
  {
    page_inform(2,$back_url,str('SHOUTCAST_TV'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $item_list = array();
    foreach ($items as $item)
    {
      $text = utf8_decode($item["name"]);
      $url  = play_internet_tv($item["url"]);
      $trailer_list[] = array('thumb'=>$trailer["poster"], 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('SHOUTCAST_TV'));

    browse_array(current_url(), $trailer_list, $page);

    // Make sure the "back" button goes to the correct page
    page_footer($back_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
