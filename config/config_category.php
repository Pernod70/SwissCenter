<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Displays the currently defined categories
  // ----------------------------------------------------------------------------------

  function category_display($del_message = '', $add_message = '', $edit_id = 0)
  {
    $cat           = ( isset($_REQUEST["cat"]) ? $_REQUEST["cat"] : '');
    $cat_name      = ( isset($_REQUEST["cat_name"]) ? un_magic_quote($_REQUEST["cat_name"]) : '');
    $parent_names  = db_toarray('select cat_id VAL, cat_name NAME from categories order by cat_name');
    $download_opts = array( array("VAL"=>'N',"NAME"=> str('DISABLED') )
                          , array("VAL"=>'Y',"NAME"=> str('ENABLED')  ));

    if(empty($cat))
    {
      // Get a list of all of the cats from the database and display them
      $data = db_toarray("SELECT DISTINCT c.cat_id
                               , c.cat_name 'Category'
                               , p.cat_name 'Category_Parent'
                               , (CASE c.download_info
                                  WHEN 'Y' THEN '".str('ENABLED')."'
                                  WHEN 'N' THEN '".str('DISABLED')."'
                                  ELSE 'Unknown' 
                                  END
                                 ) download_info
                            FROM categories c, categories p
                           WHERE c.parent_id=p.cat_id
                        ORDER BY p.cat_id,Category");
      
      echo "<h1>".str('CATEGORIES')."</h1>";
      message($del_message);
      form_start('index.php', 150, 'cats');
      form_hidden('section', 'CATEGORY');
      form_hidden('action', 'MODIFY');

      form_select_table('cat_ids', $data, str('NAME').','.str('CAT_PARENT').','.str('CAT_DOWNLOAD')
                       ,array('class'=>'form_select_tab','width'=>'100%'), 'cat_id'
                       ,array('CATEGORY'=>'','CATEGORY_PARENT'=>$parent_names,'DOWNLOAD_INFO'=>$download_opts)
                       , $edit_id, 'cats');
      if (!$edit_id)
        form_submit(str('CAT_DEL_BUTTON'), 1, 'center');
      form_end();
      
      echo "<p><h1>".str('CAT_ADD_TITLE')."</h1>";
      message($add_message);
      form_start('index.php',200);
      form_hidden('section', 'CATEGORY');
      form_hidden('action', 'ADD');
      form_input('cat_name', str('NAME'), 60, 100, $cat_name);
      form_list_dynamic('parent_id', str('CAT_PARENT'),"select cat_id,cat_name from categories order by cat_name", is_numeric($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : 0);

      // Only display the "Download Info" options if the user has set the global download option
      if (is_movie_check_enabled() )
      {
        form_list_static('dl_info',str('CAT_DOWNLOAD'),array( str('ENABLED')=>'Y',str('DISABLED')=>'N'),'Y');
        form_label(str('CAT_DOWNLOAD_PROMPT'));
      }
      else 
      {
        form_hidden('dl_info', 'N');
      }
      
      form_submit(str('CAT_ADD_BUTTON'), 2, 'left');
      form_end();
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Adds a new category
  // ----------------------------------------------------------------------------------

  function category_add()
  {
    $cat     = rtrim(un_magic_quote($_REQUEST["cat_name"]));
    $parent_id = (is_numeric($_REQUEST["parent_id"]) ? $_REQUEST["parent_id"] : 0);
    $dl_info = rtrim(un_magic_quote($_REQUEST["dl_info"]));
    
    if(empty($cat))
      category_display('', '!'.str('CAT_ERROR_NAME'));
    else
    {
      $exists = db_value("select count(*) from categories where cat_name='" . db_escape_str($cat) . "'");
      
      if($exists != 0)
        category_display('', '!'.str('CAT_ERROR_EXISTS'));
      else
      {
        if(db_insert_row('categories', array('cat_name'=>$cat,'parent_id'=>$parent_id,'download_info'=>$dl_info)) === false)
          category_display('', db_error());
        else
        {
          // Move media_locations from parent category
          $cat_id = db_value("select cat_id from categories where cat_name='" . db_escape_str($cat) . "'");
          db_sqlcommand("update media_locations set cat_id=$cat_id where cat_id=$parent_id");
          category_display('', str('CAT_ADDED_OK'));
        }    
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
      $parent_id     = $update_data["CATEGORY_PARENT"];
      $download_info = db_escape_str($update_data["DOWNLOAD_INFO"]);
      $id = $update_data["CAT_IDS"];
        
      if(empty($category_name))
        category_display("!".str('CAT_ERROR_NAME'));
      else
      {
        db_sqlcommand("update categories
                       set cat_name='$category_name', parent_id=$parent_id, download_info='$download_info'
                       where cat_id=$id");
        // Move media_locations from parent category
        db_sqlcommand("update media_locations set cat_id=$id where cat_id=$parent_id");
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
          if (db_value("select count(*) from categories where parent_id=$cat_id")==0)
          {
            // Ensure that the existing media_locations are updated with no category
            db_sqlcommand("update media_locations set cat_id=1 where cat_id=$cat_id");
            db_sqlcommand("delete from categories where cat_id=$cat_id");
          }
          else
          {
            $category_name = db_value("select cat_name from categories where cat_id=$cat_id");
            $message = "!".str('CAT_ERROR_DELETE',$category_name);
          }
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
