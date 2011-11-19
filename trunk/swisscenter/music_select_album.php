<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));

  $name = un_magic_quote($_REQUEST["name"]);

  // Remove SQL filter from history
  $history = page_hist_pop();
  $history["url"] = '/music_selected.php?type=album&name='.rawurlencode($name);
  $history["sql"] = preg_replace("/ and .* like '.*'/", "", $history["sql"]);
  page_hist_current_update($history["url"], $history["sql"]);

  // And go back to the 'selected' screen
  header('Location: '.$history["url"]);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
