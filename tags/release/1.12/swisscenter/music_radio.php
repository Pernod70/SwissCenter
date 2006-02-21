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
    $contents= substr($contents,strpos($contents,"\nURL=")+4);
    $url=substr($contents,1,strpos($contents,"\n"));
    return $url;
  }

  browse_fs( 'Internet Radio'
           , db_col_to_list("select name from media_locations where media_type=4")
           , 'index.php'
           , array('url')
           , ''
           , ''
           , 'LOGO_MUSIC' )


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
