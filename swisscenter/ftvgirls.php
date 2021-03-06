<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  /**
   * Retrieve and parse the FTV updates page.
   *
   * @return array
   */
  function ftvgirls_items()
  {
    // Get ftvgirls updates page
    $html = file_get_contents('http://ftvgirls.com/updates.html');

    $matches = array();
    preg_match_all('/<div class="ModelContainer">.*'.
                    '<div class="ModelName"><h2>(.*)<\/h2><\/div>.*'.
                    '<div class="ModelPhoto">.*'.
                    '<a href="(.*.html)"><img class="ModelPhotoWide" src="(http:\/\/.*.jpg)".*'.
                    '<\/div><!-- Model -->/Ums',$html,$matches);

    $items = array();
    for ($i=0; $i<=count($matches[0])-1; $i++)
    {
      $items[] = array("text"  => trim(html_entity_decode($matches[1][$i])),
                       "url"   => url_add_param('ftvgirls_selected.php', 'id', urlencode($matches[2][$i])),
                       "thumb" => $matches[3][$i]);
    }
    return $items;
  }

  /**
   * Generate a playlist of images
   *
   * @return array
   */
  function ftvgirls_playlist()
  {
    // Get ftvgirls superpics page
    $html = file_get_contents('http://ftvgirls.com/super.html');

    $matches = array();
    preg_match_all('/src="(.*images\/super.*.jpg)"/U',$html,$matches);

    $items = array();
    for ($i=0; $i<=count($matches[0])-1; $i++)
    {
      if ($matches[1][$i] !== 'super/menu.jpg')
        $items[] = array("FILENAME" => $matches[1][$i],
                         "TITLE"    => file_noext(basename($matches[1][$i])));
    }
    return $items;
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = current_url();

  $items = ftvgirls_items();

  if ( count($items) == 0 )
  {
    page_inform(2, page_hist_previous(), str('FTV_GIRLS'), str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    // Page headings
    page_header(str('FTV_GIRLS'));

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    browse_array_thumbs($this_url, $items, $page);

    // Display ABC buttons
    $buttons = array();
    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL', 'hist'=>PAGE_HISTORY_REPLACE)) );
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE', 'hist'=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => play_array_list(MEDIA_TYPE_PHOTO, ftvgirls_playlist()));

    // Make sure the "back" button goes to the correct page
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
