<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

/**
 * Class for creating and displaying menus
 *
 */

class menu
{

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------

  var $menu_items;
  var $font_size = 30;
  var $show_icons = true;

  var $up;
  var $down;
  var $vertical_margin = 40;
  
  /**
   * Constructor
   *
   * @param boolean $show_icons
   * @return menu
   */

  function menu( $show_icons = true)
  {
    $this->show_icons = $show_icons;
  }
  
  /**
   * Set the value of the padding above the menu
   *
   * @param integer $val - Logical coordinates (0-1000)
   */
  
  function set_vertical_margins( $val )
  {
    $this->vertical_margin = $val;
  }

  /**
   * Adds a menu item with an optional icon to the left, and indicator to show if there is a submenu
   * or not.
   *
   * @param string $text - Text to display to the user
   * @param string:url $url - URL to go to if the item is selected
   * @param boolean $submenu - Indicate that the user will be taken to a submenu
   * @param string $icon - (Style identifier) Icon to display on the left.
   */
  
  function add_item( $text, $url="", $submenu = false, $icon = '' )
  {
    $icon_right = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), 16 , 40, false, false, 'RESIZE');
    $icon_left  = img_gen(SC_LOCATION.style_img($icon), 16 , 40, false, false, 'RESIZE');
    
    if (substr($url,0,5) != 'href=')
      $url = 'href="'.$url.'"';
    
    if (! is_null($text) && strlen($text)>0)
      $this->menu_items[] = array( "text"  => $text
                                 , "url"   => $url
                                 , "right" => ($submenu == true ? $icon_right : '')
				                         , "left"  => (!empty($icon) ? $icon_left     : '') );
  }
  
  /**
   * Adds a two-part (info) menu item with an optional icon to the left, and indicator to show if 
   * there is a submenu or not.
   *
   * @param string $text - Text to display to the user
   * @param string $info - info text to display in the second column
   * @param string:url $url - URL to go to if the item is selected
   * @param boolean $submenu - Indicate that the user will be taken to a submenu
   * @param string $icon - (Style identifier) Icon to display on the left.
   */

  function add_info_item( $text, $info, $url, $submenu = false, $icon = false )
  {
    $icon_right = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), 16 , 40, false, false, 'RESIZE');
    $icon_left  = img_gen(SC_LOCATION.style_img($icon), 16 , 40, false, false, 'RESIZE');
    
    if (substr($url,0,5) != 'href=')
      $url = 'href="'.$url.'"';

    if (! is_null($text) && !empty($text))
      $this->menu_items[] = array( "text"  => $text
                                 , "info"  => $info
                                 , "url"   => $url
                                 , "right" => ($submenu == true ? $icon_right : '')
				                         , "left"  => (!empty($icon) ? $icon_left     : '') );
  }

  function add_up( $url )
  {
    $this->up = $url;
  }

  function add_down( $url )
  {
    $this->down = $url;
  }
  
  /**
   * Returns the number of items in the menu.
   *
   * @return integer
   */

  function num_items()
  {
    return count($this->menu_items);
  }
  
  /**
   * Returns the URL of the item at position $position.
   *
   * @param integer $position
   * @return string:url unknown
   */
  
  function item_url( $position = 0 )
  {
    $url = $this->menu_items[$position]["url"];
    return substr($url,6,strlen($url)-7);
  }
  
  /**
   * Private function. Returns the HTML code to set the background, depending upon the value stored in the style.
   *
   * @param unknown_type $position
   * @param unknown_type $width
   * @param unknown_type $height
   * @return unknown
   */
  
  function private_background_tags( $position, $width, $height )
  {
    $bg_style = 'MENU_BACKGROUND';

    if ( style_is_colour($bg_style))
      return ' bgcolor="'.style_value($bg_style).'" ';
    elseif ( style_is_image($bg_style))
      return  ' background="/thumb.php?src='.rawurlencode(style_img($bg_style,true)).'&x='.(convert_x($width)+6).'&y='.(convert_y($height)+6).'&stretch=Y" ';
    else 
      return '';
  }
  
  /**
   * Private function. Outputs a navigation row (next/previous arrows) for the table.
   *
   * @param unknown_type $link_html
   */
  
  function private_nav_cell( $cell_pos, $total_cells, $link_html )
  {
    if ($this->show_icons)
    {
      echo '<tr>';
      if ($cell_pos >1)
        echo '<td colspan="'.($cell_pos-1).'"></td>';      
      echo '<td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y($this->vertical_margin).'">'.$link_html.'</td>';
      if ($total_cells > $cell_pos)
        echo '<td colspan="'.($total_cells-$cell_pos).'"></td>';      
      echo '</tr>';      
    }
  }
  
  /**
   * Displays the given page of menu items (used for simplicity when building an array of menu items
   * does not slow down the application)
   *
   * @param integer $page
   * @param integer $size
   */
  
  function display_page( $page=1, $tvid=1, $size=650, $align="center" )
  {
    $start      = ($page-1) * MAX_PER_PAGE; 
    $max_items  = count($this->menu_items);
    $end        = min($start+MAX_PER_PAGE,$max_items);
    $last_page  = ceil($max_items/MAX_PER_PAGE);

    if ($max_items > MAX_PER_PAGE)
    {
      $this->add_up( url_add_param(current_url(),'page',($page > 1 ? ($page-1) : $last_page)) );
      $this->add_down( url_add_param(current_url(),'page',($page < $last_page ? ($page+1) : 1)) );
    }

    $this->menu_items = array_slice($this->menu_items,$start, MAX_PER_PAGE);
    $this->display($tvid, $size, $align);    
  }
  
  /**
   * Displays the menu
   *
   * @param integer $size - (0-1000) Width of the menu on screen.
   * @param string  $align - center, left, right.
   */
  
  function display( $tvid = 1, $size=650, $align="center" )
  {
    $num_cols    = 1;
    $font_open   = font_tags($this->font_size);

    // Work out how many columns we will be displaying as there could be two text columns and two icon columns.
    $left_icons  = 0;
    $right_icons = 0;
    $info_column = 0;
    
    if($this->menu_items != null)
    {
      foreach ($this->menu_items as $item)
      {
        if ( isset($item["left"]) )  $left_icons  = 1;
        if ( isset($item["right"]) ) $right_icons = 1;
        if ( isset($item["info"]) )  $info_column = 1;
      }
    }    
    
    $num_cols = 1 + $left_icons + $right_icons + $info_column;

    // Sizes of the menu (taking into consideration the left, right and info columns)
    $info_width    = 75;
    $info_width_px = convert_x($info_width);
    $width         = $size - ($info_column == 1 ? $info_width : 0);
    $width_px      = convert_x($width);
    $height        = 40;
    $height_px     = convert_y($height);

    
    // Start the table definition to contain the menu
    echo '<center><table align="'.$align.'" cellspacing="3" cellpadding="3" border="0">';

    // Link to previous page
    $this->private_nav_cell($left_icons+1, $num_cols, up_link($this->up));    
    
    // Now process each item in the menu and output the appropriate html.
    if (! empty($this->menu_items))
    {
      foreach ($this->menu_items as $item)
      {
        $text = shorten($item["text"], $width-80, 1, $this->font_size, true, false);
        
        // Single fixed background at the moment - may allow it to change based on position in the future.
        $background      = $this->private_background_tags( $tvid, $width, $height);
        $info_background = $this->private_background_tags( $tvid, $info_width, $height);
                
        // Start row  
        echo '<tr>';
        
        // Left icon?
        if ($left_icons == 1)
          echo '<td align="right" valign="middle" height="'.$height_px.'">'.$item["left"].'</td>';
        
        // Main text  
        echo '<td valign="middle" width="'.$width_px.'" height="'.$height_px.'" '.$background.'>'.
               '<a style="width:'.($width_px-2).'" '.
                 $item["url"].' TVID="'.$tvid.'" name="'.$tvid.'">'.$font_open.'&nbsp;&nbsp;&nbsp;'.$tvid.'. '.$text.'</font>'.
               '</a>'.
              '</td>';
        
        // Info columns?
        if ($info_column == 1)
          echo '<td align="right" '.$info_background.' width="'.$info_width_px.'">'.$font_open.$item["info"].'</font></td>';
              
        // Right icon?
        if ($right_icons == 1)
          echo '<td align="right" valign="middle" height="'.$height_px.'">'.$item["right"].'</td>';
          
        // End row
        echo '</tr>';
        $tvid++;
      }
    }

    // Link to next page
    $this->private_nav_cell($left_icons+1, $num_cols, down_link($this->down));    

    // End the containing table definition
    echo '</table></center>';
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
