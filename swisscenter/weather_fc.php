<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/prefs.php'));
  require_once( realpath(dirname(__FILE__).'/base/infotab.php'));
  require_once( realpath(dirname(__FILE__).'/base/weather.php'));

  $buttons = array();
  $loc_id  = $_REQUEST["loc"];

  // Change between standard/metric measurements.
  if (!empty($_REQUEST["units"]))
    set_user_pref('WEATHER_UNITS',$_REQUEST["units"]);

  // Get XML data (from DB or from weather.com)
  purge_weather();
  $xml_fc    = get_weather_xml( $loc_id, 'dayf', '5');
  $xml_links = get_weather_xml( $loc_id, 'link', 'xoap');

  $time      = split(' ',$xml_fc["dayf"]["lsup"]);

  // Display Weather Icon and statistics
  page_header($xml_fc[$loc_id]["dnam"],'');

  $menu = new menu();
  $menu->add_item( str('WEATHER_CURRENT') ,'weather_cc.php?loc='.$loc_id);

  echo '<table border=0 cellspacing=0 cellpadding=0 width="100%">
          <tr>';
            
  for ($i=0; $i<5; $i++)
  {
    if ( $xml_fc["dayf"][$i]["d"]["icon"] == '-')
    {
      $xml_fc["dayf"][$i]["d"]["icon"] = 'na';
      $xml_fc["dayf"][$i]["hi"]        = chr(151);
    }

    if ( $xml_fc["dayf"][$i]["n"]["icon"] == '-')
    {
      $xml_fc["dayf"][$i]["n"]["icon"] = 'na';
    }

    $fc = new infotab();
    $fc->set_col_attrib(1,'width',convert_x(54));
    $fc->set_col_attrib(1,'align','right');
    $fc->set_col_attrib(2,'align','right');
    $fc->set_col_attrib(2,'width',convert_x(70));
    $fc->add_item('Hi', $xml_fc["dayf"][$i]["hi"].chr(176).$xml_fc["head"]["ut"]);
    $fc->add_item('Lo', $xml_fc["dayf"][$i]["low"].chr(176).$xml_fc["head"]["ut"]);
    $day = date('D',time()+$i*86400);

    echo '<td><center><font color="'.style_value("PAGE_TITLE_COLOUR").'" size="4">'.$day.'</font><p>'
         .img_gen(SC_LOCATION.'/weather/large/'.$xml_fc["dayf"][$i]["d"]["icon"].'.gif',100,130,false,false,'RESIZE').'<br>';
          $fc->display(100);
    echo  img_gen(SC_LOCATION.'/weather/large/'.$xml_fc["dayf"][$i]["n"]["icon"].'.gif',100,130,false,false,'RESIZE').'<br>
          </center><p></td>';
  }
              
  echo '  </tr>
        </table><p>
        <table cellspacing=0 cellpadding=0 width="100%">
          <tr>
            <td width="'.convert_x(640).'">';
              $menu->display(560);
  echo '    </td>
            <td align="center" valign="bottom"><a href="'.weather_link().'">'.img_gen(SC_LOCATION.'/weather/logo.gif',130,130,false,false,'RESIZE').'</a></td>
          </tr>
        </table>';

  //
  // Display ABC buttons as necessary
  //
  if (get_user_pref("weather_units") == 'm')
    $buttons[] = array('id'=>'A', 'text'=>str('WEATHER_IMPERIAL'), 'url'=>'weather_fc.php?units=s&loc='.$loc_id );
  else
    $buttons[] = array('id'=>'A', 'text'=>str('WEATHER_METRIC'), 'url'=>'weather_fc.php?units=m&loc='.$loc_id );

  page_footer('weather_cc.php?loc='.$loc_id, $buttons);
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
