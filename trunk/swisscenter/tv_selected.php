<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));

  $menu           = new menu();
  $programme      = urldecode($_REQUEST["programme"]);
  $view_status    = $_REQUEST["view_status"];
  $page           = nvl($_REQUEST["page"],1);
  $predicate      = get_rating_filter().category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV);
  $series         = db_col_to_list("select distinct series from tv media ".get_rating_join()." where programme = '$programme' $predicate order by 1");
  $current_series = nvl($_REQUEST["series"], $series[0]["SERIES"]);
  $this_url       = current_url();
  $episodes       = db_toarray("select *
                                  from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
                                 where programme = '$programme'".(empty($current_series) ? "" : " 
                                   and series = $current_series $predicate").
                                       viewed_n_times_predicate( ($view_status == 'unviewed' ? '=' : '>='),0)."
                              order by episode");

  if ( $view_status == 'unviewed')
    $buttons[] = array('text'=>str('VIEW_ALL'), 'url'=> url_set_param($this_url,'view_status','all') );
  else 
    $buttons[] = array('text'=>str('VIEW_UNVIEWED'), 'url'=> url_set_param($this_url,'view_status','unviewed') );  

  page_header( $programme);
  
  // There may only be a single series for the selected programme
  if (count($series) > 1)
  {
    // Output a link to the various series/seasons available for this programme.s
    echo '<center><font color="'.style_value("PAGE_TITLE_COLOUR",'#FFFFFF').'">'.str('SERIES').'</font>';
    
    foreach ($series as $s)
    {
      if ($s == $current_series)
        echo '&nbsp; <a href="'.url_add_params($this_url,array("series"=>$s,"page"=>1)).'"><font color="'.style_value("PAGE_TITLE_COLOUR",'#FFFFFF').'">'.$s.'</font></a>';
      else
        echo '&nbsp; <a href="'.url_add_params($this_url,array("series"=>$s,"page"=>1)).'">'.$s.'</a>';
    }
        
    echo '</center>';
  }

  // Build up a menu of episodes that the user can select from.
  foreach ($episodes as $ep)
  {
    $viewed = viewed_icon(viewings_count( MEDIA_TYPE_TV, $ep["FILE_ID"]));
    $menu->add_item( "$ep[TITLE] ".(empty($ep["EPISODE"]) ? '' : str('EPISODE_SUFFIX',$ep["EPISODE"])), play_file( MEDIA_TYPE_TV, $ep["FILE_ID"]), false, $viewed);
  }

  $menu->display_page( $page );
  page_footer( search_picker_most_recent(), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
