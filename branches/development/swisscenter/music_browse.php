<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/browse.php');
  require_once('base/playlist.php');
  require_once('base/rating.php');

  function output_link( $file )
  {
    $file_id = db_value("select file_id from mp3s where concat(dirname,filename)='$file'");
    return play_file(MEDIA_TYPE_MUSIC,$file_id);
  }

  $sql = 'from mp3s media'.get_rating_join().'where 1=1';

  browse_db( str('BROWSE_MUSIC')
           , db_col_to_list("select name from media_locations where media_type=1")
           , $sql.$_SESSION["history"][0]["sql"]
           , $_SESSION["history"][0]["url"]
           , array('mp3')
           , ''
           , 'LOGO_MUSIC' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
