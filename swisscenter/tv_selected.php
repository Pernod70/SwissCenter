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
  if (isset($_REQUEST["shuffle"]))
  {
    $_SESSION["shuffle"] = $_REQUEST["shuffle"];
    set_user_pref('shuffle',$_REQUEST["shuffle"]);
  }

  $series         = db_col_to_list("select distinct series 
                                      from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)." 
                                     where programme = '$programme' $predicate ".
                                           viewed_n_times_predicate( ($view_status == 'unviewed' ? '=' : '>='),0)."
                                  order by 1");

  $current_series = (in_array($_REQUEST["series"], $series) ? $_REQUEST["series"] : $series[count($series)-1]);
  $this_url       = url_set_param(current_url(),'del','N');

  $episodes_sql   = "select * from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
                                 where programme = '$programme'".(is_null($current_series) ? "" : " 
                                   and series = $current_series $predicate").
                                       viewed_n_times_predicate( ($view_status == 'unviewed' ? '=' : '>='),0)."
                              order by episode";
  $episodes       = db_toarray($episodes_sql);
               
  // Should we delete the last entry on the history stack?
  if (isset($_REQUEST["del"]) && strtoupper($_REQUEST["del"]) == 'Y')
    search_hist_pop();
  
  search_hist_push( $this_url , $predicate );
  page_header( $programme,'','',1,false,'',MEDIA_TYPE_TV );
  
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
    $menu->add_item( "$ep[TITLE] ".(empty($ep["EPISODE"]) ? '' : str('EPISODE_SUFFIX',$ep["EPISODE"])), url_add_params('/tv_episode_selected.php', array("file_id"=>$ep["FILE_ID"],"add"=>"Y")), false, $viewed);
  }

  if ($menu->num_items() > 0)
    $menu->display_page( $page,1,style_value("MENU_TV_WIDTH"),style_value("MENU_TV_ALIGN") );
    
  $buttons = array();
  $buttons[] = array('text' => str('QUICK_PLAY'),'url' => play_sql_list(MEDIA_TYPE_TV, $episodes_sql));                                
  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_param($this_url,'shuffle','on') );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_param($this_url,'shuffle','off') );
  if ( $view_status == 'unviewed')
    $buttons[] = array('text'=>str('VIEW_ALL'), 'url'=> url_set_param($this_url,'view_status','all') );
  else 
    $buttons[] = array('text'=>str('VIEW_UNVIEWED'), 'url'=> url_set_param($this_url,'view_status','unviewed') );  

  page_footer( url_add_params( search_picker_most_recent(), array("p_del"=>"y","del"=>"y") ), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
