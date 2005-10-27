<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once('base/browse.php');
  require_once('base/playlist.php');
  require_once('base/rating.php');
  require_once("base/search.php");

  function output_link( $file )
  {
    $file_id = db_value("select file_id from mp3s where concat(dirname,filename)='".db_escape_str($file)."'");
    return play_file(MEDIA_TYPE_MUSIC,$file_id);
  }

  $hist = search_hist_most_recent();
  $sql  = 'from mp3s media'.get_rating_join().'where 1=1'.$hist["sql"];

  browse_db( str('BROWSE_MUSIC')                                                   // Title
           , db_col_to_list("select name from media_locations where media_type=1") // Directories
           , $sql                                                                  // SQL (from...)
           , $hist["url"]                                                          // Return URL
           , explode(',',MEDIA_EXT_MUSIC)                                          // Filetypes
           , MEDIA_TYPE_MUSIC                                                      // Select all media type
           );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
