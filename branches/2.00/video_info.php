<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  if ( isset($_REQUEST["image"]) )
  {
    $actor = un_magic_quote(rawurldecode($_REQUEST["actor"]));
    $image = un_magic_quote(rawurldecode($_REQUEST["image"]));

    page_header( $actor );

    echo '<center>'.img_gen($image,500,670).'</center>';

    // Make sure the "back" button goes to the correct page:
    page_footer( page_hist_previous() );
  }
  else
  {
    // Get actor/director/genre lists
    if (isset($_REQUEST["tv"]))
    {
      $tv_id = $_REQUEST["tv"];
      $info      = db_row("select * from tv where file_id=$tv_id");
      $directors = db_col_to_list("select d.director_name name from directors_of_tv dot, directors d where dot.director_id = d.director_id and dot.tv_id=$tv_id");
      $actors    = db_col_to_list("select a.actor_name name from actors_in_tv ait, actors a where ait.actor_id = a.actor_id and ait.tv_id=$tv_id");
      $genres    = db_col_to_list("select g.genre_name name from genres_of_tv got, genres g where got.genre_id = g.genre_id and got.tv_id=$tv_id");
      $languages = db_col_to_list("select l.language name from languages_of_tv lot, languages l where lot.language_id = l.language_id and lot.tv_id=$tv_id");
      $title     = $info['PROGRAMME'];
      $tagline   = $info['TITLE'].(empty($info["YEAR"]) ? '' : ' ('.$info["YEAR"].')');
      $title_theme = $info['PROGRAMME'];
      $media_type = 6;
    }
    else
    {
      $movie_id = $_REQUEST["movie"];
      $info      = db_row("select * from movies where file_id=$movie_id");
      $directors = db_col_to_list("select d.director_name name from directors_of_movie dom, directors d where dom.director_id = d.director_id and dom.movie_id=$movie_id");
      $actors    = db_col_to_list("select a.actor_name name from actors_in_movie aim, actors a where aim.actor_id = a.actor_id and aim.movie_id=$movie_id");
      $genres    = db_col_to_list("select g.genre_name name from genres_of_movie gom, genres g where gom.genre_id = g.genre_id and gom.movie_id=$movie_id");
      $languages = db_col_to_list("select l.language name from languages_of_movie lom, languages l where lom.language_id = l.language_id and lom.movie_id=$movie_id");
      $title     = $info['TITLE'].(empty($info["YEAR"]) ? '' : ' ('.$info["YEAR"].')');
      $tagline   = '';
      $title_theme = $info['TITLE'];
      $media_type = 3;
    }

    // Random fanart image
    $themes = db_toarray('select processed_image, show_banner, show_image from themes where media_type='.$media_type.' and title="'.db_escape_str($title_theme).'" and use_synopsis=1 and processed_image is not NULL');
    $theme = $themes[mt_rand(0,count($themes)-1)];

    if ( file_exists($theme['PROCESSED_IMAGE']) )
      $background = $theme['PROCESSED_IMAGE'];
    else
      $background = -1;

    page_header( $title, $tagline, '', 1, false, '', $background, false, 'PAGE_TEXT_BACKGROUND' );

    if (is_pc())
      echo '<div style="height:'.convert_y(750).'; overflow:scroll;">';

    echo '<table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0" align="center">';

    // Genres
    if ($genres)
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('GENRE').':</font></b></td></tr>';
      echo '<tr><td colspan="5">'.font_tags(FONTSIZE_BODY).implode(', ', $genres).'</font></td></tr>';
    }

    // Languages
    if ($languages)
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('SPOKEN_LANGUAGE').':</font></b></td></tr>';
      echo '<tr><td colspan="5">'.font_tags(FONTSIZE_BODY).implode(', ', $languages).'</font></td></tr>';
    }

    // Director
    if ($directors)
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('DIRECTOR').':</font></b></td></tr>';
      echo '<tr><td colspan="5">'.font_tags(FONTSIZE_BODY).implode(', ', $directors).'</font></td></tr>';
    }

    // Cast
    if ($actors)
    {
      echo '<tr><td colspan="5"><b>'.font_tags(FONTSIZE_BODY,'PAGE_TEXT_BOLD_COLOUR').str('ACTOR').':</font></b></td></tr>
            <tr>';
      foreach ($actors as $i=>$actor)
      {
        // Random actor image
        $images = dir_to_array(SC_LOCATION.'fanart/actors/'.filename_safe(strtolower($actor)), '.jpg', 5);
        if (empty($images))
        {
          $image = style_img('MISSING_PERSON_ART', true);
          echo '<td align="center">'.img_gen($image,70,150,false,false,false,array(),false).'<br>'.font_tags(FONTSIZE_BODY).$actor.'</font></td>';
        }
        else
        {
          $image = $images[mt_rand(0,count($images)-1)];
          echo '<td align="center">'.img_gen($image,70,150,false,false,false,array(),false).'<br><a href="'.url_add_params(current_url(), array('image'=>rawurlencode($image), 'actor'=>rawurlencode($actor))).'">'.font_tags(FONTSIZE_BODY).$actor.'</font></a></td>';
        }
        if (($i+1) % 5 == 0)
          echo '</tr><tr>';
      }
      echo '</tr>';
    }

    echo '</table>';

    if (is_pc())
      echo '</div>';

    page_footer( page_hist_previous() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
