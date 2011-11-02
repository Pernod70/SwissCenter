<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/base/page.php'));
require_once( realpath(dirname(__FILE__).'/base/list_picker.class.php'));
require_once( realpath(dirname(__FILE__).'/resources/trailers/film_trailer_feeds.php'));

/**
 * A class that extends the abstract list_picker class to provide a keyboard style picker
 * for film trailers.
 *
 */

class filmtrailer_picker extends list_picker
{

  function filmtrailer_picker()
  {
    parent::list_picker();
    $this->url = url_add_param('film_trailer_search.php', 'hist', PAGE_HISTORY_REPLACE);

    // Where do we send the user back to if they quit this page?
    $this->back_url = page_hist_back_url();
  }

  function link_url($item)
  {
    return url_add_param('film_trailer_selected.php', 'id', $item["film_id"]);
  }

  function data_list( $search_string, $start, $end)
  {
    $filmtrailer = new FilmTrailer();
    $trailers = $filmtrailer->quickFind($search_string);

    return array_slice($trailers, $start, $end);
  }

  function data_count( $search_string )
  {
    $filmtrailer = new FilmTrailer();
    $trailers = $filmtrailer->quickFind($search_string);

    return count($trailers);
  }

  function display_title()
  {
    return str('FILM_TRAILERS');
  }

  function display_subtitle()
  {
    return str('SEARCH').' : '.$this->search;
  }

  function display_format_name( $item )
  {
    return utf8_decode($item["title"]).(count($item["trailers"]) > 1 ? ' ('.count($item["trailers"]).')' : '');
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
        $this->menu->add_up( url_add_params($this->url, array("last"=>MAX_PER_PAGE, "search"=>urlencode($this->search), 'page'=>($this->page > 0 ? ($this->page-1) : $last_page)) ));
        $this->menu->add_down( url_add_params($this->url, array("last"=>1, "search"=>urlencode($this->search), 'page'=>($this->page < $last_page ? ($this->page+1) : 0)) ));
      }

      foreach ($data as $id=>$item)
        $this->menu->add_item($this->display_format_name($item), $this->link_url($item), true, $this->icon($item));

      $this->menu->display( 1, 480 );
    }

    echo '</td></tr></table>';

    // Display ABC buttons
    $buttons = array();
    $buttons[] = array('text' => str('SEARCH_CLEAR'), 'url' => $this->url);

    // Make sure the "back" button goes to the correct page
    page_footer( $this->back_url, $buttons);
  }
}

/**
 * Display the search page
 *
 */

  $page = new filmtrailer_picker();
  $page->display();

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
