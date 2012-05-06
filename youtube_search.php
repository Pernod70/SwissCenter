<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/resources/video/youtube.php'));

/**
 * A class that extends the abstract list_picker class to provide a keyboard style picker
 * for searching YouTube videos.
 *
 */

class youtube_picker extends list_picker
{
  var $feed_type;

  function youtube_picker()
  {
    parent::list_picker();
    $this->url = url_add_param('youtube_search.php', 'hist', PAGE_HISTORY_REPLACE);
    $this->feed_type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : 'videos';

    // Where do we send the user back to if they quit this page?
    $this->back_url = page_hist_previous();
  }

  function link_url($item)
  {
    return $item['url'];
  }

  function data_list( $search_string, $start, $end)
  {
    $youtube = new phpYouTube();
    switch ( $this->feed_type )
    {
      case 'videos':    { $feed = $youtube->videoSearch($search_string); break; }
      case 'playlists': { $feed = $youtube->playlistSearch($search_string); break; }
      case 'channels':  { $feed = $youtube->channelSearch($search_string); break; }
    }

    $entry_list = array();

    if ( count($feed['feed']['entry']) !== 0 )
    {
      // Add entries from selected feed
      foreach ($feed['feed']['entry'] as $entry)
      {
        // Check [app$control] tag for rejected or blocked entry
        if ( !isset($entry['app$control']) || $entry['app$control']['yt$state']['name'] == 'restricted' )
        {
          switch ($this->feed_type)
          {
            case 'videos':
              $text = utf8_decode($entry['media$group']['media$title']['$t']);
              $url  = url_add_param('youtube_video_selected.php', 'video_id', $entry['media$group']['yt$videoid']['$t']);
              break;

            case 'playlists':
              $text = utf8_decode($entry['title']['$t']).' ('.$entry['yt$countHint']['$t'].')';
              $url  = url_add_params('youtube_browse.php', array('type'=>'playlist', 'playlist_id'=>$entry['yt$playlistId']['$t']));
              break;

            case 'channels':
              $text = utf8_decode($entry['author'][0]['name']['$t']).' ('.$entry['gd$feedLink'][0]['countHint'].')';
              $url  = url_add_params('youtube_browse.php', array('username'=>utf8_decode($entry['author'][0]['name']['$t']), 'type'=>'uploads'));
              break;
          }
          $entry_list[] = array('text' => $text, 'url' => $url);
        }
      }
    }

    return array_slice($entry_list, $start, $end);
  }

  function data_count( $search_string )
  {
    $youtube = new phpYouTube();
    switch ( $this->feed_type )
    {
      case 'videos':    { $feed = $youtube->videoSearch($search_string); break; }
      case 'playlists': { $feed = $youtube->playlistSearch($search_string); break; }
      case 'channels':  { $feed = $youtube->channelSearch($search_string); break; }
    }

    return count($feed['feed']['entry']);
  }

  function display_title()
  {
    return str('YOUTUBE');
  }

  function display_subtitle()
  {
    return str('YOUTUBE_SEARCH_'.$this->feed_type).' : '.$this->search;
  }

  function display_format_name( $item )
  {
    return utf8_decode($item);
  }

  function display()
  {
    // Get data
    $data     = $this->data_list($this->search, ($this->page*MAX_PER_PAGE), MAX_PER_PAGE);
    $num_rows = $this->data_count($this->search);

    // Header
    page_header( $this->display_title($this->search), $this->display_subtitle($this->search),'', $this->focus, false,'','PAGE_KEYBOARD');

    // A-Z picker
    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( url_add_param($this->url, 'search', '')
               , $this->search
               );
    echo '</td><td valign=top>';

    if ( $num_rows == 0)
    {
      // If there's nothing to display, we might want to output a message or some alternative content
      $this->display_nodata($this->search);
    }
    else
    {
      // Display links for previous and next pages
      $last_page  = ceil($num_rows/MAX_PER_PAGE)-1;
      if ($num_rows > MAX_PER_PAGE)
      {
        $this->menu->add_up( url_add_params($this->url, array("type"=>$this->feed_type, "last"=>MAX_PER_PAGE, "search"=>urlencode($this->search), 'page'=>($this->page > 0 ? ($this->page-1) : $last_page)) ));
        $this->menu->add_down( url_add_params($this->url, array("type"=>$this->feed_type, "last"=>1, "search"=>urlencode($this->search), 'page'=>($this->page < $last_page ? ($this->page+1) : 0)) ));
      }

      foreach ($data as $id=>$item)
        $this->menu->add_item($this->display_format_name($item["text"]), $this->link_url($item), true, $this->icon($item));

      $this->menu->display( 1, 480 );
    }

    echo '</td></tr></table>';

    // Display ABC buttons
    $buttons = array();
    if ( $this->feed_type == 'videos' )
      $buttons[] = array( 'text' => str('YOUTUBE_SEARCH_PLAYLISTS'), 'url' => url_add_params($this->url, array("type"=>"playlists", "search"=>urlencode($this->search), "hist"=>PAGE_HISTORY_REPLACE)) );
    elseif ( $this->feed_type == 'playlists' )
      $buttons[] = array( 'text' => str('YOUTUBE_SEARCH_CHANNELS'), 'url' => url_add_params($this->url, array("type"=>"channels", "search"=>urlencode($this->search), "hist"=>PAGE_HISTORY_REPLACE)) );
    else
      $buttons[] = array( 'text' => str('YOUTUBE_SEARCH_VIDEOS'), 'url' => url_add_params($this->url, array("type"=>"videos", "search"=>urlencode($this->search), "hist"=>PAGE_HISTORY_REPLACE)) );
    $buttons[] = array( 'text' => str('SEARCH_CLEAR'), 'url' => $this->url );

    // Make sure the "back" button goes to the correct page
    page_footer( $this->back_url, $buttons);
  }
}

/**
 * Display the search page
 *
 */

  $page = new youtube_picker();
  $page->display();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
