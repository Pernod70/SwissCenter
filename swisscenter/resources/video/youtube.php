<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/youtube_api.php'));

  /**
   * Return the video_id of a YouTube video url.
   *
   * http://www.youtube.com/watch?v=q-wGMlSuX_c
   *
   * @param string $video_url
   */
  function get_youtube_video_id( $video_url )
  {
    return preg_get("/[^a-z]v=([^(&|$)]*)/", $video_url);
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

 /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
