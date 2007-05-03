<?
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/base/page.php'));
 require_once( realpath(dirname(__FILE__).'/base/browse.php'));

 //
 // Takes 
 //
 
 function make_genre_menu($genre,$type) 
 {
   $url = current_url();
   $ac  = count($genre);
   
   for ($i0;$i<$ac;++$i) 
   {
     $object->name = ucwords($genre[$i]);
     $object->url  = url_set_param( $url, $type, $genre[$i]);
     $array[] = $object;
   }
   $page = $_REQUEST["page"];

   if (empty($page)) 
     $page = 0;

   browse_array($url,$array,$page);
 }

/**************************************************************************************************
   Main code
 *************************************************************************************************/

 $menu = new menu();
 $current_url  = current_url();

 switch ($_REQUEST["class"]) 
 {
   case shoutcast :
       require_once( realpath(dirname(__FILE__).'/ext/iradio/shoutcast.php'));
       $iradio  = new shoutcast;
       $cachedir = get_sys_pref('CACHE_DIR').'/shoutcast';
       break;
   case liveradio :
       require_once( realpath(dirname(__FILE__).'/ext/iradio/live-radio.php'));
       $iradio  = new liveradio;
       $cachedir = get_sys_pref('CACHE_DIR').'/liveradio';
       break;
 }
 
 if (!empty($_REQUEST["class"])) 
 {
   if (!file_exists($cachedir)) 
     mkdir($cachedir);
     
   $iradio->set_cache( $cachedir );
   $iradio->set_cache_expiration(get_sys_pref('',24));
   $iradio->set_max_results(get_sys_pref('iradio_cache_expire',3600));
 }

 // Output the appropriate menu based, based on earlier choices and ensure that the
 // back button on the remote takes you to the page you just came from.

 if (empty($_REQUEST["by_genre"]) && empty($_REQUEST["by_country"]))
 {
   // Select Browse Type
   page_header(str('IRADIO_SEARCH'));
   $menu->add_item(str('BROWSE_GENRE'), url_add_param($current_url,'by_genre','1') );
   $menu->add_item(str('BROWSE_COUNTRY'), url_add_param($current_url,'by_country','1') );
   $menu->display();   
   page_footer('music_radio.php');
 }
 else
 {
   if (!empty($_REQUEST["by_genre"]))
   {
     // Browse by Genre/Subgenre
     if (empty($_REQUEST["maingenre"]) )
     {
       // Choose main genre
       page_header(str('IRADIO_MAINGENRE SELECT'));
       $genres = $iradio->get_maingenres();
       make_genre_menu($genres,"maingenre");
       page_footer( url_remove_param( $current_url,'by_genre') );
     }
     elseif (empty($_REQUEST["subgenre"]) )
     {
       // Choose sub-genre
       page_header(str('IRADIO_SUBGENRE_SELECT'));
       $genres = $iradio->get_subgenres($_REQUEST["maingenre"]);
       make_genre_menu($genres,"subgenre");
       page_footer( url_remove_param( $current_url,'maingenre') );
     }
     else 
     {
       // Main and subgenre chosen, so list the available radio stations.
       $back_url = url_remove_param( $current_url,'subgenre');
       $iradio->search_genre($_REQUEST["subgenre"]);
       $stations = $iradio->get_station();
       if (count($stations) >0 )
       {
         (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
         page_header(str('IRADIO_STATION_SELECT'));         
         display_iradio($current_url, $stations, $page);
         page_footer( $back_url );
       }
       else
       {
         page_inform(5,$back_url,str('IRADIO_NO_STATIONS'),str('IRADIO_NO_STATIONS_MSG',$_REQUEST["maingenre"].':'.$_REQUEST["subgenre"]));
       }
     }
   }
   else 
   {
     if (empty($_REQUEST["country"]) )
     {
       // Browse by Country/Language
       page_header(str('IRADIO_COUNTRY_SELECT'));
       $countries = $iradio->get_countries();
       make_genre_menu($countries,"country");
       page_footer( url_remove_param( $current_url,'by_country') );
     }
     else 
     {
       // Now the country/langauge has been chosen, list the available stations.
       $back_url = url_remove_param( $current_url,'country');
       $iradio->search_country($_REQUEST["country"]);
       $stations = $iradio->get_station();
       if (count($stations) >0 )
       {
         (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
         page_header(str('IRADIO_STATION_SELECT'));
         display_iradio($current_url,$stations,$page);
         page_footer( $back_url );
       }
       else
       {
         page_inform(5,$back_url,str('IRADIO_NO_STATIONS'),str('IRADIO_NO_STATIONS_MSG',$_REQUEST["country"]));
       }
     }
   }   
 }
 
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
