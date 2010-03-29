<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

/**
 * Get an array of online parsers for displaying in a form drop-down list.
 *
 * @param string $type
 * @return array
 */

function get_parsers_list($type)
{
  $path_to_classes = dir_to_array( realpath(dirname(__FILE__).'/'.$type), '.*\.php' );
  foreach ($path_to_classes as $file)
  {
    $name = file_noext($file);
    $parser = new $name;
    $parser_list[$parser->getName()] = $name;
  }
  return $parser_list;
}
?>