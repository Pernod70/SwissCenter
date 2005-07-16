<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("page.php");
  require_once("utils.php");
  require_once("file.php");
  require_once("mysql.php");
  require_once("thumblist.php");
  require_once("prefs.php");
  
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

  function display_names ($url, $dir, $dir_list, $file_list, $start, $end, $page, $up, $down)
  {
    $menu = new menu();

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

    // Does this folder have an albumart file? If so, display it
    $image = file_albumart($dir);
    if (! empty($image))
    {
      $x = 150;
      $y = 200;
      image_resized_xy($image,$x,$y);
      echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
            <tr><td valign=top height="320px" width="170px" align="center">
                <table width="100%"><tr><td height="'.((320-$y)/2).'px"></td></tr><tr><td valign=top>
                  <center>'.img_gen($image,150,200).'</center>
                </td></tr></table></td>
                <td valign="top">';
                $menu->display(350);
     echo '     </td></td></table>';
    }
    else
      $menu->display(400);
   }

  // ----------------------------------------------------------------------------------
  // Displays the dirs/files to the user in "thumbnail" format 
  // ----------------------------------------------------------------------------------

  function display_thumbs ($url, $dir, $dir_list, $file_list, $start, $end, $page, $up, $down)
  {
    $tlist = new thumb_list(550);

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

  function browse_page(&$dir_list, &$file_list, $heading, $back_url, $all_link, $logo )
  {
    // Remove unwanted directories and/or files
    tidy_lists ( $dir_list, $file_list );
    
    // Page settings
    $url         = $_SERVER["PHP_SELF"];
    $page        = ( !isset($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
    $dir         = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $start       = $page * (MAX_PER_PAGE);
    $end         = min(count($dir_list)+count($file_list) , $start+MAX_PER_PAGE);
    $buttons     = array();

    // Switch between Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
      set_user_pref('DISPLAY_THUMBS',strtoupper($_REQUEST["thumbs"]));

    page_header( $heading, substr($dir,0,-1), $logo );

    if ( get_user_pref("DISPLAY_THUMBS") == "YES" )
    {
      display_thumbs ($url, $dir, $dir_list, $file_list, $start, $end, $page, ($page > 0), ($end < count($dir_list)+count($file_list)));
      $buttons[] = array('text'=>'List View', 'url'=>$url.'?thumbs=NO&DIR='.rawurlencode($dir) );
    }
    else
    {
      display_names ($url, $dir, $dir_list, $file_list, $start, $end, $page, ($page > 0), ($end < count($dir_list)+count($file_list)));
      $buttons[] = array('text'=>'Thumbnail View', 'url'=>$url.'?thumbs=YES&DIR='.rawurlencode($dir) );
    }
    
    // Should we present a link to select all files?
    if ($all_link!='')
      $buttons[] = array('text'=>'Select All', 'url'=>$all_link.'&dir='.rawurlencode($dir) );
    
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

  function browse_fs($heading, $media_dirs, $back_url, $filetypes, $all_link='', $logo='' )
  {
    // Check page parameters, and if not set then assign default values.
    $dir             = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $media_locations = ( is_array($media_dirs) ? $media_dirs : array($media_dirs));
    $dir_list        = array();
    $file_list       = array();

    // Get a list of files/dirs from the filesystem.      
    foreach ($media_locations as $path)
      dir_contents_FS(str_suffix($path,'/').$dir, $filetypes, $dir_list, $file_list);  

    browse_page($dir_list, $file_list, $heading, $back_url, $all_link, $logo);     
  }

  // ----------------------------------------------------------------------------------
  // Ouputs all the details for browsing the files/dirs listed in the database and
  // choosing an individual file.
  //
  // NOTE: £sql_table should be of the form "from <tablename> where <conditions>"
  // ----------------------------------------------------------------------------------

  function browse_db($heading, $media_dirs, $sql_table, $back_url, $filetypes, $all_link='', $logo='' )
  {
    // Check page parameters, and if not set then assign default values.
    $dir             = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $media_locations = ( is_array($media_dirs) ? $media_dirs : array($media_dirs));
    $dir_list        = array();
    $file_list       = array();

    foreach ($media_locations as $path)
      dir_contents_DB($dir_list, $file_list, $sql_table, str_suffix($path,'/').$dir);
    
    browse_page($dir_list, $file_list, $heading, $back_url, $all_link, $logo);     
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
