<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));

  function output_link( $file )
  {
    $file_id = db_value("select file_id from movies where concat(dirname,filename)='".db_escape_str($file)."'");
    return play_file(MEDIA_TYPE_VIDEO,$file_id);
  }

  $sql = 'from movies media'.get_rating_join().'where 1=1';

  browse_db( str('BROWSE_MOVIES')                                                    // Title
           , $sql.$_SESSION["history"][0]["sql"]                                     // SQL (from...)
           , $_SESSION["history"][0]["url"]                                          // Back URL
           , MEDIA_TYPE_VIDEO                                                        // Select All media type
           );
           
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
