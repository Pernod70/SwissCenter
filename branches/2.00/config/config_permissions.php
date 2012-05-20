<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function permissions_display($edit = 0)
  {
    $locs  = db_toarray("select location_id, media_name 'Type', cat_name 'Category', ml.name 'Directory' ".
                        "from media_locations ml, media_types mt, categories cat ".
                        "where mt.media_id = ml.media_type and ml.cat_id = cat.cat_id order by 2,3,4");
    $users = db_toarray("select * from users order by name");

    // Use language translation for MEDIA_NAME
    for ($i = 0; $i<count($locs); $i++)
      $locs[$i]['TYPE'] = str('MEDIA_TYPE_'.strtoupper($locs[$i]['TYPE']));

    // Display user permissions.
    echo '<h1>'.str('USER_PERMISSIONS').'</h1>
          <form enctype="multipart/form-data" action="" method="post">
          <input type="hidden" name="section" value="PERMISSIONS">
          <input type="hidden" name="action" value="UPDATE">
          <table class="form_select_tab" width="100%" cellspacing="4">
          <tr>
            <th>'.str('TYPE').'</th><th>'.str('CATEGORY').'</th><th>'.str('DIRECTORY').'</th>';
    foreach ($users as $user)
      echo '<th>'.$user['NAME'].'</th>';

    foreach ($locs as $loc)
    {
      echo '</tr><tr>
              <td>'.$loc['TYPE'].'</td><td>'.$loc['CATEGORY'].'</td><td>'.$loc['DIRECTORY'].'</td>';
      foreach ($users as $user)
        echo '<td><input type="checkbox" name="perm[]" value="'.$loc['LOCATION_ID'].'|'.$user['USER_ID'].'" '.
             (db_value('select user_id from user_permissions where user_id='.$user['USER_ID'].' and location_id='.$loc['LOCATION_ID']) ? 'checked' : '').'></td>';
    }
    echo '</tr>';

    echo '</table>
          <p align="center"><input type="submit" value="'.str('UPDATE_PERMISSIONS').'">
          </form>';
  }

  // ----------------------------------------------------------------------------------
  // Update the permissions
  // ----------------------------------------------------------------------------------

  function permissions_update()
  {
    db_sqlcommand('delete from user_permissions');
    if (isset($_REQUEST['perm']))
    {
      foreach ($_REQUEST['perm'] as $perm)
      {
        list($loc_id, $user_id) = explode('|', $perm);
        db_insert_row('user_permissions', array('user_id'=>$user_id, 'location_id'=>$loc_id));
      }
    }
    permissions_display(str('PERMISSIONS_UPDATED'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
