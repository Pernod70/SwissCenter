<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
 
 require_once("mysql.php");
 require_once("users.php");
 
 function get_user_pref( $pref, $default = '' )
 {
   if(is_user_selected())
     $result = db_value("select value from user_prefs where user_id = ".get_current_user_id()." and name='".strtoupper($pref)."'");

   if ($result == '')
     return $default;
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
 
 function set_user_pref( $name, $value, $user = '')
 {
   if ($user == '')
     $user = get_current_user_id();
   
   if(!empty($user))
   {
     db_sqlcommand("delete from user_prefs where name='".strtoupper($name)."' and user_id=".$user);
     $result = db_insert_row('user_prefs', array("USER_ID"=>$user, "NAME"=>strtoupper($name), "VALUE"=>$value) );

     if (!$result)
       send_to_log("Unable to store preferemce '$name' = '$value' for user '$user'");
     else
       send_to_log("Set user preference '$name' to '$value' for user '$user'");
   }   
   
   return $result;
 }

 function set_sys_pref( $name, $value)
 {
   db_sqlcommand("delete from system_prefs where name='".strtoupper($name)."'");
   $result = db_insert_row('system_prefs', array("NAME"=>strtoupper($name), "VALUE"=>$value) );

   if (!$result)
     send_to_log("Unable to store system preference '$name' = '$value'");
   else
     send_to_log("Set system preference '$name' to '$value'");

   return $result;
 }
 
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
