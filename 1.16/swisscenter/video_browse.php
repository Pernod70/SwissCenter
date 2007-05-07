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
    $data = db_toarray("select * from movies where concat(dirname,filename)='".db_escape_str($file)."'");

    if ( support_resume() && file_exists( bookmark_file($data[0]["DIRNAME"].$data[0]["FILENAME"]) ))
      return resume_file(MEDIA_TYPE_VIDEO,$data[0]["FILE_ID"]);
    else
     return play_file(MEDIA_TYPE_VIDEO,$data[0]["FILE_ID"]);
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
