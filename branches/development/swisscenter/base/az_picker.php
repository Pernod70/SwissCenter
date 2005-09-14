<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  function show_picker( $url="", $search, $case = '' )
  {
    if ($case == '' || $case == 'U')
      $keys = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_,.'-#";
    else
      $keys = "abcdefghijklmnopqrstuvwxyz0123456789_,.'-#";

    $cols = 6;

    echo '<table><tr>
                 <td colspan=3 height="30px" align="center"><a href="'.
                     $url.rawurlencode($search." ").'&last=KEY_SPC" name="KEY_SPC">SPACE</a></td>
                 <td colspan=3 height="30px" align="center"><a href="'.
                     $url.rawurlencode(substr($search,0,-1)).'&last=KEY_DEL" name="KEY_DEL">DELETE</a></td>
                 </tr>
                 <tr>';

    for ($n=1; $n<=strlen($keys); $n++)
    {
      $this_key = substr($keys,($n-1),1);
      $url = url_set_param($url,'last','KEY_'.$this_key);
      $url = url_set_param($url,'search',$search.$this_key);
      echo '<td width="25px" height="30px" align="center"><a href="'.$url.'" name="KEY_'.$this_key.'">'.$this_key.'</a></td>';

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