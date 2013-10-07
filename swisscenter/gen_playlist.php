<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/session.php'));
  require_once( realpath(dirname(__FILE__).'/base/server.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/capabilities.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/db_abstract.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));

  // Log details of the playlist request
  send_to_log(0,"Playlist: ".$_SERVER["REQUEST_METHOD"]." ".current_url()." by client (".client_ip().")");

/**************************************************************************************************
// Notes
//*************************************************************************************************

 [1] The hardware players can only cope with a playlists containing a limited number of entries.
     This probably varies between players, and so is set in the capabilities.php file and returned
     using the function max_playlist_size().

 [2] The hardware players expect to find an extension on the end of the URL. They use this to determine
     the data format (to play) and also substitute "avi" for a subtitle extension before requesting a
     subtitle.

*/

  // Generate the playlist based on the values passed as part of the request
  $shuffle   = isset($_REQUEST["shuffle"]) ? ($_REQUEST["shuffle"] == 'on') : ($_SESSION["shuffle"] == 'on');
  $tracklist = nvl($_REQUEST["tracklist"],'');
  generate_tracklist( $_REQUEST["seed"], $shuffle, $_REQUEST["spec_type"], $_REQUEST["spec"], $_REQUEST["media_type"], $tracklist);

  $data       = get_tracklist($tracklist);
  $server     = server_address();
  $max_size   = max_playlist_size();
  $resume     = (isset($_REQUEST["resume"]) && $_REQUEST["resume"] == 'Y');
  $item_count = 0;
  $media_type = 0;
  $file_id    = 0;

  send_to_log(7,'Generating list of media files to send to the networked media player.');

  foreach ($data as $row)
  {
    if ($item_count >= $max_size)
      break;

    // We need to identify the media_type. This may have been passed in on the query string (if the list of files
    // are all of the same type) or we may need to determine it from the database.
    if (isset($_REQUEST["media_type"]) && !empty($_REQUEST["media_type"]))
    {
      $media_type = $_REQUEST["media_type"];
      $file_id    = $row["FILE_ID"];
    }
    else
    {
      list($media_type, $file_id) = find_media_in_db($row["DIRNAME"].$row["FILENAME"]);
    }

    // If this is a hardware player, then we might wish to resume playback of a file partway through
    $start_pos = 0;
    if ( $resume && support_resume() )
    {
      $filename = $row["DIRNAME"].$row["FILENAME"];
      $bookmark_filename = bookmark_file($filename);
      if (file_exists($bookmark_filename))
      {
        $start_pos = (int)trim(file_get_contents($bookmark_filename));
        send_to_log(7,"- Resuming playback from ".$start_pos."%");
      }
    }

    /**
     * Check that we're not trying to stream a DVD image, as this is not supported by any player
     * at the moment
     */

    if ($media_type == MEDIA_TYPE_VIDEO && in_array(file_ext($row["FILENAME"]), media_exts_dvd()) )
    {
      send_to_log(5,'Cannot stream DVD image from a playlist',array( "File ID"=>$file_id, "Media Type"=>$media_type, "Location"=>$row["DIRNAME"].$row["FILENAME"] ));
      break;
    }
    elseif ($media_type == MEDIA_TYPE_VIDEO || $media_type == MEDIA_TYPE_TV ) // Movie
    {
      /**
       *  We don't use "stream.php" for movie/tv files yet as we still need to sort
       *  out the lack of subtitles!
       */

      store_request_details( $media_type, $file_id);
      send_to_log(7,'Attempting to stream the following video',array( "File ID"=>$file_id, "Media Type"=>$media_type, "Location"=>$row["DIRNAME"].$row["FILENAME"] ));
      $url = $server.make_url_path($row["DIRNAME"].$row["FILENAME"]);
    }
    else
      $url = $server.'stream.php?'.current_session().'&tracklist='.$tracklist.'&media_type='.$media_type.'&idx='.$item_count.'&ext=.'.file_ext($row["FILENAME"]);


    // Build up the playlist row to send to the player, including the title of the movie (for the on-screen display)
    $title = rtrim(nvl( $row["TITLE"] , file_noext(basename($row["FILENAME"])) ));
    send_to_log(7," - ".$url);

    if (is_hardware_player())
      echo  $title.'|'.$start_pos.'|0|'.$url."|\n";
    else
      echo  $url.newline();

    $item_count++;
  }

  // If this is a PC browser then we need to output some headers

  if ( is_pc() )
  {
    header('Content-Disposition: attachment; filename=Playlist.m3u');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Content-Type: audio/x-mpegurl');
    header('Content-Length: '.ob_get_length());
    ob_flush();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
