<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));

  /**
   * Retrieve and parser the girls details page.
   *
   * @param array
   */
  function ftvgirls_details($url)
  {
    // Get ftvgirls superpics page
    $html = file_get_contents($url);

    $title = preg_get('/<title>(.*)<\/title>/U',$html);
    $image = preg_get('/<img id="Magazine" src="(.*)"/U',$html);
    $video = preg_get('/href="(.*.mp4)">/U',$html);
    $stats = preg_get('/<h2>(<b>Age:<\/b>.*)<span/Us',$html);

    return array('title'=>$title, 'image'=>$image, 'video'=>$video, 'stats'=>$stats);
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Get ftvgirls details page
  $details = ftvgirls_details('http://ftvgirls.com/'.$_REQUEST["id"]);

  // Download the main image to use as background
  $background = download_and_cache_image($details["image"]);

  page_header( $details["title"], $details["stats"], '<meta SYABAS-PLAYERMODE="video">', 1, false, '', $background, false, 'PAGE_TEXT_BACKGROUND' );

  $menu = new menu();
  $menu->add_item( str('PLAY_NOW'), 'href="'.$details["video"].'" vod ');

  // Display page
  echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
          <tr>';
  echo '    <td valign="bottom">';
              $menu->display(1, 480);
  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous() );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
