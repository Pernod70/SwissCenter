<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

class iconbar
{

  // -------------------------------------------------------------------------------------------------
  // Member variables
  // -------------------------------------------------------------------------------------------------

  var $icons = array();
  var $bar_width = 400;
  
  // -------------------------------------------------------------------------------------------------
  // Get/set methods
  // -------------------------------------------------------------------------------------------------

  function set_bar_width($width)
  {
    $this->bar_width = $width;
  }
    
  // -------------------------------------------------------------------------------------------------
  // Constructor
  // -------------------------------------------------------------------------------------------------

  function iconbar($width = 400)
  {
    $this->bar_width = $width;
  }
  
  // -------------------------------------------------------------------------------------------------
  // Methods
  // -------------------------------------------------------------------------------------------------

  function add_icon($img_name, $text, $link_url = "")
  {
    $filename = style_img($img_name);
    $imagedata  = getimagesize(style_img($img_name,true));
    $this->icons[] = array( "img"=>$filename, "text" => $text, "link"=>$link_url, "width" =>$imagedata[0], "height"=>$imagedata[1] );
  }
  
  function display()
  {
    echo '<table border="0" align="center" width="'.$this->bar_width.'px" >';
    echo '<tr>';
    
    foreach($this->icons as $icon)
    {
      $width  = $this->icon_width;
      $height = $this->icon_height;      

      echo '<td valign=middle align="center" width="'.$this->bar_width / count($this->icons).'px">
            <img align="absmiddle" border="0" src="'.$icon["img"].'" height="'.$icon["height"].'" width="'.$icon["width"].'">&nbsp;';

      if(!empty($icon["link"]))
        echo '<a href="'.$icon["link"].'"><font size="1">'.$icon["text"].'</font></a>';
      else 
        echo '<font size="1">'.$icon["text"].'</font>';

      echo '</td>';
    }
    
    echo '</tr></table>';
  }
}


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
