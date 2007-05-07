<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
   
  switch ($_REQUEST["status"])
  {
    case "ERROR" :
      page_inform(2,"index.php",str('UPDATE_TITLE'),str('UPDATE_FAILED'));
      break;
    case "NONE" :
      page_inform(2,"index.php",str('UPDATE_TITLE'),str('UPDATE_NONE'));
      break;
    case "UPDATED" :
      page_inform(2,"index.php",str('UPDATE_TITLE'),str('UPDATE_SUCCESS'));
      $_SESSION = array();
      break;
  }
   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
