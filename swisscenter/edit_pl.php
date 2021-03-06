<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  $page          = ( !isset($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
  $highlight     = ( !isset($_REQUEST["hl"]) ? ($page * MAX_PER_PAGE) : $_REQUEST["hl"]);
  $width         = 560;

  $navup_img     = SC_LOCATION.style_img("PAGE_UP");
  $navdown_img   = SC_LOCATION.style_img("PAGE_DOWN");

  $up_img        = SC_LOCATION.style_img("IMG_PLAYLIST_UP");
  $down_img      = SC_LOCATION.style_img("IMG_PLAYLIST_DOWN");
  $del_img       = SC_LOCATION.style_img("IMG_PLAYLIST_DELETE");

  // Clean the current url in the history
  page_hist_current_update(url_remove_params(current_url(), array('up', 'down', 'del')), '');

  //---------------------------------------------------------------------------------------
  // Process any actions passed on the query string
  //---------------------------------------------------------------------------------------

  // Move an Item up
  if ( isset($_REQUEST["up"]) && $_REQUEST["up"] != 0)
  {
    $temp = $_SESSION["playlist"][($_REQUEST["up"]-1)];
    $_SESSION["playlist"][($_REQUEST["up"]-1)] = $_SESSION["playlist"][$_REQUEST["up"]];
    $_SESSION["playlist"][$_REQUEST["up"]] = $temp;
    $page=floor(($_REQUEST["up"]-1)/MAX_PER_PAGE);
    $highlight=($_REQUEST["up"]-1).'u';
  }

  // Move an Item down
  elseif ( isset($_REQUEST["down"]) && $_REQUEST["down"] != count($_SESSION["playlist"]))
  {
    $temp = $_SESSION["playlist"][($_REQUEST["down"]+1)];
    $_SESSION["playlist"][($_REQUEST["down"]+1)] = $_SESSION["playlist"][$_REQUEST["down"]];
    $_SESSION["playlist"][$_REQUEST["down"]] = $temp;
    $page=floor(($_REQUEST["down"]+1)/MAX_PER_PAGE);
    $highlight=($_REQUEST["down"]+1).'d';
  }

  // Delete an item
  elseif ( isset($_REQUEST["del"]))
  {
    array_splice($_SESSION["playlist"],$_REQUEST["del"],1);
    $page=floor(min($_REQUEST["del"],count($_SESSION["playlist"])-1)/MAX_PER_PAGE);
    $highlight=min($_REQUEST["del"],count($_SESSION["playlist"])-1).'x';
  }

  //---------------------------------------------------------------------------------------
  // Output the page
  //---------------------------------------------------------------------------------------

  $items      = $_SESSION["playlist"];
  $num_tracks = count($_SESSION["playlist"]);

  page_header( str('PLAYLIST_EDIT'), $_SESSION["playlist_name"],'', $highlight );

  echo '<center><table cellspacing="3" cellpadding="3" border="0">';

  if ($page > 0)
    echo '<tr><td align="center" valign="middle" width="'.convert_x($width).'" height="'.convert_y(20).'">'
         .up_link('edit_pl.php?page='.($page-1)).'</td></tr>';
  else
    echo '<tr><td align="center" valign="middle" width="'.convert_x($width).'" height="'.convert_y(20).'"></td></tr>';

  $start = $page * MAX_PER_PAGE;
  $end   = min( count($items) , $start+MAX_PER_PAGE);

  for ($i=$start; $i < $end; $i++)
  {
    // Get the link address for playing this file.
    list($media_type, $file_id) = find_media_in_db( $items[$i]["DIRNAME"].$items[$i]["FILENAME"]);
    $play_link     = play_file( $media_type, $file_id);

    $up_link       = 'edit_pl.php?up='.$i.'&hist='.PAGE_HISTORY_REPLACE;
    $down_link     = 'edit_pl.php?down='.$i.'&hist='.PAGE_HISTORY_REPLACE;
    $del_link      = 'edit_pl.php?del='.$i.'&hist='.PAGE_HISTORY_REPLACE;

    $text = $items[$i]["TITLE"];

    echo '<tr>'.
           '<td valign="middle" width="'.convert_x($width).'" height="'.convert_y(40).'">'.
           '  &nbsp;&nbsp;&nbsp;<a '.$play_link.' TVID="'.$i.'" name="'.$i.'">'.font_tags(FONTSIZE_BODY).$text.'</a>'.
           '</td>'.
           '<td valign="middle" width="'.convert_x(40).'" height="'.convert_y(40).'">'.
           '<a href="'.$up_link.'" name="'.$i.'u">'.img_gen($up_img,40,40).'</a>'.
           '</td>'.
           '<td valign="middle" width="'.convert_x(40).'" height="'.convert_y(40).'">'.
           '<a href="'.$down_link.'" name="'.$i.'d">'.img_gen($down_img,40,40).'</a>'.
           '</td>'.
           '<td valign="middle" width="'.convert_x(40).'" height="'.convert_y(40).'">'.
           '<a href="'.$del_link.'" name="'.$i.'x">'.img_gen($del_img,40,40).'</a>'.
           '</td>'.
         '</tr>';
  }

  if (($page+1)*MAX_PER_PAGE < $num_tracks)
    echo '<tr><td align="center" valign="middle" width="'.convert_x($width).'" height="'.convert_y(20).'">'
         .down_link('edit_pl.php?page='.($page+1)).'</td></tr>';

  echo '</table></center>';

  $buttons = array();
  $buttons[] = array('text'=>str('FINISHED'), 'url'=>page_hist_previous());
  page_footer( page_hist_previous(), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
