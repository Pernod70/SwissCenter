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
    return pl_link('file',$file,'photo');
  }

  browse_fs('Browse Photographs', $_SESSION["opts"]["dirs"]["photo"], 'photo.php', array('gif','png','jpg','jpeg'), 'photo_select_all.php?', 'select filename,dirname from photos where dirname','LOGO_PHOTO' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
