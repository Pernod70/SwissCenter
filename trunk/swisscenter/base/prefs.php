<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
 
 require_once( realpath(dirname(__FILE__).'/mysql.php'));
 require_once( realpath(dirname(__FILE__).'/users.php'));
 require_once( realpath(dirname(__FILE__).'/server.php'));
 
 // ----------------------------------------------------------------------------------
 // USER proferences
 // ----------------------------------------------------------------------------------

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
   if (db_value("select count(*) from system_prefs where name='".strtoupper($name)."' and value='$value'") == 0)
   {
     db_sqlcommand("delete from system_prefs where name='".strtoupper($name)."'");
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
 
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
