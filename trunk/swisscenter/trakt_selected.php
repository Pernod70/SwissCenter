<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/resources/info/trakt.php'));
  require_once( realpath(dirname(__FILE__).'/resources/video/youtube.php'));

  /**
   * Return truncated synopsis.
   *
   * @param array $item
   * @param integer $num_menu_items
   */
  function trakt_synopsis($item, $num_menu_items=0)
  {
    $synopsis = $item['overview'];
    $synlen   = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

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
   * @param array $item
   */
  function trakt_details($item)
  {
    echo '<table width="100%" cellpadding="0" cellspacing="10" border="0">
            <tr>
              <td valign="top">';
                // Movie synopsis
                trakt_synopsis($item);

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
   * @param array $item
   */
  function trakt_info($item)
  {
    if (is_pc())
      echo '<div style="height:'.convert_y(750).'; overflow:scroll;">';

    echo '<table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0" align="center">';

    // Genres
    if (isset($item['genres']))
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('GENRE').':</font></b></td></tr>';
      echo '<tr><td colspan="5">'.font_tags(FONTSIZE_BODY).implode(', ', $item['genres']).'</font></td></tr>';
    }

    // Director
    if (isset($item['people']['directors']))
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('DIRECTOR').':</font></b></td></tr>
            <tr>';
      foreach ($item['people']['directors'] as $i=>$person)
      {
        $director = $person['name'];
        $image = $person['images']['headshot'];
        echo '<td align="center">'.img_gen($image,70,150,false,false,false,array(),false).'<br>'.font_tags(FONTSIZE_BODY).$director.'</font></td>';
        if (($i+1) % 5 == 0)
          echo '</tr><tr>';
      }
      echo '</tr>';
    }

    // Cast
    if (isset($item['people']['actors']))
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('ACTOR').':</font></b></td></tr>
            <tr>';
      foreach ($item['people']['actors'] as $i=>$person)
      {
        $actor = $person['name'];
        $image = $person['images']['headshot'];
        echo '<td align="center">'.img_gen($image,70,150,false,false,false,array(),false).'<br>'.font_tags(FONTSIZE_BODY).$actor.'</font></td>';
        if (($i+1) % 5 == 0)
          echo '</tr><tr>';
      }
      echo '</tr>';
    }
    echo '</table>';

    if (is_pc())
      echo '</div>';
  }

  /**
   * Displays the running time for a movie.
   *
   * @param $runtime
   */
  function running_time($runtime)
  {
    if (!is_null($runtime))
      echo font_tags(FONTSIZE_BODY).str('RUNNING_TIME').': '.hhmmss($runtime*60).'</font>';
  }

  /**
   * Displays the rating for a movie as a string of stars.
   *
   * @param $rating
   * @return string - HTML
   */
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
//*************************************************************************************************
// Main Code
//*************************************************************************************************

  $type    = $_REQUEST["type"];
  $imdb_id = isset($_REQUEST["imdb_id"]) ? $_REQUEST["imdb_id"] : 0;
  $tvdb_id = isset($_REQUEST["tvdb_id"]) ? $_REQUEST["tvdb_id"] : 0;
  $current_url = current_url();

  // Get recommended item.
  $trakt = new Trakt();

  $user = db_row("select u.user_id, u.name, un.value username, pw.value password
                  from users u join user_prefs un on (un.user_id = u.user_id and un.name = 'TRAKT_USERNAME')
                               join user_prefs pw on (pw.user_id = u.user_id and pw.name = 'TRAKT_PASSWORD')
                  where u.user_id=".get_current_user_id());

  $trakt->setAuth($user['USERNAME'], $user['PASSWORD']);

  if ( isset($_REQUEST["a"]) && $_REQUEST["a"]=='dismiss' )
  {
    if ($type == 'movies')
      $status = $trakt->recommendationsMoviesDismiss(array('imdb_id' => $imdb_id));
    else
      $status = $trakt->recommendationsShowsDismiss(array('tvdb_id' => $tvdb_id));
    page_hist_pop();
    header ("Location: ".page_hist_previous());
  }
  else
  {
    if ($type == 'movies')
      $item = $trakt->movieSummary($imdb_id);
    else
      $item = $trakt->showSummary($tvdb_id);

    page_header( $item['title'].' ('.$item['year'].')', star_rating($item['ratings']['percentage']), '<meta SYABAS-PLAYERMODE="video">',1,false,'',$item['images']['fanart'],false,'PAGE_TEXT_BACKGROUND' );

    // Which page to show?
    if ( isset($_REQUEST["a"]) && $_REQUEST["a"]=='cast' )
      trakt_info($item);
    elseif ( isset($_REQUEST["a"]) && $_REQUEST["a"]=='synopsis' )
      trakt_details($item);
    else
    {
      $menu = new menu();
      $menu->add_item( str('VIDEO_INFO'), url_add_params($current_url, array('a'=>'cast')) );
      // Add trailer menu item
      if (isset($item['trailer']) && !empty($item['trailer']))
      {
        if (strpos($item['trailer'],'youtube.com') > 0)
          $menu->add_item( str('PLAY_TRAILER'), 'href="stream_url.php?'.current_session().'&youtube_id='.get_youtube_video_id($item['trailer']).'&ext=.mp4" vod ');
        elseif (is_remote_file($item['trailer']))
          $menu->add_item( str('PLAY_TRAILER'), 'href="'.url_add_params('stream_url.php', array('user_agent' => rawurlencode('QuickTime/7.6'),
                                                                                                'url' => rawurlencode($item['trailer']),
                                                                                                'ext' => '.'.file_ext($item['trailer']))).'" vod ');
        else
          $menu->add_item( str('PLAY_TRAILER'), "href='".server_address().make_url_path($item['trailer'])."' vod" );
      }
      // Certificate? Get the appropriate image.
      if (get_cert_from_name($item['certification']))
        $cert_img = img_gen(SC_LOCATION.'images/ratings/'.get_rating_scheme_name().'/'.get_cert_name( get_nearest_cert_in_scheme(get_cert_from_name($item['certification']))).'.gif',280,100,false,false,false,array(),false);
      else
        $cert_img = '';

      echo '<table width="100%" height="'.convert_y(650).'" cellpadding="0" cellspacing="0" border="0">
              <tr>';
      // Column 1: Image
      echo '    <td width="'.convert_x(280).'" valign="middle">
                  <table '.style_background('PAGE_TEXT_BACKGROUND').' cellpadding="10" cellspacing="0" border="0">
                    <tr>
                      <td><center>'.img_gen($item['images']['poster'],280,550,false,false,false,array(),false).'<br>'.$cert_img.'</center></td>
                    </tr>
                  </table>
                </td>';
      // Column 2: Gap
      echo '    <td width="'.convert_x(10).'"></td>';
      // Column 3: Details and menu
      echo '    <td valign="top">
                  <table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0">
                    <tr>
                      <td>';
                      // Movie synopsis
                      trakt_synopsis($item,$menu->num_items());
      echo '          <br>';
                      // Running Time
                      running_time($item['runtime']);
      echo '          </td>
                    </tr>
                  </table>';
                  $menu->display(1, 480);
      echo '    </td>
              </tr>
            </table>';

      // Display ABC buttons
      $buttons = array();
      $buttons[] = array('text'=>str('DISMISS'), 'url'=> url_add_params($current_url, array('a'=>'dismiss')) );
    }

    // Make sure the "back" button goes to the correct page:
    page_footer( page_hist_previous(), $buttons );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
