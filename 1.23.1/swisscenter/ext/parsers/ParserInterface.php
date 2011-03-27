<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

interface ParserInterface
{
  // Function that parses and returns a property based on a string with the property name, like 'ACTORS', 'TITLE' etc
  public function parseProperty($propertyName);

  public function getProperty($propertyName);

  // Method to determine if the property is supported. true/false
  public function isSupportedProperty($propertyName);

  // Constructor
  public function __construct($id = null, $filename = null, $search_params = null);

  // Returns the name of the parser
  public static function getName();
}
?>

