<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  function display_flickr_photoset( $photoset_id )
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    // Update page history
    $back_url = flickr_page_params();
    $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

    // Gets information about a photoset.
    $photoset = $flickr->photosets_getInfo($photoset_id);

    // Get list of photos for selected photoset
    $photos = $flickr->photosets_getPhotos($photoset_id);

    // Get information about a user.
    $person = $flickr->people_getInfo($photoset["owner"]);

    $photo_list = array();
    foreach ($photos["photo"] as $photo)
    {
      $text = (empty($photo["title"]) ? '?' : utf8_decode($photo["title"]) );
      $url  = url_add_param('flickr_photo.php', 'photo_id', $photo["id"]);
      $photo_list[] = array('thumb'=>flickr_photo_url($photo, 'm'), 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('FLICKR_PHOTOS'), utf8_decode($person["username"]).' : '.str('FLICKR_PHOTOSETS').' : '.utf8_decode($photoset["title"]));
    $shortlen = $_SESSION["device"]["browser_x_res"];
    echo shorten(font_tags(32).utf8_decode($photoset["description"]), $shortlen, 1, 32);

    browse_array_thumbs(url_add_param(current_url(), 'del', 1), $photo_list, $page);

    // Output ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => flickr_slideshow('photoset', $photoset_id));

    // Make sure the "back" button goes to the correct page:
    page_footer($back_url, $buttons);
  }

/**************************************************************************************************
   Main page output
 *************************************************************************************************/
    
  $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
  $flickr->enableCache("db");

  // Get Flickr UserID of current user
  $user_id = (isset($_REQUEST["user_id"]) ? $_REQUEST["user_id"] : get_user_pref('FLICKR_USERID'));

  // Returns the photosets belonging to the specified user.
  $photosets = $flickr->photosets_getList($user_id);

  if ( isset($_REQUEST["photoset_id"]) )
  {
    display_flickr_photoset( $_REQUEST["photoset_id"] );
  }
  elseif ( count($photosets["photoset"]) == 0 )
  {
    // Update page history
    $back_url = flickr_page_params();

    page_inform(2,$back_url,str('FLICKR_PHOTOSETS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    // Update page history
    $back_url = flickr_page_params();
    $page     = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

    // Get information about a user.
    $person = $flickr->people_getInfo($user_id);

    $photoset_list = array();
    foreach ($photosets["photoset"] as $photoset)
    {
      $text = utf8_decode($photoset["title"]).' ('.$photoset["photos"].')';
      $url  = url_add_param('flickr_photosets.php', 'photoset_id', $photoset["id"]);
      $photoset_list[] = array('thumb'=>flickr_photo_url($photoset, 'm'), 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('FLICKR_PHOTOS'), utf8_decode($person["username"]).' : '.str('FLICKR_PHOTOSETS'));

    browse_array_thumbs(url_add_param(current_url(), 'del', 1), $photoset_list, $page);

    // Make sure the "back" button goes to the correct page:
    page_footer($back_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
