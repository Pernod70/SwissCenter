<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/prefs.php");

  set_user_pref('style',$_REQUEST["style"]);
  header("Location: /style.php");


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
