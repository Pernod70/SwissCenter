<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/youtube.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Get YouTube username of current user
  $username = get_user_pref('YOUTUBE_USERNAME');

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  $region   = isset($_REQUEST["region"]) ? $_REQUEST["region"] : '';
  $category = isset($_REQUEST["category"]) ? $_REQUEST["category"] : '';

  $general_menu  = new menu();
  $general_menu->add_item( str('VIDEOS'),   url_add_params('youtube_browse.php', array('type'=>'most_viewed', 'order'=>'views', 'cat'=>$category, 'region'=>$region)), true);
  $general_menu->add_item( str('CHANNELS'), url_add_params('youtube_browse.php', array('type'=>'channels', 'order'=>'rating')), true);
  $general_menu->add_item( str('SEARCH'),   url_add_params('youtube_search.php', array('type'=>'videos')), true);

  // Display the page
  page_header(str('YOUTUBE'));
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('YOUTUBE',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';
  echo        '<center>'.font_tags(FONTSIZE_SUBHEADER).str('YOUTUBE_PROMPT_GENERAL').'</center>';
  $general_menu->display( 1, 520 );

  // Only display the personal options if a username is available
  if (!empty($username))
  {
    $youtube  = new phpYouTube();
    $profile  = $youtube->entryUserProfile($username);

    foreach ($profile['entry']['gd$feedLink'] as $feedlink)
    {
      switch ($feedlink['rel'])
      {
        case 'http://gdata.youtube.com/schemas/2007#user.favorites':
          $num_favourites = isset($feedlink['countHint']) ? $feedlink['countHint'] : 0;
          break;
        case 'http://gdata.youtube.com/schemas/2007#user.playlists':
          $num_playlists = isset($feedlink['countHint']) ? $feedlink['countHint'] : 0;
          break;
        case 'http://gdata.youtube.com/schemas/2007#user.subscriptions':
          $num_subscriptions = isset($feedlink['countHint']) ? $feedlink['countHint'] : 0;
          break;
        case 'http://gdata.youtube.com/schemas/2007#user.contacts':
          $num_contacts = isset($feedlink['countHint']) ? $feedlink['countHint'] : 0;
          break;
      }
    }

    $personal_menu = new menu();
    $personal_menu->add_item( str('FAVORITES').' ('.$num_favourites.')',        url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'favorites')), true );
    $personal_menu->add_item( str('PLAYLISTS'),                                 url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'playlists')), true );
    $personal_menu->add_item( str('SUBSCRIPTIONS').' ('.$num_subscriptions.')', url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'subscriptions')), true );
//    $personal_menu->add_item( str('CONTACTS').' ('.$num_contacts.')',           url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'contacts')), true );
    echo '<center>'.font_tags(FONTSIZE_SUBHEADER).str('YOUTUBE_PROMPT_PERSONAL').'</center>';
    $personal_menu->display( $general_menu->num_items()+1, 520 );
  }

  echo '    </td>
          </tr>
        </table>';

  // Output ABC buttons
  $buttons = array();
//  $buttons[] = array('text' => str('SELECT_REGION'), 'url' => 'youtube_regions.php');

  // Make sure the "back" button goes to the correct page:
  page_footer('internet_tv.php', $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
