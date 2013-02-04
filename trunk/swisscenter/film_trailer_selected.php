<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/resources/trailers/film_trailer_feeds.php'));

  /**
   * Return truncated synopsis.
   *
   * @param array $trailer
   * @param integer $num_menu_items
   */
  function trailer_synopsis ($synopsis, $num_menu_items=0)
  {
    $synlen = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

    // Synopsis
    if ( !empty($synopsis) )
    {
      $text = isset($_REQUEST["show"]) ? $synopsis : shorten($synopsis,$synlen,1,FONTSIZE_BODY);
      if (mb_strlen($text) != mb_strlen($synopsis))
      {
        $text = $text.' <a href="'.url_add_param( current_url(), 'show', 'synopsis' ).'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('MORE')).'</a>';
      }
    }
    else
      $text = str('NO_SYNOPSIS_AVAILABLE');

    echo font_tags(FONTSIZE_BODY).$text.'</font>';
  }

  /**
   * Display full synopsis for movie.
   *
   * @param array $trailer
   */
  function trailer_details($trailer)
  {
    echo '<table width="100%" cellpadding="0" cellspacing="10" border="0">
            <tr>
              <td valign="top">';
                // Movie synopsis
                trailer_synopsis($trailer[0]['PRODUCTS'][0]['DESCRIPTION']);

                $menu = new menu();
                $menu->add_item(str('RETURN_TO_SELECTION'), page_hist_previous());
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
  function trailer_info($trailer)
  {
    echo '<table width="100%" cellpadding="0" cellspacing="10" border="0">
            <tr>
              <td valign="top">';

                // Cast
                if (isset($trailer[0]["ACTORS"]))
                {
                  echo '<p><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('ACTOR').':</font></b>';
                  echo '<br>'.font_tags(FONTSIZE_BODY).implode(', ', $trailer[0]["ACTORS"]).'</font>';
                }

                // Director
                if (isset($trailer[0]["DIRECTORS"]))
                {
                  echo '<p><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('DIRECTOR').':</font></b>';
                  echo '<br>'.font_tags(FONTSIZE_BODY).implode(', ', $trailer[0]["DIRECTORS"]).'</font>';
                }

                // Genres
                if (isset($trailer[0]["CATEGORIES"]))
                {
                  echo '<p><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('GENRE').':</font></b>';
                  echo '<br>'.font_tags(FONTSIZE_BODY).implode(', ', $trailer[0]["CATEGORIES"]).'</font>';
                }

                $menu = new menu();
                $menu->add_item(str('RETURN_TO_SELECTION'), page_hist_previous());
                $menu->display(1, 400);

    echo '    </td>
            </tr>
          </table>';
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Retrieve the selected trailer details
  $filmtrailer = new FilmTrailer();
  $filmtrailer->set_region_code(substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2));

  $trailer = $filmtrailer->getFeed($_REQUEST["id"]);

  $buttons = array();
  page_header( $trailer[0]["ORIGINAL_TITLE"], '', '<meta SYABAS-PLAYERMODE="video">' );

  // Which page to show?
  if ( isset($_REQUEST["show"]) && $_REQUEST["show"]=='cast' )
    trailer_info($trailer);
  elseif ( isset($_REQUEST["show"]) && $_REQUEST["show"]=='synopsis' )
    trailer_details($trailer);
  else
  {
    $menu = new menu();

    // List available trailers by title
    foreach ($trailer[0]['CLIPS'] as $clip)
    {
      if (is_array($clip['FILES']))
      {
        $file = array_pop($clip['FILES']);
        $menu->add_item($clip['NAME'], 'href="'.$file['URL'].'" vod');
      }
    }

    // Column 1: Image
    echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td valign="middle">
                <td>'.img_gen($trailer[0]["PICTURES"][1]['URL'],280,550,false,false,false,array(),true).'</td>
              </td>';
    // Column 2: Gap
    echo '    <td width="'.convert_x(10).'"></td>';
    // Column 3: Details and menu
    echo '    <td valign="top">';
                // Movie synopsis
                trailer_synopsis($trailer[0]['PRODUCTS'][0]['DESCRIPTION'], $menu->num_items());
    echo '      <p>';
                // Release date
                if (isset($trailer[0]['PRODUCTS'][0]['PREMIERE']))
                  echo font_tags(FONTSIZE_BODY).str('RELEASE_DATE').': '.$trailer[0]['PRODUCTS'][0]['PREMIERE'].'</font>';
                $menu->display(1, 480);
    echo '    </td>
            </tr>
          </table>';

    // Display ABC buttons
    $buttons[] = array('text'=>str('VIDEO_INFO'), 'url'=> url_add_params(current_url(), array('show'=>'cast')) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous(), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
