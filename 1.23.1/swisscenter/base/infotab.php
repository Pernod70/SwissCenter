<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/settings.php'));

//
// Class for outputting information tables.
//

class infotab
{

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------
  var $items;
  var $font_size = FONTSIZE_BODY;
  var $cols = array( "1" => array("width"=>"", "align"=>"right")
                   , "2" => array("width"=>"49%", "align"=>"left"));

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

  function display( $trunc=640, $lines=1)
  {
    $col_opts = array();

    foreach ($this->cols as $coln => $colp)
    {
      $col_opts[$coln] = '';
      foreach ($colp as $pn => $pv)
        $col_opts[$coln] .= " ".$pn.'="'.$pv.'"';
    }

    if (! empty($this->items))
    {
      echo '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
      foreach ($this->items as $item)
      {
        $text = shorten($item["text"],$trunc*$lines);

        if (!is_null( $item["text"]))
        {
          echo '<tr>'.newline();
          echo '<td'.$col_opts[1].'>'.font_tags($this->font_size, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).$item["title"].'</td>'.newline();
          echo '<td width="2%"></td>'.newline();
          echo '<td'.$col_opts[2].'>'.font_tags($this->font_size).$text.'</td>'.newline();
          echo '</tr>'.newline();
        }
      }
      echo '</table>';
    }
  }

}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
