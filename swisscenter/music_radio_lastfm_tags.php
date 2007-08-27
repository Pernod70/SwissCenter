<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
require_once( realpath(dirname(__FILE__).'/ext/lastfm/datafeeds.php'));

class lastfm_tag_picker extends list_picker 
{
  
  function lastfm_tag_picker()
  {
    parent::list_picker();
    $this->url = 'music_radio_lastfm_tags.php';
  }
  
  function link_url($item)
  {
    return play_lastfm( LASTFM_TAG, $item);
  }

  function data_list( $search_string, $start, $end)
  {
    return db_col_to_list("select tag from lastfm_tags where tag like '$search_string' limit $start,$end");
  }
  
  function data_count( $search_string )
  {
    return db_value("select count(*) from lastfm_tags where tag like '$search_string'");
  }
  
  function display_nodata()
  {
    echo '';
  }
  
  function display_title()
  {
    return 'LastFM Tags';
  }
  
  function display_subtitle()
  {
    return 'Tag: '.$this->search;
  }  

  function display_format_name( $item )
  {
    return ucwords($item);
  }
  
}

// Ensure that the list of Last.fm tags is up to date.
lastfm_toptags();

// Display the search page
$page = new lastfm_tag_picker();
$page->display();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
