<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
   
  switch ($_REQUEST["status"])
  {
    case "ERROR" :
      page_inform(2,"index.php","Online Update","Update failed - Please try again later.");
      break;
    case "NONE" :
      page_inform(2,"index.php","Online Update","There are no updates available at this time.");
      break;
    case "UPDATED" :
      page_inform(2,"index.php","Online Update","Your system has been updated.");
      $_SESSION = array();
      break;
  }
   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
