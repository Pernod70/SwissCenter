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
    return pl_link('file',$file,'photo');
  }

  $sql = 'from photos media'.get_rating_join().'where 1=1';

  browse_db( 'Browse Photographs'
           , db_col_to_list("select name from media_locations where media_type=2")
           , $sql.$_SESSION["history"][0]["sql"]
           , $_SESSION["history"][0]["url"]
           , array('gif','png','jpg','jpeg')
           , 'photo_select_all.php?'
           , 'LOGO_PHOTO' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
