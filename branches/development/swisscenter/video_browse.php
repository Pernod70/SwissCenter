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

  browse_fs( 'Browse Movies'
           , db_col_to_list("select name from media_locations where media_type=3")
           , 'video.php'
           , explode(',',MEDIA_EXT_MOVIE)
           , ''
           , 'select filename,dirname from movies where dirname'
           , 'LOGO_MOVIE' );
           
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
