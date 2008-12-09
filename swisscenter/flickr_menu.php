<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Get Flickr UserID of current user
  if ( isset($_REQUEST["user_id"]) )
  {
    $user_id = $_REQUEST["user_id"];
    // Update page history
    $back_url = flickr_page_params();
  }
  else
  {
    $user_id = get_user_pref('FLICKR_USERID');
    $back_url = 'index.php?submenu=internet';
    flickr_hist_init('flickr_menu.php');
  }

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  $flickr = new phpFlickr(FLICKR_API_KEY, FLICKR_API_SECRET);
  $flickr->enableCache("db");

  // Get information about a user.
  $person = $flickr->people_getInfo($user_id);

  // Page headings
  $tagline = (!empty($user_id) ? $person["username"] : str('FLICKR_NO_USER'));
  page_header(str('FLICKR_PHOTOS'), $tagline,'',1,false,'', MEDIA_TYPE_PHOTO);

  $menu = new menu();
  $menu->add_item( str('FLICKR_PHOTOSETS'), url_add_param('flickr_photosets.php', 'user_id', $user_id), true);
  $menu->add_item( str('FLICKR_FAVORITES'), url_add_param('flickr_favorites.php', 'user_id', $user_id), true);
  $menu->add_item( str('FLICKR_CONTACTS'),  url_add_param('flickr_contacts.php', 'user_id', $user_id), true);
  $menu->add_item( str('FLICKR_INTERESTINGNESS') ,'flickr_interestingness.php', true);
  if ( $user_id !== get_user_pref('FLICKR_USERID') )
    $menu->add_item( str('FLICKR_RETURN') ,'flickr_menu.php', true);

  $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));

  // Make sure the "back" button goes to the correct page:
  page_footer($back_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
