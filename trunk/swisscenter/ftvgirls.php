<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
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
    preg_match_all('/<img border="0" src="(mod.*)".*<a href="(mod.*)">(.*)<\/a><\/font>/Us',$html,$matches);

    $items = array();
    for ($i=0; $i<=count($matches[0])-1; $i++)
    {
      $items[] = array("text"  => trim(html_entity_decode($matches[3][$i])),
                       "url"   => url_add_param('ftvgirls_selected.php', 'id', urlencode($matches[2][$i])),
                       "thumb" => 'http://ftvgirls.com/'.$matches[1][$i]);
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
    preg_match_all('/src="(super2.*)"/U',$html,$matches);

    $items = array();
    for ($i=0; $i<=count($matches[0])-1; $i++)
    {
      if ($matches[1][$i] !== 'super2/menu.jpg')
        $items[] = array("FILENAME" => 'http://ftvgirls.com/'.$matches[1][$i],
                         "TITLE"    => file_noext(basename($matches[1][$i])));
    }
    return $items;
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Update page history
  $back_url = 'internet_tv.php';
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $this_url = current_url();
  search_hist_init($this_url);

  $items = ftvgirls_items();

  if ( count($items) == 0 )
  {
    page_inform(2,$back_url,str('FTV_GIRLS'),str('NO_ITEMS_TO_DISPLAY'));
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
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page/2), 'thumbs'=>'FULL')) );
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($this_url, array('page'=>floor($page*2), 'thumbs'=>'LARGE')) );
    $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => play_array_list(MEDIA_TYPE_PHOTO, ftvgirls_playlist()));

    // Make sure the "back" button goes to the correct page
    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
