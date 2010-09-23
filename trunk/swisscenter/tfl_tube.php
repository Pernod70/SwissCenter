<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/tfl_feeds.php'));

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  $current = isset($_REQUEST["tab"]) ? $_REQUEST["tab"] : 'LINES';
  $tabs    = array('LINES', 'STATIONS');

  $tab_strip = '';
  foreach ($tabs as $key=>$tab)
    $tab_strip .= ($key > 0 ? ' | ' : '').'<a href="tfl_tube.php?tab='.$tab.'">'.
                  ($tab == $current ? font_tags(FONTSIZE_BODY, style_value("PAGE_TITLE_COLOUR",'#FFFFFF')).str($tab) : font_tags(FONTSIZE_BODY).str($tab)).'</font></a>';

  // Page headings
  page_header( str('TFL'), str('TFL_TUBE_THIS_WEEKEND') );

  echo '<center>'.font_tags(FONTSIZE_BODY).$tab_strip.'</center>';

  // Get the feed from Tfl
  $tfl = new Tfl();
  $feed = $tfl->getFeed(7);

  // Display the feed details
  echo '<table width="100%" cellpadding=2 cellspacing=2 border=0>';

  if (isset($_REQUEST["item"]))
  {
    $line = $_REQUEST["item"];
    echo '<tr><td bgcolor="#'.$feed[$current][$line]['BGCOLOUR'].'">'.font_tags(FONTSIZE_BODY).$feed[$current][$line]['NAME'].'</font></td>
              <td bgcolor="#'.$feed[$current][$line]['BGCOLOUR'].'">'.font_tags(FONTSIZE_BODY).$feed[$current][$line]['STATUS'].'</font></td></tr>';
    echo '<tr><td colspan="2">'.font_tags(FONTSIZE_BODY).$feed[$current][$line]['MESSAGE'].'</font></td></tr>';
    $back_url = 'tfl_tube.php?tab='.$current;
  }
  else
  {
    for ($i=0; $i<=count($feed[$current])-1; $i++)
    {
      if ( $feed[$current][$i]['BGCOLOUR'] == 'FFF' ) $feed[$current][$i]['BGCOLOUR'] = '0';
      echo '<tr><td bgcolor="#'.$feed[$current][$i]['BGCOLOUR'].'">'.font_tags(FONTSIZE_BODY).$feed[$current][$i]['NAME'].'</font><a href="tfl_tube.php?tab='.$current.'&item='.$i.'"</a></td>
                <td bgcolor="#'.$feed[$current][$i]['BGCOLOUR'].'">'.font_tags(FONTSIZE_BODY).$feed[$current][$i]['STATUS'].'</font></td></tr>';
    }
    $back_url = 'tfl.php';
  }
  echo '</table>';

  // Make sure the "back" button goes to the correct page
  page_footer($back_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
