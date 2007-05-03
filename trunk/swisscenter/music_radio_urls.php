<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));

  function output_link( $file )
  {
    $contents= file_get_contents($file);
    preg_match('/\bURL=.*\n?/',$contents,$matches);
    $url = substr($matches[0],4);
    return $url;
  }

  browse_fs( str('LISTEN_RADIO')
           , db_col_to_list("select name from media_locations where media_type=4")
           , 'music_radio.php'
           , array('url')
           );


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
