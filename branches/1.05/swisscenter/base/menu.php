<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

ob_start();

require_once("settings.php");
require_once("utils.php");
require_once("page.php");

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

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function menu( $show_icons = true)
  {
    $this->icons = $show_icons;
  }

  function add_item( $text, $url="", $right=false )
  {
    if (! is_null($text))
      $this->menu_items[] = array( "text"=>$text, "url"=>$url,
                                   "right"=> ($right == true ? '</td><td><img src="'.style_img("IMG_RIGHT").'">' : '') );
  }

  function add_up( $url )
  {
    $this->up = $url;
  }

  function add_down( $url )
  {
    $this->down = $url;
  }

  function display( $size=400 )
  {
    $i=0;
    $link="";

    echo '<center><table cellspacing="3" cellpadding="3" border="0">';

    if ($this->icons )
    {
      if (! empty($this->up))
        echo '<tr><td align="center" valign="middle" width="'.$size.'px" height="10px">'.
             up_link($this->up).
             '</td></tr>';
      else
        echo '<tr><td align="center" valign="middle" width="'.$size.'px" height="10px"></a></td></tr>';
    }

    if (! empty($this->menu_items))
    {
      foreach ($this->menu_items as $item)
      {
        $text = shorten($item["text"],$size - 50);

        $link = $item["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';

        echo '<tr><td valign="middle" width="'.$size.'px" height="25px" background="'.
              style_img("IMG_MENU").'">&nbsp;&nbsp;&nbsp;'.
              '<font color="'.style_col("TITLE_COLOUR").'">'.++$i.'.</font> <a '.$link.' TVID="'.$i.
              '" name="'.$i.'">'.$text.'</a>'.$item["right"].'</td></tr>';
      }
    }

    if ($this->icons && !empty($this->down))
    {
        echo '<tr><td align="center" valign="middle" width="'.$size.'px" height="10px">'.
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
