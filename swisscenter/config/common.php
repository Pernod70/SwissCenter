<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  //
  // Shows the result of an SQL statement as a HTML table
  //
  
  function array_to_table( $data, $table_headings = '', $width = '100%' )
  {  
    if (count($data) !=0)
    { 
      echo '<table class="form_select_tab" width="'.$width.'"><tr>';
  
      foreach (explode(',',$table_headings) as $value)
        echo '<th>'.ucwords($value).'</th>';

      foreach ($data as $row)
      {
        echo '</tr><tr>';
        foreach ($row as $cell)
          echo '<td>'.$cell.'</td>';
      }  
  
      echo '</tr></table>';
    }
  }
  
  //
  // Display an error or success message to the user
  //
  
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
  
  //
  // Displays a series of links to the previous page, next page and all pages 
  // inbetween (appending the page number to the specified target URL).
  //
    
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

  //
  // Simple function to output all elements of an array as a drop-down or multi-select list.
  //
  
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
      foreach ($this->menus as $menu)
      {
        $menu_id++;
        echo '<a class="menu" onclick="showHide(\'submenu'.$menu_id.'\');">'.$menu["title"].'</a>
              <div id="submenu'.$menu_id.'" class="hide">';
        
        // Output each option
        foreach ($menu["items"] as $item)
          echo '<a <a href="?menu='.$menu_id.'&'.$item["params"].'" class="submenu">'.$item["text"].'</a>';
        
        echo '</div>';
      }
    }    
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
