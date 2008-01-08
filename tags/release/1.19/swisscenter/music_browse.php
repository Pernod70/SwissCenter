<?php
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
    return '/music_selected.php?name='.rawurlencode(" concat(dirname,filename) like '".db_escape_str($file)."%'");
  }

  $sql  = 'from mp3s media'.get_rating_join().'where 1=1';

  browse_db( str('BROWSE_MUSIC')                                                   // Title
           , $sql.$_SESSION["history"][0]["sql"]                                   // SQL (from...)
           , $_SESSION["history"][0]["url"]                                        // Return URL
           , MEDIA_TYPE_MUSIC                                                      // Select all media type
           );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
