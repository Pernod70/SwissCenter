<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("settings.php");

#-------------------------------------------------------------------------------------------------
# Check that the swisscenter ini file exists and display an error message if it doesn't.
#-------------------------------------------------------------------------------------------------

if (! file_exists(SC_LOCATION.'/config/swisscenter.ini'))
 {
  if (is_showcenter()) 
    fatal_error('Your SwissCenter Configuration file is missing.'
                ,'Please use the Configuration Utility to create a configuration file');
  else
    fatal_error('Your SwissCenter Configuration file is missing.'
                ,'Please use the <a href="/config/index.php">Configuration Utility<a> to create a configuration file');
}
  
#-------------------------------------------------------------------------------------------------
# Whenever a new session starts, check that there is some media in the database. If not, inform
# the user that they need to add a location or do a media search.
#-------------------------------------------------------------------------------------------------

if (!isset($_SESSION["media_check"]))
{ 
  $media_count = db_value("select count(*) from media_locations");

  if ($media_count ==0)
  {
    if (is_showcenter()) 
      fatal_error('You have no media locations defined.','Please use the Configuration Utility to add media locations');
    else
      fatal_error('You have no media locations defined.','Please use the <a href="/config/index.php">Configuration Utility<a> to add media locations.');
  }

  $_SESSION["media_check"] = 'YES';  
}

#-------------------------------------------------------------------------------------------------
# Whenever a new session starts, determine the details of the client and store them 
# in the database.
#-------------------------------------------------------------------------------------------------

if (!isset($_SESSION["device"]))
{ 
  $device = array();
  $device["ip_address"]   = str_replace('\\','/',$_SERVER["REMOTE_ADDR"]);
  $device["agent_string"] = $_SERVER['HTTP_USER_AGENT'];
  
	db_sqlcommand("delete from clients where ip_address='$ip'");
	db_insert_row('clients',$device);

  $_SESSION["device"] = $device;  
}

?>
