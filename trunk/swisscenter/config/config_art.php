<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function art_display($delete = '', $new = '', $opt = '', $edit_id = '')
  {
    $list = array(str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    $data = db_toarray("select filename, filename 'Name' from art_files order by 1");

    echo "<h1>".str('ART_FILES_TITLE')."</h1>";
    echo('');

    echo '<p><h1>'.str('ART_FILES_CURRENT').'<p>';
    message($delete);
    form_start('index.php', 150, 'art');
    form_hidden('section','ART');
    form_hidden('action','MODIFY');
    form_select_table('filename',$data,str('FILENAME')
                     ,array('class'=>'form_select_tab','width'=>'100%'),'filename',
                      array('NAME'=>''), $edit_id, 'art');
    if (!$edit_id)
      form_submit(str('ART_FILES_DEL_BUTTON'),1,'center');
    form_end();

    echo '<p><h1>'.str('ART_FILES_ADD_TITLE').'<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','ART');
    form_hidden('action','NEW');
    form_input('name',str('FILENAME'),50,'',$_REQUEST['name']);
    form_label(str('ART_FILENAME_PROMPT'));
    form_submit(str('ART_FILES_ADD_BUTTON'),2);
    form_end();

    echo '<p><h1>'.str('OPTIONS').'<p>';
    message($opt);
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'ART');
    form_hidden('action', 'OPTIONS');

    form_radio_static('id3',str('ART_ID3_TAG'),$list,get_sys_pref('use_id3_art','YES'),false,true);
    form_label(str('ART_ID3_TAG_PROMPT'));
    form_submit(str('SAVE_SETTINGS'), 2);
    form_end();
    }

  // ----------------------------------------------------------------------------------
  // Stores the albumart options
  // ----------------------------------------------------------------------------------

  function art_options()
  {
    set_sys_pref('USE_ID3_ART',$_REQUEST["id3"]);
    art_display('','',str('SAVE_SETTINGS_OK'));
  }

  // ----------------------------------------------------------------------------------
  // Delete an existing location
  // ----------------------------------------------------------------------------------

  function art_modify()
  {
    $selected = form_select_table_vals('filename');
    $edit_id = form_select_table_edit('filename', 'art');
    $update_data = form_select_table_update('filename', 'art');

    if(!empty($edit_id))
    {
      art_display('', '', '', $edit_id);
    }
    else if(!empty($update_data))
    {

      $name = $update_data["NAME"];
      $oldname = $update_data["FILENAME"];

      if (empty($name))
        art_display("!".str('ART_ERROR_FILENAME'));
      elseif ( strpos($name,"'") !== false || strpos($name,'"') !== false)
        art_display("!".str('ART_ERROR_QUOTE'));
      elseif ( strpos($name,"/") !== false || strpos($name,"\\") !== false)
        art_display("!".str('ART_ERROR_SLASH'));
      elseif ( !in_array(strtolower(file_ext($name)), array('jpg','jpeg','gif','png')) )
        art_display("!".str('ART_ERROR_FILETYPE'));
      else
      {
        db_sqlcommand("update art_files set filename='".db_escape_str($name)."' where BINARY filename='".db_escape_str($oldname)."'");
        art_display(str('ART_UPDATE_OK'));
      }
    }
    else if(!empty($selected))
    {
      foreach ($selected as $id)
        db_sqlcommand("delete from art_files where BINARY filename='".$id."'");

      art_display(str('ART_DELETE_OK'));
    }
    else
      art_display();
  }

  // ----------------------------------------------------------------------------------
  // Add a new location
  // ----------------------------------------------------------------------------------

  function art_new()
  {
    $name = $_REQUEST["name"];

    if (empty($name))
      art_display('',"!".str('ART_ERROR_FILENAME'));
    elseif ( strpos($name,"'") !== false || strpos($name,'"') !== false)
      art_display('',"!".str('ART_ERROR_QUOTE'));
    elseif ( strpos($name,"/") !== false || strpos($name,"\\") !== false)
      art_display('',"!".str('ART_ERROR_SLASH'));
    elseif ( !in_array(strtolower(file_ext($name)), array('jpg','jpeg','gif','png')) )
      art_display('',"!".str('ART_ERROR_FILETYPE'));
    else
    {
      if ( db_insert_row('art_files',array('filename'=>$name)) === false)
      {
        art_display(db_error());
      }
      else
      {
        art_display(str('ART_ADDED_OK'));
      }
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
