<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  
  function filter_set( $name = '', $predicate = '' )
  {
    $_SESSION["filter"] = array( "name" => $name, "predicate" => $predicate);
  }
  
  function filter_get_name()
  {
    return $_SESSION["filter"]["name"];
  }
  
  function filter_get_predicate()
  {
    return $_SESSION["filter"]["predicate"];
  }
    
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
