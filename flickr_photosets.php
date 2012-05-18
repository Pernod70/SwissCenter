<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  function display_flickr_photoset( $photoset_id )
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    $page   = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);
    $server = server_address();

    // Gets information about a photoset.
    $photoset = $flickr->photosets_getInfo($photoset_id);

    // Get list of photos for selected photoset
    $photos = $flickr->photosets_getPhotos($photoset_id);

    // Get information about a user.
    $person = $flickr->people_getInfo($photoset["owner"]);

    $photo_list = array();
    $playlist = array();
    foreach ($photos["photoset"]["photo"] as $photo)
    {
      $text = (empty($photo["title"]) ? '?' : $photo["title"] );
      $url  = url_add_param('flickr_photo.php', 'photo_id', $photo["id"]);
      $photo_list[] = array('thumb'=>flickr_photo_url($photo, 'm'), 'text'=>$text, 'url'=>$url);

      // Playlist used for slideshow.
      $playlist[] = array('TITLE'=>$text, 'FILENAME'=>$server.'flickr_image.php?photo_id='.$photo["id"].'&ext=.jpg');
    }

    // Page headings
    page_header(str('FLICKR_PHOTOS'), $person["username"].' : '.str('FLICKR_PHOTOSETS').' : '.$photoset["title"]);
    $shortlen = $_SESSION["device"]["browser_x_res"];
    echo shorten(font_tags(FONTSIZE_BODY).$photoset["description"], $shortlen, 1, FONTSIZE_BODY);

    browse_array_thumbs(current_url(), $photo_list, $page);

    // Output ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('START_SLIDESHOW'),'url' => play_array_list(MEDIA_TYPE_PHOTO, $playlist));

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous(), $buttons);
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
    page_inform(2,page_hist_previous(),str('FLICKR_PHOTOSETS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

    // Get information about a user.
    $person = $flickr->people_getInfo($user_id);

    $photoset_list = array();
    foreach ($photosets["photoset"] as $photoset)
    {
      $text = $photoset["title"].' ('.$photoset["photos"].')';
      $url  = url_add_param('flickr_photosets.php', 'photoset_id', $photoset["id"]);
      $photoset_list[] = array('thumb'=>flickr_photo_url($photoset, 'm'), 'text'=>$text, 'url'=>$url);
    }

    // Page headings
    page_header(str('FLICKR_PHOTOS'), $person["username"].' : '.str('FLICKR_PHOTOSETS'));

    browse_array_thumbs(current_url(), $photoset_list, $page);

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous());
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
