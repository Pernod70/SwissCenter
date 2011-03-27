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
    $back_url = page_hist_back_url();
  }
  else
  {
    $user_id = get_user_pref('FLICKR_USERID');
    $back_url = 'index.php?submenu=internet';
    page_hist_init(current_url());
  }

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  $flickr = new phpFlickr(FLICKR_API_KEY, FLICKR_API_SECRET);
  $flickr->enableCache("db");

  // Get information about a user.
  $person = $flickr->people_getInfo($user_id);

  // Page headings
  $tagline = (!empty($user_id) ? $person["username"] : str('FLICKR_NO_USER'));
  page_header(str('FLICKR_PHOTOS'));
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('FLICKR',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  $menu = new menu();
  $menu->add_item( str('FLICKR_PHOTOSETS'), url_add_param('flickr_photosets.php', 'user_id', $user_id), true);
  $menu->add_item( str('FLICKR_FAVORITES'), url_add_param('flickr_favorites.php', 'user_id', $user_id), true);
  $menu->add_item( str('FLICKR_CONTACTS'),  url_add_param('flickr_contacts.php', 'user_id', $user_id), true);
  $menu->add_item( str('FLICKR_INTERESTINGNESS') ,'flickr_interestingness.php', true);
  if ( $user_id !== get_user_pref('FLICKR_USERID') )
    $menu->add_item( str('FLICKR_RETURN') ,'flickr_menu.php', true);

  $menu->display(1, style_value("MENU_PHOTO_WIDTH"), style_value("MENU_PHOTO_ALIGN"));

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer($back_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
