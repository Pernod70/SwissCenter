<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/utils.php'));

  $video_id  = $_REQUEST["video_id"];
  $video_url = 'http://www.youtube.com/watch?v='.$video_id;
  $html      = file_get_contents($video_url);

  // Retrieve signature from returned YouTube page
  $video_hash = preg_get('/swfArgs.*{.*"t".*"(.*)".*}/U',$html);

  // Determine whether to use the HD stream
  $fmt = 18;
  if (get_sys_pref('YOUTUBE_HD','YES') == 'YES' && preg_get("/isHDAvailable.*=.*([a-z]*)/",$html) == 'true')
    $fmt = 22;

  // Form URL of YouTube video to stream
  $stream_url = 'http://www.youtube.com/get_video?fmt='.$fmt.'&video_id='.$video_id.'&t='.$video_hash.'&ext=.mp4';
  send_to_log(7,'Attempting to stream the following YouTube video', $stream_url);

  // Send a redirect header to the player with the real location of the media file.
  header ("Content-type: ".mime_content_type($stream_url));
  send_to_log(8,'Redirecting to '.$stream_url);
  header ("HTTP/1.0 307 Temporary redirect");
  header ("location: ".$stream_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>