<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../base/rss.php'));



  // ----------------------------------------------------------------------------------
  // Displays the currently defined categories
  // ----------------------------------------------------------------------------------

  function rss_sub_display($main_msg, $add_msg, $edit = 0)
  {
    $sub      = ( isset($_REQUEST["sub"]) ? $_REQUEST["sub"] : '');
    
    if(empty($sub))
    {
      // Get a list of all of the subscriptions from the database and display them
      $data = db_toarray("select id,title,url,update_frequency from rss_subscriptions order by title asc");
      
      echo "<h1>".str('RSS_SUBSCRIPTIONS')."</h1>";
      message($main_msg);
      form_start('index.php', 150, 'rss_subs');
      form_hidden('section', 'RSS_SUB');
      form_hidden('action', 'MODIFY');
      form_select_table('sub_ids', $data, str('RSS_SUB_HEADINGS')
                       ,array('class'=>'form_select_tab','width'=>'100%'), 'id',
                        array('TITLE'=>'', 'URL'=>'','UPDATE_FREQUENCY'=>''), $edit, 'rss_subs');
      form_submit(str('RSS_SUB_DEL_BUTTON'), 1, 'center');
      form_end();
      
      echo "<p><h1>".str('RSS_SUB_ADD_TITLE')."</h1>";
      message($add_msg);
      form_start('index.php');
      form_hidden('section', 'RSS_SUB');
      form_hidden('action', 'ADD');
      form_input('sub_title', str('TITLE'), 70, 100);
      form_input('sub_url', str('URL'), 70, 1024);
      form_input('sub_updatefreq', str('RSS_UPDATE_FREQ'), 10, '', '', false, '[0-9]{1-5}', str('RSS_UPDATE_FREQ_UNITS'));
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
    else if(empty($sub_url))
      rss_sub_display('', '!'.str('RSS_SUB_ERROR_URL'));
    else if(empty($sub_updatefreq) || !is_numeric($sub_updatefreq) || ($sub_updatefreq <= 0))
      rss_sub_display('', '!'.str('RSS_SUB_ERROR_UPDATEFREQ'));
    else
    {
      $exists = db_value("select count(*) from rss_subscriptions where title='" . db_escape_str($sub_title) .
        "' OR url='" . db_escape_str($sub_url) . "'");
      
      if($exists != 0)
        rss_sub_display('', '!'.str('RSS_SUB_ERROR_EXISTS'));
      else
      {
        $ret_val = rss_create_subscription($sub_title, $sub_url, $sub_updatefreq);
        switch($ret_val)
        {
          case RSS_FAIL_DIR:
            rss_sub_display('', '!'.str('RSS_SUB_ERROR_NOCREATEDIR'));
            break;

          case RSS_FAIL_DB:
            rss_sub_display('', db_error());
            break;
            
          case RSS_FAIL_XFER:
            rss_sub_display('', '!'.str('RSS_SUB_ERROR_DOWNLOAD'));
            break;
            
          default:
            rss_sub_display('', str('RSS_SUB_ADDED_OK'));
        }
      }
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Modifies/deletes an existing category
  // ----------------------------------------------------------------------------------

  function rss_sub_modify()
  {
    $selected_ids = form_select_table_vals('sub_ids');
    $edit_id = form_select_table_edit('sub_ids', 'rss_subs');
    $update_data = form_select_table_update('sub_ids', 'rss_subs');
    

    if(!empty($edit_id))
    {
      rss_sub_display('', '', $edit_id);
    }
    elseif(!empty($update_data))
    {
      $sub_title = $update_data["TITLE"];
      $id = $update_data["SUB_IDS"];
      $sub_url = $update_data["URL"];
      $sub_updatefreq = $update_data["UPDATE_FREQUENCY"];

      if(empty($sub_title))
        rss_sub_display("!".str('RSS_SUB_ERROR_TITLE'));
      else if(empty($sub_url))
        rss_sub_display('!'.str('RSS_SUB_ERROR_URL'));
      else if(empty($sub_updatefreq) || !is_numeric($sub_updatefreq) || ($sub_updatefreq <= 0))
        rss_sub_display('!'.str('RSS_SUB_ERROR_UPDATEFREQ'));
      else
      {
        if(rss_update_subscription($id, $sub_title, $sub_url, $sub_updatefreq) == RSS_OK)
          rss_sub_display(str('RSS_SUB_UPDATE_OK'));
        else
          rss_sub_display(db_error());
      }
    }
    elseif(!empty($selected_ids))
    {
      $message = str('RSS_SUB_DELETE_OK');

      foreach($selected_ids as $sub_id)
      {
        // Delete the RSS subscription
        if(rss_delete_subscription($sub_id) != RSS_OK)
          $message = '!'.str('RSS_SUB_ERROR_DELETE');
      }
  
      rss_sub_display($message);
    }
    else
      rss_sub_display();
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
