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
    if ( is_parser($file) )
    {
      $name = file_noext($file);
      $parser = new $name;
      $parser_list[$parser->getName()] = $name;
    }
  }
  return $parser_list;
}

/**
 * Check whether the file is a valid parser.
 *
 * @param string $file
 * @return boolean
 */

function is_parser($file)
{
  $contents = file_get_contents($file);
  if (strpos($contents, 'ParserInterface') > 0 && strpos($contents, '$supportedProperties') > 0)
    return true;
  else
    return false;
}
?>