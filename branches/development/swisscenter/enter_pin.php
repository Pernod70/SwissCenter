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
  

  echo '<p><center><font color="'.style_value("TITLE_COLOUR").'">'.$message.'</font></center><p>&nbsp;<p>';
  
  if(strlen($pin) > 0)
    echo '<center><font size="large">&gt; &nbsp; '.str_repeat("*", strlen($pin)).' &nbsp; &lt;</font></center>';
  else
    echo '<center><font size="large">&gt; &nbsp; &nbsp; &lt;</font></center>';
  
  echo '<p>&nbsp;<p align="center"><a href="'.$next_url.'">OK</a></center>';

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
      echo '<p>&nbsp;<p align=center>';
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
    echo '<p align="center"><br>Maximum PIN length reached';

  echo '';

  $buttons[] = array('text'=>'Clear PIN'
                    ,'url'=>'enter_pin.php?ok_url='.urlencode($return_url).
                            '&message='.urlencode($message).
                            '&cancel_url='.urlencode($cancel_url).
                            '&pin=');
                     
  $buttons[] = array('text'=>'Delete last digit'
                    ,'url'=>'enter_pin.php?ok_url='.urlencode($ok_url).
                            '&message='.urlencode($message).
                            '&cancel_url='.urlencode($cancel_url).
                            '&pin='.substr($pin, 0, strlen($pin)-1));

  page_footer($cancel_url, $buttons);
?>
