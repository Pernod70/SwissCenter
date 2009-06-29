<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/youtube.php'));

  function display_regions($next_page)
  {
    echo '<p>';

    $youtube = new phpYouTube();
    $regions = $youtube->getRegions();
//    $regions    = array_merge( array(str($categories, $special);

    $page       = (isset($_REQUEST["cat_page"]) ? $_REQUEST["cat_page"] : 1);
    $start      = ($page-1) * MAX_PER_PAGE;
    $end        = min($start+MAX_PER_PAGE,count($regions));
    $last_page  = ceil(count($regions)/MAX_PER_PAGE);

    $menu = new menu();

    if (count($regions) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param(current_url(),'page',($page > 1 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param(current_url(),'page',($page < $last_page ? ($page+1) : 1)) );
    }

    for ($i=$start; $i<$end; $i++)
    {
      $menu->add_item($regions[$i]["CAT_NAME"], $next_page."?cat=".$regions[$i]["CAT_ID"], true);
    }

    $menu->display();

    // Make sure the "back" button goes to the correct page:
    page_footer( $back_url );
  }

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  // Get YouTube username of current user
  $username = get_user_pref('YOUTUBE_USERNAME');

  youtube_hist_init('youtube_menu.php');

  // If COMPACT mode was last used then set to FULL as downloading 12 images per page takes too long!
  if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" ) { set_user_pref("DISPLAY_THUMBS","FULL"); }

  $youtube = new phpYouTube();
  $profile = $youtube->entryUserProfile($username);

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

  $general_menu  = new menu();
  $personal_menu = new menu();

  $general_menu->add_item( str('VIDEOS'),   url_add_params('youtube_browse.php', array('type'=>'most_viewed', 'order'=>'views')), true);
  $general_menu->add_item( str('CHANNELS'), url_add_params('youtube_browse.php', array('type'=>'channels', 'order'=>'rating')), true);

  // Display the page
  page_header(str('YOUTUBE'));
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('YOUTUBE_LOGO',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';
  echo        '<center>'.font_tags(32).str('YOUTUBE_PROMPT_GENERAL').'</center>';
  $general_menu->display( 1, 520 );

  // Only display the personal options if a username is available
  if (!empty($username))
  {
    $personal_menu->add_item( str('FAVORITES').' ('.$num_favourites.')',        url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'favorites')), true );
    $personal_menu->add_item( str('PLAYLISTS'),                                 url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'playlists')), true );
    $personal_menu->add_item( str('SUBSCRIPTIONS').' ('.$num_subscriptions.')', url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'subscriptions')), true );
//    $personal_menu->add_item( str('CONTACTS').' ('.$num_contacts.')',           url_add_params('youtube_browse.php', array('username'=>$username, 'type'=>'contacts')), true );
    echo '<center>'.font_tags(32).str('YOUTUBE_PROMPT_PERSONAL').'</center>';
    $personal_menu->display( $general_menu->num_items()+1, 520 );
  }

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer('internet_tv.php');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
