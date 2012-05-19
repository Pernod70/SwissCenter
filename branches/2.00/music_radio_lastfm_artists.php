<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
require_once( realpath(dirname(__FILE__).'/ext/lastfm/datafeeds.php'));

class lastfm_artist_picker extends list_picker
{

  function lastfm_artist_picker()
  {
    parent::list_picker();
    $this->url = 'music_radio_lastfm_artists.php';
    $this->back_url = 'music_radio_lastfm.php';
  }

  function link_url($item)
  {
    return play_lastfm( LASTFM_ARTIST, $item);
  }

  function data_list( $search_string, $start, $end)
  {
    return db_col_to_list("select distinct artist from media_audio where artist like '$search_string' order by 1 limit $start,$end");
  }

  function data_count( $search_string )
  {
    return db_value("select count(distinct artist) from media_audio where artist like '$search_string'");
  }

  function data_valid_chars( $search_string )
  {
    return strtoupper(join(db_col_to_list(" select distinct upper(substring( artist,".(strlen($search_string)).",1)) from media_audio where artist like '$search_string' order by 1")));
  }

  function display_title()
  {
    return str('LASTFM_ARTIST');
  }

  function display_subtitle()
  {
    return str('ARTIST').' : '.$this->search;
  }

}

// Display the search page
$page = new lastfm_artist_picker();
$page->display();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
