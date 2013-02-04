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
    $lang_id = ( !empty($_REQUEST["lang_id"]) ? $_REQUEST["lang_id"] : substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2) );

    if (empty($message) && isset($_REQUEST["message"]))
      $message = urldecode($_REQUEST["message"]);

    // Get list of available languages
    $lang_list = array();
    foreach (explode("\n",str_replace("\r",null,file_get_contents(SC_LOCATION.'lang/languages.txt'))) as $line)
    {
      $lang = explode(',',$line);
      if (!is_null($lang[0]) && strlen($lang[0])>0)
        $lang_list[$lang[0]] = $lang[1];
    }

    // Load base language 'en'
    if (!isset($_SESSION["language_base"]))
    {
      $_SESSION["language_base"] = array();
      load_lang_strings("en", "language_base");
    }

    // Load selected language, if not already loaded
    if ($lang_list[$_SESSION["language_trans"]["LANGUAGE"]["TEXT"]] !== $lang_id)
    {
      $_SESSION["language_trans"] = array();
      load_lang_strings($lang_id, "language_trans");
    }

    // Set table contents and background colours, refined by search.
    $text_list = array();
    foreach ($_SESSION["language_base"] as $key=>$text)
    {
       // Apply search filter before adding items to list
      if (empty($_REQUEST["search"]) || (!empty($_REQUEST["search"]) &&
               (mb_strpos(mb_strtoupper($key.' '.$text['TEXT'].' '.$_SESSION["language_trans"][$key]['TEXT']),mb_strtoupper(un_magic_quote($_REQUEST["search"])))!==false )) )
      {
        // Highlight text, Red=Missing, Yellow=Changed, White=Valid
        if ($_SESSION["language_trans"][$key]['TEXT'] == '')
          $text_list[$key] = array("ID"=>$key,
                                   "ENGLISH"=>$text['TEXT'],
                                   "TRANSLATION"=>array("TEXT"=>$_SESSION["language_trans"][$key]['TEXT'], "BGCOLOR"=>"red") );
        elseif ($_SESSION["language_trans"][$key]['VERSION'] < $_SESSION["language_base"][$key]['VERSION'])
          $text_list[$key] = array("ID"=>$key,
                                   "ENGLISH"=>$text['TEXT'],
                                   "TRANSLATION"=>array("TEXT"=>$_SESSION["language_trans"][$key]['TEXT'], "BGCOLOR"=>"yellow") );
        else
          $text_list[$key] = array("ID"=>$key,
                                   "ENGLISH"=>$text['TEXT'],
                                   "TRANSLATION"=>array("TEXT"=>$_SESSION["language_trans"][$key]['TEXT'], "BGCOLOR"=>"white") );
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

    $this_url = '?search='.urlencode(un_magic_quote($_REQUEST["search"])).'&lang_id='.$lang_id.'&filter='.$_REQUEST["filter"].'&section=LANGUAGE&action=DISPLAY';

    // Display language selection and filters
    form_start('index.php',50);
    form_hidden('section','LANGUAGE');
    form_hidden('action','DISPLAY');
    echo  '<tr><td>'.str('LANG_SELECT').' :
            '.form_list_static_html('lang_id',$lang_list,$lang_id,false,true,true).'&nbsp;
            <a href="'.url_set_param(url_remove_param($this_url,'search'),'filter','ALL').'"><img align="absbottom" border="0"  src="/images/filter.gif"></a>
            <a href="'.url_set_param(url_remove_param($this_url,'search'),'filter','CHANGED').'"><img align="absbottom" border="0" src="/images/filter_yellow.gif"></a>
            <a href="'.url_set_param(url_remove_param($this_url,'search'),'filter','MISSING').'"><img align="absbottom" border="0" src="/images/filter_red.gif"></a>
          </td><td width="50%" align="right">
            '.str('SEARCH').' :
            <input name="search" value="'.htmlspecialchars(un_magic_quote($_REQUEST["search"])).'" size=10>
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
              <td valign="top" width="30%">'.highlight(htmlspecialchars($text["ENGLISH"]), $_REQUEST["search"]).'</td>
              <td valign="top" width="30%" bgcolor="'.$text["TRANSLATION"]['BGCOLOR'].'">'.highlight(htmlspecialchars($text["TRANSLATION"]['TEXT']), $_REQUEST["search"]).'&nbsp;</td>
            </tr></table>';

    if ($text_count>10)
      echo '</div>';

    // Add delete button (DEVELOPERS ONLY)
    if (get_sys_pref('IS_DEVELOPMENT','NO') == 'YES')
      echo '<p align="center"><input type="submit" value="'.str('LANG_DELETE_BUTTON').'">';

    echo '</form>';

    if (isset($_REQUEST["edit_id"]))
    {
      // Display edit existing string
      echo '<p><h1>'.str('LANG_EDIT').'<p>';
      form_start('index.php');
      form_hidden('section','LANGUAGE');
      form_hidden('action','EDIT');
      $text = $text_list[$_REQUEST["edit_id"]];
      form_hidden('edit_id',$text["ID"]);
      form_hidden('lang_id',$lang_id);

      echo '<tr><td colspan="2"><b>'.str('LANG_TEXT_ID').'</b><td></tr>';
      echo '<tr><td><input size="100" name="" value="'.htmlspecialchars($text["ID"]).'" DISABLED></td></tr>';

      echo '<tr><td colspan="2"><b>'.str('LANG_TEXT').'</b><td></tr>';
      echo '<tr><td><textarea rows="5" cols="100" name="" DISABLED>'.htmlspecialchars($text["ENGLISH"]).'</textarea></td></tr>';

      echo '<tr><td colspan="2"><b>'.str('LANG_TRANS').'</b><td></tr>';
      echo '<tr><td colspan="2"><textarea required rows="5" cols="100" name="translation">'.htmlspecialchars($text["TRANSLATION"]["TEXT"]).'</textarea></td></tr>';

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
      form_input('lang_id',str('LANG_ISO639'),10);
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

    // Save modified text
    $_SESSION["language_trans"][$edit_id] = array('TEXT'    => un_magic_quote($_REQUEST["translation"]),
                                                  'VERSION' => swisscenter_version());

    if ($lang_id == 'en')
      $_SESSION["language_base"][$edit_id] = array('TEXT'    => un_magic_quote($_REQUEST["translation"]),
                                                   'VERSION' => swisscenter_version());

    save_lang($lang_id, $_SESSION["language_trans"]);
    send_to_log(4,'Edited language string '.$edit_id.' => '.un_magic_quote($_REQUEST["translation"]));

    // Reload language
    load_lang();

    $redirect_to = url_remove_param($redirect_to, 'edit_id');
    $redirect_to = url_add_param($redirect_to, 'message', str('LANG_UPDATE_OK'));
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
      foreach ($id_list as $id) { unset($_SESSION["language_base"][$id]); }
      save_lang('en', $_SESSION["language_base"]);
      send_to_log(4,'Removing language string '.$id);
      $redirect_to = url_add_param($redirect_to, 'message', str('LANG_DELETE_OK'));

      // Reload language
      load_lang();
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
    $lang_id   = trim($_REQUEST["lang_id"]);

    if ( empty($lang_name) || empty($lang_id) )
      language_display("!".str('LANG_NEW_FAIL'));
    else
    {
      // Get list of current languages
      $lang_list = array();
      foreach (explode("\n",str_replace("\r",null,file_get_contents(SC_LOCATION.'lang/languages.txt'))) as $line)
      {
        $lang = explode(',',$line);
        if (!is_null($lang[0]) && mb_strlen($lang[0])>0)
          $lang_list[$lang[0]] = $lang[1];
      }

      // Check if language exists
      if (array_search($lang_id, $lang_list) === false)
      {
        // Add new language to list
        $lang_list[$lang_name] = $lang_id;

        // Sort list alphabetically
        asort($lang_list);

        // Save new language list
        $oldumask = umask(0);
        @mkdir(SC_LOCATION."lang/".$lang_id,0777);
        umask($oldumask);
        $lang_file = SC_LOCATION."lang/languages.txt";
        $lang_string = '';
        foreach ($lang_list as $lang_key=>$lang_item)
          $lang_string .= $lang_key.",".$lang_item."\r\n";
        file_put_contents($lang_file, $lang_string);

        // Create new language xml
        $_SESSION["language_trans"] = array();
        $_SESSION["language_trans"]["LANGUAGE"] = array('TEXT' => $lang_name, 'VERSION' => swisscenter_version());
        save_lang($lang_id, $_SESSION["language_trans"]);

        send_to_log(4,'Created new language: '.$lang_id.', '.$lang_name);
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
