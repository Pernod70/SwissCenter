<?php
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/base/page.php'));
 require_once( realpath(dirname(__FILE__).'/base/browse.php'));
 require_once( realpath(dirname(__FILE__).'/base/search.php'));

/**
 * Display the genre menu
 *
 * @param array $genre - array of genres
 * @param string $type - specify 'maingenre' or 'subgenre'
 */
 function make_genre_menu($genre,$type)
 {
   $url = url_remove_param(current_url(), 'page');

   foreach ($genre as $item)
     $array[] = array("name"    => ucwords($item["text"]),
                      "url"     => url_set_param( $url, $type, $item["id"] ),
                      "submenu" => true);

   $page = $_REQUEST["page"];

   if (empty($page))
     $page = 0;

   browse_array($url,$array,$page,MEDIA_TYPE_RADIO);
 }

/**************************************************************************************************
   Main code
 *************************************************************************************************/

 $menu = new menu();
 $current_url = current_url();

 send_to_log(8,"Initializing TuneIn parser");
 require_once( realpath(dirname(__FILE__).'/resources/radio/tunein.php'));
 $iradio = new tunein;
 $iradio->restrict_mediatype('mp3,aac'); // Restrict to mp3, ogg, aac, etc.
 $iradio->set_cache(get_sys_pref('iradio_cache_expire',3600));
 $iradio->set_max_results(get_sys_pref('iradio_max_stations',24));

 // Radio service logo
 $radio_logo_start = '<table width="100%" cellpadding=0 cellspacing=0 border=0>
                        <tr>
                          <td valign=top width="'.convert_x(280).'" align="left"><br>
                            '.img_gen(style_img(strtoupper($_REQUEST["class"]),true,false),280,450).'
                          </td>
                          <td width="'.convert_x(20).'"></td>
                          <td valign="top">';
 $radio_logo_end =   '    </td>
                        </tr>
                      </table>';

 // Output the appropriate menu based, based on earlier choices and ensure that the
 // back button on the remote takes you to the page you just came from.

 if (empty($_REQUEST["by_method"]) && empty($_REQUEST["by_station"]))
 {
   // Select Browse Type
   page_header(str('IRADIO_SEARCH'));
   echo $radio_logo_start;
   if (count($iradio->get_stations()) > 0)
     $menu->add_item(str('BROWSE_STATION'), url_add_param($current_url, 'by_station','1') );
   $menu->add_item(str('BROWSE_GENRE'), url_add_params($current_url, array('by_method'=>'Browse', 'id'=>'g0')) );
   $menu->add_item(str('BROWSE_COUNTRY'), url_add_params($current_url, array('by_method'=>'Browse', 'id'=>'r0')) );
   $menu->add_item(str('BROWSE_LOCAL'), url_add_params($current_url, array('by_method'=>'Browse', 'c'=>'local')) );
   $menu->add_item(str('BROWSE_PRESETS'), url_add_params($current_url, array('by_method'=>'Browse', 'c'=>'presets')) );
   $menu->display(1, style_value("MENU_RADIO_WIDTH"), style_value("MENU_RADIO_ALIGN"));
   echo $radio_logo_end;
   page_footer( page_hist_previous() );
 }
 elseif (!empty($_REQUEST["by_method"]))
 {
   // API method and id parameter
   $method = $_REQUEST["by_method"];
   $view   = isset($_REQUEST["view"]) ? $_REQUEST["view"] : 'links';
   $page   = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;

   if ( isset($_REQUEST["id"]) )
     $iradio->parse($method,'id='.$_REQUEST["id"],$method.'_'.$_REQUEST["id"]);
   else
     $iradio->parse($method,'c='.$_REQUEST["c"],$method.'_'.$_REQUEST["c"]);

   $stations = $iradio->get_station();
   $links    = $iradio->get_link();

   send_to_log(8,"Browsing by genre chosen. Main genre list:",$stations);
   $url = url_remove_param($current_url, 'page');

   if ($view == 'links' && count($links)>0)
   {
     page_header($iradio->title);
     echo $radio_logo_start;
     foreach ($links as $item)
       $array[] = array("name"=>ucwords($item->name), "url"=>url_set_param( $url, 'id', $item->id ) );
     browse_array($url,$array,$page,MEDIA_TYPE_RADIO);
     echo $radio_logo_end;

     // Output ABC buttons
     $buttons = array();
     if (count($stations)>0)
       $buttons[] = array('text'=>str('IRADIO_VIEW_STATIONS'), 'url'=> url_add_params($current_url, array('view'=>'stations', 'hist'=>PAGE_HISTORY_REPLACE)) );

     page_footer( page_hist_previous(), $buttons );
   }
   elseif ( count($stations)>0 )
   {
     $view = 'stations';
     page_header($iradio->title);
     echo $radio_logo_start;
     display_iradio($url, $stations, $page);
     echo $radio_logo_end;

     // Output ABC buttons
     $buttons = array();
     if (count($links)>0)
       $buttons[] = array('text'=>str('IRADIO_VIEW_LINKS'), 'url'=> url_add_params($current_url, array('view'=>'links', 'hist'=>PAGE_HISTORY_REPLACE)) );

     page_footer( page_hist_previous(), $buttons );
   }
   else
   {
     page_inform(5,page_hist_previous(),str('IRADIO_NO_STATIONS'),str('IRADIO_NO_STATIONS_MSG',$_REQUEST["c"]));
   }
 }
 elseif (isset($_REQUEST["by_station"]))
 {
   if (empty($_REQUEST["station"]) )
   {
     // Browse by Broadcasters
     page_header(str('IRADIO_STATION_SEARCH'));
     echo $radio_logo_start;
     $stations = $iradio->get_stations();
     send_to_log(8,"Station search for station initialized. Station array:",$stations);
     make_genre_menu($stations,"station");
     echo $radio_logo_end;
     page_footer( page_hist_previous() );
   }
   else
   {
     // Now the station has been chosen, list the available stations.
     send_to_log(8,"Station search for '".$_REQUEST["station"]."'");
     $iradio->search_station($_REQUEST["station"]);
     $stations = $iradio->get_station();
     $links    = $iradio->get_link();

     $view = isset($_REQUEST["view"]) ? $_REQUEST["view"] : 'stations';
     $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;

     if (count($stations) >0 )
     {
       $image = db_value("select image from iradio_stations where station='".db_escape_str($_REQUEST["station"])."'");
       if ( is_file(SC_LOCATION.'images/iradio/'.$image) )
         $image = SC_LOCATION.'images/iradio/'.$image;
       else
         $image = style_img(strtoupper($_REQUEST["class"]),true,false);

       page_header(str('IRADIO_STATION_SELECT'));
       echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>'.
            '<tr><td valign=top width="'.convert_x(280).'" align="left"><br>'.
            img_gen($image,280,450).
            '</td><td width="'.convert_x(20).'"></td>'.
            '<td valign="top">';

       display_iradio(url_remove_param($current_url, 'page'), $stations, $page);

       echo '</td></table>';

       // Output ABC buttons
       $buttons = array();
//       if ($view == 'stations' && count($links)>0)
//         $buttons[] = array('text'=>str('IRADIO_VIEW_LINKS'), 'url'=> url_add_params($current_url, array('view'=>'links', 'hist'=>PAGE_HISTORY_REPLACE)) );
//       elseif ($view == 'links' && count($stations)>0)
//         $buttons[] = array('text'=>str('IRADIO_VIEW_STATIONS'), 'url'=> url_add_params($current_url, array('view'=>'stations', 'hist'=>PAGE_HISTORY_REPLACE)) );

       page_footer( page_hist_previous(), $buttons );
     }
     else
     {
       page_inform(5,page_hist_previous(),str('IRADIO_NO_STATIONS'),str('IRADIO_NO_STATIONS_MSG',$_REQUEST["station"]));
     }
   }
 }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
