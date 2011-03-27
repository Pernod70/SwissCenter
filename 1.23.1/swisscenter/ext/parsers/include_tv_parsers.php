<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/ParserUtils.php'));
require_once( realpath(dirname(__FILE__).'/ParserConstants.php'));
require_once( realpath(dirname(__FILE__).'/ParserInterface.php'));
require_once( realpath(dirname(__FILE__).'/Parser.php'));

//Include the parser classes
$path_to_classes = dir_to_array( realpath(dirname(__FILE__).'/tv/'), '.*\.php' );

$files = array();
foreach ($path_to_classes as $file)
{
  if ( is_parser($file) )
  {
    $files[] = "$file";
    require_once( "$file" );
  }
}
?>