<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/page.php'));
  require_once( realpath(dirname(__FILE__).'/utils.php'));
  require_once( realpath(dirname(__FILE__).'/settings.php'));
  require_once( realpath(dirname(__FILE__).'/file.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/thumblist.php'));
  require_once( realpath(dirname(__FILE__).'/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/media.php'));
  require_once( realpath(dirname(__FILE__).'/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/search.php'));

  // ----------------------------------------------------------------------------------
  // Returns the number of items obeing displayed on a page
  // ----------------------------------------------------------------------------------

  function items_per_page()
  {
    switch ( get_user_pref("DISPLAY_THUMBS") )
    {
      case "COMPACT":
        return 12;
        break;

      case "LARGE":
        return 4;
        break;

      default:
        return 8;
    }
  }

  // ----------------------------------------------------------------------------------
  // Does the following to the list of directories and files...
  // - Removes duplicate directory entries
  // - Sorts the files list and directories list alphabetically
  // - Removes unwanted files/directories from the list depending on the OS
  // ----------------------------------------------------------------------------------

  function tidy_lists( &$dir_list, &$file_list)
  {
    // Remove duplicate directory entries (we want to merge directories from different media locations).
    $dir_list = arrayUnique($dir_list,'filename');

    // What directories do we want to exclude?
    if (is_windows())
      $exclude = array('/RECYCLER/i','/System Volume Information/i');
    else
      $exclude = array('/^\./');

    // Check for excluded directories
    foreach( $exclude as $preg)
    {
      for ($i=0; $i<count($dir_list); $i++)
        if ( preg_match($preg,$dir_list[$i]['filename']) != 0)
          unset($dir_list[$i]);

      // Check for excluded files
      for ($i=0; $i<count($file_list); $i++)
        if ( preg_match($preg,$file_list[$i]['filename']) != 0)
          unset($file_list[$i]);
    }

    // Sort the arrays into alphabetical order.
    @array_sort($dir_list,'filename');
    @array_sort($file_list,'filename');
  }

  // ----------------------------------------------------------------------------------
  // Displays the iradio stations to the user in "text menu" format
  // ----------------------------------------------------------------------------------

  function display_iradio ($url, $stations, $page=0)
  {
    $menu      = new menu();
    $no_items  = MAX_PER_PAGE;
    $start     = $page * ($no_items);
    $end       = min(count($stations), $start+$no_items);
    $last_page = ceil(count($stations)/MAX_PER_PAGE)-1;

    if (count($stations) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param($url,'page',($page > 0 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param($url,'page',($page < $last_page ? ($page+1) : 0)) );
    }
    $menu->set_page($page+1, $last_page+1);

    for ($i=$start; $i<$end; $i++)
    {
      // Output a link to cause the specified playlist to be loaded into the session
      $menu->add_info_item($stations[$i]->name.' ('.$stations[$i]->type.')', $stations[$i]->bitrate."k", play_internet_radio($stations[$i]->source, $stations[$i]->playlist, $stations[$i]->name, $stations[$i]->type, $stations[$i]->image));
    }

    $menu->display(1,style_value("MENU_RADIO_WIDTH"), style_value("MENU_RADIO_ALIGN"));
   }

  // ----------------------------------------------------------------------------------
  // Displays the rss feeds to the user in "text menu" format
  // ----------------------------------------------------------------------------------

  function display_rss ($url, $rss_feeds, $page=0)
  {
    $menu      = new menu();
    $no_items  = MAX_PER_PAGE;
    $start     = $page * ($no_items);
    $end       = min(count($rss_feeds), $start+$no_items);
    $last_page = ceil(count($rss_feeds)/MAX_PER_PAGE)-1;

    if (count($rss_feeds) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param($url,'page',($page > 0 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param($url,'page',($page < $last_page ? ($page+1) : 0)) );
    }

    for ($i=$start; $i<$end; $i++)
    {
      // Count items in subscription
      $count = db_value("select count(*) from rss_items where subscription_id=".$rss_feeds[$i]['ID']);

      switch ($rss_feeds[$i]['TYPE'])
      {
        case MEDIA_TYPE_MUSIC:
          $icon = 'RSS_AUDIO';
          break;
        case MEDIA_TYPE_PHOTO:
          $icon = 'RSS_IMAGE';
          break;
        case MEDIA_TYPE_VIDEO:
          $icon = 'RSS_VIDEO';
          break;
      }
      // Output a link to display the specified rss feed
      $menu->add_item($rss_feeds[$i]['TITLE'].' ('.$count.')', ($count==0 ? current_url() : url_add_param($url,'sub_id',$rss_feeds[$i]['ID'])), false, $icon);
    }

    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('MISSING_RSS_ART',true,false),280,450).'
              </td><td width="'.convert_x(20).'"></td>
              <td valign="top">';
              $menu->display( 1,520 );
    echo '    </td></table>';
  }

  // ----------------------------------------------------------------------------------
  // Browse a given array (of objects, properties name & url) in "text menu" format
  // (first parameter is the calling URL without the "page" parameter)
  // ----------------------------------------------------------------------------------

  function browse_array ($url, $array, $page, $media_type=0)
  {
    $menu      = new menu();
    $no_items  = MAX_PER_PAGE;
    $start     = $page * ($no_items);
    $end       = min(count($array), $start+$no_items);
    $last_page = ceil(count($array)/MAX_PER_PAGE)-1;

    if (count($array) > MAX_PER_PAGE)
    {
      $menu->add_up( url_add_param($url,'page',($page > 0 ? ($page-1) : $last_page)) );
      $menu->add_down( url_add_param($url,'page',($page < $last_page ? ($page+1) : 0)) );
    }

    for ($i=$start; $i<$end; $i++)
    {
      $sub  = isset($array[$i]["submenu"]) ? $array[$i]["submenu"] : false;
      $icon = isset($array[$i]["icon"]) ? $array[$i]["icon"] : false;
      $menu->add_item($array[$i]["name"], $array[$i]["url"], $sub, $icon);
    }

    // Determine menu properties for this media type
    switch ($media_type)
    {
      case MEDIA_TYPE_MUSIC :
        $width = style_value("MENU_MUSIC_WIDTH");
        $align = style_value("MENU_MUSIC_ALIGN");
        break;
      case MEDIA_TYPE_PHOTO :
        $width = style_value("MENU_PHOTO_WIDTH");
        $align = style_value("MENU_PHOTO_ALIGN");
        break;
      case MEDIA_TYPE_VIDEO :
        $width = style_value("MENU_VIDEO_WIDTH");
        $align = style_value("MENU_VIDEO_ALIGN");
        break;
      case MEDIA_TYPE_RADIO :
        $width = style_value("MENU_RADIO_WIDTH");
        $align = style_value("MENU_RADIO_ALIGN");
        break;
      case MEDIA_TYPE_TV    :
        $width = style_value("MENU_TV_WIDTH");
        $align = style_value("MENU_TV_ALIGN");
        break;
      case MEDIA_TYPE_WEB   :
        $width = style_value("MENU_WEB_WIDTH");
        $align = style_value("MENU_WEB_ALIGN");
        break;
      case MEDIA_TYPE_INTERNET_TV :
        $width = style_value("MENU_INTERNET_TV_WIDTH");
        $align = style_value("MENU_INTERNET_TV_ALIGN");
        break;
      default               :
        $width = 650;
        $align = 'center';
    }

    $menu->display(1, $width, $align);
  }

  // ----------------------------------------------------------------------------------
  // Fills the two arrays with the directory and file names to be found in the given
  // directory. The array $filetypes specified which file extensions are to be allowed.
  // ----------------------------------------------------------------------------------

  function dir_contents_FS( $dir, $filetypes, &$dir_list, &$file_list, $recursive=false)
  {
    if (($dh = @opendir($dir)) !== false)
    {
      while (($name = readdir($dh)) !== false)
      {
        if (isdir($dir.$name) && $name != '.' && $name != '..')
        {
          $dir_list[]  = array("dirname" => $dir, "filename" => $name);
          if ($recursive)
            dir_contents_FS(str_suffix($dir.$name,'/'), $filetypes, $dir_list, $file_list, $recursive);
        }
        elseif ( in_array(file_ext(strtolower($name)), $filetypes))
        {
          $file_list[] = array("dirname" => $dir, "filename" => $name );
        }
      }
      closedir($dh);
    }
  }

  // ----------------------------------------------------------------------------------
  // Fills the $file_list array with media files listed in the database by matching the
  // directories on the filesystem with the paths stored in the database.
  // ----------------------------------------------------------------------------------

  function dir_contents_DB (&$dir_list, &$file_list, $sql_table, $dir)
  {
    // Get list of subdirectories
    $data = db_toarray("select distinct substring(substring(dirname,".(strlen($dir)+1)."),1,instr(substring(dirname,".(strlen($dir)+1)."),'/')-1) dir
                        $sql_table and dirname like '".db_escape_str($dir)."%' and dirname!='".db_escape_str($dir)."'");
    if (!empty($data))
      foreach ($data as $row)
        $dir_list[] = array("dirname" => $dir, "filename"=>$row["DIR"]);

    // Get list of files
    $data = db_toarray("select dirname,filename $sql_table and dirname = '".db_escape_str($dir)."'");

    if (!empty($data))
      foreach ($data as $row)
        $file_list[] = array("dirname" => $row["DIRNAME"], "filename" => $row["FILENAME"] );
  }

  // ----------------------------------------------------------------------------------
  // Displays the dirs/files to the user in "text menu" format (with an optional image
  // to the left hand side).
  // ----------------------------------------------------------------------------------

  function display_names ($url, $dir, $dir_list, $file_list, $page, $media_type)
  {
    $menu      = new menu();
    $no_items  = items_per_page();
    $start     = $page * ($no_items);
    $end       = min(count($dir_list)+count($file_list) , $start+$no_items);
    $last_page = ceil((count($dir_list)+count($file_list))/items_per_page())-1;

    if ((count($dir_list)+count($file_list)) > items_per_page())
    {
      $menu->add_up( url_add_params($url,array('page'=>($page > 0 ? ($page-1) : $last_page), 'DIR'=>rawurlencode($dir))));
      $menu->add_down( url_add_params($url,array('page'=>($page < $last_page ? ($page+1) : 0), 'DIR'=>rawurlencode($dir))));
    }

    for ($i=$start; $i<$end; $i++)
    {
      if ($i < count($dir_list))
      {
        // Output a link to call this page again, but passing in the selected directory.
        $menu->add_item($dir_list[$i]["filename"], url_add_param(url_remove_param($url,'page'),'DIR',rawurlencode($dir.$dir_list[$i]["filename"].'/')), true);
      }
      else
      {
        // Output a link to cause the specified playlist to be loaded into the session
        $details   = $file_list[$i-count($dir_list)];
        $viewed    = viewed_icon(viewings_count( $media_type, $details["dirname"].$details["filename"]));
        $link_url  = output_link( $details["dirname"].$details["filename"]);
        $menu->add_item( ucwords(file_noext($details["filename"])) ,$link_url, false, $viewed );
      }
    }

    // Determine menu properties for this media type
    switch ($media_type)
    {
      case MEDIA_TYPE_MUSIC :
        $width = style_value("MENU_MUSIC_WIDTH");
        $align = style_value("MENU_MUSIC_ALIGN");
        break;
      case MEDIA_TYPE_PHOTO :
        $width = style_value("MENU_PHOTO_WIDTH");
        $align = style_value("MENU_PHOTO_ALIGN");
        break;
      case MEDIA_TYPE_VIDEO :
        $width = style_value("MENU_VIDEO_WIDTH");
        $align = style_value("MENU_VIDEO_ALIGN");
        break;
      case MEDIA_TYPE_RADIO :
        $width = style_value("MENU_RADIO_WIDTH");
        $align = style_value("MENU_RADIO_ALIGN");
        break;
      case MEDIA_TYPE_TV    :
        $width = style_value("MENU_TV_WIDTH");
        $align = style_value("MENU_TV_ALIGN");
        break;
      case MEDIA_TYPE_WEB   :
        $width = style_value("MENU_WEB_WIDTH");
        $align = style_value("MENU_WEB_ALIGN");
        break;
      case MEDIA_TYPE_INTERNET_TV :
        $width = style_value("MENU_INTERNET_TV_WIDTH");
        $align = style_value("MENU_INTERNET_TV_ALIGN");
        break;
      default               :
        $width = 650;
        $align = 'center';
    }

    $menu->display(1, $width, $align);
   }

  // ----------------------------------------------------------------------------------
  // Displays the dirs/files to the user in "thumbnail" format
  // ----------------------------------------------------------------------------------

  function display_thumbs ($url, $dir, $dir_list, $file_list, $page, $media_type)
  {
    $tlist    = new thumb_list();
    $no_items = items_per_page();

    switch ( get_user_pref("DISPLAY_THUMBS") )
    {
      case "COMPACT":
        // Compact View
        $tlist->set_num_cols(6);
        $tlist->set_num_rows(2);
        $tlist->set_titles_off();
        break;

      case "LARGE":
        // Large View
        $tlist->set_num_cols(4);
        $tlist->set_num_rows(1);
        $tlist->set_thumbnail_size(THUMBNAIL_LARGE_X_SIZE, THUMBNAIL_LARGE_Y_SIZE);
        break;
    }

    $start = $page * ($no_items);
    $end   = min(count($dir_list)+count($file_list) , $start+$no_items);
    $up    = ($page > 0);
    $down  = ($end < count($dir_list)+count($file_list));

   // Populate an array with the details that will be displayed
    for ($i=$start; $i<$end; $i++)
    {
      if ($i < count($dir_list))
      {
        // Directory Icon or thumbnail for the directory if one exists
        $image = file_thumbnail($dir_list[$i]["dirname"].$dir_list[$i]["filename"]);
        $tlist->add_item($image, $dir_list[$i]["filename"], url_add_param(url_remove_param($url,'page'),'DIR',rawurlencode($dir.$dir_list[$i]["filename"].'/')) );
      }
      else
      {
        // Output a link to cause the specified playlist to be loaded into the session
        $details   = $file_list[$i-count($dir_list)];

        if ( viewings_count( $media_type, $details["dirname"].$details["filename"]) >0)
          $viewed = viewed_icon(1);
        else
          $viewed = '';

        $link_url = output_link( $details["dirname"].$details["filename"] );
        $tlist->add_item( file_thumbnail($details["dirname"].$details["filename"]) , str_replace('.',' ',file_noext($details["filename"])) , $link_url, $viewed);
      }
    }

    if ($up)
      $tlist->set_up( url_add_params($url, array('page'=>($page-1),'DIR'=>rawurlencode($dir)) ) );

    if ($down)
      $tlist->set_down( url_add_params($url, array('page'=>($page+1),'DIR'=>rawurlencode($dir)) ) ) ;

    $tlist->display();
   }

  // ----------------------------------------------------------------------------------
  // Display the list of choices in either thumbnail or list view (detect if the user
  // has changed it and act appropriately)
  // ----------------------------------------------------------------------------------

  function browse_page(&$dir_list, &$file_list, $heading, $back_url, $media_type )
  {
    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    // Remove unwanted directories and/or files
    tidy_lists ( $dir_list, $file_list );

    // Make sure that the session variable for "shuffle" matches the user's preference (because it will have been set "on" for quick play).
    $_SESSION["shuffle"] = get_user_pref('shuffle','off');

    // Page settings
    $url         = url_remove_params(current_url(),array('page','thumbs','del','p_del'));
    $page        = ( !isset($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
    $dir         = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $buttons     = array();
    $total_pages = ceil( ( count($dir_list)+count($file_list) ) / items_per_page() );

    if ( get_user_pref("DISPLAY_THUMBS") == "LARGE" )
    {
      page_header( $heading, substr($dir,0,-1),'',1,false);
      display_thumbs ($url, $dir, $dir_list, $file_list, $page, $media_type);
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>url_add_params($url, array('page'=>floor($page/2),'thumbs'=>'FULL','DIR'=>rawurlencode($dir))) );
    }
    elseif ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
    {
      page_header( $heading, substr($dir,0,-1),'',1,false);
      display_thumbs ($url, $dir, $dir_list, $file_list, $page, $media_type);
      $buttons[] = array('text'=>str('COMPACT_VIEW'), 'url'=>url_add_params($url, array('page'=>floor($page/1.5),'thumbs'=>'COMPACT','DIR'=>rawurlencode($dir))) );
    }
    elseif ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" )
    {
      page_header( $heading, substr($dir,0,-1),'',1,false,style_value("PAGE_FOCUS_IMAGES"));
      display_thumbs ($url, $dir, $dir_list, $file_list, $page, $media_type);
      $buttons[] = array('text'=>str('LIST_VIEW'), 'url'=>url_add_params($url, array('page'=>floor($page*1.5),'thumbs'=>'NO','DIR'=>rawurlencode($dir))) );
    }
    else
    {
      page_header( $heading, substr($dir,0,-1),'',1,false,'',$media_type);
      display_names ($url, $dir, $dir_list, $file_list, $page, $media_type);
      $buttons[] = array('text'=>str('LARGE_VIEW'), 'url'=>url_add_params($url, array('page'=>$page*2,'thumbs'=>'LARGE','DIR'=>rawurlencode($dir))) );
    }

    // Output some links to allow the user to jump though all pages returned using the keys "1" to "9" on the
    // remote control. Note: "1" jumps to the first page, and "9" to the last.
    for ( $ir_key=1; $ir_key<10; $ir_key++ )
      echo '<a href="'.url_set_params($url, array('page'=>floor(($ir_key-1)/8*($total_pages-1)), 'DIR'=>rawurlencode($dir))).'" '.tvid($ir_key).'></a>';

    // Should we present a link to select all files?
    if ($media_type > 0 && !in_array($media_type, array(MEDIA_TYPE_WEB, MEDIA_TYPE_RADIO)))
      $buttons[] = array('text'=>str('SELECT_ALL'), 'url'=>  output_link( '%/'.$dir) );

    // Link to scan/refresh the directory
      $buttons[] = array('text'=>str('REFRESH_DIR_BUTTON'), 'url' => '/media_dir_refresh.php?media_type='.$media_type.'&dir='.urlencode($dir).'&return_url='.urlencode(current_url()) );

    // Output ABC buttons if appropriate
    if ( empty($dir) )
      page_footer( $back_url, $buttons );
    else
      page_footer( url_add_param($url, 'DIR', rawurlencode(parent_dir($dir))), $buttons );
  }

  // ----------------------------------------------------------------------------------
  // Ouputs all the details for browsing the filesystem directly, and choosing an
  // individual file
  // ----------------------------------------------------------------------------------

  function browse_fs($heading, $media_dirs, $back_url, $filetypes, $media_type = 0)
  {
    // Check page parameters, and if not set then assign default values.
    $dir             = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $media_locations = ( is_array($media_dirs) ? $media_dirs : array($media_dirs));
    $dir_list        = array();
    $file_list       = array();

    // Get a list of files/dirs from the filesystem.
    foreach ($media_locations as $path)
      dir_contents_FS(str_suffix($path,'/').$dir, $filetypes, $dir_list, $file_list);

    browse_page($dir_list, $file_list, $heading, $back_url, $media_type);
  }

  // ----------------------------------------------------------------------------------
  // Ouputs all the details for browsing the files/dirs listed in the database and
  // choosing an individual file.
  //
  // NOTE: $sql_table should be of the form "from <tablename> where <conditions>"
  // ----------------------------------------------------------------------------------

  function browse_db($heading, $sql_table, $back_url, $media_type = 0 )
  {
    // Should we delete the last entry on the history stack?
    if (isset($_REQUEST["del"]) && strtoupper($_REQUEST["del"]) == 'Y')
      search_hist_pop();

    // Push this page onto the "picker" stack
    search_picker_init( current_url() );

    // Check page parameters, and if not set then assign default values.
    $dir_list        = array();
    $file_list       = array();
    $dir             = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $media_locations = db_toarray("select * from media_locations where media_type=".$media_type);

    // Get list of files/dirs from the database
    foreach ($media_locations as $row)
      dir_contents_DB($dir_list, $file_list, $sql_table, str_suffix($row["NAME"],'/').$dir);

    browse_page($dir_list, $file_list, $heading, $back_url, $media_type);
  }

  // ----------------------------------------------------------------------------------
  // Browse a given array (of objects, properties name & url) in "thumb menu" format
  // (first parameter is the calling URL without the "page" parameter)
  // ----------------------------------------------------------------------------------

  function browse_array_thumbs ($url, $array, $page)
  {
    $no_items  = items_per_page();
    $start     = $page * ($no_items);
    $end       = min(count($array), $start+$no_items);
    $up        = ($page > 0);
    $down      = ($end < count($array));

    $tlist     = new thumb_list();

    switch ( get_user_pref("DISPLAY_THUMBS") )
    {
      case "COMPACT":
        // Compact View
        $tlist->set_num_cols(6);
        $tlist->set_num_rows(2);
        $tlist->set_titles_off();
        break;

      case "LARGE":
        // Large View
        $tlist->set_num_cols(4);
        $tlist->set_num_rows(1);
        $tlist->set_thumbnail_size(THUMBNAIL_LARGE_X_SIZE, THUMBNAIL_LARGE_Y_SIZE);
        break;
    }

    // Populate an array with the details that will be displayed
    for ($i=$start; $i<$end; $i++)
    {
      if ($i < count($array))
        $tlist->add_item( $array[$i]["thumb"], $array[$i]["text"], $array[$i]["url"] );
    }

    if ($up)
      $tlist->set_up( url_add_param($url, 'page', $page-1) );

    if ($down)
      $tlist->set_down( url_add_param($url, 'page', $page+1) ) ;

    $tlist->display();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
