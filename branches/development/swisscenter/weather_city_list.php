<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/az_picker.php");

  function search_page( $this_url, $prefix, $search, $page)
  {
    page_header('Cities', 'Select City : '.$search,'LOGO_WEATHER','', (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] ) );

    $menu      = new menu();
    $data      = array();
    $errmsg    = "";

    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( $this_url.'?any='.$prefix.'&search=', $search);
    echo '</td><td valign=top>';

    if ( ($data = db_toarray("select name from cities where name like '".$prefix.str_replace("_","\_",$search)."%' order by 1 limit ".(($page*MAX_PER_PAGE)).",".MAX_PER_PAGE)) === false)
      echo $errmsg;

    $num_rows = db_value("select count(name) from cities where name like '".$prefix.str_replace("_","\_",$search)."%' ",$errmsg);

    if (empty($data))
    {
      echo 'There are no cities that match your search for <font color="'.style_value("TITLE_COLOUR",'#FFFFFF').'">'.$search.'</font> in the SwissCenter database.
            <p> Would you like to search <font color="'.style_value("TITLE_COLOUR",'#FFFFFF').'">The Weather Channel</font> for possible matches anyway? 
               (enter the city name in full before searching)<p>';

      $menu->add_item("Yes, Search Anyway",'city_selected.php?name='.rawurlencode($search),true);
      $menu->add_item("No, Start A New Search",$this_url.'?sort='.$sort.'&any='.$prefix,true);
      $menu->display( "300" );
 
    }
    else
    {

      // We are not on the first page, so output a link to go "up" a page of entries.
      if ($page > 0)
        $menu->add_up( $this_url.'?search='.rawurlencode($search).'&any='.$prefix.'&page='.($page-1));

      // We are not on the last page, so output a link to go "down" a page of entries.
      if (($page+1)*MAX_PER_PAGE < $num_rows)
        $menu->add_down( $this_url.'?search='.rawurlencode($search).'&any='.$prefix.'&page='.($page+1));

      foreach ($data as $row)
        $menu->add_item($row["NAME"],'city_selected.php?name='.rawurlencode($row["NAME"]),true);

      $menu->display( "300" );
    }

    echo '</td></tr></table>';

    // Output ABC buttons if appropriate

    if (empty($prefix))
      $buttons[] = array('id'=>'A', 'text'=>'Anywhere in Name', 'url'=>$this_url.'?sort='.$sort.'&search='.rawurlencode($search).'&any=%' );
    else
      $buttons[] = array('id'=>'A', 'text'=>'Start of Name', 'url'=>$this_url.'?sort='.$sort.'&search='.rawurlencode($search).'&any=' );

    $buttons[] = array('id'=>'B', 'text'=>'Clear Search', 'url'=>$this_url.'?sort='.$sort.'&any='.$prefix);

    page_footer('weather_cc.php', $buttons);
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
