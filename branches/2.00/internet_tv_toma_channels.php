<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
require_once( realpath(dirname(__FILE__).'/resources/video/toma_internet_tv.php'));

/**
 * A class that extends the abstract list_picker class to provide a keyboard style picker
 * for TOMA channels (with the auto invalid character removal feature).
 *
 */

class toma_channel_picker extends list_picker
{

  function toma_channel_picker()
  {
    parent::list_picker();
    $this->url = url_remove_params(current_url(),array('page','search'));

    // Where do we send the user back to if they quit this page?
    $this->back_url = url_add_params('internet_tv_toma.php',array('category'=>$_REQUEST["category"], 'country'=>$_REQUEST["country"], 'sort'=>$_REQUEST["sort"]));
  }

  function link_url($item)
  {
    return play_internet_tv($item['STREAM_URL']);
  }

  function icon($item)
  {
    // The icons are reverse engineered to match those shown on the TOMA Internet TV website
    if ($item['SPAM'] >= 4 && max($item['WORKS_FINE'], $item['NOT_WORKING'], $item['WRONG_INFO'], $item['DUPLICATE'], $item['SPAM']) == $item['SPAM'])
      return ICON_TRASH;
    elseif ($item['DUPLICATE'] >= 4 && max($item['WORKS_FINE'], $item['NOT_WORKING'], $item['WRONG_INFO'], $item['DUPLICATE'], $item['SPAM']) == $item['DUPLICATE'])
      return ICON_DUPLICATE;
    elseif ($item['WRONG_INFO'] >= 4 && max($item['WORKS_FINE'], $item['NOT_WORKING'], $item['WRONG_INFO'], $item['DUPLICATE'], $item['SPAM']) == $item['WRONG_INFO'])
      return ICON_QUESTION;
    elseif ($item['NOT_WORKING'] >= 4 && max($item['WORKS_FINE'], $item['NOT_WORKING'], $item['WRONG_INFO'], $item['DUPLICATE'], $item['SPAM']) == $item['NOT_WORKING'])
      return ICON_CROSS;
    elseif ($item['WORKS_FINE'] >= 4 && max($item['WORKS_FINE'], $item['NOT_WORKING'], $item['WRONG_INFO'], $item['DUPLICATE'], $item['SPAM']) == $item['WORKS_FINE'])
      return ICON_TICK;
    else
      return ICON_NEW;
  }

  function data_list( $search_string, $start, $end)
  {
    $order = (in_array($_REQUEST["sort"], array('name','category')) ? 'asc' : 'desc');
    $sql = "select * from toma_channels
             where name like '".db_escape_str($search_string)."'".
                   toma_filter_media_sql().
                   toma_filter_category_sql($_REQUEST["category"]).
                   toma_filter_country_sql($_REQUEST["country"])."
          order by ".$_REQUEST["sort"]." $order limit $start,$end";

    return db_toarray($sql);
  }

  function data_count( $search_string )
  {
    $sql = "select count(name) from toma_channels
             where name like '".db_escape_str($search_string)."'".
                   toma_filter_media_sql().
                   toma_filter_category_sql($_REQUEST["category"]).
                   toma_filter_country_sql($_REQUEST["country"]);

    return db_value($sql);
  }

  function data_valid_chars( $search_string )
  {
    $sql = "select distinct upper(substring( name,".(strlen($search_string)).",1))
              from toma_channels
             where name like '".db_escape_str($search_string)."'".
                   toma_filter_media_sql().
                   toma_filter_category_sql($_REQUEST["category"]).
                   toma_filter_country_sql($_REQUEST["country"])."
          order by 1";

    return strtoupper(join(db_col_to_list($sql)));
  }

  function display_title()
  {
    return str('TOMA_INTERNET_TV');
  }

  function display_subtitle()
  {
    return str('NAME').' : '.$this->search;
  }

  function display_format_name( $item )
  {
    return $item['NAME'].(in_array($_REQUEST["sort"], array('id','name')) ? '' : ' ('.$item[strtoupper($_REQUEST["sort"])].')').(empty($item['DESCRIPTION']) ? '' : ' - '.$item['DESCRIPTION']);
  }

}

// Display the search page
$page = new toma_channel_picker();
$page->display();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
