<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/filter.php'));

  $menu        = new menu();
  $programme   = un_magic_quote($_REQUEST["programme"]);
  $view_status = $_REQUEST["view_status"];
  $page        = nvl($_REQUEST["page"],1);
  $predicate   = get_rating_filter().category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV).filter_get_predicate();
  $this_url    = url_remove_params(current_url(), array('shuffle', 'viewed'));

  // Clean the current url in the history
  page_hist_current_update($this_url, $predicate);

  $min_viewed_series = db_value("select min(series)
                                   from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
                                  where programme = '".db_escape_str($programme)."' $predicate ".viewed_n_times_predicate()."
                                  order by 1");

  $series = db_col_to_list("select distinct series
                              from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
                             where programme = '".db_escape_str($programme)."' $predicate ".viewed_n_times_predicate( ($view_status == 'unviewed' ? '=' : '>='),0)."
                             order by 1");

  $current_series = (in_array($_REQUEST["series"], $series) ? $_REQUEST["series"] : nvl($min_viewed_series,$series[0]) );

  $episodes_sql = "select *
                     from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
                    where programme = '".db_escape_str($programme)."'".(is_null($current_series) ? "" : "
                      and series = $current_series $predicate").viewed_n_times_predicate( ($view_status == 'unviewed' ? '=' : '>='),0)."
                    order by episode";

  $episodes = db_toarray($episodes_sql);

  if (isset($_REQUEST["shuffle"]))
  {
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];
    set_user_pref('shuffle',$_REQUEST["shuffle"]);
  }

  // Set viewed status
  if (isset($_REQUEST["viewed"]))
  {
    foreach ($episodes as $ep)
      store_request_details(MEDIA_TYPE_TV, $ep["FILE_ID"], ($_REQUEST["viewed"] == 1 ? true : false));
  }

  // Random fanart image
  $themes = db_toarray('select processed_image, show_banner, show_image from themes where media_type='.MEDIA_TYPE_TV.' and title="'.db_escape_str($programme).'" and use_series=1 and processed_image is not NULL');
  $theme = (count($themes) > 0 ? $themes[mt_rand(0,count($themes)-1)] : '');

  // Set banner image
  if ( !empty($theme) && !$theme['SHOW_BANNER'] )
    $banner_img = false;
  else
  {
    // Random banner image
    $banner_imgs = dir_to_array($episodes[0]['DIRNAME'].'banners/','banner_*.*');
    $banner_img = (count($banner_imgs) > 0 ? $banner_imgs[mt_rand(0,count($banner_imgs)-1)] : '');
  }

  // Set series image
  if ( !empty($theme) && !$theme['SHOW_IMAGE'] )
    $series_img = SC_LOCATION.'images/dot.gif';
  else
  {
    // Random series image
    $series_imgs = dir_to_array($episodes[0]['DIRNAME'].'banners/','series'.sprintf("%02d", $current_series).'_*.*');
    $series_img = (count($series_imgs) > 0 ? $series_imgs[mt_rand(0,count($series_imgs)-1)] : '');
  }

  // Set background image
  if ( !empty($theme) && file_exists($theme['PROCESSED_IMAGE']) )
    $background = $theme['PROCESSED_IMAGE'];
  else
    $background = (file_exists($series_img) ? -1 : MEDIA_TYPE_TV);

  page_header( $programme,'','<meta SYABAS-PLAYERMODE="video">',1,false,'',$background,
               ( get_sys_pref('tv_use_banners','YES') == 'YES' && file_exists($banner_img) ? $banner_img : false), 'PAGE_TEXT_BACKGROUND' );

  // There may only be a single series for the selected programme
  if (count($series) > 1)
  {
    // Output a link to the various series/seasons available for this programme
    echo '<table '.style_background('PAGE_TEXT_BACKGROUND').' align="center" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align=center>'.font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).'&nbsp;'.str('SERIES').'</font>';
    foreach ($series as $idx=>$s)
    {
      if ($s == $current_series)
      {
        $current_idx = $idx;
        echo '&nbsp; <a href="'.url_add_params($this_url, array("series"=>$s, "page"=>1, "hist"=>PAGE_HISTORY_REPLACE)).'">',font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).$s.'</font></a>';
      }
      else
        echo '&nbsp; <a href="'.url_add_params($this_url, array("series"=>$s, "page"=>1, "hist"=>PAGE_HISTORY_REPLACE)).'">'.font_tags(FONTSIZE_BODY).$s.'</font></a>';
    }
    echo '&nbsp;</td></table>';

    // Assign prev/next buttons to quickly switch series
    echo '<a href="'.url_add_params($this_url, array("page"=>1, "hist"=>PAGE_HISTORY_REPLACE)).'" TVID="0"></a>';
  }

  // Build up a menu of episodes that the user can select from.
  $viewed_count = 0;
  foreach ($episodes as $ep)
  {
    $viewed_count += viewings_count( MEDIA_TYPE_TV, $ep["FILE_ID"] );
    $viewed = viewed_icon(viewings_count( MEDIA_TYPE_TV, $ep["FILE_ID"]));
    $episode_info = (empty($ep["EPISODE"]) && empty($ep["SERIES"])) ? '' : $ep["SERIES"].'x'.$ep["EPISODE"];
    $menu->add_info_item( $ep[TITLE], $episode_info, url_add_params('/tv_episode_selected.php', array("file_id"=>$ep["FILE_ID"],"cat"=>$_REQUEST["cat"],"view_status"=>$view_status)), false, $viewed);
  }

  if ($menu->num_items() > 0)
  {
    if (file_exists($series_img) )
    {
      // Column 1: Image
      echo '<table width="100%" cellpadding="0" cellspacing="0" border="0">
              <tr>
                <td valign="top">
                  <table '.($theme['SHOW_IMAGE'] ? style_background('PAGE_TEXT_BACKGROUND') : '').' cellpadding="10" cellspacing="0" border="0">
                    <tr valign="top">
                      <td>'.img_gen($series_img,280,550,false,false,false,array(),false).'</td>
                    </tr>
                  </table>
                </td>';
      // Column 2: Gap
      echo '    <td width="'.convert_x(10).'"></td>';
      // Column 3: Menu
      echo '    <td valign="top">';
                  $menu->display_page( $page,1,480,'right' );
      echo '    </td>
              </tr>
            </table>';
    }
    else
    {
      $menu->display_page( $page,1,style_value("MENU_TV_WIDTH"),style_value("MENU_TV_ALIGN") );
    }
  }

  // Output ABC buttons
  $buttons = array();
  $buttons[0] = array('text' => str('QUICK_PLAY'),'url' => play_sql_list(MEDIA_TYPE_TV, $episodes_sql));
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[1] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_params($this_url, array('shuffle'=>'on', 'hist'=>PAGE_HISTORY_REPLACE)) );
  else
    $buttons[1] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_params($this_url, array('shuffle'=>'off', 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( $view_status == 'unviewed')
    $buttons[2] = array('text'=>str('VIEW_ALL'), 'url'=> url_set_params($this_url, array('view_status'=>'all', 'hist'=>PAGE_HISTORY_REPLACE)) );
  else
    $buttons[2] = array('text'=>str('VIEW_UNVIEWED'), 'url'=> url_set_params($this_url, array('view_status'=>'unviewed', 'hist'=>PAGE_HISTORY_REPLACE)) );
  if ( is_user_admin() )
  {
    if ( $viewed_count == 0 )
      $buttons[3] = array('text'=>str('VIEWED_SET'), 'url'=> url_set_params($this_url, array('viewed'=>1, 'hist'=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[3] = array('text'=>str('VIEWED_RESET'), 'url'=> url_set_params($this_url, array('viewed'=>0, 'hist'=>PAGE_HISTORY_REPLACE)) );
  }

  // Make sure the "back" button goes to the correct page:
  page_footer( page_hist_previous(), $buttons, 0, true, 'PAGE_TEXT_BACKGROUND' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
