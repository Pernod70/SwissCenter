<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/page.php");
  require_once("base/menu.php");
  require_once("base/mysql.php");
  require_once("base/users.php");

/**************************************************************************************************
   Main page
 *************************************************************************************************/

  $user_id = get_current_user_id();
  $pin = $_REQUEST["pin"];
  $type = $_REQUEST["type"];
  $newpin1 = $_REQUEST["np"];
  
  if(!empty($type))
  {
    switch($type)
    {
      case "O":
        if(check_pin($user_id, $pin))
        {
          ob_clean();
          header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_pin.php?id='.
                  $user_id.'&type=N1').'&cancel_url=config.php&message='.urlencode('Please enter your new PIN'));
        }
        else
        {
          page_inform(2,'config.php','Change PIN',"That PIN is incorrect");
        }          
        break;
        
      case "N1";
        ob_clean();
        header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_pin.php?id='.
                $user_id.'&np='.$pin.'&type=N2').'&cancel_url=config.php&message='.urlencode('Please confirm your new PIN'));
        break;
        
      case "N2";
        if($pin != $newpin1)
        {
          page_inform(2,'config.php','Change PIN',"The PIN numbers did not match, not changed");
        }
        else
        {
          change_pin($user_id, $pin);
          page_inform(2,'config.php','Change PIN',"Your PIN number has been changed");
        }
        break;
    }
  }
  else
  {
    ob_clean();
    
    if(has_pin($user_id))
    {
      header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_pin.php?id='.
              $user_id.'&type=O').'&cancel_url=config.php&message='.urlencode('Please enter your old PIN'));
    }
    else
    {
      header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_pin.php?id='.
              $user_id.'&type=N1').'&cancel_url=config.php&message='.urlencode('Please enter your new PIN'));
    }
  }
  
  
?>
