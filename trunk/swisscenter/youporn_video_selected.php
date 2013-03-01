<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/resources/video/youporn.php'));

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

  $url   = rawurldecode($_REQUEST["url"]);
  $image = rawurldecode($_REQUEST["img"]);

  // Get information about a video.
  $youporn = new YouPorn();
  $details = $youporn->getDetails($url);

  // Page headings
  page_header($details["title"], star_rating($details["rating"]));

  $menu = new menu();
  if (!empty($details["mpg_url"]))
    $menu->add_item( str('PLAY_NOW').' (MPG)', 'href="'.url_add_params('stream_url.php?'.current_session(), array('url' => rawurlencode($details["mpg_url"]))).'" vod ');
  if (!empty($details["mp4_url"]))
    $menu->add_item( str('PLAY_NOW').' (MP4)', 'href="'.url_add_params('stream_url.php?'.current_session(), array('url' => rawurlencode($details["mp4_url"]))).'" vod ');
  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left">'.img_gen($image,280,550).'</td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">'.
              font_tags(FONTSIZE_BODY).str('RUNNING_TIME').': '.$details["duration"].'</font>
              <p>'.font_tags(FONTSIZE_BODY).str('DATE').': '.$details["date"].'</font>
              <p>'.font_tags(FONTSIZE_BODY).str('VIEWED').': '.$details["viewed"].'</font>';
              $menu->display(1, 480);
  echo     '</td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer(page_hist_previous());

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
