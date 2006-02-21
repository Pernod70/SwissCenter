<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/browse.php');

  function output_link( $file )
  {
    $contents= file_get_contents($file);
    preg_match('/\bURL=.*\n?/',$contents,$matches);
    $url = substr($matches[0],4);
    return $url;
  }

  browse_fs( str('LISTEN_RADIO')
           , db_col_to_list("select name from media_locations where media_type=4")
           , 'index.php'
           , array('url')
           );


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
