<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
 
 require_once("mysql.php");
 
 function set_user_pref( $name, $value, $user = '')
 {
   if ($user == '')
     $user = $_SESSION["opts"]["user_id"];
     
   db_sqlcommand("delete from user_prefs where name='".$name."' and user_id=".$user);
   $result = db_insert_row('user_prefs', array("USER_ID"=>$user, "NAME"=>strtolower($name), "VALUE"=>$value) );
   $_SESSION["opts"]["reload"] = true;
   send_to_log("Set user preference '$name' to '$value' for user '$user'");
   return $result;
 }

 function set_sys_pref( $name, $value)
 {
   db_sqlcommand("delete from system_prefs where name='".$name."'");
   $row = array("NAME"=>strtolower($name), "VALUE"=>$value);
   $result = db_insert_row('system_prefs', $row );

   if (!$result)
     send_to_log('Unable to store system pref',$row);
   else
     send_to_log("Set system preference '$name' to '$value'");

   $_SESSION["opts"]["reload"] = true;
   return $result;
 }
 
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
