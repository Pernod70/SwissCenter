<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Displays the language details for editing
  // ----------------------------------------------------------------------------------

  function language_display($message = '', $edit = '')
  {
    // Get important paramters from the URL
    $_SESSION["last_search_page"] = current_url( true );
    if (isset($_REQUEST["lang_tag"]) && !empty($_REQUEST["lang_tag"]))
      $lang_id = db_value("select lang_id from translate_languages where ietf_tag='".$_REQUEST["lang_tag"]."'");
    elseif (isset($_REQUEST["lang_id"]) && !empty($_REQUEST["lang_id"]))
      $lang_id = $_REQUEST["lang_id"];
    else
      $lang_id = db_value("select lang_id from translate_languages where ietf_tag='".substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2)."'");

    if (empty($message) && isset($_REQUEST["message"]))
      $message = urldecode($_REQUEST["message"]);

    // Get list of available languages
    $lang_list = array();
    foreach (db_toarray('select lang_id, name from translate_languages order by name') as $lang)
      $lang_list[$lang['NAME']] = $lang['LANG_ID'];

    // Set table contents and background colours, refined by search.
    $text_list = array();
    $translations = db_toarray("select tk.key_id key_id, tk.text_id id, tb.text base_text, tb.version base_version, tt.text trans_text, tt.version trans_version
                                from translate_keys tk
                                left join translate_text tb on tk.key_id=tb.key_id and tb.lang_id=(select lang_id from translate_languages where ietf_tag='en')
                                left join translate_text tt on tt.key_id=tb.key_id and tt.lang_id=$lang_id
                                order by id");
    foreach ($translations as $translation)
    {
       // Apply search filter before adding items to list
      if (empty($_REQUEST["search"]) || (!empty($_REQUEST["search"]) &&
               (strpos(strtoupper($translation['ID'].' '.$translation['BASE_TEXT'].' '.$translation['TRANS_TEXT']),strtoupper($_REQUEST["search"]))!==false )) )
      {
        // Highlight text, Red=Missing, Yellow=Changed, White=Valid
        $key_id = $translation['KEY_ID'];
        if (empty($translation['TRANS_TEXT']))
          $text_list[$key_id] = array('ID'          => $translation['ID'],
                                      'ENGLISH'     => $translation['BASE_TEXT'],
                                      'TRANSLATION' => array('TEXT'=>$translation['TRANS_TEXT'], 'BGCOLOR'=>"red") );
        elseif ($translation['TRANS_VERSION'] < $translation['BASE_VERSION'])
          $text_list[$key_id] = array('ID'          => $translation['ID'],
                                      'ENGLISH'     => $translation['BASE_TEXT'],
                                      'TRANSLATION' => array('TEXT'=>$translation['TRANS_TEXT'], 'BGCOLOR'=>"yellow") );
        else
          $text_list[$key_id] = array('ID'          => $translation['ID'],
                                      'ENGLISH'     => $translation['BASE_TEXT'],
                                      'TRANSLATION' => array('TEXT'=>$translation['TRANS_TEXT'], 'BGCOLOR'=>"white") );
      }
    }

    // Apply filter to table contents, then sort.
    switch ($_REQUEST["filter"])
    {
      case 'MISSING' : $text_list = array_filter($text_list, 'missing'); break;
      case 'CHANGED' : $text_list = array_filter($text_list, 'changed'); break;
    }
    $text_count = count($text_list);

    echo '<h1>'.str('LANG_EDITOR').'</h1>';
    message($message);
    echo '<p>'.str('LANG_PROMPT');

    $this_url = '?search='.urlencode($_REQUEST["search"]).'&lang_id='.$lang_id.'&filter='.$_REQUEST["filter"].'&section=LANGUAGE&action=DISPLAY';

    // Display language selection and filters
    form_start('index.php',50);
    form_hidden('section','LANGUAGE');
    form_hidden('action','DISPLAY');
    echo  '<tr><td>'.str('LANG_SELECT').' :
            '.form_list_static_html('lang_id',$lang_list,$lang_id,false,true,true).'&nbsp;
            <a href="'.url_set_param(url_remove_param($this_url,'search'),'filter','ALL').'"><img align="absbottom" border="0"  src="../images/filter.gif"></a>
            <a href="'.url_set_param(url_remove_param($this_url,'search'),'filter','CHANGED').'"><img align="absbottom" border="0" src="../images/filter_yellow.gif"></a>
            <a href="'.url_set_param(url_remove_param($this_url,'search'),'filter','MISSING').'"><img align="absbottom" border="0" src="../images/filter_red.gif"></a>
          </td><td width="50%" align="right">
            '.str('SEARCH').' :
            <input name="search" value="'.htmlspecialchars($_REQUEST["search"]).'" size=10>
          </td></tr>';
    form_end();

    // Use content box with scrollbars only if enough text
    if ($text_count>10)
      echo '<div style="border:1px solid; width:100%; height:500px; overflow:auto;">';

    // Display language texts
    echo '<form enctype="multipart/form-data" action="" method="post">';
    form_hidden('section','LANGUAGE');
    form_hidden('action','DELETE');

    // Display table headers
    echo '<table class="form_select_tab" width="100%"><tr>
            <th width="4%">&nbsp;</th>
            <th width="30%"> '.str('LANG_TEXT_ID').' </th>
            <th width="30%"> '.str('LANG_TEXT').' </th>
            <th width="30%"> '.str('LANG_TRANS').' </th>
          </tr></table>';

    // Display table
    foreach ($text_list as $key=>$text)
      echo '<table class="form_select_tab" width="100%"><tr>
              <td valign="top" width="4%"><input type="checkbox" name="text_id[]" value="'.$text["ID"].'"></input></td>
              <td valign="top" width="30%">
                <a href="'.url_set_param($this_url,'edit_id',$key).'">'.strtr(highlight($text["ID"], $_REQUEST["search"]),'_',' ').'</a>
              </td>
              <td valign="top" width="30%">'.highlight(htmlspecialchars($text['ENGLISH']), $_REQUEST["search"]).'</td>
              <td valign="top" width="30%" bgcolor="'.$text['TRANSLATION']['BGCOLOR'].'">'.highlight(htmlspecialchars($text['TRANSLATION']['TEXT']), $_REQUEST["search"]).'&nbsp;</td>
            </tr></table>';

    if ($text_count>10)
      echo '</div>';

    // Add delete button (DEVELOPERS ONLY)
    if (get_sys_pref('IS_DEVELOPMENT','NO') == 'YES')
      echo '<p align="center"><input type="submit" value="'.str('LANG_DELETE_BUTTON').'">';
    echo '</form>';

    // Add export to XML button
    form_start('index.php');
    form_hidden('section','LANGUAGE');
    form_hidden('action','EXPORT');
    form_hidden('lang_id',$lang_id);
    form_submit(str('LANG_EXPORT_XML'),1,'center');
    form_end();

    if (isset($_REQUEST["edit_id"]))
    {
      // Display edit existing string
      echo '<p><h1>'.str('LANG_EDIT').'<p>';
      form_start('index.php');
      form_hidden('section','LANGUAGE');
      form_hidden('action','EDIT');
      $text = $text_list[$_REQUEST["edit_id"]];
      form_hidden('edit_id',$_REQUEST["edit_id"]);
      form_hidden('lang_id',$lang_id);

      echo '<tr><td colspan="2"><b>'.str('LANG_TEXT_ID').'</b><td></tr>';
      echo '<tr><td><input size="100" name="" value="'.htmlspecialchars($text["ID"]).'" DISABLED></td></tr>';

      echo '<tr><td colspan="2"><b>'.str('LANG_TEXT').'</b><td></tr>';
      echo '<tr><td><textarea rows="5" cols="100" name="" DISABLED>'.htmlspecialchars($text['ENGLISH']).'</textarea></td></tr>';

      echo '<tr><td colspan="2"><b>'.str('LANG_TRANS').'</b><td></tr>';
      echo '<tr><td colspan="2"><textarea required rows="5" cols="100" name="translation">'.htmlspecialchars($text['TRANSLATION']['TEXT']).'</textarea></td></tr>';

      form_submit(str('LANG_SAVE_BUTTON'),1);
      form_end();
    }
    else
    {
      // Option to add a new language
      echo '<p><h1>'.str('LANG_NEW').'<p>';
      form_start('index.php');
      form_hidden('section','LANGUAGE');
      form_hidden('action','NEW');
      form_input('lang_name',str('LANG_NATIVE'),20);
      form_input('lang_tag',str('LANG_ISO639'),10);
      form_label(str('LANG_NEW_PROMPT', '<a href=http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes>ISO&nbsp;639-1</a>',
                                        '<a href=http://en.wikipedia.org/wiki/ISO_3166-1>ISO&nbsp;3166-1</a>'));
      form_submit(str('LANG_NEW_BUTTON'),2);
      form_end();
    }
  }

  // ----------------------------------------------------------------------------------
  // Edit a translation
  // ----------------------------------------------------------------------------------

  function language_edit()
  {
    $redirect_to = $_SESSION["last_search_page"];
    $edit_id = $_REQUEST["edit_id"];
    $lang_id = $_REQUEST["lang_id"];

    // Remove existing text
    db_sqlcommand("delete from translate_text where key_id=$edit_id and lang_id=$lang_id");

    // Save modified text
    $success = db_insert_row( 'translate_text', array('KEY_ID'  => $edit_id,
                                                      'LANG_ID' => $lang_id,
                                                      'TEXT'    => $_REQUEST["translation"],
                                                      'VERSION' => swisscenter_version()) );

    send_to_log(4,'Edited language string '.db_value("select text_id from translate_keys where key_id=$edit_id").' => '.$_REQUEST["translation"]);

    $redirect_to = url_remove_param($redirect_to, 'edit_id');
    $redirect_to = url_add_param($redirect_to, 'message', str('LANG_UPDATE_OK'));
    header("Location: $redirect_to");
  }

  // ----------------------------------------------------------------------------------
  // Export a translation
  // ----------------------------------------------------------------------------------

  function language_export()
  {
    $redirect_to = $_SESSION["last_search_page"];
    $lang_tag = db_value("select ietf_tag from translate_languages where lang_id=".$_REQUEST["lang_id"]);

    save_lang_xml($lang_tag);

    $redirect_to = url_add_param($redirect_to, 'message', str('LANG_EXPORT_OK'));
    header("Location: $redirect_to");
  }

  // ----------------------------------------------------------------------------------
  // Remove a language string (DEVELOPERS ONLY)
  // ----------------------------------------------------------------------------------

  function language_delete()
  {
    $redirect_to = $_SESSION["last_search_page"];
    $lang_id = $_REQUEST["lang_id"];
    $id_list = $_REQUEST["text_id"];

    // Delete selected language strings
    if (count($id_list) == 0)
      $redirect_to = url_add_param($redirect_to, 'message', "!".str('LANG_ERROR_NO_SELECT'));
    else
    {
      foreach ($id_list as $id)
      {
        db_sqlcommand("delete from translate_keys where text_id='$id'");
        send_to_log(4,'Removing language string '.$id);
      }
      // Update the en language xml
      save_lang_xml('en');

      $redirect_to = url_add_param($redirect_to, 'message', str('LANG_DELETE_OK'));
    }
    header("Location: $redirect_to");
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

  // ----------------------------------------------------------------------------------
  // Add a new language
  // ----------------------------------------------------------------------------------

  function language_new()
  {
    $lang_name = trim($_REQUEST["lang_name"]);
    $lang_tag  = trim($_REQUEST["lang_tag"]);

    if ( empty($lang_name) || empty($lang_tag) )
      language_display("!".str('LANG_NEW_FAIL'));
    else
    {
      // Check if language exists
      if (!db_value("select name from translate_languages where ietf_tag='$lang_tag'"))
      {
        // Add new language to database
        db_insert_row('translate_languages', array('IETF_TAG'=>$lang_tag, 'NAME'=>$lang_name));

        // Save new language list
        @mkdir(SC_LOCATION."lang/".$lang_tag);
        $lang_file = SC_LOCATION."lang/languages.txt";
        $lang_string = '';
        foreach (db_toarray('select ietf_tag, name from translate_languages order by name') as $lang)
          $lang_string .= $lang['NAME'].",".$lang['IETF_TAG']."\r\n";
        file_put_contents($lang_file, $lang_string);

        // Create new language xml
        save_lang_xml($lang_tag);

        send_to_log(4,'Created new language: '.$lang_tag.', '.$lang_name);
        language_display(str('LANG_NEW_OK'));
      }
      else
        language_display("!".str('LANG_NEW_EXISTS'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
