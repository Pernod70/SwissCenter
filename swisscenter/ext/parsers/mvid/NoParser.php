<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

/**
 * Class to represent blank option in select boxes and in database
 */
class mvid_NoParser extends Parser implements ParserInterface
{
  public $supportedProperties = array ();

  public static function getName() {
    return "None";
  }

  public function isSupportedProperty($propertyName) {
    //always return false, no properties
    return false;
  }

  public function parseProperty($propertyName) {
    $this->setProperty($propertyName, '');
    return '';
  }

  protected function populatePage($search_params = array()) {
  }
}
?>

