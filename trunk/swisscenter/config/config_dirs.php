<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/musicip.php'));

  // PHP caches information on whether files/dirs exist, permissions, etc - we need to clear the cache.
  clearstatcache();

  function restart_swissmonitor()
  {
    if (win_service_status("SwissMonitorService") == SERVICE_STARTED)
    {
      win_service_stop("SwissMonitorService");
      win_service_start("SwissMonitorService");
    }
  }

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function dirs_display($delete = '', $new = '', $edit = 0)
  {
    // Ensure that on Linux/Unix systems there is a "media" directory present for symbolic links to go in.
    if (!is_windows() && !file_exists(SC_LOCATION.'media'))
    {
      $oldumask = umask(0);
      mkdir(SC_LOCATION.'media',0777);
      umask($oldumask);
    }

    // Retrieve list of Network Shares from NMT
    $share_opts = get_nmt_network_shares();
    send_to_log(6,'Identified network shares',$share_opts);

    // Form arrays for dropdowns and SQL case for network shares
    $share_list = array();
    if (count($share_opts)>0)
    {
      $share_sql_case = "(CASE ml.network_share ";
      for ($i = 0; $i<count($share_opts); $i++)
      {
        $share_sql_case .= "WHEN '".db_escape_str($share_opts[$i]["path"])."' THEN '".db_escape_str($share_opts[$i]["name"])."' ";
        $share_list[$share_opts[$i]["name"]] = $share_opts[$i]["path"];
      }
      $share_sql_case .= "ELSE '' END) ";
    }
    else
    {
      $share_sql_case = "'' ";
    }
    $share_opts = array_merge(array(array("path"=>'', "name"=>'')), $share_opts);

    // Form list of media types
    $media_type_opts = db_toarray("select media_id,media_name from media_types where media_table is not null order by 2");

    // Use language translation for MEDIA_NAME
    for ($i = 0; $i<count($media_type_opts); $i++)
    {
      $media_type_opts[$i]["MEDIA_NAME"] = str('MEDIA_TYPE_'.strtoupper($media_type_opts[$i]["MEDIA_NAME"]));
      $media_type_list[$media_type_opts[$i]["MEDIA_NAME"]] = $media_type_opts[$i]["MEDIA_ID"];
    }

    $data = db_toarray("select location_id,media_name 'Type', cat_name 'Category', cert.name 'Certificate', ml.name 'Directory', ".
                       "(CASE media_id WHEN ".MEDIA_TYPE_VIDEO." THEN ".$share_sql_case."ELSE '' END) 'Share' ".
                       "from media_locations ml, media_types mt, categories cat, certificates cert ".
                       "where ml.unrated=cert.cert_id and mt.media_id = ml.media_type and ml.cat_id = cat.cat_id order by 2,3,4");

    // Use language translation for MEDIA_NAME
    for ($i = 0; $i<count($data); $i++)
      $data[$i]["TYPE"] = str('MEDIA_TYPE_'.strtoupper($data[$i]["TYPE"]));

    // Try to determine sensible default values for "Category" and "Certification".
    if (empty($_REQUEST["cat"]))
      $_REQUEST["cat"] = db_value("select cat_id from categories where cat_name='General'");
    if (empty($_REQUEST["cert"]))
      $_REQUEST["cert"] = db_value("select cert_id from certificates where scheme = '".get_rating_scheme_name()."' order by rank limit 1");

    echo "<h1>".str('MEDIA_LOCATIONS')."</h1>";
    message($delete);
    form_start('index.php', 150, 'dirs');
    form_hidden('section','DIRS');
    form_hidden('action','MODIFY');
    form_select_table('loc_id',$data, str('MEDIA_LOC_HEADINGS')
                     ,array('class'=>'form_select_tab','width'=>'100%'),'location_id',
                      array('DIRECTORY'=>'', 'SHARE'=>$share_opts, 'TYPE'=>$media_type_opts,
                            'CATEGORY'=>'select cat_id,cat_name from categories where cat_id not in ('.
                                         implode(',', db_col_to_list('select distinct parent_id from categories')).') order by cat_name',
                            'CERTIFICATE'=>get_cert_list_sql()), $edit, 'dirs');
    if (!$edit)
    {
      echo '<tr><td align="center" colspan="3">
            <input type="Submit" name="subaction" value="'.str('MEDIA_LOC_DEL_BUTTON').'"> &nbsp;';
      if (is_unix())
        echo '<input type="Submit" name="subaction" value="'.str('MEDIA_LOC_SYMLINKS').'"> &nbsp;';
      echo '</td></tr>';
    }
    form_end();

    echo '<p><h1>'.str('MEDIA_LOC_ADD_TITLE').'<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','DIRS');
    form_hidden('action','NEW');
    form_input('location',str('LOCATION'),50,'',$_REQUEST['location']);
    form_label(str('LOCATION_PROMPT'));
    form_list_static('share',str('NETWORK_SHARE'), $share_list, $_REQUEST['share'], true);
    if (count($share_opts)==1)
      form_label(str('NETWORK_SHARE_MSG'));
    else
      form_label(str('NETWORK_SHARE_PROMPT'));
    form_list_static('type',str('MEDIA_TYPE'), $media_type_list, $_REQUEST['type']);
    form_label(str('MEDIA_TYPE_PROMPT'));
    form_list_dynamic('cat', str('CATEGORY'),"select cat_id,cat_name from categories where cat_id not in (".
                                              implode(',', db_col_to_list('select distinct parent_id from categories')).")
                                              order by cat_name", $_REQUEST['cat']);
    form_label(str('CATEGORY_PROMPT'));
    form_list_dynamic('cert', str('UNRATED_CERTIFICATE'), get_cert_list_sql(), $_REQUEST['cert']);
    form_label(str('UNRATED_CERT_PROMPT'));
    form_submit(str('MEDIA_LOC_ADD_BUTTON'),2);
    form_end();
  }

  // ----------------------------------------------------------------------------------
  // Logs details about why the given path could not be added as media location.
  // ----------------------------------------------------------------------------------

  function log_dir_failure($dir)
  {
    send_to_log(2,'Unable to add media location : '.$dir);
    while ($dir != '')
    {
      $output = '';

      if (isdir($dir))
        $output .= 'Directory';
      elseif (is_file($dir))
        $output .= 'File';
      elseif ( file_exists($dir))
        $output .= 'Exists, but is not a file or directory.';
      else
        $output .= 'Does not exist';

      if (is_unix())
        send_to_log(2,@stat('Stat() of '.$dir,$dir));

      send_to_log(2,$dir.' >> '.$output);
      $dir = parent_dir($dir);
    }
  }

  function dirs_modify()
  {
    if ($_REQUEST["subaction"] == str('MEDIA_LOC_DEL_BUTTON'))
      dirs_delete();
    elseif ($_REQUEST["subaction"] == str('MEDIA_LOC_SYMLINKS'))
      dirs_recreate_symlinks();
    elseif (empty($_REQUEST["subaction"]))
      dirs_edit();
    else
      send_to_log(1,'Unknown value recieved for "subaction" parameter : '.$_REQUEST["subaction"]);
  }

  // ----------------------------------------------------------------------------------
  // Recreate symbolic links for all locations
  // ----------------------------------------------------------------------------------

  function dirs_recreate_symlinks()
  {
    $locs = db_toarray("select location_id, name from media_locations");
    foreach ($locs as $loc)
    {
      @unlink(SC_LOCATION.'media/'.$loc['LOCATION_ID']);
      symlink($loc['NAME'],SC_LOCATION.'media/'.$loc['LOCATION_ID']);
    }
    dirs_display(str('MEDIA_LOC_SYMLINKS_OK'));
  }

  // ----------------------------------------------------------------------------------
  // Delete an existing location
  // ----------------------------------------------------------------------------------

  function dirs_delete()
  {
    // Get the selected items
    $selected = form_select_table_vals('loc_id');

    if (count($selected) == 0)
      dirs_display("!".str('MEDIA_LOC_NO_SELECT'));
    else
    {
      // Delete the selected directories
      foreach ($selected as $id)
      {
        db_sqlcommand("delete from media_art using mp3s, media_art where mp3s.art_sha1 = media_art.art_sha1 and mp3s.location_id=$id");
        db_sqlcommand("delete from media_art using movies, media_art where movies.art_sha1 = media_art.art_sha1 and movies.location_id=$id");
        db_sqlcommand("delete from media_locations where location_id=$id");
        db_sqlcommand("delete from mp3s where location_id=$id");
        db_sqlcommand("delete from movies where location_id=$id");
        db_sqlcommand("delete from photos where location_id=$id");
        db_sqlcommand("delete from tv where location_id=$id");

        if ( is_unix() )
          @unlink(SC_LOCATION.'media/'.$id);
      }

      // Restart SwissMonitor service to force refresh locations to monitor
      if ( is_windows() )
        restart_swissmonitor();

      dirs_display(str('MEDIA_LOC_DEL_OK'));
    }
  }

  // ----------------------------------------------------------------------------------
  // Edit an existing location
  // ----------------------------------------------------------------------------------

  function dirs_edit()
  {
    $edit     = form_select_table_edit('loc_id', 'dirs');    // Get the id of the edited row
    $update   = form_select_table_update('loc_id', 'dirs');  // Get the updates from an edit

    if(!empty($edit))
    {
      // There was an edit, display the dirs with the table in edit mode on the selected row
      dirs_display('', '', $edit);
    }
    elseif(!empty($update))
    {
      // Update the row given in the database and redisplay the dirs
      // Process the directory passed in
      $dir = rtrim(str_replace('\\','/',$update["DIRECTORY"]),'/');
      $share = rtrim(str_replace('\\','/',$update["SHARE"]),'/');
      $type_id = $update["TYPE"];
      $cat_id  = $update["CATEGORY"];
      $id      = $update["LOC_ID"];
      $cert    = $update["CERTIFICATE"];

      send_to_log(4,'Updating media location',$update);

      if (empty($type_id))
        dirs_display('',"!".str('MEDIA_LOC_ERROR_TYPE'));
      elseif (empty($cat_id))
        dirs_display('',"!".str('MEDIA_LOC_ERROR_CAT'));
      elseif (empty($cert))
        dirs_display('',"!".str('MEDIA_LOC_ERROR_CERT'));
      elseif (empty($dir))
        dirs_display('',"!".str('MEDIA_LOC_ERROR_LOC'));
      elseif (!file_exists($dir))
      {
        log_dir_failure($dir);
        dirs_display('',"!".str('MEDIA_LOC_ERROR_DIRFAIL'));
      }
      elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir == '..' || $dir == '.')
        dirs_display('',"!".str('MEDIA_LOC_ERROR_PATH'));
      else
      {
        // Update dirs for all media at the old location, otherwise they will orphaned after the next scan
        $type_id_old = db_value("select media_type from media_locations where location_id=$id");
        if ($type_id == $type_id_old)
        {
          // Location same media type so update existing media with new dirname
          // (if media is not physically moved to new location then it will be removed during next scan)
          $table = db_value("select mt.media_table from media_types mt, media_locations ml
                              where ml.media_type = mt.media_id and ml.location_id=$id");
          $dir_old = db_value("select name from media_locations where location_id=$id");
          foreach (db_toarray("select file_id, dirname from $table where location_id=$id") as $row)
          {
            $dir_new = str_replace($dir_old, $dir, $row["DIRNAME"]);
            db_sqlcommand("update $table set dirname='".db_escape_str($dir_new)."' where file_id=".$row["FILE_ID"]);
          }
        }
        else
        {
          // Location changed type so remove media from location
          // (same as removing and adding location)
          db_sqlcommand("delete from media_art using mp3s m, media_art ma where m.art_sha1 = ma.art_sha1 and m.location_id=$id");
          db_sqlcommand("delete from media_art using movies m, media_art ma where m.art_sha1 = ma.art_sha1 and m.location_id=$id");
          db_sqlcommand("delete from mp3s where location_id=$id");
          db_sqlcommand("delete from movies where location_id=$id");
          db_sqlcommand("delete from photos where location_id=$id");
          db_sqlcommand("delete from tv where location_id=$id");
        }

        db_sqlcommand("update media_locations set name='".db_escape_str($dir)."',media_type=$type_id, cat_id=$cat_id, unrated=$cert, network_share='".db_escape_str($share)."'
                        where location_id=$id");

        if ( is_windows() )
        {
          restart_swissmonitor();
        }
        else
        {
          @unlink(SC_LOCATION.'media/'.$id);
          symlink($dir,SC_LOCATION.'media/'.$id);
        }

        dirs_display(str('MEDIA_LOC_UPDATE_OK'));

        // Tell MusicIP to add this location.
        if ($type_id == MEDIA_TYPE_MUSIC)
          musicip_server_add_dir($dir);
      }
    }
    else
      dirs_display();
  }

  // ----------------------------------------------------------------------------------
  // Add a new location
  // ----------------------------------------------------------------------------------

  function dirs_new()
  {
    // Process the directory passed in
    $dir = rtrim(str_replace('\\','/',$_REQUEST["location"]),'/');
    $share = rtrim(str_replace('\\','/',$_REQUEST["share"]),'/');

    if (empty($_REQUEST["type"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_TYPE'));
    elseif (empty($_REQUEST["cat"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_CAT'));
    elseif (empty($_REQUEST["cert"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_CERT'));
    elseif (empty($_REQUEST["location"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_LOC'));
    elseif (!isdir($dir))
    {
      log_dir_failure($dir);
      dirs_display('',"!".str('MEDIA_LOC_ERROR_DIRFAIL'));
    }
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      dirs_display('',"!".str('MEDIA_LOC_ERROR_PATH'));
    else
    {
      $new_row = array( 'name'       => $dir
                      , 'media_type' => $_REQUEST["type"]
                      , 'cat_id'     => $_REQUEST["cat"]
                      , 'unrated'    => $_REQUEST["cert"]
                      , 'network_share' => $_REQUEST["share"]);

      send_to_log(4,'Adding new media location',$new_row);

      $loc_id = db_insert_row('media_locations', $new_row);
      if ($loc_id === false)
        dirs_display(db_error());
      else
      {
        if ( is_windows() )
        {
          restart_swissmonitor();
        }
        else
        {
          @unlink(SC_LOCATION.'media/'.$loc_id);
          symlink($dir,SC_LOCATION.'media/'.$loc_id);
        }

        // Tell MusicIP to add this location.
        if ( $_REQUEST["type"] == MEDIA_TYPE_MUSIC)
          musicip_server_add_dir($dir);

        // Assign location to all users
        $users = db_toarray("select user_id from users");
        foreach ($users as $user)
          db_insert_row('user_permissions', array('user_id'=>$user['USER_ID'], 'location_id'=>$loc_id));

        dirs_display('',str('MEDIA_LOC_ADD_OK'));
      }
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
