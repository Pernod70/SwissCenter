<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/weather.php'));

  $city    = un_magic_quote($_REQUEST["name"]);
  $matches = get_matching_cities($city);

  if (count($matches) == 0)
  {
    page_header(str('WEATHER_MATCHING_TITLE'),str('WEATHER_MATCHING'));

    echo '&nbsp;<center><p>'.font_tags(FONTSIZE_BODY)
         .str('WEATHER_NO_MATCHING'
             ,'<font color="'.style_value("PAGE_TEXT_BOLD_COLOUR",'#FFFFFF').'">The Weather Channel</font>'
             ,'<font color="'.style_value("PAGE_TEXT_BOLD_COLOUR",'#FFFFFF').'">'.$_REQUEST["name"].'</font>')
         .'<p></center>';

    $menu = new menu();
    $menu->add_item(str('WEATHER_BACK_TO_SEARCH'),'weather_city_list.php',true);
    $menu->display();
    page_footer("weather_cc.php");
  }
  elseif (count($matches) == 1)
  {
    // Single match, so redirect back to the main weather page, but passing the selected city as a parameter.
    $codes = array_keys($matches);
    header("Location: /weather_cc.php?loc=".$codes[0]);
  }
  else
  {
    // More than one match was found, and the database was updated, so redirect the use back to the A-Z
    // city listings.
    header("Location: /weather_city_list.php?search=".rawurlencode($city));
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
