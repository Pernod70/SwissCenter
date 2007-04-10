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
  var $stream = 0;

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function menu( $show_icons = true)
  {
    $this->icons = $show_icons;
  }

  function add_item( $text, $url="", $right = false )
  {
    $icon = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), 16 , 40, false, false, 'RESIZE');
    
    if (! is_null($text) && !empty($text))
      $this->menu_items[] = array( "text"=>$text
                                 , "url"=>$url
                                 , "right"=> ($right == true ? '</td><td>'.$icon : '') );
  }

  function add_streamitem( $text, $url, $info, $right = false )
  {
    $icon = img_gen(SC_LOCATION.style_img("MENU_RIGHT"), 16 , 40, false, false, 'RESIZE');
    
    if (! is_null($text) && !empty($text))
      $this->menu_items[] = array( "text"=>$text
                                 , "info"=>$info
                                 , "url"=>$url
                                 , "right"=> ($right == true ? '</td><td>'.$icon : '') );
  }

  function add_up( $url )
  {
    $this->up = $url;
  }

  function add_down( $url )
  {
    $this->down = $url;
  }

  function display( $size=650 )
  {
    $i=0;
    $link="";
    if ($this->stream)
    {
#      $tdinfo = "</td><td>&nbsp;";
      $tdinfo = "</td><td>";
    } else {
      $tdinfo = "";
      $info   = "";
    }

    echo '<center><table cellspacing="3" cellpadding="3" border="0">';

    if ($this->icons )
    {
      if (! empty($this->up))
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'">'.
             up_link($this->up).
             $tdinfo.'</td></tr>';
      else
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'"></a>'.$tdinfo.'</td></tr>';
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

        if ($this->stream)
        {
          $info = "</td><td align='right'".style_background('MENU_BACKGROUND').">".font_tags($this->font_size).$info;
        }
          
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
