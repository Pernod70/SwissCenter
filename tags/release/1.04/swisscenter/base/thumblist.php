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

  var $n_cols = 4;
  var $n_rows = 2;
  var $max_title_length = 23;
  var $tn_size = array("X"=>80, "Y"=>80);

  #-------------------------------------------------------------------------------------------------
  # Get/Set attributes
  #-------------------------------------------------------------------------------------------------

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

  function set_title_length ( $n_chars )
  {
    $this->max_title_length = $n_chars;
  }
  
  function set_thumbnail_size ($x = 80, $y = 80)
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
    
    if (! is_null($image) && !is_null($text))
      $this->items[] = array( "img"=>  $image
                            , "txt" => shorten($text,$this->max_title_length)
                            , "url" => $url );
  }

  function display()
  {
    // Display a link to the previous page
    if ( !empty($this->up))
    {
      echo '<table border="0" cellspacing="0" cellpadding="0"><tr>
              <td align="center" width="'.$this->control_width.'px" height="10px">'.up_link($this->up).'</td>
              </tr></table>';
    }
  
    // Display the table containing the images and textual links
    echo '<table width="'.$this->control_width.'px" border="0" cellspacing="8" cellpadding="0"><tr>';      

        
    for ($row=0; $row < $this->n_rows; $row++)
    {
      echo "</tr><tr>"; 
      
      $max_col_this_row = min( (count($this->items) - $row*$this->n_cols), $this->n_cols);
      
      // Thumbnail images
      for ($col=0; $col < $max_col_this_row ; $col++)
      {
        $cell_no = $row*$this->n_cols+$col;
        echo '<td valign="top" height="'.($this->tn_size["Y"]).'" width="'.floor(100/$this->n_cols).'%"><center>'.img_gen($this->items[$cell_no]["img"],$this->tn_size["X"],$this->tn_size["Y"]).'</center></td>';        
      }
      
      echo "</tr><tr>";

      // Text/Link
      for ($col=0; $col < $max_col_this_row ; $col++)
      {
        $cell_no = $row*$this->n_cols+$col;
        echo '<td valign="top" width="'.floor(100/$this->n_cols).'%"><center><a '.$this->items[$cell_no]["url"].'><font size="1">'.$this->items[$cell_no]["txt"].'</font></a></center></td>';
      }

      echo "</tr><tr>";      
    }
    
    echo '</tr></table>';

    // Display a link to the next page
    if ( !empty($this->down))
    {
      echo '<table border="0" cellspacing="0" cellpadding="0"><tr>
              <td align="center" width="'.$this->control_width.'px" height="10px">'.down_link($this->down).'</td>
              </tr></table>';
    }
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
