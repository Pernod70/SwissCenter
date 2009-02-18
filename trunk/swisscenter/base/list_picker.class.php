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
  var $back_url;

  function list_picker()
  {
    $this->url = '';
    $this->back_url = '';
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

  function icon($item)
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

  function data_valid_chars( $search_string )
  {
    return '';
  }

  function display_nodata( $search_string)
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
    // Get data
    $sql_search    = $this->prefix.db_escape_wildcards($this->search).'%';
    $data          = $this->data_list($sql_search, ($this->page*MAX_PER_PAGE), MAX_PER_PAGE);
    $num_rows      = $this->data_count($sql_search);

    // Header
    page_header( $this->display_title($this->search), $this->display_subtitle($this->search),'', $this->focus, false,'','PAGE_KEYBOARD');

    // A-Z picker
    echo '<table border=0 height="320px" width="100%"><tr><td width="200px" valign="top">';
    show_picker( url_add_params($this->url,array("any"=>$this->prefix,"search"=>''))
               , $this->search
               , ''
               , ( empty($this->prefix) ? $this->data_valid_chars($sql_search) : '' )
               );
    echo '</td><td valign=top>';

    if ( $num_rows == 0)
    {
      // If there's nothing to display, we might want to output a message or some alternative content
      $this->display_nodata($sql_search);
    }
    else
    {
      // Display links for previous and next pages
      $last_page  = ceil($num_rows/MAX_PER_PAGE)-1;
      if ($num_rows > MAX_PER_PAGE)
      {
        $this->menu->add_up( url_add_params($this->url, array("last"=>MAX_PER_PAGE, "search"=>rawurlencode($this->search),'any'=>$this->prefix, 'page'=>($this->page > 0 ? ($this->page-1) : $last_page)) ));
        $this->menu->add_down( url_add_params($this->url, array("last"=>1, "search"=>rawurlencode($this->search),'any'=>$this->prefix, 'page'=>($this->page < $last_page ? ($this->page+1) : 0)) ));
      }

      foreach ($data as $item)
        $this->menu->add_item($this->display_format_name($item), $this->link_url($item), true, $this->icon($item));

      $this->menu->display( 1, 480 );
    }

    echo '</td></tr></table>';

    // Footer
    $buttons   = array();
    $buttons[] = array('id'=>'A', 'text'=>str('SEARCH_ANYWHERE'), 'url'=>url_add_params($this->url, array('search'=>rawurlencode($this->search), 'any'=>(empty($this->prefix) ? '%' : ''))));
    $buttons[] = array('id'=>'B', 'text'=>str('SEARCH_CLEAR'),    'url'=>url_add_param($this->url, 'any', $this->prefix));
    page_footer( $this->back_url , $buttons);
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
