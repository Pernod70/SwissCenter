<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

ob_start();

require_once("settings.php");

//
// Class for outputting information tables.
//

class infotab
{

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------
  var $items;
  var $cols = array( "1" => array("width"=>"", "align"=>"right")
                   , "2" => array("width"=>"", "align"=>"left"));

  #-------------------------------------------------------------------------------------------------
  # Member Functions
  #-------------------------------------------------------------------------------------------------

  function add_item( $title, $text)
  {
    if (! is_null($text) && ! empty($text))
      $this->items[] = array( "title"=>$title, "text"=>$text );
  }

  function set_col_attrib( $col, $param, $val)
  {
    $this->cols[$col][$param] = $val;
  }

  function display( $trunc=400, $lines=1 )
  {
    $col_opts = array();

    foreach ($this->cols as $coln => $colp)
      foreach ($colp as $pn => $pv)
        $col_opts[$coln] .= " ".$pn.'="'.$pv.'"';

    if (! empty($this->items))
    {
      echo '<center><table cellpadding=0 cellspacing=0 border=0>';
      foreach ($this->items as $item)
      {
        $text = shorten($item["text"],$trunc*$lines);
          
        if (!is_null( $item["text"]))
          echo '<tr><td'.$col_opts[1].'><font color="'.style_col("TITLE_COLOUR").'">'.$item["title"].'</font></td><td width="6"></td><td'.$col_opts[2].'>'.$text.'</td></tr>';
      }
      echo '</table></center>';
    }
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
