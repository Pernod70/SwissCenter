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

  browse_fs('Browse Music', $_SESSION["opts"]["dirs"]["music"], 'music.php', array('mp3'), 'music_select_all.php?', 'select filename,dirname from mp3s where dirname' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
