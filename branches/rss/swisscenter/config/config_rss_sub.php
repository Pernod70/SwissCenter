<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Displays the currently defined categories
  // ----------------------------------------------------------------------------------

  function rss_sub_display($main_msg, $add_msg)
  {
    $sub      = ( isset($_REQUEST["sub"]) ? $_REQUEST["sub"] : '');
    
    if(empty($sub))
    {
      // Get a list of all of the subscriptions from the database and display them
      $data = db_toarray("select id,title,url,update_frequency from rss_subscriptions order by title asc");
      
      echo "<h1>".str('RSS_SUBSCRIPTIONS')."</h1>";
      form_start('index.php', 150, 'pod_subs');
      form_hidden('section', 'RSS_SUB');
      form_hidden('action', 'MODIFY');
      form_select_table('sub_ids', $data, str('RSS_SUB_HEADINGS')
                       ,array('class'=>'form_select_tab','width'=>'100%'), 'id'
                        );
      form_submit(str('RSS_SUB_DEL_BUTTON'), 1, 'center');
      form_end();
      
      echo "<p><h1>".str('RSS_SUB_ADD_TITLE')."</h1>";
      message($add_msg);
      form_start('index.php');
      form_hidden('section', 'RSS_SUB');
      form_hidden('action', 'ADD');
      form_input('sub_title', str('TITLE'), 70, 100);
      form_input('sub_url', str('URL'), 70, 1024);
      form_input('sub_updatefreq', str('RSS_UPDATE_FREQ'), 10, '', '', false, '', str('RSS_UPDATE_FREQ_UNITS'));
      form_submit(str('RSS_SUB_ADD_BUTTON'), 2, 'left');
      form_end();
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Adds a new category
  // ----------------------------------------------------------------------------------

  function rss_sub_add()
  {
    $sub_title = rtrim(un_magic_quote($_REQUEST["sub_title"]));
    $sub_url = rtrim(un_magic_quote($_REQUEST["sub_url"]));
    $sub_updatefreq = rtrim(un_magic_quote($_REQUEST["sub_updatefreq"]));
    
    if(empty($sub_title))
      rss_sub_display('', '!'.str('RSS_SUB_ERROR_TITLE'));
    else
    {
      $exists = db_value("select count(*) from rss_subscriptions where title='" . db_escape_str($sub_title) .
        "' OR url='" . db_escape_str($sub_url) . "'");
      
      if($exists != 0)
        rss_sub_display('', '!'.str('RSS_SUB_ERROR_EXISTS'));
      else
      {
        if(db_insert_row('rss_subscriptions', array('title'=>$sub_title, 'url'=>$sub_url,
                         'update_frequency'=>$sub_updatefreq, 'last_update'=>db_datestr())) === false)
          rss_sub_display('', db_error());
        else
          rss_sub_display('', str('RSS_SUB_ADDED_OK'));
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
