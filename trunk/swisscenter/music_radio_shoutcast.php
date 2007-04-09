<?
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/users.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/messages_db.php'));
  require_once( realpath(dirname(__FILE__).'/ext/iradio/shoutcast.php'));

  $menu       = new menu();

 $iradio = new shoutcast;

#===================================================[ Display Station List ]===
 if (!empty($_REQUEST["subgenre"])) {
   page_header('Select the station to tune in:');
   $iradio->search_genre($_REQUEST["subgenre"]);
   $stations = $iradio->get_station();
   $sc = count($stations);
   for ($i=0;$i<$sc;++$i) {
     $menu->add_item($stations[$i]->name,$stations[$i]->playlist);
#     echo " <LI><A HREF='".$stations[$i]->playlist."'>".$stations[$i]->name."</A> (".$stations[$i]->type.", ".$stations[$i]->bitrate." kBPS)</LI>\n";
   }
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
