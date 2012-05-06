<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/svn.php'));

  if (!isset($_REQUEST["action"]))
    page_inform(2,"run_svn_update.php?action=update",str('UPDATING'),str('UPDATING_PLEASE_WAIT'));
  elseif ($_REQUEST["action"] == "update")
    header("Location: /update_outcome.php?status=".svn_update());
  else
    page_inform(2,"index.php",str('UPDATING'),str('UNKNOWN_ERROR'));

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
