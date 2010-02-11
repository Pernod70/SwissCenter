<?php
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
   $url = url_remove_param(current_url(), 'page');
   $ac  = count($genre);

   for ($i=0;$i<$ac;++$i)
     $array[] = array("name"=>ucwords($genre[$i]), "url"=>url_set_param( $url, $type, $genre[$i]));

   $page = $_REQUEST["page"];

   if (empty($page))
     $page = 0;

   browse_array($url,$array,$page,MEDIA_TYPE_RADIO);
 }

/**************************************************************************************************
   Main code
 *************************************************************************************************/

 $menu = new menu();
 $current_url  = current_url();

 switch ($_REQUEST["class"])
 {
   case shoutcast :
       send_to_log(8,"Initializing ShoutCast parser");
       require_once( realpath(dirname(__FILE__).'/ext/iradio/shoutcast.php'));
       $iradio  = new shoutcast;
       $cachedir = get_sys_pref('CACHE_DIR').'/shoutcast';
       break;
   case liveradio :
       send_to_log(8,"Initializing Live-Radio parser");
       require_once( realpath(dirname(__FILE__).'/ext/iradio/live-radio.php'));
       $iradio  = new liveradio;
       $cachedir = get_sys_pref('CACHE_DIR').'/liveradio';
       $iradio->restrict_mediatype("mp3");
       break;
   case live365 :
       send_to_log(8,"Initializing Live365 parser");
       require_once( realpath(dirname(__FILE__).'/ext/iradio/live365.php'));
       $iradio  = new live365;
       $cachedir = get_sys_pref('CACHE_DIR').'/live365';
       break;
 }

 if (!empty($_REQUEST["class"]))
 {
   if (!file_exists($cachedir))
     @mkdir($cachedir);

   send_to_log(8,"Initialize station cache. CacheDir: '$cachedir', Expiry: '".get_sys_pref('iradio_cache_expire',3600)."'");
   $iradio->set_cache( $cachedir );
   $iradio->set_cache_expiration(get_sys_pref('iradio_cache_expire',3600));
   $iradio->set_max_results(get_sys_pref('iradio_max_stations',24));

 }

 // Output the appropriate menu based, based on earlier choices and ensure that the
 // back button on the remote takes you to the page you just came from.

 if (empty($_REQUEST["by_genre"]) && empty($_REQUEST["by_station"]) && empty($_REQUEST["by_country"]))
 {
   // Select Browse Type
   page_header(str('IRADIO_SEARCH') , '','',1,false,'',MEDIA_TYPE_RADIO);
   $menu->add_item(str('BROWSE_STATION'), url_add_param($current_url,'by_station','1') );
   $menu->add_item(str('BROWSE_GENRE'), url_add_param($current_url,'by_genre','1') );
   $menu->add_item(str('BROWSE_COUNTRY'), url_add_param($current_url,'by_country','1') );
   $menu->display(1, style_value("MENU_RADIO_WIDTH"), style_value("MENU_RADIO_ALIGN"));
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
       page_header(str('IRADIO_MAINGENRE SELECT'),'','',1,false,'',MEDIA_TYPE_RADIO);
       $genres = $iradio->get_maingenres();
       send_to_log(8,"Browsing by genre chosen. Main genre list:",$genres);
       make_genre_menu($genres,"maingenre");
       page_footer( url_remove_params( $current_url,array('by_genre','page')) );
     }
     elseif (empty($_REQUEST["subgenre"]) )
     {
       // Choose sub-genre
       page_header(str('IRADIO_SUBGENRE_SELECT'),'','',1,false,'',MEDIA_TYPE_RADIO);
       $genres = $iradio->get_subgenres($_REQUEST["maingenre"]);
       send_to_log(8,"Browsing by genre chosen. Main genre: '".$_REQUEST["maingenre"]."', sub-genre list:",$genres);
       make_genre_menu($genres,"subgenre");
       page_footer( url_remove_params( $current_url,array('maingenre','page')) );
     }
     else
     {
       // Main and subgenre chosen, so list the available radio stations.
       $back_url = url_remove_params( $current_url,array('subgenre','page'));
       send_to_log(8,"Genre search for '".$_REQUEST["subgenre"]."'");
       $iradio->search_genre($_REQUEST["subgenre"]);
       $stations = $iradio->get_station();
       send_to_log(8,"Station list parsed",$stations);
       if (count($stations) >0 )
       {
         (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
         page_header(str('IRADIO_STATION_SELECT'),'','',1,false,'',MEDIA_TYPE_RADIO);
         display_iradio(url_remove_param( $current_url, 'page'), $stations, $page);
         page_footer( $back_url );
       }
       else
       {
         page_inform(5,$back_url,str('IRADIO_NO_STATIONS'),str('IRADIO_NO_STATIONS_MSG',$_REQUEST["maingenre"].':'.$_REQUEST["subgenre"]));
       }
     }
   }
   elseif (!empty($_REQUEST["by_station"]))
   {
     if (empty($_REQUEST["station"]) )
     {
       // Browse by Broadcasters
       page_header(str('IRADIO_STATION_SEARCH'),'','',1,false,'',MEDIA_TYPE_RADIO);
       $stations = $iradio->get_stations();
       send_to_log(8,"Station search for station initialized. Station array:",$stations);
       make_genre_menu($stations,"station");
       page_footer( url_remove_params( $current_url,array('by_station','page')) );
     }
     else
     {
       // Now the station has been chosen, list the available stations.
       send_to_log(8,"Station search for '".$_REQUEST["station"]."'");
       $back_url = url_remove_params( $current_url,array('station','page'));
       $iradio->search_station($_REQUEST["station"]);
       $stations = $iradio->get_station();
       send_to_log(8,"Station list parsed",$stations);
       if (count($stations) >0 )
       {
         (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
         $image = db_value("select image from iradio_stations where station='".db_escape_str($_REQUEST["station"])."'");
         if ( is_file(SC_LOCATION."images/iradio/$image") )
         {
           page_header(str('IRADIO_STATION_SELECT'));
           echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>'.
                '<tr><td valign=top width="'.convert_x(280).'" align="left">'.
                img_gen(SC_LOCATION."images/iradio/$image",280,550).
                '</td><td width="'.convert_x(20).'"></td>'.
                '<td valign="top">';
                display_iradio(url_remove_param( $current_url, 'page'),$stations,$page);
           echo '</td></table>';
         }
         else
         {
           page_header(str('IRADIO_STATION_SELECT'),'','',1,false,'',MEDIA_TYPE_RADIO);
           display_iradio(url_remove_param( $current_url, 'page'),$stations,$page);
         }
         page_footer( $back_url );
       }
       else
       {
         page_inform(5,$back_url,str('IRADIO_NO_STATIONS'),str('IRADIO_NO_STATIONS_MSG',$_REQUEST["station"]));
       }
     }
   }
   else
   {
     if (empty($_REQUEST["country"]) )
     {
       // Browse by Country/Language
       page_header(str('IRADIO_COUNTRY_SELECT'),'','',1,false,'',MEDIA_TYPE_RADIO);
       $countries = $iradio->get_countries();
       send_to_log(8,"Station search for country initialized. Country array:",$countries);
       make_genre_menu($countries,"country");
       page_footer( url_remove_params( $current_url,array('by_country','page')) );
     }
     else
     {
       // Now the country/langauge has been chosen, list the available stations.
       send_to_log(8,"Country search for '".$_REQUEST["country"]."'");
       $back_url = url_remove_params( $current_url,array('country','page'));
       $iradio->search_country($_REQUEST["country"]);
       $stations = $iradio->get_station();
       send_to_log(8,"Station list parsed",$stations);
       if (count($stations) >0 )
       {
         (isset($_REQUEST["page"])) ? $page = $_REQUEST["page"] : $page = 0;
         page_header(str('IRADIO_STATION_SELECT'),'','',1,false,'',MEDIA_TYPE_RADIO);
         display_iradio(url_remove_param( $current_url, 'page'),$stations,$page);
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
