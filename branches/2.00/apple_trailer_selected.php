<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/resources/trailers/apple_trailers.php'));

  /**
   * Return truncated synopsis.
   *
   * @param array $trailer
   * @param integer $num_menu_items
   */
  function trailer_synopsis ($trailer, $num_menu_items=0)
  {
    $synopsis = get_trailer_description($trailer);
    $synlen   = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

    // Synopsis
    if ( !empty($synopsis) )
    {
      $text = isset($_REQUEST["show"]) ? $synopsis : shorten($synopsis,$synlen,1,FONTSIZE_BODY);
      if (strlen($text) != strlen($synopsis))
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
                trailer_synopsis($trailer);

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
                if (isset($trailer["actors"]))
                {
                  echo '<p><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('ACTOR').':</font></b>';
                  echo '<br>'.font_tags(FONTSIZE_BODY).implode(', ', $trailer["actors"]).'</font>';
                }

                // Director
                if (isset($trailer["directors"]))
                {
                  echo '<p><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('DIRECTOR').':</font></b>';
                  echo '<br>'.font_tags(FONTSIZE_BODY).$trailer["directors"].'</font>';
                }

                // Genres
                if (isset($trailer["genre"]))
                {
                  echo '<p><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('GENRE').':</font></b>';
                  echo '<br>'.font_tags(FONTSIZE_BODY).implode(', ', $trailer["genre"]).'</font>';
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

  $current_url = current_url();

  // Retrieve the selected trailer details
  $apple = new AppleTrailers();

  if ( isset($_REQUEST["feed"]) )
  {
    $id = $_REQUEST["id"];
    $feed = $_REQUEST["feed"];
    $trailers = $apple->getFeed($feed);
  }
  else
  {
    $id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : 0;
    $query = $_REQUEST["query"];
    $trailers = $apple->quickFind($query);
  }

  $buttons = array();
  page_header( utf8_decode($trailers[$id]["title"]), utf8_decode($trailers[$id]["studio"]), '<meta SYABAS-PLAYERMODE="video">' );

  // Which page to show?
  if ( isset($_REQUEST["show"]) && $_REQUEST["show"]=='cast' )
    trailer_info($trailers[$id]);
  elseif ( isset($_REQUEST["show"]) && $_REQUEST["show"]=='synopsis' )
    trailer_details($trailers[$id]);
  else
  {
    $menu = new menu();

    if ( isset($_REQUEST["xml"]) )
    {
      // List selected trailer by size
      $items = get_trailer_urls($_REQUEST["xml"]);
      foreach ($items[1] as $key=>$title)
      {
        // Omit iPod trailers
        if (strpos($title, 'iPod') === false)
          $menu->add_item( $title, 'href="'.url_add_params('stream_url.php', array('user_agent' => rawurlencode('QuickTime/7.6'),
                                                                                   'url' => rawurlencode($items[2][$key]),
                                                                                   'ext' => '.'.file_ext($items[2][$key]))).'" vod ');
      }
    }
    else
    {
      // List available trailers by title
      $items      = get_trailer_index($trailers[$id]);
      $page       = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 1);
      $start      = ($page-1) * MAX_PER_PAGE;
      $end        = min($start+MAX_PER_PAGE,count($items[1]));
      $last_page  = ceil(count($items[1])/MAX_PER_PAGE);

      if (count($items[1]) > MAX_PER_PAGE)
      {
        $menu->add_up( url_add_param($current_url, 'page', ($page > 1 ? ($page-1) : $last_page)) );
        $menu->add_down( url_add_param($current_url, 'page', ($page < $last_page ? ($page+1) : 1)) );
      }

      for ($i=$start; $i<$end; $i++)
        $menu->add_item($items[2][$i], url_add_param($current_url, 'xml', rawurlencode($items[1][$i])), true);
    }

    // Certificate? Get the appropriate image.
    if (get_cert_from_name($trailers[$id]["rating"]))
      $cert_img = img_gen(SC_LOCATION.'images/ratings/'.get_rating_scheme_name().'/'.get_cert_name( get_nearest_cert_in_scheme(get_cert_from_name($trailers[$id]["rating"]))).'.gif',280,100,false,false,false,array(),false);
    else
      $cert_img = '';

      // Column 1: Image
      echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td valign="middle">
                  <td><center>'.img_gen($trailers[$id]["poster"],280,550,false,false,false,array(),true).'<br>'.$cert_img.'</center></td>
                </td>';
      // Column 2: Gap
      echo '    <td width="'.convert_x(10).'"></td>';
      // Column 3: Details and menu
      echo '    <td valign="top">';
                  // Movie synopsis
                  trailer_synopsis($trailers[$id], $menu->num_items());
      echo '      <p>';
                  // Release date
                  if (isset($trailers[$id]["releasedate"]))
                    echo font_tags(FONTSIZE_BODY).str('RELEASE_DATE').': '.date('d M Y', strtotime($trailers[$id]["releasedate"])).'</font>';
                  $menu->display(1, 480);
      echo '    </td>
              </tr>
            </table>';

    // Display ABC buttons
    $buttons[] = array('text'=>str('VIDEO_INFO'), 'url'=> url_add_params($current_url, array('show'=>'cast')) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous(), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
