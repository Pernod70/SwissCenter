<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

  $name = $_REQUEST["name"];

  // Remove SQL 'like' filter from history
  $history = array("url" => '/music_selected.php?type=album&name='.rawurlencode($name),
                   "sql" => preg_replace('/ and [a-z]+ like \'.*?\'/', '', page_hist_previous('sql')));

  page_hist_current_update($history["url"], $history["sql"]);

  // And go back to the 'selected' screen
  header('Location: '.$history["url"].'&hist='.PAGE_HISTORY_REPLACE);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
