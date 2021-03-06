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
    return 'video_selected.php?name='.rawurlencode(" concat(media.dirname,media.filename) like '".db_escape_str($file)."%'");
  }

  $sql = 'from movies media'.get_rating_join().'where 1=1';

  browse_db( str('BROWSE_MOVIES')                                                    // Title
           , $sql.page_hist_current('sql')                                           // SQL (from...)
           , page_hist_previous()                                                    // Back URL
           , MEDIA_TYPE_VIDEO                                                        // Select All media type
           );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
