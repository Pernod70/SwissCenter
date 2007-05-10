<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/settings.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

//
// Class for outputting menus.
//

class menu
{

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------
  var $menu_items;
  var $up;
  var $down;
  var $icons = true;
  var $font_size = 30;
  var $info_column = false;
  var $padding = 40;

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function menu( $show_icons = true)
  {
    $this->icons = $show_icons;
  }
  
  function padding( $val )
  {
    $this->padding = $val;
  }

  function add_item( $text, $url="", $right = false, $left = false )
  {
    $icon = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), 16 , 40, false, false, 'RESIZE');
    $icon_left = img_gen(SC_LOCATION.style_img("MENU_LEFT"), 16 , 40, false, false, 'RESIZE');
    
    if (substr($url,0,5) != 'href=')
      $url = 'href="'.$url.'"';
    
    if (! is_null($text) && strlen($text)>0)
      $this->menu_items[] = array( "text"=>$text
                                 , "url"=>$url
                                 , "right"=> ($right == true ? '</td><td>'.$icon : '')
				 , "left"=> ($left == true ? '<td>'.$icon_left.'</td>' : '') );
  }

  function add_info_item( $text, $url, $info, $right = false, $left = false )
  {
    $icon = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), 16 , 40, false, false, 'RESIZE');
    $icon_left = img_gen(SC_LOCATION.style_img("MENU_LEFT"), 16 , 40, false, false, 'RESIZE');
    
    $this->info_column = true;
    
    if (substr($url,0,5) != 'href=')
      $url = 'href="'.$url.'"';

    if (! is_null($text) && !empty($text))
      $this->menu_items[] = array( "text"=>$text
                                 , "info"=>$info
                                 , "url"=>$url
                                 , "right"=> ($right == true ? '</td><td>'.$icon : '') 
				 , "left"=> ($left == true ? '<td>'.$icon_left.'</td>' : '') );
  }

  function add_up( $url )
  {
    $this->up = $url;
  }

  function add_down( $url )
  {
    $this->down = $url;
  }

  function num_items()
  {
    return count($this->menu_items);
  }
  
  function item_url( $item=0 )
  {
    $url = $this->menu_items[$item]["url"];
    if (substr($url,0,5) == 'href=')
      return substr($url,6,strlen($url)-7);
    else
      return $url;
  }
  
  function display( $size=650 )
  {
    $i        = 0;
    $width    = convert_x($size);
    $height   = convert_y(40);
    $bg_style = 'MENU_BACKGROUND';
    $tdinfo = "";
    $info   = "";
    
    if ( style_is_colour($bg_style))
      $background = ' bgcolor="'.style_value($bg_style).'" ';
    elseif ( style_is_image($bg_style))
      $background = ' background="/thumb.php?src='.rawurlencode(style_img($bg_style,true)).'&x='.($width+6).'&y='.($height+9).'&stretch=Y" ';
    else 
      $background = '';

    if ($this->info_column)
      $tdinfo = "</td><td>";

    echo '<center><table cellspacing="3" cellpadding="3" border="0">';

    if ($this->icons )
    {
      if (! empty($this->up))
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'">'.
             up_link($this->up).
             $tdinfo.'</td></tr>';
      else
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y($this->padding).'"></a>'.$tdinfo.'</td></tr>';
    }

    if (! empty($this->menu_items))
    {
      foreach ($this->menu_items as $item)
      {
        $text = shorten_chars($item["text"],$size-80,1,$this->font_size);
        $info = shorten_chars($item["info"],$size-80,1,$this->font_size);

        $link = $item["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';

        if ($this->info_column)
          $info = "</td><td align='right'".style_background('MENU_BACKGROUND').">".font_tags($this->font_size).$info;
          
        $i++;

        echo '<tr><td valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'" '.style_background('MENU_BACKGROUND').'>'.'<a style="width:'.(convert_x($size)-2).'" '.
              $link.' TVID="'.$i.'" name="'.$i.'">'.font_tags($this->font_size).'&nbsp;&nbsp;&nbsp;'.$i.'. '.$text.'</font></a>'.$item["right"].$info.'</td></tr>';
      }
    }

    if ($this->icons && !empty($this->down))
    {
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'">'.
             down_link($this->down).
             '</td></tr>';
    }

    echo '</table></center>';
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
