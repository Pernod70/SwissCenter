<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/resources/video/youporn.php'));

/**
 * A class that extends the abstract list_picker class to provide a keyboard style picker
 * for searching YouPorn videos.
 *
 */

class youporn_picker extends list_picker
{
  var $sort;

  function youporn_picker()
  {
    parent::list_picker();
    $this->url = url_add_param('youporn_search.php', 'hist', PAGE_HISTORY_REPLACE);
    $this->sort = isset($_REQUEST["sort"]) ? $_REQUEST["sort"] : 'relevance';

    // Where do we send the user back to if they quit this page?
    $this->back_url = page_hist_previous();
  }

  function link_url($item)
  {
    return $item['url'];
  }

  function data_list( $search_string, $start, $end)
  {
    $youporn = new YouPorn();
    $items = $youporn->getItems('/search/'.$this->sort.'?query='.rawurlencode($search_string));

    $entry_list = array();

    if ( count($items[0]) !== 0 )
    {
      // Add entries from selected feed
      foreach ($items[3] as $idx=>$item)
      {
        $text = $item;
        $url  = url_add_params('youporn_video_selected.php', array('url'=>rawurlencode($items[1][$idx]), 'img'=>rawurlencode($items[2][$idx])));
        $entry_list[] = array('text' => $text, 'url' => $url);
      }
    }

    return array_slice($entry_list, $start, $end);
  }

  function data_count( $search_string )
  {
    $youporn = new YouPorn();
    $items = $youporn->getItems('/search/'.$this->sort.'?query='.rawurlencode($search_string));

    return count($items[1]);
  }

  function display_title()
  {
    return str('YOUPORN');
  }

  function display_subtitle()
  {
    return $this->search;
  }

  function display_format_name( $item )
  {
    return $item;
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
        $this->menu->add_up( url_add_params($this->url, array("last"=>MAX_PER_PAGE, "search"=>rawurlencode($this->search), 'page'=>($this->page > 0 ? ($this->page-1) : $last_page)) ));
        $this->menu->add_down( url_add_params($this->url, array("last"=>1, "search"=>rawurlencode($this->search), 'page'=>($this->page < $last_page ? ($this->page+1) : 0)) ));
      }

      foreach ($data as $id=>$item)
        $this->menu->add_item($this->display_format_name($item["text"]), $this->link_url($item), true, $this->icon($item));

      $this->menu->display( 1, 480 );
    }

    echo '</td></tr></table>';

    // Display ABC buttons
    $buttons = array();
    $buttons[] = array( 'text' => str('SEARCH_CLEAR'), 'url' => $this->url );

    // Sort parameter
    if ( $this->sort == 'relevance' )
      $buttons[] = array('text'=>str('SORT_VIEWS'), 'url'=>url_add_params($this->url, array('sort'=>'views', 'search'=>rawurlencode($this->search), 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( $this->sort == 'views' )
      $buttons[] = array('text'=>str('SORT_RATING'), 'url'=>url_add_params($this->url, array('sort'=>'rating', 'search'=>rawurlencode($this->search), 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( $this->sort == 'rating' )
      $buttons[] = array('text'=>str('SORT_DURATION'), 'url'=>url_add_params($this->url, array('sort'=>'duration', 'search'=>rawurlencode($this->search), 'hist'=>PAGE_HISTORY_REPLACE )));
    elseif ( $this->sort == 'duration' )
      $buttons[] = array('text'=>str('SORT_DATE'), 'url'=>url_add_params($this->url, array('sort'=>'time', 'search'=>rawurlencode($this->search), 'hist'=>PAGE_HISTORY_REPLACE)));
    elseif ( $this->sort == 'time' )
      $buttons[] = array('text'=>str('SORT_RELEVANCE'), 'url'=>url_add_params($this->url, array('sort'=>'relevance', 'search'=>rawurlencode($this->search), 'hist'=>PAGE_HISTORY_REPLACE)));

    // Make sure the "back" button goes to the correct page
    page_footer( $this->back_url, $buttons);
  }
}

/**
 * Display the search page
 *
 */

  $page = new youporn_picker();
  $page->display();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
