<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/base/page.php'));
 require_once( realpath(dirname(__FILE__).'/base/browse.php'));
 require_once( realpath(dirname(__FILE__).'/resources/video/shoutcasttv.php'));

/**
 * Displays a menu of genres and allows you to choose one.
 *
 * @param array $directory
 */

function display_shoutcast_tv_genres($genres)
{
  $current_url = url_remove_param(current_url(), 'page');
  $select_url = url_remove_param($current_url, 'filter');

  foreach ($genres as $name=>$count)
    $array[] = array("name"=>$name.' ('.$count.')', "url"=>url_set_param( $select_url, 'genre', rawurlencode($name) ));

  $page = (isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0);

  browse_array($current_url,$array,$page,MEDIA_TYPE_INTERNET_TV);
}

/**************************************************************************************************
   Main code
 *************************************************************************************************/

$current_url = url_remove_param(current_url(), 'filter');

$shoutcast = new SHOUTcastTV();

if ( isset($_REQUEST["menu"]) )
{
  // Set subtitle for current menu
  switch ($_REQUEST["menu"])
  {
    case 'genres':            { $subtitle = str('BROWSE_GENRE'); break; }
    case 'studios':           { $subtitle = str('BROWSE_STUDIO'); break; }
  }
}
else
{
  $subtitle = '';
}

page_header( str('SHOUTCAST_TV'), $subtitle );
$buttons = array();

echo '<table width="100%" cellpadding=0 cellspacing=0 border=0>
        <tr>
          <td valign=top width="'.convert_x(280).'" align="left"><br>
            '.img_gen(style_img('SHOUTCAST_TV',true,false),280,450).'
          </td>
          <td width="'.convert_x(20).'"></td>
          <td valign="top">';

if ( isset($_REQUEST["menu"]) && $_REQUEST["menu"] == 'genres' )
{
  $back_url = apple_trailer_page_params();
  display_apple_trailer_menu( get_apple_trailers_genres() );
}


if (isset($_REQUEST["filter"]) && $_REQUEST["filter"] == 'genre')
{
  // Select genre
  $genres = $shoutcast->getGenres();
  display_shoutcast_tv_genres($genres);
  $back_url = $current_url;
}
else
{
  // Select browse channels or select a filter
  $menu = new menu();
  $menu->add_item(str('BROWSE_TITLE'), url_add_param('shoutcast_tv_search.php', 'sort', 'name'));
  $menu->add_item(str('BROWSE_RATING'), url_add_param('shoutcast_tv_search.php', 'sort', 'viewers'));
  $menu->add_item(str('BROWSE_GENRE'), url_add_param($current_url,'filter','genre') );
  $menu->display(1, 520);
  $back_url = 'internet_tv.php';
}

page_footer( $back_url );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
