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

  $flickr = new phpFlickr(FLICKR_API_KEY, FLICKR_API_SECRET);
  $flickr->enableCache("db");

  $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

  // Get Flickr UserID of current user
  $user_id = (isset($_REQUEST["user_id"]) ? $_REQUEST["user_id"] : get_user_pref('FLICKR_USERID'));

  // Get the contact list for a user.
  $contacts = $flickr->contacts_getPublicList($user_id);
  if ( count($contacts["contact"]) == 0 )
  {
    page_inform(2,page_hist_previous(),str('FLICKR_CONTACTS'),str('NO_ITEMS_TO_DISPLAY'));
  }
  else
  {
    // Get information about a user.
    $person = $flickr->people_getInfo($user_id);

    $contact_list = array();
    foreach ($contacts["contact"] as $contact)
      $contact_list[] = array('thumb'=>flickr_buddy_icon($contact), 'text'=>utf8_decode($contact["username"]), 'url'=>url_add_param('flickr_menu.php', 'user_id', $contact["nsid"]) );

    // Page headings
    page_header(str('FLICKR_PHOTOS'), utf8_decode($person["username"]).' : '.str('FLICKR_CONTACTS'));

    browse_array_thumbs(current_url(), $contact_list, $page);

    // Make sure the "back" button goes to the correct page:
    page_footer(page_hist_previous());
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
