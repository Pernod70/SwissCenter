<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

ob_start();

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
  var $font_size = 28;

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function menu( $show_icons = true)
  {
    $this->icons = $show_icons;
  }

  function add_item( $text, $url="", $right=false )
  {
    $right = img_gen(SC_LOCATION.style_img("IMG_RIGHT"), 16 , 40);
    if (! is_null($text) && !empty($text))
      $this->menu_items[] = array( "text"=>$text, "url"=>$url,
                                   "right"=> ($right == true ? '</td><td>'.$right : '') );
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

    echo '<center><table cellspacing="3" cellpadding="3" border="0">';

    if ($this->icons )
    {
      if (! empty($this->up))
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'">'.
             up_link($this->up).
             '</td></tr>';
      else
        echo '<tr><td align="center" valign="middle" width="'.convert_x($size).'" height="'.convert_y(40).'"></a></td></tr>';
    }

    if (! empty($this->menu_items))
    {
      foreach ($this->menu_items as $item)
      {
        $text = shorten_chars($item["text"],$size-80,1,$this->font_size);

        $link = $item["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';
          
        if ( style_img_exists("IMG_MENU_".$size) )
          $menu_bg_img = style_img("IMG_MENU_".$size);
        else 
          $menu_bg_img = style_img("IMG_MENU");
          
        $i++;

        echo '<tr><td valign="middle" width="'.convert_x($size).'" height="'.convert_y(50).'" background="'.$menu_bg_img.'">'.
              '<a style="width:'.(convert_x($size)-2).'" '.
              $link.' TVID="'.$i.'" name="'.$i.'">'.font_tags($this->font_size).'&nbsp;&nbsp;&nbsp;'.$i.'. '.$text.'</font></a>'.$item["right"].'</td></tr>';
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
