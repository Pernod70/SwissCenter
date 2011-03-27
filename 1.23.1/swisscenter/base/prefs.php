<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/mysql.php'));
 require_once( realpath(dirname(__FILE__).'/users.php'));
 require_once( realpath(dirname(__FILE__).'/server.php'));

 // ----------------------------------------------------------------------------------
 // USER preferences
 // ----------------------------------------------------------------------------------

 function get_user_pref_modified_date( $pref, $user_id = '' )
 {
   if ($user_id == '')
     $user_id = get_current_user_id();

   $result = db_value("select modified from user_prefs where user_id = ".$user_id." and name='".strtoupper($pref)."'");

   if (!$result || is_null($result))
     return false;
   else
     return $result;
 }

 function get_user_pref( $pref, $default = '', $user_id = '')
 {
   if ($user_id == '')
     $user_id = get_current_user_id();

   if ($user_id != '')
     $result = db_value("select value from user_prefs where user_id = ".$user_id." and name='".strtoupper($pref)."'");

   if ($result == '')
     return $default;
   else
     return $result;
 }

 function set_user_pref( $name, $value, $user = '')
 {
   if ($user == '')
     $user = get_current_user_id();

   if(!empty($user))
   {
     db_sqlcommand("delete from user_prefs where name='".strtoupper($name)."' and user_id=".$user);
     if ( in_array('modified', db_table_columns('user_prefs')) )
       $result = db_insert_row('user_prefs', array("USER_ID"=>$user, "NAME"=>strtoupper($name), "VALUE"=>$value, "MODIFIED"=>db_datestr()) );
     else
       $result = db_insert_row('user_prefs', array("USER_ID"=>$user, "NAME"=>strtoupper($name), "VALUE"=>$value) );

     if (!$result)
       send_to_log(1,"Unable to store preferemce '$name' = '$value' for user '$user'");
     else
       send_to_log(6,"Set user preference '$name' to '$value' for user '$user'");
   }

   return $result;
 }

 // ----------------------------------------------------------------------------------
 // SYSTEM preferences
 // ----------------------------------------------------------------------------------

 function get_sys_pref_modified_date( $pref )
 {
   $result = db_value("select modified from system_prefs where name='".strtoupper($pref)."'");

   if (!$result || is_null($result))
     return false;
   else
     return $result;
 }

 function get_sys_pref( $pref, $default = '' )
 {
   $result = db_value("select value from system_prefs where name='".strtoupper($pref)."'");

   if ($result == '')
     return $default;
   else
     return $result;
 }

 function set_sys_pref( $name, $value)
 {
   // Only update if the value changes
   if (db_value("select count(*) from system_prefs where name='".strtoupper($name)."' and BINARY value='".db_escape_str($value)."'") == 0)
   {
     db_sqlcommand("delete from system_prefs where name='".strtoupper($name)."'");
     if ( in_array('modified', db_table_columns('system_prefs')) )
       $result = db_insert_row('system_prefs', array("NAME"=>strtoupper($name), "VALUE"=>$value, "MODIFIED"=>db_datestr()) );
     else
       $result = db_insert_row('system_prefs', array("NAME"=>strtoupper($name), "VALUE"=>$value) );

     if (!$result)
       send_to_log(1,"Unable to store system preference '$name' = '$value'");
     else
       send_to_log(6,"Set system preference '$name' to '$value'");

     return $result;
   }
   else
     return true;
 }

 function delete_sys_pref( $name )
 {
   db_sqlcommand("delete from system_prefs where name='".strtoupper($name)."'");
 }

 // ----------------------------------------------------------------------------------
 // Online movie checking
 // ----------------------------------------------------------------------------------

 function is_movie_check_enabled()
 {
   return (internet_available() && get_sys_pref('movie_check_enabled','YES') == 'YES');
 }

 // ----------------------------------------------------------------------------------
 // Online tv show checking
 // ----------------------------------------------------------------------------------

 function is_tv_check_enabled()
 {
   return (internet_available() && get_sys_pref('tv_check_enabled','YES') == 'YES');
 }

 // ----------------------------------------------------------------------------------
 // TVID preferences
 // ----------------------------------------------------------------------------------

 function get_tvid_pref( $player_type, $tvid )
 {
   // All NMT players use the same TVID codes so use PCH A-100 settings
   if ( get_player_model() > 400 )
     $player_type = 'POP-402';

   $data = db_toarray("select tvid_custom, tvid_default from tvid_prefs where player_type='".$player_type."' and tvid_sc='".$tvid."'");

   if ($data == false)
     return $tvid;
   elseif (!is_null($data[0]["TVID_CUSTOM"]))
     return $data[0]["TVID_CUSTOM"];
   elseif (!is_null($data[0]["TVID_DEFAULT"]))
     return $data[0]["TVID_DEFAULT"];
 }

 function set_tvid_pref( $player_type, $tvid, $tvid_pref )
 {
   // All NMT players use the same TVID codes so use PCH A-100 settings
   if ( get_player_model() > 400 )
     $player_type = 'POP-402';

   if (db_value("select count(*) from tvid_prefs where player_type='".$player_type."' and tvid_sc='".$tvid."'") == 0)
     $result = db_insert_row('tvid_prefs', array("PLAYER_TYPE"  => $player_type,
                                                 "TVID_SC"      => $tvid,
                                                 "TVID_CUSTOM"  => $tvid_pref) );
   else
     $result = db_sqlcommand("update tvid_prefs set tvid_custom ='".$tvid_pref."' where player_type='".$player_type."' and tvid_sc='".$tvid."'",false);

   if (!$result)
     send_to_log(1,"Unable to store tvid preference '$player_type','$tvid' = '$tvid_pref'");
   else
     send_to_log(6,"Set tvid preference '$player_type','$tvid' to '$tvid_pref'");

   return $result;
 }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
