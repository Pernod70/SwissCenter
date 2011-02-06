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

define('MENU_TYPE_SELECT',1);
define('MENU_TYPE_LIST',2);
define('MENU_TYPE_IMAGE',3);

class menu
{

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------

  var $menu_items;
  var $font_size = FONTSIZE_MENUTEXT;
  var $show_icons = true;

  var $up;
  var $down;
  var $vertical_margin = 40;
  var $page;
  var $page_total;

  var $img_size = array("X"=>210, "Y"=>210);
  var $img_font_size = FONTSIZE_THUMBTEXT;

  var $icon_left_size = array("X"=>25, "Y"=>40);
  var $icon_right_size = array("X"=>25, "Y"=>40);

  var $menu_type = MENU_TYPE_SELECT;

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
   * Set the size of menu icons
   *
   * @param integer $x, $y - Logical size of icons (0-1000)
   */

  function set_icon_left_size ($x, $y)
  {
    $this->icon_left_size = array("X"=>$x, "Y"=>$y);
  }

  function set_icon_right_size ($x, $y)
  {
    $this->icon_right_size = array("X"=>$x, "Y"=>$y);
  }

  function set_menu_type ($type)
  {
    $this->menu_type = $type;
  }

  function set_page ($page, $page_total)
  {
    $this->page = $page;
    $this->page_total = $page_total;
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
    $icon_right = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), $this->icon_right_size["X"], $this->icon_right_size["Y"], false, false, 'RESIZE');
    $icon_left  = img_gen(SC_LOCATION.style_img($icon), $this->icon_left_size["X"], $this->icon_left_size["Y"], false, false, 'RESIZE');

    if (substr($url,0,5) != 'href=')
      $url = 'href="'.$url.'"';

    if (! is_null($text) && strlen($text)>0)
      $this->menu_items[] = array( "text"  => $text
                                 , "url"   => $url
                                 , "right" => ($submenu == true ? $icon_right : '')
                                 , "left"  => (!empty($icon) ? $icon_left     : '') );
  }

  /**
   * Adds an image menu item.
   *
   * @param string $image - Image to display to the user
   * @param string $image_on - Image to display if item is in focus
   * @param string:url $url - URL to go to if the item is selected
   */

  function add_image_item( $text, $image, $image_on, $url="" )
  {
    if (substr($url,0,5) != 'href=')
      $url = 'href="'.$url.'"';

    if (! is_null($image) && strlen($image)>0)
      $this->menu_items[] = array( "text"     => $text
                                 , "image"    => $image
                                 , "image_on" => $image_on
                                 , "url"      => $url );
  }

  /**
   * Adds an table menu item.
   *
   * @param array $data - an array of table data items
   */

  function add_table_item( $data )
  {
    $this->menu_items[] = array( "data" => $data );
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
    $icon_right = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), $this->icon_right_size["X"], $this->icon_right_size["Y"], false, false, 'RESIZE');
    $icon_left  = img_gen(SC_LOCATION.style_img($icon), $this->icon_left_size["X"], $this->icon_left_size["Y"], false, false, 'RESIZE');

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
      return ' background="/thumb.php?src='.rawurlencode(style_img($bg_style,true)).'&x='.(convert_x($width)+6).'&y='.(convert_y($height)+6).'&stretch=Y" ';
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
      if ($cell_pos == 0)
        echo '<td colspan="'.($total_cells).'" align="center" valign="middle" height="'.convert_y($this->vertical_margin).'">'.$link_html.'</td>';
      else
      {
        if ($cell_pos >1)
          echo '<td colspan="'.($cell_pos-1).'"></td>';
        echo '<td align="center" valign="middle" height="'.convert_y($this->vertical_margin).'">'.$link_html.'</td>';
        if ($total_cells > $cell_pos)
          echo '<td colspan="'.($total_cells-$cell_pos).'"></td>';
      }
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

    if ($page > 1)
      $this->add_up( url_add_param(current_url(),'page',($page-1)));

    if ( $max_items > $end)
      $this->add_down( url_add_param(current_url(),'page',($page+1)));

    $this->menu_items = array_slice($this->menu_items,$start, MAX_PER_PAGE);

    switch ( $this->menu_type )
    {
      case MENU_TYPE_SELECT:
        $this->display($tvid, $size, $align);
        break;

      case MENU_TYPE_LIST:
        $this->display_table($tvid, $size, $align);
        break;
    }
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
    $info_width    = 100;
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
        $text = shorten($item["text"], $width_px-80, 1, $this->font_size, true, false);

        // Single fixed background at the moment - may allow it to change based on position in the future.
        $background      = $this->private_background_tags( $tvid, $width, $height);
        $info_background = $this->private_background_tags( $tvid, $info_width, $height);

        // Start row
        echo '<tr>';

        // Left icon?
        if ($left_icons == 1)
          echo '<td align="right" valign="middle" height="'.$height_px.'">'.$item["left"].'</td>';

        // Main text - NMT players support the marquee tag to scroll text
        if ( get_player_model() > 400 && get_sys_pref('DISABLE_MENU_SCROLL','NO') == 'NO')
          echo '<td valign="middle" width="'.$width_px.'" height="'.$height_px.'" '.$background.'>'.$font_open.
                  '&nbsp;&nbsp;&nbsp;'.$tvid.'.&nbsp;
                  <a style="width:'.($width_px-50).'" '.$item["url"].' TVID="'.$tvid.'" name="'.$tvid.'">'.
                   '<marquee behavior="focus" width="'.($width_px-50).'">'.$item["text"].'</marquee>'.
                 '</a></font>
                </td>';
        else
          echo '<td valign="middle" width="'.$width_px.'" height="'.$height_px.'" '.$background.'>'.
                 '<a style="width:'.($width_px-2).'" '.
                   $item["url"].' TVID="'.$tvid.'" name="'.$tvid.'">'.$font_open.'&nbsp;&nbsp;&nbsp;'.$tvid.'. '.$text.'</font>'.
                 '</a>'.
                '</td>';

        // Info columns?
        if ($info_column == 1)
          echo '<td align="center" '.$info_background.' width="'.$info_width_px.'">'.$font_open.$item["info"].'</font></td>';

        // Right icon?
        if ($right_icons == 1)
          echo '<td align="left" valign="middle" height="'.$height_px.'">'.$item["right"].'</td>';

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

  /**
   * Displays the menu with images
   *
   */

  function display_images( $tvid = 1, $size=850, $align="center" )
  {
    $this->img_size["X"] = $size/4;
    $num_cols      = min(4, $this->num_items());
    $num_rows      = ($this->num_items() > 4 ? 2 : 1);
    $cell_width    = floor($size / $num_cols);

    // Display the table containing the image links
    for ($row=0; $row < $num_rows; $row++)
    {
      $max_col_this_row = min( ($this->num_items() - $row*$num_cols), $num_cols);

      echo '<center><table align="'.$align.'" border="0" cellspacing="2" cellpadding="0"><tr>';

      // Image and link
      for ($col=0; $col < $max_col_this_row ; $col++)
      {
        $cell_no = $row*$num_cols+$col;
        $img_src = rawurlencode($this->menu_items[$cell_no]["image"]);
        $img_foc = rawurlencode($this->menu_items[$cell_no]["image_on"]);
        $img_x   = $this->img_size["X"];
        $img_y   = $this->img_size["Y"];

        // Set navigation
        $OnKeyLeftSet = ' OnKeyLeftSet="'.($tvid - 1).'"';
        $OnKeyRightSet = ' OnKeyRightSet="'.($tvid + 1).'"';

        echo '<td align="center" valign="middle" width="'.convert_x($cell_width).'" height="'.convert_y($img_y).'">'
             .'<a TVID="'.$tvid.'" name="'.$tvid.'" '.$this->menu_items[$cell_no]["url"].$OnKeyLeftSet.$OnKeyRightSet.'>'
             .img_gen(array($img_src,$img_foc), $img_x, $img_y).'</a></td>';
        $tvid++;
      }
      echo "</tr><tr>";

      // Text
      for ($col=0; $col < $max_col_this_row ; $col++)
      {
        $cell_no = $row*$num_cols+$col;
        $text    = $this->menu_items[$cell_no]["text"];
        echo '<td align="center" valign="top" width="'.convert_x($cell_width).'">'.font_tags($this->font_size).'<b>'.$text.'</b></font></td>';
      }
      echo "</tr></table></center>";
    }
  }

  /**
   * Displays the menu
   *
   * @param integer $size - (0-1000) Width of the menu on screen.
   * @param string  $align - center, left, right.
   */

  function display_table( $tvid = 1, $size=650, $align="center" )
  {
    $num_cols    = count($this->menu_items[0]["data"]);
    $font_open   = font_tags($this->font_size);

    // Sizes of the menu
    $height        = 40;
    $height_px     = convert_y($height);

    // Start the table definition to contain the menu
    echo '<center><table align="'.$align.'" cellspacing="3" cellpadding="3" border="0">';

    // Link to previous page
    $this->private_nav_cell(0, $num_cols, up_link($this->up));

    // Now process each item in the menu and output the appropriate html.
    if (! empty($this->menu_items))
    {
      foreach ($this->menu_items as $item)
      {
        // Start row
        echo '<tr>';

        foreach ($item["data"] as $data)
          echo '<td valign="middle" height="'.$height_px.'" >'.$font_open.$data.'</font></td>';

        // End row
        echo '</tr>';
      }
    }

    // Link to next page
    $this->private_nav_cell(0, $num_cols, down_link($this->down));

    // End the containing table definition
    echo '</table></center>';
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
