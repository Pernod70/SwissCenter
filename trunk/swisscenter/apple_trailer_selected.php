<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/apple_trailers.php'));

  /**
   * Return truncated synopsis.
   *
   * @param string $location
   * @param integer $num_menu_items
   */
  function trailer_synopsis ($trailer, $num_menu_items=0)
  {
    $synopsis = get_trailer_description($trailer);
    $synlen   = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

    // Synopsis
    if ( !empty($synopsis) )
    {
      $text = isset($_REQUEST["show"]) ? $synopsis : shorten($synopsis,$synlen,1,30);
      if (strlen($text) != strlen($synopsis))
      {
        $text = $text.' <a href="'.url_add_param( current_url(), 'show', 'synopsis' ).'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('MORE')).'</a>';
      }
    }
    else
      $text = str('NO_SYNOPSIS_AVAILABLE');

    echo font_tags(30).$text.'</font>';
  }

  /**
   * Display full synopsis for movie.
   *
   * @param array $trailer
   */
  function trailer_details($trailer, $back_url)
  {
    echo '<table width="100%" cellpadding="0" cellspacing="10" border="0">
            <tr>
              <td valign="top">';
                // Movie synopsis
                trailer_synopsis($trailer);

                $menu = new menu();
                $menu->add_item(str('RETURN_TO_SELECTION'), $back_url);
                $menu->display(1, 400);

    echo '    </td>
            </tr>
          </table>';
  }

  /**
   * Display cast, directors, genres for movie.
   *
   * @param array $trailer
   */
  function trailer_info($trailer, $back_url)
  {
    echo '<table width="100%" cellpadding="0" cellspacing="10" border="0">
            <tr>
              <td valign="top">';

                // Cast
                if (isset($trailer["actors"]))
                {
                  echo '<p><b>'.font_tags(30,'PAGE_TEXT_BOLD_COLOUR').str('ACTOR').':</font></b>';
                  echo '<br>'.font_tags(30).implode(', ', $trailer["actors"]).'</font>';
                }

                // Director
                if (isset($trailer["directors"]))
                {
                  echo '<p><b>'.font_tags(30,'PAGE_TEXT_BOLD_COLOUR').str('DIRECTOR').':</font></b>';
                  echo '<br>'.font_tags(30).$trailer["directors"].'</font>';
                }

                // Genres
                if (isset($trailer["genre"]))
                {
                  echo '<p><b>'.font_tags(30,'PAGE_TEXT_BOLD_COLOUR').str('GENRE').':</font></b>';
                  echo '<br>'.font_tags(30).implode(', ', $trailer["genre"]).'</font>';
                }

                $menu = new menu();
                $menu->add_item(str('RETURN_TO_SELECTION'), $back_url);
                $menu->display(1, 400);

    echo '    </td>
            </tr>
          </table>';
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Update page history
  $back_url = apple_trailer_page_params();
  $this_url = url_remove_param(current_url(), 'del');

  // Retrieve the selected trailer details
  $id = $_REQUEST["id"];
  $apple = new AppleTrailers();

  if ( isset($_REQUEST["feed"]) )
  {
    $feed = $_REQUEST["feed"];
    $trailers = $apple->getFeed($feed);
  }
  else
  {
    $query = $_REQUEST["query"];
    $trailers = $apple->quickFind($query);
  }

  $buttons = array();
  page_header( utf8_decode($trailers[$id]["title"]), utf8_decode($trailers[$id]["studio"]), '<meta SYABAS-PLAYERMODE="video">' );

  // Which page to show?
  if ( isset($_REQUEST["show"]) && $_REQUEST["show"]=='cast' )
    trailer_info($trailers[$id], $back_url);
  elseif ( isset($_REQUEST["show"]) && $_REQUEST["show"]=='synopsis' )
    trailer_details($trailers[$id], $back_url);
  else
  {
    $menu = new menu();

    // List available trailers
    $trailer_urls = get_trailer_urls($trailers[$id]);
    foreach ($trailer_urls[1] as $key=>$title)
    {
      // Omit iPod, Small, and Medium sized trailers
      if (preg_match('[\(iPod\)|\(Small\)|\(Medium\)]', $title) == 0)
        $menu->add_item( $title, 'href="'.$trailer_urls[2][$key].'" vod ');
    }

    // Certificate? Get the appropriate image.
    if (get_cert_from_name($trailers[$id]["rating"]))
      $cert_img = img_gen(SC_LOCATION.'images/ratings/'.get_rating_scheme_name().'/'.get_cert_name( get_nearest_cert_in_scheme(get_cert_from_name($trailers[$id]["rating"]))).'.gif',280,100,false,false,false,array(),false);
    else
      $cert_img = '';

      // Column 1: Image
      echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td valign="top">
                  <td>'.img_gen($trailers[$id]["poster"],280,550,false,false,false,array(),false).'<br><center>'.$cert_img.'</center></td>
                </td>';
      // Column 2: Gap
      echo '    <td width="'.convert_x(10).'"></td>';
      // Column 3: Details and menu
      echo '    <td valign="top">';
                  // Movie synopsis
                  trailer_synopsis($trailers[$id], $menu->num_items());
      echo '      <p>';
                  // Release date
                  echo font_tags(30).str('RELEASE_DATE').': '.date('d M Y', strtotime($trailers[$id]["releasedate"])).'</font>';
                  $menu->display(1, 480);
      echo '    </td>
              </tr>
            </table>';

    // Display ABC buttons
    $buttons[] = array('text'=>str('VIDEO_INFO'), 'url'=> url_add_params($this_url, array('show'=>'cast')) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer( $back_url, $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
