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

  browse_fs('Internet Radio', $_SESSION["opts"]["dirs"]["radio"], 'music.php', array('url') )


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
