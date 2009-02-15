<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/stylelib.php'));

/**
 * Records the current user and updates the user timeout value (default 4 hours).
 *
 */

function set_user_timeout()
{
  $_SESSION["CURRENT_USER_TIMEOUT"] = (time() + get_sys_pref('USER_TIMEOUT',14400));
}

/**
 * Gets the user ID of the currently logged on user.
 *
 * @return integer
 */

function get_current_user_id()
{
  // User is logged in?
  if (isset($_SESSION["CURRENT_USER"]) )
  {
    // User has a PIN and is therefore subject to a timeout?
    if ( has_pin($_SESSION["CURRENT_USER"]) && time() > $_SESSION["CURRENT_USER_TIMEOUT"])
    {
      return false;
    }
    else
    {
      set_user_timeout();
      return $_SESSION["CURRENT_USER"];
    }
  }
  else
    return false;
}

/**
 * Returns the name of the currently logged on user
 *
 * @return string
 */

function get_current_user_name()
{
  return db_value('SELECT name FROM users WHERE user_id='.get_current_user_id());
}

/**
 * Returns the last user that was logged on to the system
 *
 * @return integer - User ID (or false if there was no *valid* last user
 */

function get_last_user()
{
  $user_id = get_sys_pref('LAST_USER');
  if ( db_value("select count(*) from users where user_id = $user_id") != 0)
    return $user_id;
  else
    return false;
}

/**
 * Sets the last user that was logged on to the system
 *
 * @param integer $user_id - User ID
 */

function set_last_user( $user_id )
{
  set_sys_pref('LAST_USER',$user_id);
}

/**
 * Sets the current user to the ID given provided the supplied PIN is correct.
 *
 * @param integer $user_id - User to make current
 * @param string $pin - PIN code for the user
 * @return boolean
 */

function change_current_user_id($user_id, $pin = null)
{
  // Check the pin in the db
  $ok = check_pin($user_id, $pin);

  if($ok)
  {
    set_last_user( $user_id );
    $_SESSION["CURRENT_USER"] = $user_id;
    set_user_timeout();
    load_style();
    load_lang();
  }

  return $ok;
}

/**
 * Validates the PIN against the specified user.
 *
 * @param integer $user_id - User ID to check PIN for
 * @param string $pin - The PIN number
 * @return boolean
 */

function check_pin($user_id, $pin)
{
  $sql = "SELECT count(*) FROM users WHERE user_id=".$user_id." AND pin";

  if(empty($pin))
    $sql = $sql." IS NULL";
  else
    $sql = $sql."=".$pin;

  return db_value($sql);
}

/**
 * Returns true if the user has set a PIN on their account
 *
 * @param integer $user_id
 * @return boolean
 */

function has_pin($user_id)
{
  $pin = db_value("SELECT pin FROM users WHERE user_id=".$user_id);

  return !empty($pin);
}

/**
 * Sets the PIN for the specified user
 *
 * @param integer $user_id
 * @param string $pin
 */

function change_pin($user_id, $pin)
{
  if(empty($pin))
    db_sqlcommand("UPDATE users SET pin=NULL WHERE user_id=$user_id");
  else
    db_sqlcommand("UPDATE users SET pin='$pin' WHERE user_id=$user_id");
}

/**
 * Returns whether or not a user is currently selected.
 *
 * @return boolean
 */

function is_user_selected()
{
  $current_user_id = get_current_user_id();

  // If there is no user then try and change to a default user and try again
  if($current_user_id === false)
  {
    $default_user = get_default_user();
    if( $default_user !== false)
    {
      change_current_user_id($default_user);
      $current_user_id = get_current_user_id();
    }
  }

  return !($current_user_id === false);
}

/**
 * Gets the maximum rank (in terms of media certificates) that the specified user is able to access.
 *
 * @return integer
 */

function get_current_user_rank()
{
  $user_id = get_current_user_id();

  if(!empty($user_id))
  {
    $rank = db_value("select c.rank from users,certificates c where users.maxcert=c.cert_id and user_id=$user_id");
  }

  return $rank;
}

/**
 * Returns the default user ID if a default user is configured, or FALSE otherwise.
 *
 * @return integer
 */

function get_default_user()
{
  // Look for only a single user with no PIN
  $data = db_toarray('SELECT name,user_id FROM users WHERE pin IS NULL');

  if(count($data) == 1)
    return $data[0]["USER_ID"];
  else
    return FALSE;
}

/**
 * Returns the number of users defined in the system
 *
 * @return integer
 */

function get_num_users()
{
  return db_value('SELECT count(*) FROM users');
}

/**
 * Returns whether or not a user is admin (Super User).
 *
 * @return boolean
 */

function is_user_admin()
{
  $user_id = get_current_user_id();

  if(!empty($user_id))
  {
    $admin = db_value("select admin from users where user_id=$user_id");
  }

  return ($admin==1);
}
?>
