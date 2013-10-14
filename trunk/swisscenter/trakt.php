<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

/**************************************************************************************************
   Main page output
 **************************************************************************************************/

  page_header( str('TRAKT'), str('RECOMMENDATIONS') );
  $buttons = array();

  echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr>
            <td valign=top width="'.convert_x(280).'" align="left"><br>
              '.img_gen(style_img('TRAKT',true,false),280,450).'
            </td>
            <td width="'.convert_x(20).'"></td>
            <td valign="top">';

  $menu = new menu();
  $menu->add_item(str('MOVIES'),   'trakt_browse.php?type=movies', true);
  $menu->add_item(str('TV_SHOWS'), 'trakt_browse.php?type=shows', true);
  $menu->display(1, 520);

  // Output ABC buttons
  $buttons = array();
  $buttons[] = array('text' => str('SEARCH').' '.str('MOVIES'), 'url' => url_add_param('trakt_search.php', 'type', 'movies'));
  $buttons[] = array('text' => str('SEARCH').' '.str('TV_SHOWS'), 'url' => url_add_param('trakt_search.php', 'type', 'shows'));

  echo '    </td>
          </tr>
        </table>';

  // Make sure the "back" button goes to the correct page
  page_footer(page_hist_previous(), $buttons);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
