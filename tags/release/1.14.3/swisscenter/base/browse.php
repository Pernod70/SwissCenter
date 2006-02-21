<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/page.php'));
  require_once( realpath(dirname(__FILE__).'/utils.php'));
  require_once( realpath(dirname(__FILE__).'/file.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/thumblist.php'));
  require_once( realpath(dirname(__FILE__).'/prefs.php'));
  
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
      $exclude = array('/RECYCLER/i','/System Volume Information/i','/^\./');
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
  // Fills the two arrays with the directory and file names to be found in the given
  // directory. The array $filetypes specified which file extensions are to be allowed.
  // ----------------------------------------------------------------------------------

  function dir_contents_FS( $dir, $filetypes, &$dir_list, &$file_list)
  {
    if (($dh = @opendir($dir)) !== false)
    {
      while (($name = readdir($dh)) !== false)
      {
        if (is_dir($dir.$name) && $name != '.' && $name != '..')
        {
          $dir_list[]  = array("dirname" => $dir, "filename" => $name);          
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

  function display_names ($url, $dir, $dir_list, $file_list, $page)
  {
    $menu      = new menu();
    $no_items  = 8;
    $start     = $page * ($no_items);
    $end       = min(count($dir_list)+count($file_list) , $start+$no_items);
    $up        = ($page > 0);
    $down      = ($end < count($dir_list)+count($file_list));

    if ($up)
      $menu->add_up($url.'?page='.($page-1).'&DIR='.rawurlencode($dir));

    if ($down)
      $menu->add_down($url.'?page='.($page+1).'&DIR='.rawurlencode($dir));

    for ($i=$start; $i<$end; $i++)
    {
      if ($i < count($dir_list))
      {
        // Output a link to call this page again, but passing in the selected directory.
        $menu->add_item($dir_list[$i]["filename"],$url.'?DIR='.rawurlencode($dir.$dir_list[$i]["filename"].'/'), true);
      }
      else
      {
        // Output a link to cause the specified playlist to be loaded into the session
        eval('$dest = output_link( "'.$file_list[$i-count($dir_list)]["dirname"].$file_list[$i-count($dir_list)]["filename"].'" );');
        $menu->add_item(ucwords(file_noext($file_list[$i-count($dir_list)]["filename"])),$dest);
      }
    }
    $menu->display(400);
   }

  // ----------------------------------------------------------------------------------
  // Displays the dirs/files to the user in "thumbnail" format 
  // ----------------------------------------------------------------------------------

  function display_thumbs ($url, $dir, $dir_list, $file_list, $page)
  {
    $tlist   = new thumb_list(550);

    // Compact View
    if ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" )
    {
      $tlist->set_num_cols(6);
      $tlist->set_num_rows(3);
      $tlist->set_titles_off();
      $no_items = 18;
    }
    else 
      $no_items = 8;

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
        $tlist->add_item($image, $dir_list[$i]["filename"], $url.'?DIR='.rawurlencode($dir.$dir_list[$i]["filename"].'/') );
      }
      else
      {
        // Output a link to cause the specified playlist to be loaded into the session
        $details   = $file_list[$i-count($dir_list)];  
        eval('$link_url = output_link( "'.$details["dirname"].$details["filename"].'" );');
        $tlist->add_item(file_thumbnail($details["dirname"].$details["filename"]), file_noext($details["filename"]), $link_url);
      }
    }

    if ($up)   
      $tlist->set_up( $url.'?page='.($page-1).'&DIR='.rawurlencode($dir) ); 

    if ($down) 
      $tlist->set_down( $url.'?page='.($page+1).'&DIR='.rawurlencode($dir) ); 

    $tlist->display();
   }
  
  // ----------------------------------------------------------------------------------
  // Display the list of choices in either thumbnail or list view (detect if the user
  // has changed it and act appropriately)
  // ----------------------------------------------------------------------------------

  function browse_page(&$dir_list, &$file_list, $heading, $back_url, $media_type )
  {
    // Remove unwanted directories and/or files
    tidy_lists ( $dir_list, $file_list );
    
    // Page settings
    $url         = $_SERVER["PHP_SELF"];
    $page        = ( !isset($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
    $dir         = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $buttons     = array();

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    if ( get_user_pref("DISPLAY_THUMBS") == "FULL" )
    {
      page_header( $heading, substr($dir,0,-1),'',1,false);
      display_thumbs ($url, $dir, $dir_list, $file_list, $page);
      $buttons[] = array('text'=>str('COMPACT_VIEW'), 'url'=>$url.'?thumbs=COMPACT&DIR='.rawurlencode($dir) );
    }
    elseif ( get_user_pref("DISPLAY_THUMBS") == "COMPACT" )
    {
      page_header( $heading, substr($dir,0,-1),'',1,false,style_value("PAGE_FOCUS_IMAGES"));
      display_thumbs ($url, $dir, $dir_list, $file_list, $page);
      $buttons[] = array('text'=>str('LIST_VIEW'), 'url'=>$url.'?thumbs=NO&DIR='.rawurlencode($dir) );
    }
    else
    {
      page_header( $heading, substr($dir,0,-1),'',1,false);
      display_names ($url, $dir, $dir_list, $file_list, $page);
      $buttons[] = array('text'=>str('THUMBNAIL_VIEW'), 'url'=>$url.'?thumbs=FULL&DIR='.rawurlencode($dir) );
    }
    
    // Should we present a link to select all files?
    if ($media_type > 0)
      $buttons[] = array('text'=>str('SELECT_ALL'), 'url'=> play_dir( $media_type, $dir));

    // Output ABC buttons if appropriate
    if ( empty($dir) )
      page_footer( $back_url, $buttons );
    else
      page_footer( $url.'?DIR='.rawurlencode(parent_dir($dir)), $buttons );
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
  // NOTE: £sql_table should be of the form "from <tablename> where <conditions>"
  // ----------------------------------------------------------------------------------

  function browse_db($heading, $media_dirs, $sql_table, $back_url, $filetypes, $media_type = 0 )
  {
    // Check page parameters, and if not set then assign default values.
    $dir             = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $media_locations = ( is_array($media_dirs) ? $media_dirs : array($media_dirs));
    $dir_list        = array();
    $file_list       = array();

    foreach ($media_locations as $path)
      dir_contents_DB($dir_list, $file_list, $sql_table, str_suffix($path,'/').$dir);
    
    browse_page($dir_list, $file_list, $heading, $back_url, $media_type);     
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
