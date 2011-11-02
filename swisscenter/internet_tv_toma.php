<?php
/**************************************************************************************************
   SWISScenter Source                                                              Itzchak Rehberg
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/base/page.php'));
 require_once( realpath(dirname(__FILE__).'/base/browse.php'));
 require_once( realpath(dirname(__FILE__).'/resources/video/toma_internet_tv.php'));

/**
 * Displays a menu of categories and allows you to choose one.
 *
 * @param string $country
 */

function display_toma_categories($country)
{
  $current_url = url_remove_param(current_url(), 'page');
  $select_url = url_remove_param($current_url, 'filter');

  $categories = db_col_to_list("select distinct category from toma_channels where 1=1".toma_filter_media_sql().
                                                                                       toma_filter_country_sql($country)." order by category");

  for ($i=0; $i<count($categories); ++$i)
    $array[] = array("name"=>ucwords($categories[$i]).' ('.toma_channels_count($categories[$i],$country).')', "url"=>url_set_param( $select_url, 'category', $categories[$i]));

  $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

  browse_array($current_url,$array,$page,MEDIA_TYPE_INTERNET_TV);
}

/**
 * Displays a menu of countries and allows you to choose one.
 *
 * @param string $category
 */

function display_toma_countries($category)
{
  $current_url = url_remove_param(current_url(), 'page');
  $select_url = url_remove_param($current_url, 'filter');

  $countries = db_col_to_list("select distinct country from toma_channels where 1=1".toma_filter_media_sql().
                                                                                     toma_filter_category_sql($category)." order by country");

  for ($i=0; $i<count($countries); ++$i)
    $array[] = array("name"=>ucwords($countries[$i]).' ('.toma_channels_count($category,$countries[$i]).')', "url"=>url_set_param( $select_url, 'country', $countries[$i]));

  $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

  browse_array($current_url,$array,$page,MEDIA_TYPE_INTERNET_TV);
}

/**************************************************************************************************
   Main code
 *************************************************************************************************/

$category = (isset($_REQUEST["category"]) ? $_REQUEST["category"] : '');
$country  = (isset($_REQUEST["country"])  ? $_REQUEST["country"] : '');
$sort     = (isset($_REQUEST["sort"])     ? $_REQUEST["sort"] : 'rating');
$current_url = url_remove_params(current_url(), array('filter', 'page'));

// Update the channel list if requested, or if no channels exist
if (isset($_REQUEST["update"]) || toma_channels_count()==0)
{
  page_inform(2, url_remove_param($current_url, 'update'), str('TOMA_INTERNET_TV'), str('TOMA_CHANNELS_UPDATE_MSG'));
  if (update_channel_list() !== false)
    set_sys_pref('TOMA_CHANNELS_UPDATE_TIME',time());
  return;
}

page_header(str('TOMA_INTERNET_TV'), str('TOMA_CHANNELS_AVAILABLE',toma_channels_count($category,$country)),'',1,false,'',MEDIA_TYPE_INTERNET_TV);

if (isset($_REQUEST["filter"]) && $_REQUEST["filter"] == 'category')
{
  // Select category filter
  display_toma_categories($country);
  $back_url = $current_url;
}
elseif (isset($_REQUEST["filter"]) && $_REQUEST["filter"] == 'country')
{
  // Select country filter
  display_toma_countries($category);
  $back_url = $current_url;
}
else
{
  // Select browse channels or select a filter
  $menu = new menu();
  $menu->add_item(str('TOMA_BROWSE_CHANNELS').(!empty($sort) ? ': '.str('SORT_'.strtoupper($sort)) : ''), url_add_params('internet_tv_toma_channels.php',array('category'=>$category, 'country'=>$country, 'sort'=>$sort)) );
  $menu->add_item(str('TOMA_BROWSE_CATEGORY').(!empty($category) ? ': '.$category : ''), url_add_param($current_url,'filter','category') );
  $menu->add_item(str('TOMA_BROWSE_COUNTRY').(!empty($country) ? ': '.$country : ''), url_add_param($current_url,'filter','country') );
  $menu->display(1, style_value("MENU_INTERNET_TV_WIDTH"), style_value("MENU_INTERNET_TV_ALIGN"));
  $back_url = 'internet_tv.php';
}

$buttons = array();
// Sort order
if (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 'rating')
  $buttons[] = array('text' => str('SORT_NAME'),'url' => url_add_param($current_url,'sort','name') );
elseif (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 'name')
  $buttons[] = array('text' => str('SORT_CATEGORY'),'url' => url_add_param($current_url,'sort','category') );
elseif (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 'category')
  $buttons[] = array('text' => str('SORT_ID'),'url' => url_add_param($current_url,'sort','id') );
elseif (isset($_REQUEST["sort"]) && $_REQUEST["sort"] == 'id')
  $buttons[] = array('text' => str('SORT_BITRATE'),'url' => url_add_param($current_url,'sort','bitrate') );
else
  $buttons[] = array('text' => str('SORT_RATING'),'url' => url_add_param($current_url,'sort','rating') );

// Remove filters
$buttons[] = array('text' => str('TOMA_REMOVE_FILTER'), 'url' => url_remove_params($current_url,array('category','country')) );

// Ensure 24 hours have elapsed since last update
if (time() > (get_sys_pref('TOMA_CHANNELS_UPDATE_TIME',0)+86400))
  $buttons[] = array('text' => str('TOMA_UPDATE_CHANNELS'), 'url' => url_add_param($current_url,'update',1) );

page_footer( $back_url, $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
