<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/browse.php');
  require_once('base/playlist.php');

  function output_link( $file )
  {
    return pl_link('file',$file);
  }

  browse_fs('Browse Movies', $_SESSION["opts"]["dirs"]["video"], 'video.php', array('avi','mpg','mpeg'), 'movie_select_all.php?', 'select filename,dirname from movies where dirname' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
