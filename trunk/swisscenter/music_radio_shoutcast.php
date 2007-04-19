<?
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/ext/iradio/shoutcast.php'));

  $menu       = new menu();
  $iradio     = new shoutcast;
  
  $iradio->set_cache( get_sys_pref('CACHE_DIR').'/shoutcast' );
  $iradio->set_cache_expiration(get_sys_pref('',24));
  $iradio->set_max_results(get_sys_pref('iradio_cache_expire',3600));

  function make_genre_menu($url,$genre,$type) 
  {
    $ac = count($genre);
    $page = $_REQUEST["page"];

    for ($i=0;$i<$ac;++$i) 
    {
      $object->name = ucwords($genre[$i]);
      $object->url  = $_SERVER["PHP_SELF"]."?$type=".$genre[$i];
      $array[] = $object;
    }
    
    if (empty($page)) 
      $page = 0;
      
    browse_array($url,$array,$page);
  }
 
  if (!empty($_REQUEST["subgenre"])) 
  {
    #Display Station List
    (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
    page_header(str('IRADIO_STATION_SELECT'));
    $iradio->search_genre($_REQUEST["subgenre"]);
    $stations = $iradio->get_station();
    display_iradio($_SERVER["PHP_SELF"]."?subgenre=".$_REQUEST["subgenre"],$stations,$page);
    $menu->display();
  } 
  elseif (!empty($_REQUEST["maingenre"])) 
  {
    # Select the SubGenre
    page_header(str('IRADIO_SUBGENRE_SELECT'));
    $genres = $iradio->get_subgenres($_REQUEST["maingenre"]);
    $url = $_SERVER["PHP_SELF"]."?maingenre=".$_REQUEST["maingenre"];
    make_genre_menu($url,$genres,"subgenre");
  } 
  else 
  {
    # Select the MainGenre
    page_header(str('IRADIO_MAINGENRE SELECT'));
    $genres = $iradio->get_maingenres();
    $url = $_SERVER["PHP_SELF"]."?ab"; // "ab" is just a dummy, since &page= will be appended
    make_genre_menu($url,$genres,"maingenre");
  }
  
  page_footer('', '', $icons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
