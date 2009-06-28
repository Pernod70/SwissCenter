<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/search.php'));

function show_picker( $url="", $search, $case = '', $validchars = '' )
  {
    if ( strlen($validchars)>0 )
      $keys = trim(strtoupper($validchars));
    elseif ($case == '' || $case == 'U')
      $keys = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_,.'-#";
    else
      $keys = "abcdefghijklmnopqrstuvwxyz0123456789_,.'-#";

    $cols = 6;

    echo '<table cellspacing=2 border=0 '.style_background('KEYBOARD_BACKGROUND').'><tr>';
    $url = url_set_param($url,'last','KEY_SPC');
    $url = url_set_param($url,'search', rawurlencode($search.' '));
    echo '<td colspan=3 height="'.convert_y(50).'" '.style_background('KEYBOARD_KEY_BACKGROUND').'
           align="center"><a href="'.$url.'" style="width:'.convert_x(105).'"name="KEY_SPC">'.font_tags(32).str('KEYBOARD_SPC').'</a></td>';
    $url = url_set_param($url,'last','KEY_DEL');
    $url = url_set_param($url,'search', rawurlencode(substr($search,0,-1)));
    echo '<td colspan=3 height="'.convert_y(50).'" '.style_background('KEYBOARD_KEY_BACKGROUND').'
           align="center"><a href="'.$url.'" style="width:'.convert_x(105).'"name="KEY_DEL">'.font_tags(32).str('KEYBOARD_DEL').'</a></td>';
    echo '</tr><tr>';

    for ($n=1; $n<=strlen($keys); $n++)
    {
      $this_key = substr($keys,($n-1),1);
      $url = url_set_param($url,'last','KEY_'.$this_key);
      $url = url_set_param($url,'search',rawurlencode($search.$this_key));
      $link = '<a href="'.$url.'" name="KEY_'.$this_key.'">'.font_tags(32).$this_key.'</a>';

      echo '<td width="'.convert_x(35).'" height="'.convert_y(50).'"'.style_background('KEYBOARD_KEY_BACKGROUND').'
                align="center">'.$link.'</td>';

      // End of row? start another
      if ($n % $cols == 0)
        echo '</tr><tr>';
    }

    echo '  </tr>
          </table>';

    // Save the history of the A-Z picker.
    search_picker_push( current_url() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
