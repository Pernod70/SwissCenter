<?
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/ext/iradio/shoutcast.php'));

  $menu       = new menu();

 $iradio = new shoutcast;
 $iradio->set_cache( realpath(dirname(__FILE__).'/cache/shoutcast') );

#===================================================[ Display Station List ]===
 if (!empty($_REQUEST["subgenre"])) {
   (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
   page_header('Select the station to tune in:');
   $iradio->search_genre($_REQUEST["subgenre"]);
   $stations = $iradio->get_station();
   display_iradio($_SERVER["PHP_SELF"]."?subgenre=".$_REQUEST["subgenre"],$stations,$page);
#====================================================[ Select the SubGenre ]===
 } elseif (!empty($_REQUEST["maingenre"])) {
   page_header('Select the Sub-Genre to search for:');
   $genres = $iradio->get_subgenres($_REQUEST["maingenre"]);
   $gc = count($genres);
   for ($i=0;$i<$gc;++$i) {
     $menu->add_item(ucfirst($genres[$i]),$_SERVER["PHP_SELF"]."?subgenre=".$genres[$i],true);
   }
#===================================================[ Select the MainGenre ]===
 } else {
   page_header('Select the Main-Genre to search for:');
   $genres = $iradio->get_maingenres();
   $gc = count($genres);
   for ($i=0;$i<$gc;++$i) {
     $menu->add_item(ucfirst($genres[$i]),$_SERVER["PHP_SELF"]."?maingenre=".$genres[$i],true);
   }
 }
 $menu->display();
 page_footer('', '', $icons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
