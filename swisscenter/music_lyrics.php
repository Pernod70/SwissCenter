<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/resources/lyrics/metrolyrics.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  // Get artist and track details
  $artist = $_REQUEST["artist"];
  $track  = $_REQUEST["track"];

  // Search for lyrics
  $metro  = new MetroLyrics();
  $lyrics = $metro->getLyrics($artist, $track);

  // Check for no lyrics available
  if ( $lyrics === false )
  {
    page_inform(2, page_hist_previous(), $artist.' - '.$track,str('NO_LYRICS_AVAILABLE'));
  }
  else
  {
    // Display the lyrics
    page_header( $artist.' - '.$track );

    if (is_pc())
      echo '<div style="height:'.convert_y(750).'; overflow:scroll;">';

    echo '<table '.style_background('PAGE_TEXT_BACKGROUND').' width="100%" cellpadding="5" cellspacing="0" border="0" align="center">';
    echo '<tr><td><center>'.font_tags(FONTSIZE_BODY).$lyrics.'</font><center></td></tr>';
    echo '</table>';

    if (is_pc())
      echo '</div>';

    page_footer(page_hist_previous());
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
