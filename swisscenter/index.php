<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/patching.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  function media_exists( $media_type )
  {
    $table = db_value("select media_table from media_types where media_id = $media_type");
    if ( db_value("select 'YES' from $table t, media_locations ml where t.location_id=ml.location_id and ml.media_type=$media_type limit 1") == 'YES')
      return true;
    else
      return false;
  }

  /**
   * Display the Internet Services submenu
   */
  function display_internet_menu()
  {
    page_header( str('INTERNET_SERVICES'),'','',1,false,'','PAGE_INTERNET');

    /**
     * Dertermine whether images are defined for this style.
     */
    $image_menu = style_value('MENU_INTERNET_RADIO',false) && style_value('MENU_INTERNET_BROWSE',false) && style_value('MENU_INTERNET_RSS',false) &&
                  style_value('MENU_INTERNET_FLICKR',false) && style_value('MENU_INTERNET_WEATHER',false);

    /**
     * Menu Items
     */
    $menu = new menu();
    $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);

    // Only display the Internet radio options if an internet connection is active and the user has enabled internet radio support
    if (internet_available() && get_sys_pref('radio_enabled','YES') == 'YES')
      if ($image_menu)
        $menu->add_image_item( str('LISTEN_RADIO'),style_img('MENU_INTERNET_RADIO',true),style_img('MENU_INTERNET_RADIO_ON',true,false),'music_radio.php');
      else
        $menu->add_item( str('LISTEN_RADIO'),"music_radio.php",true);

    // Only display the internet tv options if an internet connection is active and the user has enabled internet tv support
    if (internet_available() && get_sys_pref('internet_tv_enabled','YES') == 'YES')
      if ($image_menu)
        $menu->add_image_item( str('WATCH_INTERNET_TV'),style_img('MENU_INTERNET_TV',true),style_img('MENU_INTERNET_TV_ON',true,false),'internet_tv.php');
      else
        $menu->add_item( str('WATCH_INTERNET_TV'),"internet_tv.php",true);

    // Only display the web links option if an internet connection is active, the user has enabled weblinks and defined some urls
    if (internet_available() && get_sys_pref('web_enabled','YES') == 'YES'
         && db_value("select 'YES' from internet_urls where type=".MEDIA_TYPE_WEB." limit 1") == 'YES')
      if ($image_menu)
        $menu->add_image_item(str('BROWSE_WEB'),style_img('MENU_INTERNET_BROWSE',true),style_img('MENU_INTERNET_BROWSE_ON',true,false),'web_urls.php');
      else
        $menu->add_item(str('BROWSE_WEB'),'web_urls.php',true);

    // Only display the flickr option if an internet connection is active, the user has enabled flickr support.
    if (internet_available() && get_sys_pref('flickr_enabled','YES') == 'YES' )
      if ($image_menu)
        $menu->add_image_item(str('FLICKR_PHOTOS'),style_img('MENU_INTERNET_FLICKR',true),style_img('MENU_INTERNET_FLICKR_ON',true,false),'flickr_menu.php');
      else
        $menu->add_item( str('FLICKR_PHOTOS') ,'flickr_menu.php',true);

    // Only display the RSS options if an internet connection is active, the user has enabled RSS support and has defined some subscriptions.
    if (internet_available() && get_sys_pref('rss_enabled','YES') == 'YES' && db_value("select 'YES' from rss_subscriptions limit 1") == 'YES')
      if ($image_menu)
        $menu->add_image_item( str('RSS_FEEDS') ,style_img('MENU_INTERNET_RSS',true),style_img('MENU_INTERNET_RSS_ON',true,false),'rss_feeds.php');
      else
        $menu->add_item( str('RSS_FEEDS') ,'rss_feeds.php',true);

    // Only display the weather options if an internet connection is active and the user has enabled weather support
    if (internet_available() && get_sys_pref('weather_enabled','YES') == 'YES')
      if ($image_menu)
        $menu->add_image_item( str('VIEW_WEATHER') ,style_img('MENU_INTERNET_WEATHER',true),style_img('MENU_INTERNET_WEATHER_ON',true,false),'weather_cc.php');
      else
        $menu->add_item( str('VIEW_WEATHER') ,'weather_cc.php',true);

    /**
    * Display the page content
    */
    if ($menu->num_items() == 1)
      header('Location: '.server_address().$menu->item_url(0));
    else
      if ($image_menu)
        $menu->display_images(1, style_value("MENU_INTERNET_WIDTH"), style_value("MENU_INTERNET_ALIGN"));
      else
        $menu->display(1, style_value("MENU_INTERNET_WIDTH"), style_value("MENU_INTERNET_ALIGN"));

    page_footer('index.php');
  }

  /**
   * Submenu selected?
   */
  if( isset($_REQUEST["submenu"]) && $_REQUEST["submenu"] == 'internet' )
    display_internet_menu();
  else
  {
    /**
     * Check for and apply any oustanding database patches.
     */

    apply_database_patches();

    /**
     * Automatically load playlist?
     */

    if ( !isset($_SESSION["playlist_autoloaded"]) && get_sys_pref('PLAYLIST_AUTOLOAD','')!=='' )
    {
      $fsp    = get_sys_pref('PLAYLIST_AUTOLOAD');
      $name   = file_noext(basename($fsp));
      $tracks = load_pl($fsp, $failed);
      set_current_playlist($name, $tracks);
      $_SESSION["playlist_autoloaded"] = 'yes';
    }

    /**
     * Reset the filter.
     */

    filter_set();

    /**
     * Output the page header immediately, as it performs the authentication check and will redirect
     * the user to the "change_user" screen if there are multiple users to choose from or a password
     * is required.
     * If an image menu and there are any missing onFocus images then use FocusColor.
     */

    page_header( str('MAIN_MENU'),'','',1,false,'','PAGE_INDEX');

    /**
     * Dertermine whether images are defined for this style.
     */
    $image_menu = style_value('MENU_INDEX_VIDEO',false) && style_value('MENU_INDEX_TV',false) && style_value('MENU_INDEX_MUSIC',false) &&
                  style_value('MENU_INDEX_PHOTO',false) && style_value('MENU_INDEX_INTERNET',false) && style_value('MENU_INDEX_PLAYLIST',false) &&
                  style_value('MENU_INDEX_CONFIG',false);

    /**
     * Menu Items
     */

    $menu = new menu();
    $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);

    // Only display the video options if the user has some videos stored in the database.
    if ( media_exists(MEDIA_TYPE_VIDEO))
      if ($image_menu)
        $menu->add_image_item(str('WATCH_MOVIE'),style_img('MENU_INDEX_VIDEO',true),style_img('MENU_INDEX_VIDEO_ON',true,false),'video.php');
      else
        $menu->add_item(str('WATCH_MOVIE'),'video.php',true);

    // Only display the TV Series options if the user has some TV episodes stored in the database.
    if ( media_exists(MEDIA_TYPE_TV))
      if ($image_menu)
        $menu->add_image_item(str('WATCH_TV'),style_img('MENU_INDEX_TV',true),style_img('MENU_INDEX_TV_ON',true,false),'tv.php');
      else
        $menu->add_item(str('WATCH_TV'),'tv.php',true);

    // Only display the music options if the user has some music stored in the database.
    if ( media_exists(MEDIA_TYPE_MUSIC))
      if ($image_menu)
        $menu->add_image_item(str('LISTEN_MUSIC'),style_img('MENU_INDEX_MUSIC',true),style_img('MENU_INDEX_MUSIC_ON',true,false),'music.php');
      else
        $menu->add_item(str('LISTEN_MUSIC'),'music.php',true);

    // Only display the photo options if the user has some photos stored in the database.
    if ( media_exists(MEDIA_TYPE_PHOTO))
      if ($image_menu)
        $menu->add_image_item( str('VIEW_PHOTO'),style_img('MENU_INDEX_PHOTO',true),style_img('MENU_INDEX_PHOTO_ON',true,false),'photo.php');
      else
        $menu->add_item( str('VIEW_PHOTO'),'photo.php',true);

    // Recent media
    if ($image_menu)
      $menu->add_image_item(str('RECENT_MEDIA'),style_img('MENU_INDEX_RECENT',true),style_img('MENU_INDEX_RECENT_ON',true,false),'recent.php');
    else
      $menu->add_item(str('RECENT_MEDIA'),'recent.php',true);

    // Only display the Internet options if an internet connection is active and internet options are enabled.
    if (internet_available() && (get_sys_pref('weather_enabled','YES') == 'YES' || get_sys_pref('radio_enabled','YES') == 'YES'
          || (get_sys_pref('web_enabled','YES') == 'YES' && db_value("select 'YES' from media_locations where media_type=".MEDIA_TYPE_WEB." limit 1") == 'YES')
          ||  get_sys_pref('OVERRIDE_ENABLE_WEBLINKS','NO') == 'YES' || get_sys_pref('flickr_enabled','YES') == 'YES'
          || (get_sys_pref('rss_enabled','YES') == 'YES' && db_value("select 'YES' from rss_subscriptions limit 1") == 'YES')) )
      if ($image_menu)
        $menu->add_image_item( str('INTERNET_SERVICES'),style_img('MENU_INDEX_INTERNET',true),style_img('MENU_INDEX_INTERNET_ON',true,false),'index.php?submenu=internet');
      else
        $menu->add_item( str('INTERNET_SERVICES'),"index.php?submenu=internet",true);

    // Only display the playlist options if the user has enabled playlist support
    if (pl_enabled())
      if ($image_menu)
        $menu->add_image_item( str('MANAGE_PLAYLISTS'),style_img('MENU_INDEX_PLAYLIST',true),style_img('MENU_INDEX_PLAYLIST_ON',true,false),'manage_pl.php');
      else
        $menu->add_item( str('MANAGE_PLAYLISTS'),'manage_pl.php',true);

    if ($image_menu)
      $menu->add_image_item( str('SETUP'),style_img('MENU_INDEX_CONFIG',true),style_img('MENU_INDEX_CONFIG_ON',true,false),'config.php');
    else
      $menu->add_item( str('SETUP'),'config.php',true);

    /**
     * Icons
     */

    $icons = new iconbar();

    if (get_num_users() > 1)
      $icons->add_icon("ICON_USER",get_current_user_name(),'change_user.php');

    if (internet_available() && update_available() )
      $icons->add_icon("ICON_UPDATE",str('UPDATE'),'run_update.php');

    if ( count_messages_with_status(MESSAGE_STATUS_NEW) > 0)
      $icons->add_icon("ICON_MAIL",str('NEW'),"messages.php?return=".current_url());

    /**
     * Display the page content
     */

    echo '<p>';
    if ($image_menu)
      $menu->display_images(1, style_value("MENU_INDEX_WIDTH"), style_value("MENU_INDEX_ALIGN"));
    else
      $menu->display_page($page, 1, style_value("MENU_INDEX_WIDTH"), style_value("MENU_INDEX_ALIGN"));
    page_footer('', '', $icons);

  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
