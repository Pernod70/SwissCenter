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
    $id = db_value("select file_id from mp3s where concat(dirname,filename) = '".db_escape_str($file)."'");   
    return pl_link('sql','select * from mp3s where file_id='.$id,'audio');
  }

  browse_fs( 'Browse Music'
           , db_col_to_list("select name from media_locations where media_type=1")
           , 'music.php'
           , array('mp3')
           , ''
           , 'select filename,dirname from mp3s where dirname'
           , 'LOGO_MUSIC' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
