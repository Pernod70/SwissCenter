<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/az_picker.php'));

  function search_page( $this_url, $prefix, $search, $page)
  {
    page_header('Cities', str('WEATHER_SELECT_CITY').$search,'', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );

    $menu = new menu();
    $data = array();

    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( $this_url.'?any='.$prefix.'&search=', $search);
    echo '</td><td valign=top>';

    $data = db_toarray("select name from cities where name like '".$prefix.str_replace('_','\_',$search)."%' order by 1 limit ".(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE);
    $num_rows = db_value("select count(name) from cities where name like '".$prefix.str_replace('_','\_',$search)."%' ");

    if (empty($data))
    {
      echo font_tags(FONTSIZE_BODY).str( 'WEATHER_NO_CITY'
              , '<font color="'.style_value("PAGE_TITLE_COLOUR",'#FFFFFF').'">'.$search.'</font>'
              , '<font color="'.style_value("PAGE_TITLE_COLOUR",'#FFFFFF').'">'.str('WEATHER_CHANNEL').'</font>').'<p>';

      $menu->add_item( str('SEARCH_YES'),'city_selected.php?name='.rawurlencode($search),true);
      $menu->add_item( str('SEARCH_NO'),$this_url.'?any='.$prefix.'&hist='.PAGE_HISTORY_REPLACE,true);
      $menu->display( 1,480 );

    }
    else
    {
      // Add up/down buttons as needed for prev/next page
      $last_page = ceil($num_rows/MAX_PER_PAGE)-1;
      if ($num_rows > MAX_PER_PAGE)
      {
        $menu->add_up( $this_url.'?last='.MAX_PER_PAGE.'&search='.rawurlencode($search).'&any='.$prefix.'&page='.($page > 0 ? ($page-1) : $last_page));
        $menu->add_down( $this_url.'?last=1&search='.rawurlencode($search).'&any='.$prefix.'&page='.($page < $last_page ? ($page+1) : 0));
      }

      foreach ($data as $row)
        $menu->add_item($row["NAME"],'city_selected.php?name='.rawurlencode($row["NAME"]),true);

      $menu->display( 1,480 );
    }

    echo '</td></tr></table>';

    // Output ABC buttons if appropriate
    $buttons = array();
    if (empty($prefix))
      $buttons[] = array('id'=>'A', 'text'=>str('SEARCH_ANYWHERE'), 'url'=>$this_url.'?search='.rawurlencode($search).'&any=%&hist='.PAGE_HISTORY_REPLACE );
    else
      $buttons[] = array('id'=>'A', 'text'=>str('SEARCH_START'), 'url'=>$this_url.'?search='.rawurlencode($search).'&any=&hist='.PAGE_HISTORY_REPLACE );

    $buttons[] = array('id'=>'B', 'text'=>str('SEARCH_CLEAR'), 'url'=>$this_url.'?any='.$prefix.'&hist='.PAGE_HISTORY_REPLACE );

    page_footer(page_hist_previous(), $buttons);
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Check page parameters, and if not set then assign default values.
  $search  = rawurldecode($_REQUEST["search"]);
  $prefix  = $_REQUEST["any"];
  $page    = ( empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);

  search_page( 'weather_city_list.php', $prefix, $search, $page)


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
