<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/categories.php");
  require_once("base/rating.php");
  require_once("base/playlist.php");

  function display_music_menu($cat_id)
  {
    if(empty($cat_id))
    {
      $_SESSION["history"] = array(array("url"=>"music.php",
        "sql"=>get_rating_filter()));
    }
    else
    {
      $_SESSION["history"] = array(array("url"=>"music.php?cat=$cat_id",
        "sql"=>category_select_sql($cat_id, 1).get_rating_filter()));
    }

    echo '<center>'.str('SELECT_OPTION').'</center><p>';

    $menu = new menu();
    $menu->add_item( str('BROWSE_ARTIST') ,"music_search.php?sort=artist",true);
    $menu->add_item( str('BROWSE_ALBUM') ,"music_search.php?sort=album",true);
    $menu->add_item( str('BROWSE_TRACK') ,"music_search.php?sort=title",true);
    $menu->add_item( str('BROWSE_GENRE') ,"music_search.php?sort=genre",true);
    $menu->add_item( str('BROWSE_YEAR') ,"music_search.php?sort=year",true);
    $menu->add_item( str('BROWSE_FILESYSTEM') ,"music_browse.php",true);
    $menu->display();
    
    page_footer('music.php', array(array('text'=>str('QUICK_PLAY')
                                        ,'url'=>quick_play_link("mp3s","audio",$_SESSION["history"][0][sql]))));
  }

 /**************************************************************************************************
   Main page output
   *************************************************************************************************/

  page_header( str('LISTEN_MUSIC'), '', 'LOGO_MUSIC');
  $cat_id = $_REQUEST["cat"];
  
  if( !empty($cat_id) )
    display_music_menu($cat_id);
  else
    display_categories('music.php', 1);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
