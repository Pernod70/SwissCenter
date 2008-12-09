<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
  $flickr->enableCache("db");

  // Update page history
  $back_url = flickr_page_params();
  $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

  // Get Flickr UserID of current user
  $user_id = (isset($_REQUEST["user_id"]) ? $_REQUEST["user_id"] : get_user_pref('FLICKR_USERID'));

  // Get a list of favorite public photos for the given user.
  $photos = $flickr->favorites_getPublicList($user_id);

  if ( count($photos["photo"]) == 0 )
  {
    page_inform(2,$back_url,str('FLICKR_FAVORITES'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    // Get information about a user.
    $person = $flickr->people_getInfo($user_id);

    $photo_list = array();
    foreach ($photos["photo"] as $photo)
    {
      $text = (empty($photo["title"]) ? '?' : utf8_decode($photo["title"]) );
      $url  = url_add_param('flickr_photo.php', 'photo_id', $photo["id"]);
      $photo_list[] = array('thumb'=>flickr_photo_url($photo, 'm'), 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('FLICKR_PHOTOS'), utf8_decode($person["username"]).' : '.str('FLICKR_FAVORITES'));

    browse_array_thumbs(url_add_param(current_url(), 'del', 1), $photo_list, $page);

    // Output ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => flickr_slideshow('favorites', $user_id));

    // Make sure the "back" button goes to the correct page:
    page_footer($back_url, $buttons);
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
