<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  // Get the file details from the database and add to the session playlist
  if ( build_pl(un_magic_quote($_REQUEST["sql"])) === false)
    page_error(str('DATABASE_ERROR'));
  else
  {
    page_header(str('TRACKS_ADDED_TITLE'));

    echo font_tags(FONTSIZE_BODY).str('TRACKS_ADDED_TEXT'
            ,'<font color="'.style_value("PAGE_TEXT_BOLD_COLOUR".'#FFFFFF').'">'.str('HOME').'</font>'
            ,'<font color="'.style_value("PAGE_TEXT_BOLD_COLOUR",'#FFFFFF').'">"'.str('MANAGE_PLAYLISTS').'"</font>');

    $menu = new menu();
    $menu->add_item(str('MANAGE_PLAYLISTS'),'manage_pl.php');
    $menu->add_item(str('RETURN_TO_SELECTION'), page_hist_previous());
    $menu->display();
    page_footer( page_hist_previous() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
