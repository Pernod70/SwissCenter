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

  function dir_contents( $dir, $filetypes, &$dir_list, &$file_list, &$image, $db_files_only)
  {
    if (($dh = @opendir($dir)) !== false)
    {
      while (($name = readdir($dh)) !== false)
      {
        if (is_dir($dir.$name) && $name != '.' && $name != '..')
        {
          $dir_list[]  = array("filename"=>$name, "image"=> file_thumbnail($dir.$name));          
        }
        elseif ( !$db_files_only && in_array(file_ext(strtolower($name)), $filetypes))
        {
          $file_list[] = array("dirname" => $dir, "filename" => $name, "image"=> file_thumbnail($dir.$name));
        }
        elseif ( in_array_ci(strtolower($name),$_SESSION["opts"]["art_files"]))
          $image = $dir.$name;
      }
      closedir($dh);
    }
  }

  // ----------------------------------------------------------------------------------
  // Fills the $file_list array with media files listed in the database by matching the 
  // directories on the filesystem with the paths stored in the database.
  // ----------------------------------------------------------------------------------

  function file_list_from_db ($query, &$file_list)
  {
    if ( ($db_data = db_toarray($query)) !== false)
    {
      foreach ($db_data as $row)
      {
        $file_list[] = array("dirname" => $row["DIRNAME"], "filename" => $row["FILENAME"], "image"=> file_thumbnail($row["DIRNAME"].$row["FILENAME"]));
      }
    }
  }

  // ----------------------------------------------------------------------------------
  // Displays the dirs/files to the user in "text menu" format (with an optional image
  // to the left hand side).
  // ----------------------------------------------------------------------------------

  function display_names ($url, $dir, $dir_list, $file_list, $start, $end, $page, $image, $up, $down)
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

  function display_thumbs ($url, $dir, $dir_list, $file_list, $start, $end, $page, $image, $up, $down)
  {
    $tlist = new thumb_list(550);

   // Populate an array with the details that will be displayed
    for ($i=$start; $i<$end; $i++)
    {
      if ($i < count($dir_list))
      {
        // Directory Icon or thumbnail for the directory if one exists
        $tlist->add_item($dir_list[$i]["image"], $dir_list[$i]["filename"], $url.'?DIR='.rawurlencode($dir.$dir_list[$i]["filename"].'/') );
      }
      else
      {
        // Output a link to cause the specified playlist to be loaded into the session
        $details   = $file_list[$i-count($dir_list)];  
        eval('$link_url = output_link( "'.$details["dirname"].$details["filename"].'" );');
        $tlist->add_item($details["image"], file_noext($details["filename"]), $link_url);
      }
    }

    if ($up)   
      $tlist->set_up( $url.'?page='.($page-1).'&DIR='.rawurlencode($dir) ); 

    if ($down) 
      $tlist->set_down( $url.'?page='.($page+1).'&DIR='.rawurlencode($dir) ); 

    $tlist->display();
   }
  
  // ----------------------------------------------------------------------------------
  // Ouputs all the details for browsing the filesystem directly, and choosing an
  // individual file
  // ----------------------------------------------------------------------------------

  function browse_fs($heading, $default_dir, $back_url, $filetypes, $all_link='', $sql_filelist='' )
  {
    // Check page parameters, and if not set then assign default values.
    $url           = $_SERVER["PHP_SELF"];
    $dir           = ( empty($_REQUEST["DIR"]) ? '' : un_magic_quote(rawurldecode($_REQUEST["DIR"])));
    $page          = ( !isset($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
    $pre           = ( is_array($default_dir) ? $default_dir : array($default_dir));
    $db_files_only = ( $sql_filelist != '' ? true : false);
    $dir_list      = array();
    $file_list     = array();
    $buttons       = array();

    // Switching Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
    {
      $_SESSION["opts"]["display_thumbs"] = ($_REQUEST["thumbs"] == "yes");
      set_user_pref('display_thumbs',$_SESSION["opts"]["display_thumbs"]);
    }

    // Get a list of files/dirs from the filesystem.      
    foreach ($pre as $path)
      dir_contents(str_suffix($path,'/').$dir, $filetypes, $dir_list, $file_list, $image, $db_files_only);
          
    // If the function was called with a SQL statement for the filelist, then query the
    // dataase for the files within the current directory (instead of relying on the filesystem);
    if ($db_files_only)
    {
      foreach ($pre as $path)
        file_list_from_db( $sql_filelist."='".db_escape_str(str_suffix($path,'/').$dir)."'", $file_list);
    }
    
    // Now that we have a list of files to work with, we can output the page (maximum MAX_PER_PAGE items per page).
    page_header( $heading, substr($dir,0,-1) );
    tidy_lists ( $dir_list, $file_list );
    $start     = $page * (MAX_PER_PAGE);
    $end       = min(count($dir_list)+count($file_list) , $start+MAX_PER_PAGE);

    if ( $_SESSION["opts"]["display_thumbs"] == false )
    {
      display_names ($url, $dir, $dir_list, $file_list, $start, $end, $page, $image, ($page > 0), ($end < count($dir_list)+count($file_list)));
      $buttons[] = array('text'=>'Thumbnail View', 'url'=>$url.'?thumbs=yes&DIR='.rawurlencode($dir) );
    }
    else
    {
      display_thumbs ($url, $dir, $dir_list, $file_list, $start, $end, $page, $image, ($page > 0), ($end < count($dir_list)+count($file_list)));
      $buttons[] = array('text'=>'List View', 'url'=>$url.'?thumbs=no&DIR='.rawurlencode($dir) );
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

  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
