<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/page.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/az_picker.php'));

class list_picker
{
  var $url;
  var $menu;
  var $prefix;
  var $search;
  var $page;
  var $focus;
  
  function list_picker()
  {
    $this->url = '';
    $this->menu = new menu();
    $this->prefix = $_REQUEST["any"];
    $this->search = rawurldecode($_REQUEST["search"]);
    $this->page = (empty($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
    $this->focus = (empty($_REQUEST["last"]) ? 'KEY_SPC' : $_REQUEST["last"] );
  }
  
  function link_url($item)
  {
    return '';
  }
  
  function data_list( $search_string, $start, $end)
  {
    return array();    
  }
  
  function data_count( $search_string )
  {
    return 0;
  }
  
  function display_nodata()
  {
    echo '';
  }
  
  function display_title()
  {
    return '';
  }
  
  function display_subtitle()
  {
    return '';
  }
  
  function display_format_name( $item )
  {
    return $item;
  }
  
  function display()
  {        
    // Header
    page_header( $this->display_title($this->search), $this->display_subtitle($this->search),'', $this->focus);
    
    // A-Z picker
    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( $this->url.'?any='.$this->prefix.'&search=', $this->search);
    echo '</td><td valign=top>';
    
    // Get data
    $sql_search    = $this->prefix.db_escape_wildcards($this->search).'%';
    $data          = $this->data_list($sql_search, ($this->page*MAX_PER_PAGE), MAX_PER_PAGE);
    $num_rows      = $this->data_count($sql_search);
    
    if ( $num_rows == 0)
    {
      // If there's nothing to display, we might want to output a message or some alternative content
      $this->display_nodata();
    }
    else
    {    
      // There's a previous page, so display a link
      if ($this->page > 0)
        $this->menu->add_up( $this->url.'?last='.MAX_PER_PAGE.'&search='.rawurlencode($this->search).'&any='.$this->prefix.'&page='.($this->page-1));
    
      // There's a next page, so display a link
      if (($this->page+1)*MAX_PER_PAGE < $num_rows)
        $this->menu->add_down( $this->url.'?last=1&search='.rawurlencode($this->search).'&any='.$this->prefix.'&page='.($this->page+1));
    
      foreach ($data as $item)
        $this->menu->add_item($this->display_format_name($item), $this->link_url($item), true);
    
      $this->menu->display( 480 );
    }
    
    echo '</td></tr></table>';
    
    // Footer
    $buttons   = array();
    $buttons[] = array('id'=>'A', 'text'=>str('SEARCH_ANYWHERE'), 'url'=>$this->url.'?search='.rawurlencode($this->search).'&any='.(empty($this->prefix) ? '%' : ''));
    $buttons[] = array('id'=>'B', 'text'=>str('SEARCH_CLEAR'),    'url'=>$this->url.'?any='.$this->prefix);    
    page_footer('music_radio.php', $buttons);
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
