<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/ParserUtils.php'));
require_once( realpath(dirname(__FILE__).'/ParserConstants.php'));
require_once( realpath(dirname(__FILE__).'/ParserInterface.php'));
require_once( realpath(dirname(__FILE__).'/Parser.php'));

$type = 'tv';

//Include the parser classes
$path_to_classes = dir_to_array( realpath(dirname(__FILE__).'/'.$type.'/'), '.*\.php' );

$files = array();
foreach ($path_to_classes as $file)
{
  if ( is_parser($file, $type) )
  {
    $files[] = "$file";
    require_once( "$file" );
  }
  else
  {
    send_to_log(2, 'Parser file '.basename($file).' is not a valid '.$type.' parser and will be ignored');
  }
}
?>