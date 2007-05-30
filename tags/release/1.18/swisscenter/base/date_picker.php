<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

 // Fairly rudimentary at the moment... but will be changed later to display the range of dates
 // as a calendar that the user can navigate (defaults to year=any, month=any and day=any, but allows
 // the user to refine each value)
 
 function show_date_picker( $url="", $search, $case = '' )
  {
    if ($case == '' || $case == 'U')
      $keys = "0123456789:";
    else
      $keys = "0123456789:";
    
    $cols = 6;

    echo '<table><tr>
                 <td colspan=3 height="'.convert_y(60).'" align="center"><a href="'.
                     $url.rawurlencode($search." ").'&last=KEY_SPC" name="KEY_SPC">SPACE</a></td>
                 <td colspan=3 height="'.convert_y(60).'" align="center"><a href="'.
                     $url.rawurlencode(substr($search,0,-1)).'&last=KEY_DEL" name="KEY_DEL">DELETE</a></td>
                 </tr>
                 <tr>';

    for ($n=1; $n<=strlen($keys); $n++)
    {
      $this_key = substr($keys,($n-1),1);
      echo '<td width="'.convert_x(40).'" height="'.convert_y(60).'" align="center"><a href="'.
            $url.rawurlencode($search.$this_key).'&last=KEY_'.$this_key.'" name="KEY_'.$this_key.'">'.$this_key.'</a></td>';

      // End of row? start another
      if ($n % $cols == 0)
        echo '</tr><tr>';
    }

    echo '</tr></table>';
    
    // Save the history of the A-Z picker.
    $_SESSION["last_picker"][count($_SESSION["history"])] = current_url();
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>