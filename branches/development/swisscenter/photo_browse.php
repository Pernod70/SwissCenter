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

  browse_db( 'Browse Photographs'
           , db_col_to_list("select name from media_locations where media_type=2")
           , 'from photos where 1=1'.$_SESSION["history"][0]["sql"]
           , $_SESSION["history"][0]["url"]
           , array('gif','png','jpg','jpeg')
           , 'photo_select_all.php?'
           , 'LOGO_PHOTO' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
