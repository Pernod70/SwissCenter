<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  //
  // Shows the result of an SQL statement as a HTML table
  //
  
  function array_to_table( $data, $width = '100%' )
  {  
    if (count($data) !=0)
    { 
      echo '<table class="form_select_tab" width="'.$width.'"><tr>';
  
      foreach ($data[0] as $heading => $value)
        echo '<th>'.ucwords(str_replace('_',' ',strtolower($heading))).'</th>';
  
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
    echo '<table width="100%" border=0 cellpadding=0 cellspacing=8><tr>
          <td width="60" align="left">';
          if ($current_page > 1)
            echo '<a href="'.$url.($current_page-1).'">Previous</a>';      
    echo '&nbsp;</td><td align="center">&nbsp;';
          for ($i=1; $i <= ceil($total_items/$per_page); $i++)
            echo '&nbsp;<a href="'.$url.$i.'">'.$i.'</a>&nbsp;';      
    echo '&nbsp;</td><td width="60" align="right">&nbsp;';
          if ($current_page < ceil($total_items/$per_page))
            echo '<a href="'.$url.($current_page+1).'">Next</a>';      
    echo '</tr></table>';
  }  

  //
  // Outputs a menu item link
  //
  
  function menu_item($text, $params, $background = 'menu_bgr.png')
  {
   echo '<tr><td width="5"></td>
         <td class="menu" background="../images/'.$background.'">
         <a href="?'.$params.'">'.$text.'</a>
         </td></tr>';
  }
 
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
