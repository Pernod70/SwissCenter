<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

ob_start();

require_once("settings.php");
require_once("utils.php");

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
    if (! is_null($text) && !empty($text))
      $this->menu_items[] = array( "text"=>$text, "url"=>$url,
                                   "right"=> ($right == true ? '<img src="'.style_img("IMG_RIGHT").'">' : '') );
  }

  function add_up( $url )
  {
    $this->up = $url;
  }

  function add_down( $url )
  {
    $this->down = $url;
  }

   function display( $size=500 )
  {
    $i=0;
    $link="";

    echo '<center><table cellspacing="2" cellpadding="2" border="0">';
    
    if (! empty($this->menu_items))
    {
      if (!empty($this->up))
	{
	  $up = $this->up;
	   echo '<td></td><td><a href="'.$up.'" TVID="PGUP" ONFOCUSLOAD></a></td><td></td></tr>';
	  }

      foreach ($this->menu_items as $item)
      {
        $text = shorten_chars($item["text"],$size - 0);

        $link = $item["url"];
        if (substr($link,0,5) != 'href=')
          $link = 'href="'.$link.'"';
          
        if ( style_img_exists("IMG_MENU_".$size) )
          $menu_bg_img = style_img("IMG_MENU_".$size);
        else 
          $menu_bg_img = style_img("IMG_MENU");
          
        $i++;

        echo '<tr><td>'.$item["right"].'</td><td valign="middle" width="'.$size.'px" height="18px" background="'.$menu_bg_img.'"><font size="2">'.'<a style="width:'.($size-2).'" '.$link.' TVID="'.$i.'" name="'.$i.'">&nbsp;&nbsp;&nbsp;'.$i.'. '.$text.'</a>'.'</font></td>';

	if ($this->icons )
	    {
	      if (!empty($this->up) && $i==1)

		echo '<td>'.up_link($this->up).'</td></tr>';
	      else
		if (!empty($this->down) && $i==MAX_PER_PAGE )
    		  {
        	    echo '<td>'.down_link($this->down).'</td></tr>';
    		  }
		else
        	    echo '<td></a></td></tr>';
	     }
        }

	if (!empty($this->down) )
	  {
	   $down = $this->down;
	   echo '<td></td><td valign="middle" width="'.$size.'px"><a href="'.$down.'" TVID="PGDN" ONFOCUSLOAD><font size="-5">&nbsp;&nbsp;&nbsp;</font></a></td><td></td></tr>';
	  }
    }
 
    echo '</table></center>';
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
