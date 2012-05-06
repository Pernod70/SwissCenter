<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

// ----------------------------------------------------------------------------------
// Displays options for recent view
// ----------------------------------------------------------------------------------

function recent_display( $message = '' )
{
  $list_recent_type = array( str('RECENTLY_ADDED')            => 'ADDED',
                             str('RECENTLY_CREATED')          => 'CREATED',
                             str('RECENTLY_ADDED_OR_CREATED') => 'ADDED_OR_CREATED' );

  echo "<h1>".str('RECENT_OPTIONS')."</h1>";
  message($message);

  form_start('index.php', 150, 'recent');
  form_hidden('section', 'RECENT');
  form_hidden('action', 'UPDATE');

  form_input('recent_date_limit', str('RECENT_DATE_LIMIT'), 3, '', get_sys_pref("RECENT_DATE_LIMIT", 14));
  form_label(str('RECENT_DATE_LIMIT_PROMPT'));
  form_list_static('recent_date_type', str('RECENT_DATE_TYPE'), $list_recent_type, get_sys_pref("RECENT_DATE_TYPE", "ADDED"), false, false, false);
  form_label(str('RECENT_DATE_TYPE_PROMPT'));
  form_submit(str('SAVE_SETTINGS'));
  form_end();
}

// ----------------------------------------------------------------------------------
// Saves the new parameters
// ----------------------------------------------------------------------------------

function recent_update()
{
  $recent_date_limit = $_REQUEST["recent_date_limit"];
  $recent_date_type  = $_REQUEST["recent_date_type"];

  if ($recent_date_limit < 1)
    $recent_date_limit = 1;

  set_sys_pref("RECENT_DATE_LIMIT", $recent_date_limit);
  set_sys_pref("RECENT_DATE_TYPE", $recent_date_type);

  recent_display(str('SAVE_SETTINGS_OK'));
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>