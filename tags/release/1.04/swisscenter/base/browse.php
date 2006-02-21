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
  // Fills the two arrays with the directory and file names to be found in the given
  // directory. The array $filetypes specified which file extensions are to be allowed.
  // ----------------------------------------------------------------------------------

  function dir_contents( $dir, $filetypes, &$dir_list, &$file_list, &$thumb_list, &$image, $db_files_only)
  {
    if (($dh = @opendir($dir)) !== false)
    {
      while (($name = readdir($dh)) !== false)
      {
        if (is_dir($dir.$name) && $name != '.' && $name != '..')
        {
          // Look to see if there is afile within the directory to use as a thumbnail
          $thumb_list[$name] = ifile_in_dir($dir.$name, $_SESSION["opts"]["art_files"]);
          $dir_list[] = $name;          
        }
        elseif ( !$db_files_only && in_array(file_ext(strtolower($name)), $filetypes))
          $file_list[] = array("dirname" => $dir, "filename" => $name);
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
        $file_list[] = array("dirname" => $row["DIRNAME"], "filename" => $row["FILENAME"]);
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
        $menu->add_item($dir_list[$i],$url.'?DIR='.rawurlencode($dir.$dir_list[$i].'/'), true);
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
                $menu->display(350,28);
     echo '     </td></td></table>';
    }
    else
      $menu->display(400, 32);
   }

  // ----------------------------------------------------------------------------------
  // Displays the dirs/files to the user in "text menu" format (with an optional image
  // to the left hand side).
  // ----------------------------------------------------------------------------------

  function display_thumbs ($url, $dir, $dir_list, $file_list, $thumb_list, $start, $end, $page, $image, $up, $down)
  {
    $tlist = new thumb_list(550);

   // Populate an array with the details that will be displayed
    for ($i=$start; $i<$end; $i++)
    {
      if ($i < count($dir_list))
      {
        // Directory Icon or thumbnail for the directory if one exists
        $thumb_pic = $thumb_list[$dir_list[$i]];
        $img =  ($thumb_pic == '' ? dir_icon() : $thumb_pic);
        $tlist->add_item($img, $dir_list[$i], $url.'?DIR='.rawurlencode($dir.$dir_list[$i].'/') );
      }
      else
      {
        // Output a link to cause the specified playlist to be loaded into the session
        $details = $file_list[$i-count($dir_list)];  
        eval('$link_url = output_link( "'.$details["dirname"].$details["filename"].'" );');
        $tlist->add_item(file_icon($details["filename"]), file_noext($details["filename"]), $link_url);
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
    $pre           = $default_dir;
    $db_files_only = ($sql_filelist != '' ? true : false);
    $dir_list      = array();
    $file_list     = array();
    $thumb_list    = array();
    $buttons       = array();
    $n_per_page    = MAX_PER_PAGE;

    // Should we present a link to select all files?
// TODO
//    if ($all_link!='')
//      $buttons[] = array('text'=>'Select All', 'url'=>$all_link.'&dir='.rawurlencode($dir) );
    
    // Swistching Thumbnail/Details view?
    if ( !empty($_REQUEST["thumbs"]) )
    {
      $_SESSION["opts"]["display_thumbs"] = ($_REQUEST["thumbs"] == "yes");
      set_user_pref('display_thumbs',$_SESSION["opts"]["display_thumbs"]);
    }

    // Get a list of files/dirs from the filesystem.      
    if ( is_array($pre) )
      foreach ($pre as $path)
        dir_contents(str_suffix($path,'/').$dir, $filetypes, $dir_list, $file_list, $thumb_list, $image, $db_files_only);
    else
      dir_contents(str_suffix($pre,'/').$dir, $filetypes, $dir_list, $file_list, $thumb_list, $image, $db_files_only);
    
    // If the function was called with a SQL statement for the filelist, then query the
    // dataase for the files within the current directory.
    if ($db_files_only)
    {
      if ( is_array($pre) )
        foreach ($pre as $path)
          file_list_from_db( $sql_filelist."='".db_escape_str(str_suffix($path,'/').$dir)."'", $file_list);
      else
          file_list_from_db( $sql_filelist."='".db_escape_str(str_suffix($pre,'/').$dir)."'", $file_list);
    }

    // Sort the arrays into alphabetical order, and discard the directories "." and ".." (we will use a
    // remote control key for navigating back up a directory.
    $dir_list = @array_unique($dir_list);
    @sort($dir_list);
    @array_sort($file_list,'filename');

    // Now that we have a list of files to work with, we can output the page (maximum MAX_PER_PAGE items per page).

    page_header( $heading, substr($dir,0,-1));

    $start = $page * ($n_per_page);
    $end   = min( count($dir_list)+count($file_list) , $start+$n_per_page);

    if ( count($thumb_list)==0 && !empty($image) )
    {
      display_names ($url, $dir, $dir_list, $file_list, $start, $end, $page, $image, ($page > 0), ($end < count($dir_list)+count($file_list)));
    }
    elseif ( $_SESSION["opts"]["display_thumbs"] == false )
    {
      display_names ($url, $dir, $dir_list, $file_list, $start, $end, $page, $image, ($page > 0), ($end < count($dir_list)+count($file_list)));
      $buttons[] = array('text'=>'Thumbnail View', 'url'=>$url.'?thumbs=yes&DIR='.rawurlencode($dir) );
    }
    else
    {
      display_thumbs ($url, $dir, $dir_list, $file_list, $thumb_list, $start, $end, $page, $image, ($page > 0), ($end < count($dir_list)+count($file_list)));
      $buttons[] = array('text'=>'List View', 'url'=>$url.'?thumbs=no&DIR='.rawurlencode($dir) );
    }
    
    // Show an A-Z for quick jumping through a large list.
//    for ($i=0; $i<26; $i++)
//      echo chr(65+$i).'&nbsp; ';
    
    
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
