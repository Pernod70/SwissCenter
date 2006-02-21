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
    $file_id = db_value("select file_id from movies where concat(dirname,filename)='".db_escape_str($file)."'");
    return play_file(MEDIA_TYPE_VIDEO,$file_id);
  }

  $sql = 'from movies media'.get_rating_join().'where 1=1';

  browse_db( str('BROWSE_MOVIES')                                                    // Title
           , db_col_to_list("select name from media_locations where media_type=3")   // Directories
           , $sql.$_SESSION["history"][0]["sql"]                                     // SQL (from...)
           , $_SESSION["history"][0]["url"]                                          // Back URL
           , explode(',',MEDIA_EXT_MOVIE)                                            // Filetypes
           , MEDIA_TYPE_VIDEO                                                        // Select All media type
           );
           
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
