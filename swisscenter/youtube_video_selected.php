<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/base/youtube.php'));

function star_rating( $rating )
{
  // Form star rating
  $img_rating = '';
  if ( !is_null($rating) )
  {
    $user_rating = nvl($rating/10,0);
    for ($i = 1; $i<=10; $i++)
    {
      if ( $user_rating >= $i )
        $img_rating .= img_gen(style_img('STAR',true),25,40);
      elseif ( $i-1 >= $user_rating )
        $img_rating .= img_gen(style_img('STAR_0',true),25,40);
      else
        $img_rating .= img_gen(style_img('STAR_'.(number_format($user_rating,1)-floor($user_rating))*10,true),25,40);
    }
  }
  return $img_rating;
}

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  $youtube = new phpYouTube();

  // Update page history
  $back_url = youtube_page_params();
  $this_url = url_remove_param(current_url(), 'del');

  $video_id = $_REQUEST["video_id"];

  // Get information about a video.
  $entry = $youtube->videoEntry($video_id);
  $entry = $entry['entry'];

  $title = utf8_decode($entry['media$group']['media$title']['$t']);
  $image = youtube_thumbnail_url($entry['media$group']['media$thumbnail']);
  $duration = $entry['media$group']['yt$duration']['seconds'];
  $uploaded = $entry['media$group']['yt$uploaded']['$t'];
  $rating   = $entry['gd$rating']['average'] * 20;
  $viewed   = $entry['yt$statistics']['viewCount'];

  // Page headings
  page_header($title, star_rating($rating));

  $menu = new menu();
  $menu->add_item( str('PLAY_NOW'), 'href="stream_url.php?'.current_session().'&youtube_id='.$video_id.'&ext=.mp4" vod ');

  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left">'.img_gen($image,280,550).'</td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">'.
              font_tags(FONTSIZE_BODY).utf8_decode($entry['media$group']['media$description']['$t']).'
              <p>'.font_tags(FONTSIZE_BODY).str('RUNNING_TIME').': '.hhmmss($duration).'</font>
              <p>'.font_tags(FONTSIZE_BODY).str('DATE').': '.date('jS M Y', strtotime($uploaded)).'</font>
              <p>'.font_tags(FONTSIZE_BODY).str('VIEWED').': '.number_format($viewed).'</font>';
              $menu->display(1, 480);
  echo     '</td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer($back_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
