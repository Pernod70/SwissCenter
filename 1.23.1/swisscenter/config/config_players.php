<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

// ----------------------------------------------------------------------------------
// Displays the players details for editing
// ----------------------------------------------------------------------------------

function players_display($delete = '', $new = '', $edit_id = 0)
{
  $chipset_opts    = array( array("VAL"=>'EM8550',  "NAME"=> 'EM8550' )
                          , array("VAL"=>'EM8620L', "NAME"=> 'EM8620L')
                          , array("VAL"=>'SMP8635', "NAME"=> 'SMP8635')
                          , array("VAL"=>'SMP8643', "NAME"=> 'SMP8643'));
  $yes_no_opts     = array( array("VAL"=>'YES',     "NAME"=> str('YES'))
                          , array("VAL"=>'NO',      "NAME"=> str('NO')));
  $pod_opts        = array( array("VAL"=>1,         "NAME"=> 1)
                          , array("VAL"=>2,         "NAME"=> 2)
                          , array("VAL"=>3,         "NAME"=> 3)
                          , array("VAL"=>4,         "NAME"=> 4)
                          , array("VAL"=>5,         "NAME"=> 5));
  $transition_opts = array( array("VAL"=>0,         "NAME"=> 0)
                          , array("VAL"=>1,         "NAME"=> 1)
                          , array("VAL"=>2,         "NAME"=> 2)
                          , array("VAL"=>3,         "NAME"=> 3)
                          , array("VAL"=>4,         "NAME"=> 4)
                          , array("VAL"=>5,         "NAME"=> 5)
                          , array("VAL"=>6,         "NAME"=> 6)
                          , array("VAL"=>7,         "NAME"=> 7)
                          , array("VAL"=>8,         "NAME"=> 8));

  $data = db_toarray("select player_id, name, make, model, chipset,
                            (CASE resume WHEN 'NO' THEN '".str('NO')."'
                             WHEN 'YES' THEN '".str('YES')."'
                             END)resume, pod_sync, pod_no_sync, pod_stream, transition
                             from client_profiles order by name, model");

  echo "<h1>".str('PLAYER_CONFIG')."</h1>";
  message($delete);
  echo '<p>'.str('PLAYER_MESSAGE');

  echo '<form name="players"enctype="multipart/form-data" action="" method="post">';
  form_hidden('section','PLAYERS');
  form_hidden('action','MODIFY');

  // Use content box with scrollbars
  echo '<div style="border:1px solid; width:100%; height:500px; overflow:auto;">';
  echo '<table>';
  form_select_table('player_id',$data, str('PLAYER_NAME').','.str('PLAYER_MAKE').','.str('PLAYER_MODEL').','.str('PLAYER_CHIPSET').','.str('PLAYER_RESUME').','.str('PLAYER_POD_SYNC').','.str('PLAYER_POD_NO_SYNC').','.str('PLAYER_POD_STREAM').','.str('PLAYER_TRANSITION')
                   ,array('class'=>'form_select_tab','width'=>'100%'),'player_id',
                    array('NAME'=>'', 'MAKE'=>'', 'MODEL'=>'', 'CHIPSET'=>$chipset_opts, 'RESUME'=>$yes_no_opts, 'POD_SYNC'=>array_merge(array( array("VAL"=>0, "NAME"=>0)), $pod_opts), 'POD_NO_SYNC'=>$pod_opts, 'POD_STREAM'=>$pod_opts, 'TRANSITION'=>$transition_opts)
                   , $edit_id, 'players');
  echo '</table>';
  echo '</div>';

  // Add delete button
  if (!$edit_id)
    echo '<p align="center"><input type="submit" value="'.str('PLAYER_DEL_BUTTON').'">';

  echo '</form>';

  echo '<p><h1>'.str('PLAYER_ADD_TITLE').'<p>';
  message($new);
  form_start('index.php');
  form_hidden('section','PLAYERS');
  form_hidden('action','NEW');
  form_input('name',str('PLAYER_NAME'),50,'',un_magic_quote($_REQUEST['name']));
  form_input('make',str('PLAYER_MAKE'),10,'',un_magic_quote($_REQUEST['make']));
  form_input('model',str('PLAYER_MODEL'),10,'',un_magic_quote($_REQUEST['model']));
  form_list_static('chipset',str('PLAYER_CHIPSET'), array( 'EM8550'=>'EM8550', 'EM8620L'=>'EM8620L', 'SMP8635'=>'SMP8635', 'SMP8643'=>'SMP8643'), $_REQUEST['chipset'], false, false, false);
  form_label(str('PLAYER_PROMPT'));
  form_list_static('resume',str('PLAYER_RESUME'), array( str('YES')=>'YES',str('NO')=>'NO'), $_REQUEST['resume'], false, false, false);
  form_label(str('PLAYER_RESUME_PROMPT'));
  form_list_static('pod_sync',str('PLAYER_POD_SYNC'), array( 0=>0, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5), $_REQUEST['pod_sync'], false, false, false);
  form_list_static('pod_no_sync',str('PLAYER_POD_NO_SYNC'), array( 1=>1, 2=>2, 3=>3, 4=>4, 5=>5), $_REQUEST['pod_no_sync'], false, false, false);
  form_list_static('pod_stream',str('PLAYER_POD_STREAM'), array( 1=>1, 2=>2, 3=>3, 4=>4, 5=>5), $_REQUEST['pod_stream'], false, false, false);
  form_label(str('PLAYER_POD_PROMPT'));
  form_list_static('transition',str('PLAYER_TRANSITION'), array( 0=>0, 1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6, 7=>7, 8=>8), $_REQUEST['transition'], false, false, false);
  form_label(str('PLAYER_TRANSITION_PROMPT'));

  form_submit(str('PLAYER_ADD_BUTTON'),2);
  form_end();

  // Display all detected clients on the network
  echo "<h1>".str('SUPPORT_CLIENTS_TITLE')."</h1><p>";
  array_to_table( db_toarray("select device_type, name, box_id, concat(browser_x_res,'x',browser_y_res) from clients left join client_profiles on make=substring(device_type,1,3) and model=substring(device_type,5,3) order by 1,2"), 'Make-Model,Name,Firmware,Resolution');
}

// ----------------------------------------------------------------------------------
// Modify/delete an existing player
// ----------------------------------------------------------------------------------

function players_modify()
{
  $selected = form_select_table_vals('player_id');               // Get the selected items
  $edit     = form_select_table_edit('player_id', 'players');    // Get the id of the edited row
  $update   = form_select_table_update('player_id', 'players');  // Get the updates from an edit

  if(!empty($edit))
  {
    // There was an edit, display the players with the table in edit mode on the selected row
    players_display('', '', $edit);
  }
  elseif(!empty($update))
  {
    // Update the row given in the database and redisplay the players
    $id          = $update["PLAYER_ID"];
    $name        = $update["NAME"];
    $make        = strtoupper($update["MAKE"]);
    $model       = $update["MODEL"];
    $chipset     = $update["CHIPSET"];
    $resume      = $update["RESUME"];
    $pod_sync    = $update["POD_SYNC"];
    $pod_no_sync = $update["POD_NO_SYNC"];
    $pod_stream  = $update["POD_STREAM"];
    $transition  = $update["TRANSITION"];

    send_to_log(4,'Updating player details',$update);

    if (empty($name) || empty($make) || empty($model))
      players_display("!".str('PLAYER_ERROR_MISSING'));
    elseif (!is_numeric($model))
      players_display("!".str('PLAYER_ERROR_MODEL'));
    elseif (db_value("select player_id from client_profiles where make='$make' and model=$model and player_id<>$id"))
      players_display("!".str('PLAYER_ERROR_EXISTS'));
    else
    {
      db_sqlcommand("update client_profiles set name='".db_escape_str($name)."',make='".db_escape_str($make)."',model=$model,chipset='".db_escape_str($chipset)."',resume='$resume',pod_sync=$pod_sync,pod_no_sync=$pod_no_sync,pod_stream=$pod_stream,transition=$transition
                      where player_id=$id");

      save_players_config();
      players_display(str('PLAYER_UPDATE_OK'));
    }
  }
  elseif(!empty($selected))
  {
    // Delete the selected players
    foreach ($selected as $id)
    {
      db_sqlcommand("delete from client_profiles where player_id=$id");
    }

    save_players_config();
    players_display(str('PLAYER_DEL_OK'));
  }
  else
    players_display();
}

// ----------------------------------------------------------------------------------
// Add a new player
// ----------------------------------------------------------------------------------

function players_new()
{
  $name        = un_magic_quote($_REQUEST["name"]);
  $make        = strtoupper($_REQUEST["make"]);
  $model       = $_REQUEST["model"];
  $chipset     = $_REQUEST["chipset"];
  $resume      = $_REQUEST["resume"];
  $pod_sync    = $_REQUEST["pod_sync"];
  $pod_no_sync = $_REQUEST["pod_no_sync"];
  $pod_stream  = $_REQUEST["pod_stream"];
  $transition  = $_REQUEST["transition"];

  if (empty($name) || empty($make) || empty($model))
    players_display('',"!".str('PLAYER_ERROR_MISSING'));
  elseif (!is_numeric($model))
    players_display('',"!".str('PLAYER_ERROR_MODEL'));
  elseif (db_value("select player_id from client_profiles where make='$make' and model=$model"))
    players_display('',"!".str('PLAYER_ERROR_EXISTS'));
  else
  {
    $new_row = array( 'name'        => $name
                    , 'make'        => $make
                    , 'model'       => $model
                    , 'chipset'     => $chipset
                    , 'resume'      => $resume
                    , 'pod_sync'    => $pod_sync
                    , 'pod_no_sync' => $pod_no_sync
                    , 'pod_stream'  => $pod_stream
                    , 'transition'  => $transition);

    send_to_log(4,'Adding new media player', $new_row);

    if ( db_insert_row('client_profiles', $new_row) === false)
      players_display(db_error());
    else
    {
      save_players_config();
      players_display('',str('PLAYER_ADD_OK'));
    }
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
