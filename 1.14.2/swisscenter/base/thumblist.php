<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("settings.php");
require_once("utils.php");
require_once("page.php");

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
  
  #-------------------------------------------------------------------------------------------------
  # Constructor
  #-------------------------------------------------------------------------------------------------

  function thumb_list( $control_width = 550)
  {
    $this->control_width = $control_width;
  }

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function add_item( $image, $text, $url )
  {
    if (! is_null($url) && substr($url,0,4) != "href")
      $url = 'href="'.$url.'"';
    
    $cell_width = floor(($this->control_width / $this->n_cols) - (4 * ($this->n_cols - 1)));

    if (! is_null($image) && !is_null($text))
      $this->items[] = array( "img"=>  $image
                            , "txt" => shorten($text,$cell_width, 0.75, 2)
                            , "url" => $url );
  }

  function display()
  {
    $cell_width = floor(($this->control_width / $this->n_cols) - (4 * ($this->n_cols - 1)));
    
    // Display a link to the previous page
    echo '<table border="0" cellspacing="0" cellpadding="0"><tr>
         <td align="center" width="'.$this->control_width.'px" height="10px">';

    if ( !empty($this->up))
      echo up_link($this->up);
    else
      echo '<img src="images/dot.gif">';
    
    echo '</td></tr></table><font size="1"><br></font>';
  
    // Display the table containing the images and textual links
    echo '<table width="'.$this->control_width.'px" border="0" cellspacing="0px" cellpadding="2"><tr>';      

        
    for ($row=0; $row < $this->n_rows; $row++)
    {
      echo "</tr><tr>";
      
      $max_col_this_row = min( (count($this->items) - $row*$this->n_cols), $this->n_cols);
      
      // Thumbnail images
      for ($col=0; $col < $max_col_this_row ; $col++)
      {
        $cell_no = $row*$this->n_cols+$col;
        $img_src = rawurlencode($this->items[$cell_no]["img"]);
        $img_x   = $this->tn_size["X"];
        $img_y   = $this->tn_size["Y"];
        echo '<td valign="middle" height="'.($this->tn_size["Y"]).'" width="'.$cell_width.'px"><center>'
             .($this->titles ? '' : '<a '.$this->items[$cell_no]["url"].'>')
             .img_gen($img_src, $img_x, $img_y)
             .($this->titles ? '' : '</a>')
             .'</center></td>';        
      }
      
      echo "</tr><tr>";

      if ($this->titles) 
      {
        // Text/Link
        for ($col=0; $col < $max_col_this_row ; $col++)
        {
          $cell_no = $row*$this->n_cols+$col;
          echo '<td valign="top" width="'.$cell_width.'px"><center><a name="'.($cell_no + 1).'" '
               .$this->items[$cell_no]["url"].'><font size="1">'.$this->items[$cell_no]["txt"].'</font></a></center></td>';
        }
  
        echo "</tr><tr>";      
      }
    }
    
    echo '</tr></table>';

    // Display a link to the next page
    if ( !empty($this->down))
    {
      echo '<font size="1"><br></font><table border="0" cellspacing="0" cellpadding="0"><tr>
              <td align="center" width="'.$this->control_width.'px" height="10px">'.down_link($this->down).'</td>
              </tr></table>';
    }
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
