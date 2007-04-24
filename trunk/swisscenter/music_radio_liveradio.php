<?
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/ext/iradio/live-radio.php'));

  $menu       = new menu();

 $iradio = new liveradio;
 $iradio->set_cache( realpath(dirname(__FILE__).'/cache/liveradio') );
 $iradio->set_cache_expiration(get_sys_pref('',24));
 $iradio->set_max_results(get_sys_pref('iradio_cache_expire',3600));

 function make_genre_menu($url,$genre,$type) {
   $ac = count($genre);
   for ($i=0;$i<$ac;++$i) {
     $object->name = ucwords($genre[$i]);
     $object->url  = $_SERVER["PHP_SELF"]."?$type=".$genre[$i];
     $array[] = $object;
   }
   $page = $_REQUEST["page"];
   if (empty($page)) $page = 0;
   browse_array($url,$array,$page);
 }

#=============================================[ Display Genre Station List ]===
 if (!empty($_REQUEST["subgenre"])) {
   (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
   page_header(str('IRADIO_STATION_SELECT'));
   $iradio->search_genre($_REQUEST["subgenre"]);
   $stations = $iradio->get_station();
   display_iradio($_SERVER["PHP_SELF"]."?subgenre=".$_REQUEST["subgenre"],$stations,$page);
   $menu->display();
#===========================================[ Display Country Station List ]===
 } elseif (!empty($_REQUEST["country"])) {
   (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
   page_header(str('IRADIO_STATION_SELECT'));
   $iradio->search_country($_REQUEST["country"]);
   $stations = $iradio->get_station();
   display_iradio($_SERVER["PHP_SELF"]."?country=".$_REQUEST["country"],$stations,$page);
   $menu->display();
#====================================================[ Select the SubGenre ]===
 } elseif (!empty($_REQUEST["maingenre"])) {
   page_header(str('IRADIO_SUBGENRE_SELECT'));
   $genres = $iradio->get_subgenres($_REQUEST["maingenre"]);
   $url = $_SERVER["PHP_SELF"]."?maingenre=".$_REQUEST["maingenre"];
   make_genre_menu($url,$genres,"subgenre");
#===================================================[ Select the MainGenre ]===
 } elseif(!empty($_REQUEST["by_genre"])) {
   page_header(str('IRADIO_MAINGENRE SELECT'));
   $genres = $iradio->get_maingenres();
   $url = $_SERVER["PHP_SELF"]."?ab"; // "ab" is just a dummy, since &page= will be appended
   make_genre_menu($url,$genres,"maingenre");
#===================================================[ Select the Country ]===
 } elseif(!empty($_REQUEST["by_country"])) {
   page_header(str('IRADIO_COUNTRY_SELECT'));
   $countries = $iradio->get_countries();
   $url = $_SERVER["PHP_SELF"]."?ab"; // "ab" is just a dummy, since &page= will be appended
   make_genre_menu($url,$countries,"country");
#=====================================================[ Select Browse Type ]===
 } else {
   page_header('Browse Live-Radio');
   $menu->add_item(str('BROWSE_GENRE'),$_SERVER["PHP_SELF"]."?by_genre=1");
   $menu->add_item(str('BROWSE_COUNTRY'),$_SERVER["PHP_SELF"]."?by_country=1");
   $menu->display();
 }
 page_footer('', '', $icons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
