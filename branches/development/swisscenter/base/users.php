<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once('mysql.php');

function get_current_user_id()
{
  return $_SESSION["CURRENT_USER"];
}

function get_current_user_name()
{
  return db_value('SELECT name FROM users WHERE user_id='.get_current_user_id());
}

function change_current_user_id($user_id, $pin = null)
{
  // Check the pin in the db
  $ok = check_pin($user_id, $pin);
  
  if($ok)
    $_SESSION["CURRENT_USER"] = $user_id;
  
  return $ok;
}

function check_pin($user_id, $pin)
{
  $sql = "SELECT count(*) FROM users WHERE user_id=".$user_id." AND pin";
  
  if(empty($pin))
    $sql = $sql." IS NULL";
  else
    $sql = $sql."=".$pin;
    
  return db_value($sql);
}

function has_pin($user_id)
{
  $pin = db_value("SELECT pin FROM users WHERE user_id=".$user_id);
  
  return !empty($pin);
}

function change_pin($user_id, $pin)
{
  if(empty($pin))
    db_sqlcommand("UPDATE users SET pin=NULL WHERE user_id=$user_id");
  else
    db_sqlcommand("UPDATE users SET pin='$pin' WHERE user_id=$user_id");
}

function is_user_selected()
{
  $current_user_id = get_current_user_id();
  
  // If there is no user then try and change to a default user and try again
  if(empty($current_user_id))
  {
    $default_user = get_default_user();
    if(!empty($default_user))
    {
      change_current_user_id($default_user);
      $current_user_id = get_current_user_id();
    }
  }
  
  return !empty($current_user_id);
}

function get_current_user_rank()
{
  $user_id = get_current_user_id();

  if(!empty($user_id))
  {
    $rank = db_value("select c.rank from users,certificates c where users.maxcert=c.cert_id and user_id=$user_id");
  }

  return $rank;
}

function get_default_user()
{
  // Look for only a single user with no PIN
  $data = db_toarray('SELECT name,user_id FROM users WHERE pin IS NULL');
  
  if(count($data) == 1)
    return $data[0]["USER_ID"];
  else
    return null;
}

function get_num_users()
{
  return db_value('SELECT count(*) FROM users');
}

?>
