<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  // Get video synopsis
  $file_id    = $_REQUEST["file_id"];
  $media_type = $_REQUEST["media_type"];
  $table      = db_value("select media_table from media_types where media_id = $media_type");
  $data       = db_row("select * from $table where file_id = $file_id");
  if ( $media_type == 3 )
  {
    $title       = $data['TITLE'].(empty($data["YEAR"]) ? '' : ' ('.$data["YEAR"].')');
    $tagline     = '';
    $title_theme = $data['TITLE'];
  }
  else
  {
    $title       = $data['PROGRAMME'];
    $tagline     = $data['TITLE'].(empty($data["YEAR"]) ? '' : ' ('.$data["YEAR"].')');
    $title_theme = $data['PROGRAMME'];
  }

  // Where to return to?
  $history  = search_hist_pop();
  $back_url = url_add_param($history["url"], 'add','Y');

  // Random fanart image
  $themes = db_toarray('select processed_image, show_banner, show_image from themes where media_type='.$media_type.' and title="'.db_escape_str($title_theme).'" and use_synopsis=1 and processed_image is not NULL');
  $theme = $themes[mt_rand(0,count($themes)-1)];

  if ( file_exists($theme['PROCESSED_IMAGE']) )
    $background = $theme['PROCESSED_IMAGE'];
  else
    $background = -1;

  page_header( $title, $tagline, '', 1, false, '', $background, false, 'PAGE_TEXT_BACKGROUND' );

  echo '<table width="100%" cellpadding="0" cellspacing="10" border="0">
          <tr>
            <td valign="top">
              <table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0">
                <tr>
                  <td>';

  echo font_tags(FONTSIZE_BODY).$data["SYNOPSIS"].'</font>';

  echo '          </td>
                </tr>
              </table>';

  $menu = new menu();
  $menu->add_item(str('RETURN_TO_SELECTION'), $back_url);
  $menu->display(1, 400);

  echo '    </td>
          </tr>
        </table>';

  page_footer( $back_url );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
