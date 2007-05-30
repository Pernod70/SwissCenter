<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/../base/swisscenter_configuration.php'));
  
  /**
   * Exports the swisscenter settings to an XML file
   *
   */
  
  $swiss = new Swisscenter_Configuration();
  $swiss->export_all();
  header('Content-Type: text/xml');
  header('Content-Disposition: attachment; filename="Swisscenter Settings.xml"');
  echo $swiss->get_xml();           
  
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>