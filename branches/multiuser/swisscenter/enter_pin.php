<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/page.php");
  require_once("base/menu.php");
  require_once("base/mysql.php");
  require_once("base/users.php");
  require_once("base/server.php");




/**************************************************************************************************
   Main page
 *************************************************************************************************/


  page_header("Enter PIN", "", "", "", 1, true);

  $pin = $_REQUEST["pin"];
  $ok_url = $_REQUEST["ok_url"];
  $cancel_url = $_REQUEST["cancel_url"];
  
  $message = $_REQUEST["message"];
  
  if(strstr($ok_url, "?") === false)
    $next_url = $ok_url.'?pin='.$pin;
  else
    $next_url = $ok_url.'&pin='.$pin;
  

  echo '<center>'.$message.'</center><br>';
  
  if(strlen($pin) > 0)
    echo '<center><font size="large">'.str_repeat("*", strlen($pin)).'</font></center>';
  else
    echo '<center><font size="large">&nbsp;</font></center>';
  

  echo '<center>';
  if(strlen($pin) < 10)
  {
    if(get_browser_type() == "SYABAS")
    {
      $buttons[] = array('text'=>'Enter 0',
                         'url'=>'enter_pin.php?ok_url='.urlencode($ok_url).
                           '&message='.urlencode($message).
                           '&cancel_url='.urlencode($cancel_url).
                           '&pin='.$pin.'0');
                          
      for($i = 1; $i < 10; $i++)
      {
        echo '<a href="enter_pin.php?ok_url='.urlencode($ok_url).
            '&message='.urlencode($message).
            '&cancel_url='.urlencode($cancel_url).
            '&pin='.$pin.$i.'" tvid="'.$i.'"></a>&nbsp;';
      }
    }
    else
    {
      for($i = 0; $i < 10; $i++)
      {
        echo '<a href="enter_pin.php?ok_url='.urlencode($ok_url).
            '&message='.urlencode($message).
            '&cancel_url='.urlencode($cancel_url).
            '&pin='.$pin.$i.'">'.$i.'</a>&nbsp;';
      }
    }
  }
  else
    echo 'Maximum PIN length reached';

  echo '<br><br><a href="'.$next_url.'">OK</a></center>';

  $buttons[] = array('text'=>'Clear PIN', 'url'=>'enter_pin.php?url='.urlencode($return_url).'&message='.urlencode($message).'&pin=');
  $buttons[] = array('text'=>'Delete last digit',
                     'url'=>'enter_pin.php?ok_url='.urlencode($ok_url).
                        '&message='.urlencode($message).
                        '&cancel_url='.urlencode($cancel_url).
                        '&pin='.substr($pin, 0, strlen($pin)-1));

  page_footer($cancel_url, $buttons);
?>
