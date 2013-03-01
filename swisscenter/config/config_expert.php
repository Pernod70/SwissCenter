<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  /**
   * Executes SQL entered by the user and displays the results.
   *
   */

  function expert_runsql()
  {
    $sql = $_REQUEST["sql"];
    $tabs = array();

    echo "<h1>".str('EXPERT_EDIT_DB')."</h1>";
    echo '<p>'.str('EXPERT_WARNING','<a href="http://www.swisscenter.co.uk/components/com_mambowiki/index.php?title=Expert_Parameters_List">SwissCenter.co.uk</a>').'<p>';

    if ( test_db() == 'OK' )
    {
      // Build a list of tables for reference
      foreach (db_col_to_list("show tables") as $tab)
        $tabs[$tab] = $tab;

      form_start('index.php');
      form_hidden('section','EXPERT');
      form_hidden('action','RUNSQL');
      form_text('sql','SQL',100,5,$sql);
      echo '<tr><td>';
      echo form_submit_html(str('DB_RUN_SQL'));
      echo '</td><td align="right"><b>'.str('REFERENCE').':</b> ';
      echo form_list_static_html('tab',$tabs,'',true, true, str('DB_TABLE_LIST'));
      echo '</td></tr>';
      form_end();

      $stmts = explode(';',$sql);

      // If the user selected a table from the list, don't execute the SQL in the command window
      // but describe the table instead.
      if (!empty($_REQUEST["tab"]))
        $stmts = array("desc $_REQUEST[tab]");

      // Execute all the SQL statement and display the results.
      foreach ($stmts as $sql)
      {
        $sql=trim($sql);
        if ( in_array( strtolower(substr($sql,0,strpos($sql,' '))), array('select','show','desc')) )
        {
          $data = array();
          $success = db::getInstance()->query( $sql );
          $heading = array();

          if ($success)
          {
            // Fetch data into an array
            while (!is_null($row = db::getInstance()->fetch_array()))
              $data[] = $row;

            if (count($data)>0)
            {
              // Work out what the headings are
              foreach($data[0] as $col=>$val)
                $heading[] = $col;

              // Show table name if showing definition
              if (!empty($_REQUEST["tab"]))
                echo str('TABLE_DEFINITION', $_REQUEST["tab"]);

              // Display a pretty HTML table
              echo '<p>';
              array_to_table($data,join(',',$heading));
            }
          }
          else
          {
            message('!'.str('DB_SQL_FAIL'));
            echo db::getInstance()->error;
          }

          db::getInstance()->free();
        }
        elseif (!empty($sql))
        {
          if ( db_sqlcommand($sql) )
            message(str('DB_SQL_SUCCESS'));
          else
            message('!'.str('DB_SQL_FAIL'));
        }
      }
    }
    else
    {
      message("!Unable to connect to the database.");
    }
  }

  /**
   * Displays a form to the user so that they can add new system preferences or edit existing
   * ones manually.
   *
   * @param string $message - Sucess or Failure messsage.
   * @param mixed $edit_id - Passed from the form-select_table() routine to indicate which row is currently being edited.
   */

  function expert_sysprefs( $message = '', $edit_id = '')
  {
    echo "<h1>".str('EXPERT_EDIT_PREFS')."</h1>";
    echo '<p>'.str('EXPERT_WARNING','<a href="http://www.swisscenter.co.uk/components/com_mambowiki/index.php?title=Expert_Parameters_List">SwissCenter.co.uk</a>').'<p>';
    message($message);

    echo '<p>&nbsp;<br><b>'.str('SYSPREF_ADD_NEW').'</b>';
    form_start('index.php', 150);
    form_hidden('section', 'EXPERT');
    form_hidden('action', 'SYSPREFS_NEW');
    form_input('name',str('NAME'),40);
    form_input('value',str('VALUE'),40);
    form_submit(str('SYSPREF_ADD_NEW'));
    form_end();


    echo '<p>&nbsp;<br><b>'.str('SYSPREF_EDIT_EXISTING').'</b>';
    $data = db_toarray("select name, name pref, value from system_prefs order by 1");
    form_start('index.php', 150, 'prefs');
    form_hidden('section', 'EXPERT');
    form_hidden('action', 'SYSPREFS_MODIFY');

    form_select_table( 'ids', $data, str('NAME').'|300,'.str('VALUE').'|300'
                     , array('class'=>'form_select_tab','width'=>'100%'), 'name'
                     , array('PREF'=>'30','VALUE'=>'30')
                     , $edit_id, 'prefs');
    if (!$edit_id)
      form_submit(str('SYSPREF_DEL_BUTTON'), 1, 'center');
    form_end();
    // Force the screen to be a bit wider
    echo '<table width="670"><tr><td></td></tr></table>';
  }

  /**
   * Updates a system preference entered by the user
   *
   */

  function expert_sysprefs_modify()
  {
    $selected_ids = form_select_table_vals('ids');
    $edit_id      = form_select_table_edit('ids', 'prefs');
    $update_data  = form_select_table_update('ids', 'prefs');

    if (!empty($edit_id))
      expert_sysprefs('', $edit_id);
    elseif (!empty($update_data))
    {
      $value = db_escape_str($update_data["VALUE"]);
      $id    = db_escape_str($update_data["PREF"]);

      db_sqlcommand("update system_prefs set value='$value' where name='$id'");
      expert_sysprefs(str('EXPERT_PREFS_UPDATED'));
    }
    elseif(!empty($selected_ids))
    {
      $id    = db_escape_str($update_data["PREF"]);

      foreach($selected_ids as $id)
        db_sqlcommand("delete from system_prefs where name='$id'");

      expert_sysprefs(str('EXPERT_PREFS_UPDATED'));
    }
    else
      expert_sysprefs();
  }

  /**
   * Adds a new system preference added by the user.
   *
   */

  function expert_sysprefs_new()
  {
    $name = $_REQUEST["name"];
    $val  = $_REQUEST["value"];

    if ( !empty($name) )
    {
      if ( db_value("select count(*) from system_prefs where name ='$name'") == 0)
        db_insert_row('system_prefs',array("NAME"=>$name,"VALUE"=>$val));
      else
        db_sqlcommand("update system_prefs set value='$val' where name='$name'");
    }

    expert_sysprefs(str('EXPERT_PREFS_UPDATED'));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
