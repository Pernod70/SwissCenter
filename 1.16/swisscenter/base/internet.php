<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));

// ----------------------------------------------------------------------------------
// Checks to see if a TCP socket can be opened to a specific IP address (currently
// set to the IP of http://swisscenter.co.uk/)
// ----------------------------------------------------------------------------------

function internet_check( $timeouts = 3)
{
  $temp = '';

  for ($i=0; $i < $timeouts; $i++)
    if ( $sock = @fsockopen('www.google.com', 80, $temp, $temp, 0.5))
    {
      fclose($sock);
      return true; 
    }

  return false;
}

// ----------------------------------------------------------------------------------
// Returns TRUE if the server has internet connectivity
// ----------------------------------------------------------------------------------

function internet_available()
{
  $check_type = get_sys_pref('internet_setting','AUTO');

  // Has the user selected 'Automatic' for the internet connection?
  if ( $check_type == 'AUTO' )
  {    
    // Don't check more often that ever 900 seconds (15 minutes)
    if ( get_sys_pref('INTERNET_CHECK_TIME',time()) <= time() )
    {
      set_sys_pref('INTERNET_AVAILABLE', internet_check() );    
      set_sys_pref('INTERNET_CHECK_TIME', time()+900);    
    }    
  }
  else
  {
    // User has explicitly set the internet connection to enabled or disabled.
    set_sys_pref('INTERNET_AVAILABLE', ( $check_type == 'YES') );    
  }
  
  // Return the status of the internet connection
  return get_sys_pref('INTERNET_AVAILABLE',false);
}

#-------------------------------------------------------------------------------------------------
# Returns TRUE if there is an update available on the http://swisscenter.co.uk/ website that can
# be downloaded and applied to the urrent installation.
#-------------------------------------------------------------------------------------------------

function update_available()
{
  $next_check = get_sys_pref('UPDATE_CHECK_TIME', time() );
  
  // Make sure that internet connectivity is available, and only check once a day for updates.
  if (get_sys_pref('updates_enabled','YES') == 'YES' && internet_available() && $next_check <= time() )
  {
    $new_update_version = @file_get_contents('http://update.swisscenter.co.uk/release/last_update.txt');
    $status = ( version_compare($new_update_version, swisscenter_version()) > 0);
    
    // Store availability and check again for new messages in 24 hours.
    set_sys_pref('UPDATE_AVAILABLE', $status );    
    set_sys_pref('UPDATE_CHECK_TIME', time()+86400);    
  }

  return get_sys_pref('UPDATE_AVAILABLE',false);
}

#-------------------------------------------------------------------------------------------------
# Attempt to download any messages from the swisscenter website.
#-------------------------------------------------------------------------------------------------

function download_new_messages()
{
  $next_check = get_sys_pref('NEW_MESSAGES_CHECK_TIME', time() );
  
  if ( get_sys_pref('messages_enabled','YES') == 'YES' && internet_available() && $next_check <= time() )
  {
    // Check for new messages
    $last_update = db_value("select max(added) from messages");
    $messages = file_get_contents("http://update.swisscenter.co.uk/messages.php?last_check=".urlencode($last_update));
  
    // Add the messages to the database
    if (!empty($messages))
    {
      $mesg_array = unserialize($messages);
      if (count($mesg_array)>0)
      {
        foreach ($mesg_array as $mesg)
        {
          unset ($mesg["MESSAGE_ID"]);
          db_insert_row('messages',$mesg);
        }
      }
    }
    
    // Check again for new messages in 24 hours.
    set_sys_pref('NEW_MESSAGES_CHECK_TIME', time()+86400);
  }  
}
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
