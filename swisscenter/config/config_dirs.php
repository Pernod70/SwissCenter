<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/musicip.php'));

  // PHP caches information on whether files/dirs exist, permissions, etc - we need to clear the cache.
  clearstatcache();

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function dirs_display($delete = '', $new = '', $edit = 0)
  {
    // Ensure that on Linux/Unix systems there is a "media" directory present for symbolic links to go in.
    if (!is_windows() && !file_exists(SC_LOCATION.'media'))
      mkdir(SC_LOCATION.'media');
      
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
      $share_sql_case .= "ELSE '".str('PLEASE_SELECT')."' END) ";
    }
    else
    {
      $share_sql_case = "'".str('PLEASE_SELECT')."' ";
    }
    $share_opts = array_merge(array(array("path"=>'', "name"=>'')), $share_opts);

    $data = db_toarray("select location_id,media_name 'Type', cat_name 'Category', cert.name 'Certificate', ml.name 'Directory', ".
                       "(CASE media_name WHEN 'DVD Video' THEN ".$share_sql_case."ELSE '' END) 'Share' ".
                       "from media_locations ml, media_types mt, categories cat, certificates cert ".
                       "where ml.unrated=cert.cert_id and mt.media_id = ml.media_type and ml.cat_id = cat.cat_id order by 2,3,4");

    // Try to determine sensible default values for "Category" and "Certification".
    if (empty($_REQUEST["cat"]))
      $_REQUEST["cat"] = db_value("select cat_id from categories where cat_name='General'");
    if (empty($_REQUEST["cert"]))
      $_REQUEST["cert"] = db_value("select cert_id from certificates where rank = ".db_value("select min(rank) from certificates")." limit 1");
     
    echo "<h1>".str('MEDIA_LOCATIONS')."</h1>";
    message($delete);
    form_start('index.php', 150, 'dirs');
    form_hidden('section','DIRS');
    form_hidden('action','MODIFY');
    form_select_table('loc_id',$data, str('MEDIA_LOC_HEADINGS')
                     ,array('class'=>'form_select_tab','width'=>'100%'),'location_id',
                      array('DIRECTORY'=>'', 'SHARE'=>$share_opts, 'TYPE'=>'select media_id,media_name from media_types order by 2',
                            'CATEGORY'=>'select cat_id,cat_name from categories where cat_id not in ('.
                                         implode(',', db_col_to_list('select distinct parent_id from categories')).') order by cat_name',
                            'CERTIFICATE'=>get_cert_list_sql()), $edit, 'dirs');
    if (!$edit)
      form_submit(str('MEDIA_LOC_DEL_BUTTON'),1,'center');
    form_end();
  
    echo '<p><h1>'.str('MEDIA_LOC_ADD_TITLE').'<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','DIRS');
    form_hidden('action','NEW');
    form_input('location',str('LOCATION'),50,'',un_magic_quote($_REQUEST['location']));
    form_label(str('LOCATION_PROMPT'));
    form_list_static('share',str('NETWORK_SHARE'), $share_list, un_magic_quote($_REQUEST['share']));
    if (count($share_opts)==1)
      form_label(str('NETWORK_SHARE_MSG'));
    else
      form_label(str('NETWORK_SHARE_PROMPT'));
    form_list_dynamic('type',str('MEDIA_TYPE'),"select media_id,media_name from media_types order by 2",$_REQUEST['type']);
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
        
      if (is_dir($dir))
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

  // ----------------------------------------------------------------------------------
  // Delete an existing location
  // ----------------------------------------------------------------------------------
  
  function dirs_modify()
  {
    $selected = form_select_table_vals('loc_id');            // Get the selected items
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
      $dir = rtrim(str_replace('\\','/',un_magic_quote($update["DIRECTORY"])),'/');
      $share = rtrim(str_replace('\\','/',un_magic_quote($update["SHARE"])),'/');
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
      elseif (empty($share) && $type_id==MEDIA_TYPE_DVD)
        dirs_display('',"!".str('MEDIA_LOC_ERROR_SHARE'));
      elseif (!file_exists($dir))
      {
        log_dir_failure($dir);
        dirs_display('',"!".str('MEDIA_LOC_ERROR_DIRFAIL'));
      }
      elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
        dirs_display('',"!".str('MEDIA_LOC_ERROR_PATH'));
      else
      {
        // Update dirs for all media at the old location, otherwise they will orphaned after the next scan
        $type_id_old = db_value("select media_type from media_locations where location_id=$id");
        if ($type_id==$type_id_old)
        {
          // Location same media type so update existing media with new dirname
          // (if media is not physically moved to new location then it will be removed during next scan)
          $table = db_value("select mt.media_table from media_types mt, media_locations ml 
                              where ml.media_type = mt.media_id and ml.location_id=$id");
          $dir_old = db_value("select name from media_locations where location_id=$id");
          foreach ( db_toarray("select file_id, dirname from $table where location_id=$id") as $row)
          {
            $dir_new = str_replace($dir_old, $dir, $row["DIRNAME"]);
            db_sqlcommand("update $table set dirname='".db_escape_str($dir_new)."' where file_id=".$row["FILE_ID"]);
          }
        }
        else
        {
          // Location changed type so remove media from location
          // (same as removing and adding location)
          db_sqlcommand("delete from ma using mp3s m, media_art ma where m.art_sha1 = ma.art_sha1 and m.location_id=".$id);
          db_sqlcommand("delete from ma using movies m, media_art ma where m.art_sha1 = ma.art_sha1 and m.location_id=".$id);
          db_sqlcommand("delete from mp3s where location_id=$id");
          db_sqlcommand("delete from movies where location_id=$id");
          db_sqlcommand("delete from photos where location_id=$id");
          db_sqlcommand("delete from tv where location_id=$id");
        }
        
        db_sqlcommand("update media_locations set name='".db_escape_str($dir)."',media_type=$type_id,cat_id=$cat_id,unrated=$cert,network_share='".db_escape_str($share)."' 
                        where location_id=$id");
        
        if (! is_windows() )
        {
          unlink(SC_LOCATION.'media/'.$id);
          symlink($dir,SC_LOCATION.'media/'.$id);
        }

        dirs_display(str('MEDIA_LOC_UPDATE_OK'));
        
        // Tell MusicIP to add this location.
        if ($type_id == MEDIA_TYPE_MUSIC)
          musicip_server_add_dir($dir);
      }
    }
    elseif(!empty($selected))
    {
      // Delete the selected directories
      foreach ($selected as $id)
      {
        if (! is_windows() )
          unlink(SC_LOCATION.'media/'.$id);

        db_sqlcommand("delete from ma using mp3s m, media_art ma where m.art_sha1 = ma.art_sha1 and m.location_id=".$id);
        db_sqlcommand("delete from ma using movies m, media_art ma where m.art_sha1 = ma.art_sha1 and m.location_id=".$id);
        db_sqlcommand("delete from media_locations where location_id=".$id);
        db_sqlcommand("delete from mp3s where location_id=$id");
        db_sqlcommand("delete from movies where location_id=$id");
        db_sqlcommand("delete from photos where location_id=$id");
        db_sqlcommand("delete from tv where location_id=$id");
      }

      dirs_display(str('MEDIA_LOC_DEL_OK'));
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
    $dir = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["location"])),'/');
    $share = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["share"])),'/');
    
    if (empty($_REQUEST["type"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_TYPE'));
    elseif (empty($_REQUEST["cat"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_CAT'));
    elseif (empty($_REQUEST["cert"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_CERT'));
    elseif (empty($_REQUEST["location"]))
      dirs_display('',"!".str('MEDIA_LOC_ERROR_LOC'));
    elseif (empty($share) && $_REQUEST["type"]==MEDIA_TYPE_DVD)
      dirs_display('',"!".str('MEDIA_LOC_ERROR_SHARE'));
    elseif (!file_exists($dir))
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
                      
      if ( db_insert_row('media_locations', $new_row) === false)
        dirs_display(db_error());
      else
      {
        $id = db_value("select location_id from media_locations where name='$dir' and media_type=".$_REQUEST["type"]);
        
        if (! is_windows() )
          symlink($dir,SC_LOCATION.'media/'.$id);
        
        dirs_display('',str('MEDIA_LOC_ADD_OK'));

        // Tell MusicIP to add this location.
        if ( $_REQUEST["type"] == MEDIA_TYPE_MUSIC)
          musicip_server_add_dir($dir);
      }
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
