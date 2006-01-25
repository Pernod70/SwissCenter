<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Displays the currently defined categories
  // ----------------------------------------------------------------------------------

  function category_display($del_message = '', $add_message = '', $edit_id = 0)
  {
    $cat      = ( isset($_REQUEST["cat"]) ? $_REQUEST["cat"] : '');
    $cat_name = ( isset($_REQUEST["cat_name"]) ? un_magic_quote($_REQUEST["cat_name"]) : '');
    
    if(empty($cat))
    {
      // Get a list of all of the cats from the database and display them
      $data = db_toarray("select cat_id,cat_name 'Category' from categories order by Category");
      
      echo "<h1>".str('CATEGORIES')."</h1>";
      message($del_message);
      form_start('index.php', 150, 'cats');
      form_hidden('section', 'CATEGORY');
      form_hidden('action', 'MODIFY');
      form_select_table('cat_ids', $data, str('NAME')
                       ,array('class'=>'form_select_tab','width'=>'100%'), 'cat_id',
                        array('CATEGORY'=>''), $edit_id, 'cats');
      form_submit(str('CAT_DEL_BUTTON'), 1, 'center');
      form_end();
      
      echo "<p><h1>".str('CAT_ADD_TITLE')."</h1>";
      message($add_message);
      form_start('index.php');
      form_hidden('section', 'CATEGORY');
      form_hidden('action', 'ADD');
      form_input('cat_name', str('NAME'), 70, 100, $cat_name);
      form_submit(str('CAT_ADD_BUTTON'), 2, 'left');
      form_end();
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Adds a new category
  // ----------------------------------------------------------------------------------

  function category_add()
  {
    $cat = rtrim(un_magic_quote($_REQUEST["cat_name"]));
    
    if(empty($cat))
      category_display('', '!'.str('CAT_ERROR_NAME'));
    else
    {
      $exists = db_value("select count(*) from categories where cat_name='" . db_escape_str($cat) . "'");
      
      if($exists != 0)
        category_display('', '!'.str('CAT_ERROR_EXISTS'));
      else
      {
        if(db_insert_row('categories', array('cat_name'=>$cat)) === false)
          category_display('', db_error());
        else
          category_display('', str('CAT_ADDED_OK'));
      }
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Modifies/deletes an existing category
  // ----------------------------------------------------------------------------------

  function category_modify()
  {
    $selected_ids = form_select_table_vals('cat_ids');
    $edit_id = form_select_table_edit('cat_ids', 'cats');
    $update_data = form_select_table_update('cat_ids', 'cats');
    $default_cat = db_value('select cat_name from categories where cat_id=1');
    
    if(!empty($edit_id))
    {
      category_display('', '', $edit_id);
    }
    elseif(!empty($update_data))
    {
      $category_name = db_escape_str($update_data["CATEGORY"]);
      $id = $update_data["CAT_IDS"];
      
      if(empty($category_name))
        category_display("!".str('CAT_ERROR_NAME'));
      else
      {
        db_sqlcommand("update categories set cat_name='$category_name' where cat_id=$id");
        category_display(str('CAT_UPDATE_OK'));
      }
    }
    elseif(!empty($selected_ids))
    {
      $message = str('CAT_DELETE_OK');

      foreach($selected_ids as $cat_id)
      {
        if($cat_id != 1)
        {
          // Ensure that the existing media_locations are updated with no category
          db_sqlcommand("update media_locations set cat_id=1 where cat_id=$cat_id");
          db_sqlcommand("delete from categories where cat_id=$cat_id");
        }
        else
          $message = "!".str('CAT_ERROR_DELETE',$default_cat);
      }
  
      category_display($message);
    }
    else
      category_display();
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
