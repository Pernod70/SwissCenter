<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../ext/youtube/youtube_api.php'));

  /**
   * Return the video_id of a YouTube video url.
   *
   * http://www.youtube.com/watch?v=q-wGMlSuX_c
   *
   * @param string $video_url
   */
  function get_youtube_video_id( $video_url )
  {
    return preg_get("/[^a-z]v=([^(\&|$)]*)/", $video_url);
  }

  /**
   * Search media:thumbnail array for a high-quality thumbnail.
   * The high-quality image is identified by not having a timestamp.
   *
   * http://code.google.com/apis/youtube/2.0/reference.html#youtube_data_api_tag_media:thumbnail
   *
   * @param array $thumbnail
   *
   * @return string - URL of thumbnail
   */
  function youtube_thumbnail_url( $thumbnail )
  {
    if ( isset($thumbnail['url']) )
      $thumb_url = $thumbnail['url'];
    elseif ( is_array($thumbnail) )
    {
      foreach ($thumbnail as $thumb)
      {
        if ( !isset($thumb['time']) )
        {
          $thumb_url = $thumb['url'];
          break;
        }
      }
    }
    if ( !empty($thumb_url) )
    {
      $thumb_url = explode('?', $thumb_url);
      return $thumb_url[0];
    }
    else
      return false;
  }

  /**
   * Search category array for a particular scheme.
   *
   * @param array $entry
   * @param string $scheme
   * @return string
   */
  function youtube_category_scheme( $entry, $scheme )
  {
    foreach ($entry['category'] as $category)
    {
      if ( $category['scheme'] == $scheme )
      {
        $type = $category['term'];
        break;
      }
    }
    return $type;
  }

  /**
   * Functions for managing the YouTube navigation history.
   */
  function youtube_hist_init( $url )
  {
    $_SESSION["history"] = array();
    $_SESSION["history"][] = $url;
  }

  function youtube_hist_push( $url )
  {
    $_SESSION["history"][] = $url;
  }

  function youtube_hist_pop()
  {
    if (count($_SESSION["history"]) == 0)
      page_error(str('DATABASE_ERROR'));

    return array_pop($_SESSION["history"]);
  }

  function youtube_hist_most_recent()
  {
    if (count($_SESSION["history"]) == 0)
      page_error(str('DATABASE_ERROR'));

    return $_SESSION["history"][count($_SESSION["history"])-1];
  }

  function youtube_page_params()
  {
    // Remove pages from history
    if (isset($_REQUEST["del"]))
      for ($i=0; $i<$_REQUEST["del"]; $i++)
        youtube_hist_pop();

    $this_url = url_remove_param(current_url(), 'del');
    $back_url = url_add_param(youtube_hist_most_recent(), 'del', 2);

    // Add page to history
    youtube_hist_push($this_url);

    return $back_url;
  }

 /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
