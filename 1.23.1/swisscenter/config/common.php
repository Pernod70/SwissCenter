<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  /**
   * Displays the $data (an array, usually returned from an SQL query) as
   * a HTML table.
   *
   * @param array $data
   * @param string $table_headings - [Optional] CSV string of headings
   * @param mixed $width - Table width in pixels or as a percentage
   */

  function array_to_table( $data, $table_headings = '', $width = '100%', $heading_h = true, $heading_v = false )
  {
    if (is_array($data) && count($data) !=0)
    {
      echo '<table class="form_select_tab" width="'.$width.'"><tr>';

      if ($table_headings != '' && $heading_h)
        foreach (explode(',',$table_headings) as $value)
          echo '<th>'.ucwords($value).'</th>';

      foreach ($data as $row)
      {
        echo '</tr><tr>';
        $i=0;
        foreach ($row as $cell)
        {
          if ($heading_v && $i++ == 0 )
            echo '<th>'.$cell.'</th>';
          else
            echo '<td>'.$cell.'</td>';
        }
      }

      echo '</tr></table>';
    }
  }

  /**
   * Displays a message to the user with a green background to iondicate
   * success or a red background (if $text begins with a "!") to indicate
   * failure.
   *
   * @param string $text
   */

  function message ($text)
  {
    if (!empty($text))
    {
      if ($text[0] == '!')
        echo '<p class="warning">'.substr($text,1).'</p>';
      else
        echo '<p class="message">'.$text.'</p>';
    }
  }

  /**
   * Displays a series of links to the previous page, next page and all pages
   * inbetween (appending the page number to the specified target URL).
   *
   * @param string $url - target URL
   * @param integer $total_items
   * @param integer $per_page
   * @param integer $current_page
   */

  function paginate( $url, $total_items, $per_page, $current_page)
  {
    $total_pages = ceil($total_items/$per_page);
    $start       = max(0,floor(($current_page-1)/10)*10)+1;
    $end         = min($start+9,$total_pages);

    echo '<table width="100%" border=0 cellpadding=0 cellspacing=8><tr>
          <td width="60" align="left">';
          if ($current_page > 1)
            echo '<a href="'.$url.($current_page-1).'">'.str('PREVIOUS').'</a>';
    echo '&nbsp;</td><td align="center">&nbsp;';
          if ($current_page>10)
            echo ' <a href="'.$url.($start-1).'">&lt;&lt;</a>';
          for ($i=$start; $i <=$end; $i++)
            echo ( $i == $current_page ? '&nbsp;<b>'.$i.'</b>&nbsp;' : '&nbsp;<a href="'.$url.$i.'">'.$i.'</a>&nbsp;');
          if ($start+10 <= $total_pages)
            echo ' <a href="'.$url.($end+1).'">&gt;&gt;</a>';
    echo '&nbsp;</td><td width="60" align="right">&nbsp;';
          if ($current_page < ceil($total_items/$per_page))
            echo '<a href="'.$url.($current_page+1).'">'.str('NEXT').'</a>';
    echo '</tr></table>';
  }

  /**
   * Simple function to output all elements of an array (containing an "ID" field and "NAME" field)
   * as a drop-down or multi-select list.
   *
   * @param array $array
   * @param mixed $selected
   * @return string - HTML for the option list.
   */

  function list_option_elements ($array, $selected = array() )
  {
    $list = '';
    foreach ($array as $row)
      $list .= '<option '.(in_array($row["ID"],$selected) ? ' selected ' : '').'value="'.$row["NAME"].'">'.$row["NAME"].'</option>';

   return $list;
  }

  /**
   * A simple class to create an expanding javascript menu on the configuration screen.
   *
   */

  class config_menu
  {
    var $menus;

    function config_menu()
    { $this->menus = array(); }

    function add_menu( $text )
    { $this->menus[] = array( "title" => $text , "items" => array() ); }

    function add_item( $text, $params )
    { $this->menus[count($this->menus)-1]["items"][] = array( "text" => $text, "params" => $params); }

    function display()
    {
      $menu_id = 0;
      echo '<div style="float: left" id="my_menu" class="sdmenu">';
      foreach ($this->menus as $menu)
      {
        echo '<div class="collapsed"><span>'.$menu["title"].'</span>';

        // Output each option
        foreach ($menu["items"] as $item)
          echo '<a href="?'.$item["params"].'">'.$item["text"].'</a>';

        echo '</div>';
        $menu_id++;
      }
      echo '</div>';
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
