<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/page.php");
  require_once("base/menu.php");
  require_once("base/mysql.php");
  require_once("base/users.php");



  function select_user()
  {
    page_header('Change User', "", "", "", "1", true);
    
    echo '<center>Please select a user from the list:</center><p>';

    $sql = "SELECT user_id, name FROM users";
    if(is_user_selected())
      $sql = $sql." WHERE user_id <> ".get_current_user_id();
      
    $data = db_toarray($sql);

    $menu = new menu();
    foreach($data as $user)
    {
      $menu->add_item($user["NAME"], "change_user.php?id=".$user["USER_ID"]);
    }

    $menu->display();
  }

  function user_selected($user_id)
  {
    if(has_pin($user_id))
    {
      ob_clean();
      header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_user.php?id='.$user_id).
             '&cancel_url=index.php&message='.urlencode('Please enter your PIN'));
    }
    else
    {
      change_user($user_id);
    }
  }
  
  function change_user($user_id, $pin = null)
  {
    page_header( "Change User", "", "", '<meta http-equiv="refresh" content="2;URL=index.php">', 1, true);
      
    // Change user and let them know
    $ok = change_current_user_id($user_id, $pin);
    if($ok)
      echo '<center><font color="'.style_value("PAGE_TEXT").'">Your user has been successfully changed</font></center><br>';
    else
      echo '<center><font color="'.style_value("PAGE_TEXT").'">Incorrect PIN</font></center><br>';
  }

/**************************************************************************************************
   Main page
 *************************************************************************************************/



  $user_id = $_REQUEST["id"];
  $pin = $_REQUEST["pin"];
  
  if(!empty($pin))
    change_user($user_id, $pin);
  elseif(!empty($user_id))
    user_selected($user_id);
  elseif(empty($user_id))
    select_user();

  page_footer('index.php');
 
?>
