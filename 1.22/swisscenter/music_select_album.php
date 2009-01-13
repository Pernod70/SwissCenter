<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $name = un_magic_quote($_REQUEST["name"]);

  // Remove SQL filter from history
  $history = search_hist_pop();
  $history["sql"] = preg_replace("/ and .* like '.*'/", "", $history["sql"]);
  search_hist_push($history["url"], $history["sql"]);

  // And go back to the 'selected' screen
  header("Location: /music_selected.php?type=album&name=".rawurlencode($name));

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
