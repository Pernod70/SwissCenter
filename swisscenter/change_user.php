<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/menu.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));

  function select_user()
  {
    page_header(str('USER_CHANGE'), "", "", "1", true);
    
    echo '<center>'.font_tags(32).str('SELECT_USER').'</center><p>';

    $condition = "";
    if(is_user_selected())
      $condition = " WHERE user_id <> ".get_current_user_id();
      
    $data = db_toarray("SELECT user_id, name FROM users $condition order by name");

    if($data !== false)
    {
      $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
      $start      = ($page-1) * MAX_PER_PAGE; 
      $end        = min($start+MAX_PER_PAGE,count($data));

      $menu = new menu();    
      if ($page > 1)
        $menu->add_up( url_add_param(current_url(),'page',($page-1)));
      
      if ( count($data) > $end)
        $menu->add_down( url_add_param(current_url(),'page',($page+1)));
      
      for ($i=$start; $i<$end; $i++)
        $menu->add_item($data[$i]["NAME"], "change_user.php?id=".$data[$i]["USER_ID"]);
      
      $menu->display();
    }
    else
    {
      print "<center>".font_tags(32).str('CONFIG_DB_ERROR')."</center>";
    }
    
    page_footer('index.php');    
  }

  function user_selected($user_id)
  {
    if(has_pin($user_id))
    {
      ob_clean();
      header('Location: '.server_address().'enter_pin.php?ok_url='.urlencode('change_user.php?id='.$user_id).
             '&cancel_url=index.php&message='.urlencode(str('PIN_ENTER_PROMPT')));
    }
    else
    {
      change_user($user_id);
    }
  }
  
  function change_user($user_id, $pin = null)
  {
    // Change user and let them know
    $last = get_current_user_id();
    $ok   = change_current_user_id($user_id, $pin);
    
    if($ok)
    {
      // Only confirm the user was changed if we are switching user
      if ($last !== false)
        page_inform(2,"index.php",str('USER_CHANGE'),str('USER_CHANGED'));
      else
        header('Location: index.php');
    }
    else
      page_inform(2,"index.php",str('USER_CHANGE'),str('PIN_INCORRECT'));
  }

/**************************************************************************************************
   Main page
 *************************************************************************************************/

  // if there isn't a user selected already, then use the style for the last valid user to be logged on.
  if (!is_user_selected())
    load_style(get_sys_pref('LAST_USER'));

  $user_id = $_REQUEST["id"];
  $pin = $_REQUEST["pin"];
  
  if(!empty($pin))
    change_user($user_id, $pin);
  elseif(!empty($user_id))
    user_selected($user_id);
  elseif(empty($user_id))
    select_user();
 
?>
