<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Displays the language details for editing
  // ----------------------------------------------------------------------------------
  
  function language_display($message = '', $edit = '')
  {
    // Get list of available languages
    $lang_list = array();
    foreach (explode("\n",str_replace("\r",null,file_get_contents(SC_LOCATION.'lang/languages.txt'))) as $line)
    {
      $lang = explode(',',$line);
      if (! is_null($lang[0]) && strlen($lang[0])>0)
        $lang_list[$lang[0]] = $lang[1];
    }
      
    // Load base language 'en'
    $_SESSION["language_base"] = array();
    load_lang_strings("en", "language_base");
    
    // Load selected language
    if (!empty($_REQUEST["lang_id"]) )
    {
      $_SESSION["language_trans"] = array();
      load_lang_strings($_REQUEST["lang_id"], "language_trans");
    }
  
    // Set table contents and background colours, refined by search.
    $text_list = array();
    foreach ($_SESSION["language_base"] as $key=>$text)
    {
       // Apply search filter before adding items to list               
      if (empty($_REQUEST["search"]) || (!empty($_REQUEST["search"]) &&
               (strpos(strtoupper($key.' '.$text.' '.$_SESSION["language_trans"][$key]),strtoupper($_REQUEST["search"]))!==false )) )
      {    
        if ($_SESSION["language_trans"][$key] == '')
          $text_list[] = array("TEXT_ID"=>$key, 
                               "ID"=>$key, 
                               "ENGLISH"=>$text, 
                               "TRANSLATION"=>array("VALUE"=>$_SESSION["language_trans"][$key], "BGCOLOR"=>"red") );
//        elseif ($_SESSION["language_trans"][$key] == '')
//          $text_list[] = array("TEXT_ID"=>$key, 
//                               "ID"=>$key, 
//                               "ENGLISH"=>$text, 
//                               "TRANSLATION"=>array("VALUE"=>$_SESSION["language_trans"][$key], "BGCOLOR"=>"yellow") );
        else
          $text_list[] = array("TEXT_ID"=>$key, 
                               "ID"=>$key, 
                               "ENGLISH"=>$text, 
                               "TRANSLATION"=>array("VALUE"=>$_SESSION["language_trans"][$key], "BGCOLOR"=>"white") );
      }
    }
    
    // Apply filter to table contents, then sort.
    switch ($_REQUEST["filter"])
    {
      case 'MISSING' : $text_list = array_filter($text_list,"missing"); break;
      case 'CHANGED' : $text_list = array_filter($text_list,"changed"); break;
    }
    sort($text_list);
    $text_count = count($text_list);
    
    echo '<h1>'.str('LANG_EDITOR').'</h1>';
    message($message);    
    echo '<p>'.str('LANG_PROMPT');
    
    $this_url = '?search='.$_REQUEST["search"].'&lang_id='.$_REQUEST["lang_id"].'&filter='.$_REQUEST["filter"].'&section=LANGUAGE&action=DISPLAY';
   
    // Display language selection and filters
    form_start('index.php',50);
    form_hidden('section','LANGUAGE');
    form_hidden('action','DISPLAY');
    echo  '<tr><td>'.str('LANG_SELECT').' : 
            '.form_list_static_html('lang_id',$lang_list,$_REQUEST['lang_id'],false,true,true).'&nbsp;
            <a href="'.url_set_param($this_url,'filter','ALL').'"><img align="absbottom" border="0"  src="/images/filter.gif"></a>
            <a href="'.url_set_param($this_url,'filter','CHANGED').'"><img align="absbottom" border="0" src="/images/filter_yellow.gif"></a>
            <a href="'.url_set_param($this_url,'filter','MISSING').'"><img align="absbottom" border="0" src="/images/filter_red.gif"></a>
          </td><td width="50%" align="right">
            '.str('SEARCH').' : 
            <input name="search" value="'.$_REQUEST["search"].'" size=10>
          </td></tr>';
    form_end();

    // Use content box with scrollbars only if enough text
    if ($text_count>10)
      echo '<div style="border:1px solid; width:100%; height:500px; overflow:auto;">';
    
    // Display language texts
    echo '<form enctype="multipart/form-data" action="" method="post">';
    form_hidden('section','LANGUAGE');
    form_hidden('action','MODIFY');
    
    echo '<table class="form_select_tab" width="100%"><tr>
            <th width="4%">&nbsp;</th>
            <th width="30%"> '.str('LANG_TEXT_ID').' </th>
            <th width="30%"> '.str('LANG_TEXT').' </th>
            <th width="30%"> '.str('LANG_TRANS').' </th>
          </tr></table>';
    
    foreach ($text_list as $key=>$text)
      echo '<table class="form_select_tab" width="100%"><tr>
              <td valign="top" width="4%"><input type="checkbox" name="text[]" value="'.$key.'"></input></td>
              <td valign="top" width="30%">
                <a href="?section=language&action=display&edit_id='.$key.'">'.strtr($text["ID"],'_',' ').'</a>
              </td>
              <td valign="top" width="30%">'.$text["ENGLISH"].'</td>
              <td valign="top" width="30%" bgcolor="'.$text["TRANSLATION"]['BGCOLOR'].'">'.$text["TRANSLATION"]['VALUE'].'&nbsp;</td>
            </tr></table>';

    if ($text_count>10)
      echo '</div>';

    // Add delete button
    if (get_sys_pref('IS_DEVELOPMENT','NO') == 'YES')
      echo '<p><table width="100%"><tr><td align="center">
            <input type="Submit" name="delete" value="'.str('LANG_DELETE_BUTTON').'"> &nbsp; 
            </td></tr></table>';
      
    echo '</form>';
  
    if (isset($_REQUEST["edit_id"]))
    {
      echo '<p><h1>'.str('LANG_EDIT').'<p>';
      message($new);
      form_start('index.php');
      form_hidden('section','LANGUAGE');
      form_hidden('action','MODIFY');
      
      $text = $text_list[$_REQUEST["edit_id"]];
      echo '<tr><td>'.str('LANG_TEXT_ID').' :</td>
                <td>'.$text["ID"].'</td></tr>';
      echo '<tr><td>'.str('LANG_TEXT').' :</td>
                <td>'.$text["ENGLISH"].'</td></tr>';
      form_input('translation',str('LANG_TRANS'),50,'',$text["TRANSLATION"]["VALUE"]);
      echo '<p><table width="100%"><tr><td align="center">
        <input type="Submit" name="subaction" value="'.str('LANG_SAVE_BUTTON').'"> &nbsp; 
        <input type="Submit" name="subaction" value="'.str('LANG_CANCEL_BUTTON').'"> &nbsp; 
        </td></tr>';
      form_end();
    }
    elseif (get_sys_pref('IS_DEVELOPMENT','NO') == 'YES')
    {
      echo '<p><h1>'.str('LANG_ADD_NEW').'<p>';
      message($new);
      form_start('index.php');
      form_hidden('section','LANGUAGE');
      form_hidden('action','NEW');
      form_input('text_id',str('LANG_TEXT_ID'),50,'');
      form_input('text',str('LANG_TEXT'),50,'');
      form_submit(str('LANG_ADD_BUTTON'),2);
      form_end();
    }
  }

  // ----------------------------------------------------------------------------------
  // Modifies a translation
  // ----------------------------------------------------------------------------------

  function language_modify()
  {
    if ($_REQUEST["delete"])
      language_delete_text();
    elseif ($_REQUEST["subaction"] == str('LANG_SAVE_BUTTON'))
      language_display(str('LANG_SAVE_OK'));
    elseif ($_REQUEST["subaction"] == str('LANG_CANCEL_BUTTON'))
      language_display();
    else
      send_to_log(1,'Unknown value recieved for "subaction" parameter : '.$_REQUEST["subaction"]);
    
    
    
//    $edit_id = form_select_table_edit('string_ids', 'lang');
//    $update_data = form_select_table_update('string_ids', 'lang');
//    
//    if(!empty($edit_id))
//    {
//      language_display('', $edit_id);
//    }
//    elseif(!empty($update_data))
//    {
//      $trans = $update_data["TRANSLATION"];
//      $id    = $update_data["STRING_IDS"];
//      $_SESSION["language_trans"][$id] = $trans;
//      language_display(str('LANG_UPDATE_OK'));
//    }
//    elseif(!empty($selected_ids))
//    {
//      foreach($selected_ids as $id)
//        unset($_SESSION["language_base"][$id]);
//
//      language_display(str('LANG_DELETE_OK'));
//    }
//    else
//      language_display();
  }

  // ----------------------------------------------------------------------------------
  // Delete selected language strings
  // ----------------------------------------------------------------------------------

  function language_delete_text()
  {
    $id_list = $_REQUEST["text"];
    if (count($id_list) == 0)
      language_display("!".str('LANG_ERROR_NO_SELECT'));
    else
    {
      foreach ($id_list as $id)
        unset($_SESSION["language_base"][$id]);
        
      language_display(str('LANG_DELETE_OK'));
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Add a new language string
  // ----------------------------------------------------------------------------------
  
  function language_new()
  {
    if (empty($_REQUEST["text_id"]))
      language_display("!".str('LANG_ERROR_ID'));
    elseif (empty($_REQUEST["text"]))
      language_display("!".str('LANG_ERROR_STRING'));
    else 
    {
      if (key_exists(strtoupper($_REQUEST["text_id"]),$_SESSION["language_base"]))
        language_display("!".str('LANG_ERROR_ID_EXISTS'));
      else
        $_SESSION["language_base"][strtoupper($_REQUEST["text_id"])] = $_REQUEST["text"];

      send_to_log(4,'Adding new language string',$_SESSION["language_base"][strtoupper($_REQUEST["text_id"])]);
        
      language_display(str('LANG_ADD_OK'));
    }
  }

  // ----------------------------------------------------------------------------------
  // Callback functions used in array_filter
  // ----------------------------------------------------------------------------------
  
  function missing($var)
  {
    return($var['TRANSLATION']['BGCOLOR']=='red');
  }
  
  function changed($var)
  {
    return($var['TRANSLATION']['BGCOLOR']=='yellow');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
