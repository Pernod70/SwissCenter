<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

//
// Class for outputting menus.
//

class thumb_list
{

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------
  var $items;
  var $up;
  var $down;
  var $control_width;
  var $titles = true;

  var $n_cols = 4;
  var $n_rows = 2;
  var $tn_size = array("X"=>THUMBNAIL_X_SIZE, "Y"=>THUMBNAIL_Y_SIZE);
  var $tn_font_size = FONTSIZE_THUMBTEXT;

  #-------------------------------------------------------------------------------------------------
  # Get/Set attributes
  #-------------------------------------------------------------------------------------------------

  function set_titles_on()
  {
    $this->titles = true;
  }

  function set_titles_off()
  {
    $this->titles = false;
  }

  function set_num_rows ($number)
  {
    $this->n_rows = $number;
  }

  function set_num_cols ($number)
  {
    $this->n_cols = $number;
  }

  function set_up( $url )
  {
    $this->up = $url;
  }

  function set_down( $url )
  {
    $this->down = $url;
  }

  function set_thumbnail_size ($x, $y)
  {
    $this->tn_size = array("X"=>$x,"Y"=>$y);
  }

  function set_font_size ($size)
  {
    $this->tn_font_size = $size;
  }

  #-------------------------------------------------------------------------------------------------
  # Constructor
  #-------------------------------------------------------------------------------------------------

  function thumb_list( $control_width = 850)
  {
    $this->control_width = $control_width;
  }

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function add_item( $image, $text, $url, $highlight = '' )
  {
    if (! is_null($url) && substr($url,0,4) != "href")
      $url = 'href="'.$url.'"';

    if (! is_null($image) && !is_null($text))
      $this->items[] = array( "img"=>  $image
                            , "txt" => $text
                            , "url" => $url
                            , "highlight" => $highlight);
  }

  function display()
  {
    $cell_width = floor(($this->control_width / $this->n_cols) );

    // Display a link to the previous page
    echo '<center><table border="0" cellspacing="0" cellpadding="0"><tr>
         <td align="center" width="'.convert_x($this->control_width).'" height="'.convert_y(20).'">';

    if ( !empty($this->up))
      echo up_link($this->up);
    else
      echo '<img src="images/dot.gif">';

    echo '</td></tr></table><font size="1"><br></font>';

    // Display the table containing the images and textual links
    echo '<table width="'.convert_x($this->control_width).'" border="0" cellspacing="0" cellpadding="2">';

    for ($row=0; $row < $this->n_rows; $row++)
    {
      echo '<tr>';

      $max_col_this_row = min( (count($this->items) - $row*$this->n_cols), $this->n_cols);

      // Thumbnail images
      for ($col=0; $col < $max_col_this_row ; $col++)
      {
        $cell_no = $row*$this->n_cols+$col+1;
        $img_src = $this->items[$cell_no-1]["img"];
        $img_x   = $this->tn_size["X"];
        $img_y   = $this->tn_size["Y"];

        // Set navigation
        $OnKeyUpSet = ($row == 0) ? ' OnKeyUpSet="up"' : ' OnKeyUpSet="'.($cell_no - $this->n_cols).'"';
        $OnKeyDownSet = ($row == $this->n_rows-1) ? ' OnKeyDownSet="down"' : ' OnKeyDownSet="'.($cell_no + $this->n_cols).'"';
        $OnKeyLeftSet = ($row == 0 && $col == 0) ? ' OnKeyLeftSet="up" ' : ' OnKeyLeftSet="'.($cell_no-1).'"';
        $OnKeyRightSet = ($row == $this->n_rows-1 && $col == $max_col_this_row-1) ? ' OnKeyRightSet="down"' : ' OnKeyRightSet="'.($cell_no+1).'"';

        echo '<td valign="middle" width="'.convert_x($cell_width).'" height="'.convert_y($this->tn_size["Y"]).'"><center>'
             .($this->titles ? '' : '<a name="'.($cell_no).'" '.$this->items[$cell_no-1]["url"].$OnKeyUpSet.$OnKeyDownSet.$OnKeyLeftSet.$OnKeyRightSet.'>')
             .img_gen($img_src, $img_x, $img_y)
             .($this->titles ? '' : '</a>')
             .'</center></td>';
      }
      echo '</tr><tr>';

      if ($this->titles)
      {
        // Text/Link
        for ($col=0; $col < $max_col_this_row ; $col++)
        {
          $cell_no = $row*$this->n_cols+$col+1;
          $text    = shorten($this->items[$cell_no-1]["txt"], convert_x($cell_width)*2, 1, $this->tn_font_size);

          // highlight this thumbnail?
          if (!empty($this->items[$cell_no-1]["highlight"]))
            $text = "<b>$text</b>";

          // Set navigation
          $OnKeyUpSet = ($row == 0) ? ' OnKeyUpSet="up"' : ' OnKeyUpSet="'.($cell_no - $this->n_cols).'"';
          $OnKeyDownSet = ($row == $this->n_rows-1) ? ' OnKeyDownSet="down"' : ' OnKeyDownSet="'.($cell_no + $this->n_cols).'"';
          $OnKeyLeftSet = ($row == 0 && $col == 0) ? ' OnKeyLeftSet="up" ' : ' OnKeyLeftSet="'.($cell_no-1).'"';
          $OnKeyRightSet = ($row == $this->n_rows-1 && $col == $max_col_this_row-1) ? ' OnKeyRightSet="down"' : ' OnKeyRightSet="'.($cell_no+1).'"';

          echo '<td valign="top" width="'.convert_x($cell_width).'"><center><a name="'.($cell_no).'" '
               .$this->items[$cell_no-1]["url"].$OnKeyUpSet.$OnKeyDownSet.$OnKeyLeftSet.$OnKeyRightSet.'>'.font_tags($this->tn_font_size).$text.'</font></a></center></td>';
        }
        echo '</tr>';
      }
      else
      {
        // Spacing row
        if ($row < $this->n_rows-1)
          echo '<td height="'.convert_y(20).'"></tr>';
      }
    }

    echo '</table><font size="1"><br></font>';

    // Display a link to the next page
    if ( !empty($this->down))
    {
      echo '<table border="0" cellspacing="0" cellpadding="0"><tr>
              <td align="center" width="'.convert_x($this->control_width).'" height="'.convert_y(20).'">'
             .down_link($this->down).'</td></tr></table>';
    }

    echo '</center>';
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
