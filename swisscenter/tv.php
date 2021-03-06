<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/base/categories.php'));
require_once( realpath(dirname(__FILE__).'/base/filter.php'));
require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
require_once( realpath(dirname(__FILE__).'/base/rating.php'));

/**
 * A class that extends the abstract list_picker class to provide a keyboard style picker
 * for TV series (with the auto invalid character removal feature).
 *
 */

class tv_series_picker extends list_picker
{

  function tv_series_picker()
  {
    parent::list_picker();
    $this->url = url_add_param('tv.php','cat',$_REQUEST["cat"]);

    // Where do we send the user back to if they quit this page?
    $this->back_url = page_hist_previous();
  }

  function link_url($item)
  {
    return url_add_params('tv_selected.php',array("programme"=>urlencode($item),"cat"=>$_REQUEST["cat"]));
  }

  function icon($item)
  {
    $viewed_sql = "select concat( sum(if(v.total_viewings>0,1,0)),':',count(*) ) view_status from tv media ".viewed_join(MEDIA_TYPE_TV);
    $viewed = explode(':',db_value($viewed_sql." where sort_programme='".db_escape_str($item)."'"));
    return viewed_icon($viewed[0], $viewed[1]);
  }

  function data_list( $search_string, $start, $end)
  {
    $sql = "select sort_programme
              from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
             where sort_programme like '".db_escape_str($search_string)."'
                   ".get_rating_filter()."
                   ".category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV)."
                   ".filter_get_predicate()."
          group by sort_programme
            having ".viewed_status_predicate( filter_get_name() )."
          order by sort_programme limit $start,$end";

    return db_col_to_list($sql);
  }

  function data_count( $search_string )
  {
    $sql = "select sort_programme
              from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
             where sort_programme like '".db_escape_str($search_string)."'
                   ".get_rating_filter()."
                   ".category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV)."
                   ".filter_get_predicate()."
          group by sort_programme
            having ".viewed_status_predicate( filter_get_name() );

    return count(db_col_to_list($sql));
  }

  function data_valid_chars( $search_string )
  {
    $sql = " select distinct upper(substring(sort_programme,".(mb_strlen($search_string)).",1))
               from tv media ".get_rating_join().viewed_join(MEDIA_TYPE_TV)."
              where sort_programme like '".db_escape_str($search_string)."'
                   ".get_rating_filter()."
                   ".category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV)."
                   ".filter_get_predicate()."
          group by sort_programme
            having ".viewed_status_predicate( filter_get_name() )."
           order by 1";

    return mb_strtoupper(join(db_col_to_list($sql)));
  }

  function display_title()
  {
    return str('WATCH_TV');
  }

  function display_subtitle()
  {
    return str('PROGRAMME').' : '.$this->search;
  }

  function display_format_name( $item )
  {
    return $item;
  }

}

/**
 * Initialise the search history, as this is a top-level search page.
 */

if(empty($_REQUEST["cat"]))
  page_hist_current_update( current_url(), get_rating_filter().filter_get_predicate() );
else
  page_hist_current_update( current_url(), category_select_sql($_REQUEST["cat"], MEDIA_TYPE_TV).get_rating_filter().filter_get_predicate() );

/**
 *  If the user has not selected a category, then display a page to select the appropriate category, otherwise
 * get on with the business of displaying the TV series stored in the database.
 *
 */

if( category_count(MEDIA_TYPE_TV) <= 1 || isset($_REQUEST["cat"]) )
{
  // Display the search page
  $page = new tv_series_picker();
  $page->display();
}
else
{
  page_header( str('WATCH_TV') , '','',1,false,'',MEDIA_TYPE_TV);
  if ( isset($_REQUEST["subcat"]) )
    display_categories('tv.php', MEDIA_TYPE_TV, $_REQUEST["subcat"], page_hist_previous());
  else
    display_categories('tv.php', MEDIA_TYPE_TV, 0, page_hist_previous());
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
