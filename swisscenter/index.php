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

  function media_exists( $media_type )
  {
    $table = db_value("select media_table from media_types where media_id = $media_type");
    if ( db_value("select 'YES' from $table t, media_locations ml where t.location_id=ml.location_id and ml.media_type=$media_type limit 1") == 'YES')
      return true;
    else
      return false;
  }

  /**
   * Display the Video submenu
   */
  function display_video_menu()
  {
    page_header( str('WATCH_MOVIE'),'','',1,false,'','PAGE_VIDEO');

    /**
     * Menu Items
     */
    $menu = new menu();
    $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);

    // Only display the video options if the user has some videos stored in the database.
    if ( media_exists(MEDIA_TYPE_VIDEO))
      $menu->add_item(str('WATCH_MOVIE'),'video.php',true);

    // Only display the TV Series options if the user has some TV episodes stored in the database.
    if ( media_exists(MEDIA_TYPE_TV))
      $menu->add_item(str('WATCH_TV'),'tv.php',true);

    // Only display the DVD options if the user has some DVD images stored in the database.
    if ( media_exists(MEDIA_TYPE_DVD))
      $menu->add_item(str('WATCH_DVD'),'video_dvd.php',true);

    /**
    * Display the page content
    */
    if ($menu->num_items() == 1)
    {
      search_hist_init('index.php');
      header('Location: '.server_address().$menu->item_url(0));
    }
    else
    {
      $menu->display(1, style_value("MENU_VIDEO_WIDTH"), style_value("MENU_VIDEO_ALIGN"));
    }

    page_footer('index.php');
  }

  /**
   * Display the Internet Services submenu
   */
  function display_internet_menu()
  {
    page_header( str('INTERNET_SERVICES'),'','',1,false,'','PAGE_INTERNET');

    /**
     * Menu Items
     */
    $menu = new menu();
    $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);

    // Only display the Internet radio options if an internet connection is active and the user has enabled internet radio support
    if (internet_available() && get_sys_pref('radio_enabled','YES') == 'YES')
      $menu->add_item( str('LISTEN_RADIO'),"music_radio.php",true);

    // Only display the web links option if an internet connection is active, the user has enabled weblinks and defined some media locations
    // OR the override flag is set.
    if ( ( internet_available() && ( get_sys_pref('web_enabled','YES') == 'YES'
             && db_value("select 'YES' from media_locations where media_type=".MEDIA_TYPE_WEB." limit 1") == 'YES' )
        || get_sys_pref('OVERRIDE_ENABLE_WEBLINKS','NO') == 'YES') )
    	$menu->add_item(str('BROWSE_WEB'),'web_urls.php',true);

    // Only display the RSS options if an internet connection is active, the user has enabled RSS support and has defined some subscriptions.
    if (internet_available() && get_sys_pref('flickr_enabled','YES') == 'YES' )
      $menu->add_item( str('FLICKR_PHOTOS') ,'photo_flickr.php',true);

    // Only display the RSS options if an internet connection is active, the user has enabled RSS support and has defined some subscriptions.
    if (internet_available() && get_sys_pref('rss_enabled','YES') == 'YES' && db_value("select 'YES' from rss_subscriptions limit 1") == 'YES')
      $menu->add_item( str('RSS_FEEDS') ,'rss_feeds.php',true);

    // Only display the weather options if an internet connection is active and the user has enabled weather support
    if (internet_available() && get_sys_pref('weather_enabled','YES') == 'YES')
      $menu->add_item( str('VIEW_WEATHER') ,'weather_cc.php',true);

    /**
    * Display the page content
    */
    if ($menu->num_items() == 1)
    {
      search_hist_init('index.php');
      header('Location: '.server_address().$menu->item_url(0));
    }
    else
    {
      $menu->display(1, style_value("MENU_INTERNET_WIDTH"), style_value("MENU_INTERNET_ALIGN"));
    }

    page_footer('index.php');
  }

  /**
   * Submenu selected?
   */
  if( isset($_REQUEST["submenu"]) && $_REQUEST["submenu"] == 'internet' )
    display_internet_menu();
  elseif( isset($_REQUEST["submenu"]) && $_REQUEST["submenu"] == 'video' )
    display_video_menu();
  else
  {
    /**
     * Check for and apply any oustanding database patches.
     */

    apply_database_patches();

    /**
     * Automatically load playlist
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
     * Output the page header immediately, as it performs the authentication check and will redirect
     * the user to the "change_user" screen if there are multiple users to choose from or a password
     * is required.
     */

    page_header( str('MAIN_MENU'),'','',1,false,'','PAGE_INDEX');

    /**
     * Menu Items
     */

    $menu = new menu();
    $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);

    // Only display the video options if the user has some videos stored in the database.
    if ( media_exists(MEDIA_TYPE_VIDEO) || media_exists(MEDIA_TYPE_TV) || media_exists(MEDIA_TYPE_DVD) )
      $menu->add_item(str('WATCH_MOVIE'),"index.php?submenu=video",true);

    // Only display the music options if the user has some music stored in the database.
    if ( media_exists(MEDIA_TYPE_MUSIC))
      $menu->add_item(str('LISTEN_MUSIC'),'music.php',true);

    // Only display the photo options if the user has some photos stored in the database.
    if ( media_exists(MEDIA_TYPE_PHOTO))
      $menu->add_item( str('VIEW_PHOTO'),'photo.php',true);

    // Only display the Internet options if an internet connection is active and internet options are enabled.
    if (internet_available() && (get_sys_pref('weather_enabled','YES') == 'YES' || get_sys_pref('radio_enabled','YES') == 'YES'
          || (get_sys_pref('web_enabled','YES') == 'YES' && db_value("select 'YES' from media_locations where media_type=".MEDIA_TYPE_WEB." limit 1") == 'YES')
          ||  get_sys_pref('OVERRIDE_ENABLE_WEBLINKS','NO') == 'YES'
          || (get_sys_pref('rss_enabled','YES') == 'YES' && db_value("select 'YES' from rss_subscriptions limit 1") == 'YES')) )
      $menu->add_item( str('INTERNET_SERVICES'),"index.php?submenu=internet",true);

    // Only display the playlist options if the user has enabled playlist support
    if (pl_enabled())
      $menu->add_item( str('MANAGE_PLAYLISTS'),'manage_pl.php',true);

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
    $menu->display_page($page, 1, style_value("MENU_INDEX_WIDTH"), style_value("MENU_INDEX_ALIGN"));
    page_footer('', '', $icons);

    // Clear any active filters
    filter_set();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
