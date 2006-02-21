<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/browse.php');

  function output_link( $file )
  {
    return 'manage_pl.php?action='.$_REQUEST["action"].'&load='.rawurlencode($file);
  }

  browse_fs('Load Playlist', get_sys_pref("playlists"), 'manage_pl.php', array('m3u') )


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
