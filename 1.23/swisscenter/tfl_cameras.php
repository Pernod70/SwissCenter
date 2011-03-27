<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/tfl_feeds.php'));

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  if ( isset($_REQUEST["full"]) )
  {
    page_header( '', '', '', 1, true, '', $_REQUEST["full"] );

    // Make sure the "back" button goes to the correct page:
    page_footer(url_remove_param(current_url(), 'full'));
  }
  else
  {
    // Update page history
    $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
    $this_url = current_url();

    // Page headings
    page_header( str('TFL'), str('TFL_LIVE_TRAFFIC_CAMERA') );

    // Get the feed from Tfl
    $tfl = new Tfl();
    $feed = $tfl->getFeed(3);

    $items = array();
    for ($i=0; $i<=count($feed['CAMERAS'])-1; $i++)
    {
      $items[] = array("name"  => $feed['CAMERAS'][$i]['TITLE'],
                       "url"   => url_add_param($this_url, 'full', $feed['CAMERAS'][$i]['URL']));
    }
    array_sort($items,'name');

    browse_array($this_url, $items, $page);

    // Make sure the "back" button goes to the correct page
    page_footer('tfl.php');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
