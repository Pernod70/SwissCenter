<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

class iconbar
{

  // Member variables
  var $icons = array();
  var $bar_width = 400;
  var $bar_height = 30;
  
  var $icon_width = 30;
  var $icon_height = 30;

  // Get/set methods
  function set_bar_width($width)
  {
    $this->bar_width = $width;
  }
  
  function set_bar_height($height)
  {
    $this->bar_height = $height;
  }
  
  function set_icon_width($width)
  {
    $this->icon_width = $width;
  }
  
  function set_icon_height($height)
  {
    $this->icon_heigth = $height;
  }
  
  
  // Constructor
  function iconbar($width = 400, $height = 30)
  {
    $this->bar_width = $width;
    $this->bar_height = $height;
  }
  
  // Methods
  function add_icon($img_url, $link_url = "")
  {
    $this->icons[] = array( "img"=>$img_url, "link"=>$link_url );
  }
  
  function display()
  {
    echo '<table border="0" align="center" width="'.$this->bar_width.'px" height="'.$this->bar_height.'px">';
    echo '<tr>';
    
    foreach($this->icons as $icon)
    {
      $width = $this->icon_width;
      $height = $this->icon_height;
//      image_resized_xy(style_img("ICON_".$icon["img"]), $width, $height);
      
      $height = 32;

      echo '<td align="center" height="'.$height.'"width="'.$bar_width / count($this->icons).'px">';
      
      if(!empty($icon["link"]))
        echo '<a href="'.$icon["link"].'">';

      echo '<img border="0" src="'.style_img("ICON_".$icon["img"]).'" height="'.$height.'" width="'.$width.'">';

      if(!empty($icon["link"]))
        echo '</a>';
        
      echo '</td>';
    }
    
    echo '</tr></table>';
  }
}


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
