<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once('mysql.php');

function get_current_user_id()
{
  return 1;//$_SESSION["CURRENT_USER"];
}

function change_current_user_id($user_id)
{
  $_SESSION["CURRENT_USER"] = $user_id;
}

function is_user_selected()
{
  $current_user_id = get_current_user_id();
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

?>
