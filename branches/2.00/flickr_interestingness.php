<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
  $flickr->enableCache("db");

  $page   = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
  $server = server_address();

  // Returns the list of interesting photos for the most recent day or a user-specified date.
  $photos = $flickr->interestingness_getList();

  if ( count($photos["photos"]["photo"]) == 0 )
  {
    page_inform(2,page_hist_previous(),str('FLICKR_INTERESTINGNESS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $photo_list = array();
    $playlist = array();
    foreach ($photos["photos"]["photo"] as $photo)
    {
      $text = (empty($photo["title"]) ? '?' : utf8_decode($photo["title"]) );
      $url  = url_add_param('flickr_photo.php', 'photo_id', $photo["id"]);
      $photo_list[] = array('thumb'=>flickr_photo_url($photo, 'm'), 'text'=>$text, 'url'=>$url);

      // Playlist used for slideshow.
      $playlist[] = array('TITLE'=>$text, 'FILENAME'=>$server.'flickr_image.php?photo_id='.$photo["id"].'&ext=.jpg');
    }

    // Page headings
    page_header(str('FLICKR_PHOTOS'), str('FLICKR_INTERESTINGNESS'));

    browse_array_thumbs(current_url(), $photo_list, $page);

    // Output ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => play_array_list(MEDIA_TYPE_PHOTO, $playlist));

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
