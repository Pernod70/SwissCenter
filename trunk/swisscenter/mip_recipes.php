<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/musicip.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $menu = new menu();
  $page = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);

  // Where to return to?
  $back_url = page_hist_previous();

  // Build menu of options
  $recipes = musicip_recipes();

  $menu->add_item(str('MIP_NONE'), url_remove_param($back_url, 'recipe'));
  foreach ( $recipes as $recipe )
    $menu->add_item($recipe, url_add_param($back_url, 'recipe', urlencode(utf8_decode($recipe))));


  //*************************************************************************************************
  // Display the page
  //*************************************************************************************************

  // Page headings
  page_header(str('MIP_RECIPES'));

  echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
        <tr><td valign=middle width="'.convert_x(290).'" align="center">
            <table width="100%"><tr><td height="'.convert_y(10).'"></td></tr><tr><td valign=top>
              <center>'.img_gen(style_img('MUSICIP',true,false),250,300).'</center>
            </td></tr></table></td>
            <td valign="top">';
            $menu->display_page($page, 1, 480);
  echo '    </td></td></table>';

  // Make sure the "back" button goes to the correct page:
  page_footer($back_url);

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
