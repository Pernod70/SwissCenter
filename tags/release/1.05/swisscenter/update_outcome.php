<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
   
  page_header("Updating...","","1",'<meta http-equiv="refresh" content="3;URL=index.php">');
  echo '<p>&nbsp;<p>&nbsp;<p><center>';
  switch ($_REQUEST["status"])
  {
    case "ERROR" :
      echo "Update failed - Please try again later.";
      break;
    case "NONE" :
      echo "There are no updates available at this time.";
      break;
    case "UPDATED" :
      echo "Your system has been updated.";
      break;
  }
  echo '</center>';

  page_footer('/');  
   
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
