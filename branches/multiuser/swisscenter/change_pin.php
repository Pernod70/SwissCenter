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
          page_header('Change PIN', '', '', '<meta http-equiv="refresh" content="2;URL=config.php"');
          echo '<center><font color="'.style_value("PAGE_TEXT").
                '">That PIN is incorrect</font></center></br>';
          page_footer('config.php');
        }          
        break;
        
      case "N1";
        ob_clean();
        header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_pin.php?id='.
                $user_id.'&np='.$pin.'&type=N2').'&cancel_url=config.php&message='.urlencode('Please confirm your new PIN'));
        break;
        
      case "N2";
        page_header('Change PIN', '', '', '<meta http-equiv="refresh" content="2;URL=config.php"');
        if($pin != $newpin1)
        {
          echo '<center><font color="'.style_value("PAGE_TEXT").
               '">The PIN numbers did not match, not changed</font></center></br>';
          page_footer('config.php');
        }
        else
        {
          change_pin($user_id, $pin);
          echo '<center><font color="'.style_value("PAGE_TEXT").
               '">Your PIN number has been changed</font></center></br>';
        }
        
        page_footer('config.php');
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
