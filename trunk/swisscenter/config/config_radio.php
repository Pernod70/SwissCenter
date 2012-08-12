<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  /**
   * Displays the Internet Radio configuration options to the user
   *
   * @param string $message  - A success/fail message when updating search parameters
   * @param string $shoutmsg - A success/fail message when updating Shoutcast settings
   */

  function radio_display( $message = '', $shoutmsg = '', $edit_id = 0 )
  {
    $table  = ( isset($_REQUEST["table"]) ? $_REQUEST["table"] : 'iradio_stations' );
    $types  = array( str('IRADIO_STATION') => 'iradio_stations',
                     str('IRADIO_COUNTRY') => 'iradio_countries',
                     str('IRADIO_GENRE')   => 'iradio_genres' );
    $type   = ( isset($_REQUEST["type"]) ? un_magic_quote($_REQUEST["type"]) : '');
    $param1 = ( isset($_REQUEST["param1"]) ? un_magic_quote($_REQUEST["param1"]) : '');
    $param2 = ( isset($_REQUEST["param2"]) ? un_magic_quote($_REQUEST["param2"]) : '');

    // Form list of radio types
    $iradio_opts = array(array("IRADIO_TYPE"=>IRADIO_SHOUTCAST, "IRADIO_NAME"=>str('IRADIO_SHOUTCAST')),
                         array("IRADIO_TYPE"=>IRADIO_LIVERADIO, "IRADIO_NAME"=>str('IRADIO_LIVERADIO')),
                         array("IRADIO_TYPE"=>IRADIO_LIVE365,   "IRADIO_NAME"=>str('IRADIO_LIVE365')),
                         array("IRADIO_TYPE"=>IRADIO_ICECAST,   "IRADIO_NAME"=>str('IRADIO_ICECAST')),
                         array("IRADIO_TYPE"=>IRADIO_STEAMCAST, "IRADIO_NAME"=>str('IRADIO_STEAMCAST')),
                         array("IRADIO_TYPE"=>IRADIO_TUNEIN,    "IRADIO_NAME"=>str('IRADIO_TUNEIN')));

    for ($i = 0; $i<count($iradio_opts); $i++)
      $iradio_list[$iradio_opts[$i]["IRADIO_NAME"]] = $iradio_opts[$i]["IRADIO_TYPE"];

    echo '<h1>'.str('CONFIG_RADIO_OPTIONS').'</h1>';
    message($message);
    echo '<p>'.str('IRADIO_PARAM_PROMPT');

    // Display radio browse type selection
    form_start('index.php',50);
    form_hidden('section','RADIO');
    form_hidden('action','DISPLAY');
    echo str('IRADIO_PARAM_TYPE').' : '.form_list_static_html('table',$types,$table,false,true,false);
    form_end();

    // Get a list of all items from the database and display them
    switch ( $table )
    {
      case 'iradio_stations':
        $data = db_toarray("SELECT id, (CASE iradio_type
                                        WHEN ".IRADIO_SHOUTCAST." THEN '".str('IRADIO_SHOUTCAST')."'
                                        WHEN ".IRADIO_LIVERADIO." THEN '".str('IRADIO_LIVERADIO')."'
                                        WHEN ".IRADIO_LIVE365." THEN '".str('IRADIO_LIVE365')."'
                                        WHEN ".IRADIO_ICECAST." THEN '".str('IRADIO_ICECAST')."'
                                        WHEN ".IRADIO_STEAMCAST." THEN '".str('IRADIO_STEAMCAST')."'
                                        WHEN ".IRADIO_TUNEIN." THEN '".str('IRADIO_TUNEIN')."'
                                        ELSE 'Unknown'
                                        END
                                       ) iradio_type, station, image FROM $table ORDER BY 2,3");
        $headings = str('IRADIO_TYPE').','.str('IRADIO_STATION').','.str('IRADIO_LOGO');
        $edit_options = array('IRADIO_TYPE'=>$iradio_opts, 'STATION'=>'', 'IMAGE'=>'');
        break;

      case 'iradio_countries':
        $data = db_toarray("SELECT id, country FROM $table ORDER BY 2");
        $headings = str('IRADIO_COUNTRY');
        $edit_options = array('COUNTRY'=>'');
        break;

      case 'iradio_genres':
        $data = db_toarray("SELECT id, genre, subgenre FROM $table ORDER BY 2,3");
        $headings = str('IRADIO_MAINGENRE').','.str('IRADIO_SUBGENRE');
        $edit_options = array('GENRE'=>'', 'SUBGENRE'=>'');
        break;
    }

    form_start('index.php', 150, 'items');
    form_hidden('section', 'RADIO');
    form_hidden('action', 'MODIFY_PARAM');
    form_hidden('table', $table);
    form_select_table('item_ids', $data, $headings
                     ,array('class'=>'form_select_tab','width'=>'100%'), 'id'
                     ,$edit_options, $edit_id, 'items');
    if (!$edit_id)
      form_submit(str('IRADIO_DEL_PARAM_BUTTON'), 1, 'center');
    form_end();

    echo "<p><h1>".str('IRADIO_ADD_PARAM')."</h1>";
    form_start('index.php');
    form_hidden('section', 'RADIO');
    form_hidden('action', 'ADD_PARAM');
    form_hidden('table', $table);

    switch ( $table )
    {
      case 'iradio_stations':
        form_list_static('type',str('IRADIO_TYPE'), $iradio_list, $type, false, false, false);
        form_input('param1', str('IRADIO_STATION'), 20, '', $param1);
        form_input('param2', str('IRADIO_LOGO'), 20, '', $param2, true);
        form_label(str('IRADIO_STATION_PROMPT'));
        break;

      case 'iradio_countries':
        form_input('param1', str('IRADIO_COUNTRY'), 20, '', $param1);
        form_label(str('IRADIO_COUNTRY_PROMPT','<a href="http://www.live-radio.net/">www.live-radio.net</a>'));
        break;

      case 'iradio_genres':
        form_input('param1', str('IRADIO_MAINGENRE'), 20, '', $param1);
        form_input('param2', str('IRADIO_SUBGENRE'), 20, '', $param2);
        form_label(str('IRADIO_GENRE_PROMPT','<a href="http://www.shoutcast.com/">www.shoutcast.com</a>','<a href="http://www.live-radio.net/">www.live-radio.net</a>'));
        break;
    }
    form_submit(str('IRADIO_ADD_PARAM_BUTTON'), 2);
    form_end();

    echo '<p><h1>'.str('CONFIG_SHOUTCAST_TITLE').'</h1><p>';
    message($shoutmsg);
    form_start('index.php');
    form_hidden('section','RADIO');
    form_hidden('action','UPDATE_SHOUTCAST');
    form_input('maxnum',str('IRADIO_MAX_STATIONS'),20,'2',get_sys_pref('iradio_max_stations',24));
    form_label(str('IRADIO_MAX_STATIONS_PROMPT'));
    form_input('cache_expire',str('IRADIO_CACHE_EXPIRE'),20,'',get_sys_pref('iradio_cache_expire',3600));
    form_label(str('IRADIO_CACHE_EXPIRE_PROMPT'));
    form_submit(str('SAVE_SETTINGS'),2);
    form_end();
  }

  /**
   * Adds a new search parameter
   *
   */

  function radio_add_param()
  {
    $table = $_REQUEST["table"];
    switch ( $table )
    {
      case 'iradio_stations':
        $fields = array('iradio_type'=>$_REQUEST["type"],
                        'station'=>un_magic_quote($_REQUEST["param1"]),
                        'image'=>un_magic_quote($_REQUEST["param2"]));
        break;

      case 'iradio_countries':
        $fields = array('country'=>un_magic_quote($_REQUEST["param1"]));
        break;

      case 'iradio_genres':
        $fields = array('genre'=>un_magic_quote($_REQUEST["param1"]),
                        'subgenre'=>(empty($_REQUEST["param2"]) ? un_magic_quote($_REQUEST["param1"]) : un_magic_quote($_REQUEST["param2"])));
        break;
    }

    if(empty($_REQUEST["param1"]))
      radio_display('!'.str('IRADIO_ERROR_PARAM'));
    else
    {
      if(db_insert_row($table, $fields) === false)
        radio_display(db_error());
      else
        radio_display(str('IRADIO_PARAM_ADDED_OK'));
    }
  }

  /**
   * Modifies/deletes an existing search parameter
   *
   */

  function radio_modify_param()
  {
    $selected_ids = form_select_table_vals('item_ids');
    $edit_id = form_select_table_edit('item_ids', 'items');
    $update_data = form_select_table_update('item_ids', 'items');
    $table = $_REQUEST["table"];

    if(!empty($edit_id))
    {
      radio_display('', '', $edit_id);
    }
    elseif(!empty($update_data))
    {
      switch ( $table )
      {
        case 'iradio_stations':
          $fields = "iradio_type=".$update_data["IRADIO_TYPE"].", station='".db_escape_str($update_data["STATION"])."', image='".db_escape_str($update_data["IMAGE"])."'";
          break;

        case 'iradio_countries':
          $fields = "country='".db_escape_str($update_data["COUNTRY"])."'";
          break;

        case 'iradio_genres':
          $fields = "genre='".db_escape_str($update_data["GENRE"])."', subgenre='".db_escape_str($update_data["SUBGENRE"])."'";
          break;
      }
      $id = $update_data["ITEM_IDS"];

      $param = next($update_data);
      if(empty($param))
        radio_display("!".str('IRADIO_ERROR_PARAM'));
      else
      {
        db_sqlcommand("update $table set $fields where id=".$id);
        radio_display(str('IRADIO_UPDATE_PARAM_OK'));
      }
    }
    elseif(!empty($selected_ids))
    {
      foreach($selected_ids as $id)
        db_sqlcommand("delete from $table where id=$id");

      radio_display(str('IRADIO_DELETE_PARAM_OK'));
    }
    else
      radio_display();
  }

  /**
   * Saves the Shoutcast and LiveRadio options.
   *
   */

  function radio_update_shoutcast()
  {
    $maxnum = (int) $_REQUEST["maxnum"];
    $cache_expire = (int) $_REQUEST["cache_expire"];
    if (empty($cache_expire)) $cache_expire = 0;

    if (empty($_REQUEST["maxnum"]))
      radio_display('', "!".str('IRADIO_ERROR_MAXNUM'));
    elseif (empty($maxnum))
      radio_display('', "!".str('IRADIO_ERROR_MAXNUM_ZERO'));
    elseif (empty($_REQUEST["cache_expire"]))
      radio_display('', "!".str('IRADIO_ERROR_CACHE_EXPIRE'));
    else
    {
      set_sys_pref('iradio_max_stations',$maxnum);
      set_sys_pref('iradio_cache_expire',$cache_expire);
      radio_display('', str('SAVE_SETTINGS_OK'));
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
