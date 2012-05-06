<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

class iconbar
{

  // -------------------------------------------------------------------------------------------------
  // Member variables
  // -------------------------------------------------------------------------------------------------

  var $icons = array();
  var $bar_width = 640;

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

  function iconbar($width = 640)
  {
    $this->bar_width = $width;
  }

  // -------------------------------------------------------------------------------------------------
  // Methods
  // -------------------------------------------------------------------------------------------------

  function add_icon($img_name, $text, $link_url = "")
  {
    $filename = style_img($img_name);
    $this->icons[] = array( "img"=>SC_LOCATION.$filename, "text" => $text, "link"=>$link_url);
  }

  function display()
  {
    echo '<table border="0" align="center" width="'.convert_x($this->bar_width).'" >';
    echo '<tr>';

    foreach($this->icons as $i=>$icon)
    {
      echo '<td valign=middle align="center" width="'.convert_x($this->bar_width / count($this->icons)).'">'
           .img_gen($icon["img"], 100, 65, false, false, 'RESIZE', array("align" => "absmiddle"))
           . '&nbsp;';

      if(!empty($icon["link"]))
        echo '<a href="'.$icon["link"].'"'.tvid('ICON_'.substr('123',$i,1)).'name="'.tvid_code('ICON_'.substr('123',$i,1)).'">'.font_tags(FONTSIZE_ICONBAR).$icon["text"].'</a>';
      else
        echo font_tags(FONTSIZE_ICONBAR).$icon["text"];

      echo '</td>';
    }

    echo '</tr></table>';
  }
}


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
