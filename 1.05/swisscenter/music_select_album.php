<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");

  $name = un_magic_quote($_REQUEST["name"]);

  // Now for some complete and utter cowboy code which re-seeds the history and last_picker
  // arrays that were being carefully maintained for browser history.
  
  $_SESSION["history"] = array( array("url"=>"music.php", "sql"=>""));
  $_SESSION["last_picker"] = array("1"=>"/music_search.php?sort=album");

  // And go back to the 'selected' screen
  header("Location: /music_selected.php?add=Y&type=album&name=".rawurlencode($name));

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
