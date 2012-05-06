<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));
  require_once( realpath(dirname(__FILE__).'/base/flickr.php'));

  // Flickr photo_id of image to display.
  $photo_id = isset($_REQUEST["photo_id"]) ? $_REQUEST["photo_id"] : 0;

  // Get the previous and next track details if available.
  if ( empty($photo_id) )
  {
    send_to_log(2,'ERROR: No photo_id provided in flickr_image.php.');
  }
  else
  {
    // Get the url from Flickr of the original sized image.
    $redirect_url = flickr_get_photo_size($photo_id);
    send_to_log(8,'Redirecting to : '.$redirect_url);
    header ("Location: ".$redirect_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
