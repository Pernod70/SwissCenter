<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/weather.php");

  $city    = un_magic_quote($_REQUEST["name"]);
  $matches = get_matching_cities($city);

  if (count($matches) == 0)
  {
    page_header("Matching Cities",'Cities that match your search');
    echo 'I\'m sorry, <font color="'.style_col("MENU_OPTION_REF_COLOUR").'">The Weather Channel</font> does not provide 
          forecast information for the city of <font color="'.style_col("MENU_OPTION_REF_COLOUR").'">'.$_REQUEST["name"].'</font> 
          at this time.';
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
