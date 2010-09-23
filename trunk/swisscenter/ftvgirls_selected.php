<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

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
    $image = dirname($url).'/'.preg_get('/<img border="0" src="(.*)"/U',$html);
		$video = preg_get('/<a href="(.*.wmv)">/U',$html);
		if (strpos($video,'http') !== 0 ) $video = dirname($url).'/'.$video;
		$stats = preg_get('/<font face="Arial" color="#ffffff" size="2">.*(Age.*)<\/font>/Us',$html);

    return array('title'=>$title, 'image'=>$image, 'video'=>$video, 'stats'=>$stats);
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Update page history
  $back_url = search_hist_most_recent();
  $cache_dir = get_sys_pref('cache_dir');

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
  page_footer( $back_url["url"] );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
