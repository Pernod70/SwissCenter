<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/utils.php");
  require_once("base/playlist.php");

  $page          = ( !isset($_REQUEST["page"]) ? 0 : $_REQUEST["page"]);
  $highlight     = ( !isset($_REQUEST["hl"]) ? '1' : $_REQUEST["hl"]);
  $width         = 340;
  $trunc         = 40;

  $back_img      = style_img("IMG_MENU");
  $navup_img     = style_img("IMG_PGUP");
  $navdown_img   = style_img("IMG_PGDN");
  $up_img        = style_img("IMG_PLAYLIST_UP");
  $down_img      = style_img("IMG_PLAYLIST_DOWN");
  $del_img       = style_img("IMG_PLAYLIST_DELETE");
  
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
  if ( isset($_REQUEST["down"]) && $_REQUEST["down"] != count($_SESSION["playlist"]))
  {
    $temp = $_SESSION["playlist"][($_REQUEST["down"]+1)];
    $_SESSION["playlist"][($_REQUEST["down"]+1)] = $_SESSION["playlist"][$_REQUEST["down"]];
    $_SESSION["playlist"][$_REQUEST["down"]] = $temp;
    $page=floor(($_REQUEST["down"]+1)/MAX_PER_PAGE);
    $highlight=($_REQUEST["down"]+1).'d';
  }
  
  // Delete an item
  if ( isset($_REQUEST["del"]))
  {
    array_splice($_SESSION["playlist"],$_REQUEST["del"],1);
    $page=floor(min($_REQUEST["del"],count($_SESSION["playlist"])-1)/MAX_PER_PAGE);
    $highlight=min($_REQUEST["del"],count($_SESSION["playlist"])-1).'x';
  }

  //---------------------------------------------------------------------------------------
  // Output the page
  //---------------------------------------------------------------------------------------

  $items         = $_SESSION["playlist"];
  $num_tracks    = count($_SESSION["playlist"]);

  page_header( "Edit Playlist", $_SESSION["playlist_name"], $highlight );
  
  echo '<center><table cellspacing="3" cellpadding="3" border="0">';

  if ($page > 0)
    echo '<tr><td align="center" valign="middle" width="'.$width.'px" height="10px">'.
         '<a href="edit_pl.php?page='.($page-1).'" TVID="PGUP" ONFOCUSLOAD><img border=0 src="'.$navup_img.'"></a></td></tr>';
  else
    echo '<tr><td align="center" valign="middle" width="'.$width.'px" height="10px"></a></td></tr>';

  $start = $page * MAX_PER_PAGE;
  $end   = min( count($items) , $start+MAX_PER_PAGE);

  for ($i=$start; $i < $end; $i++)
  {
    $play_link     = pl_link('file',$items[$i]["DIRNAME"].$items[$i]["FILENAME"]);
    $up_link       = 'edit_pl.php?up='.$i;
    $down_link     = 'edit_pl.php?down='.$i;
    $del_link      = 'edit_pl.php?del='.$i;
    
    $text = shorten( $items[$i]["TITLE"], $trunc );

    echo '<tr>'.
           '<td valign="middle" width="'.$width.'px" height="25px" background="'.$back_img.'">'.
           '  &nbsp;&nbsp;&nbsp;<a '.$play_link.' TVID="'.$i.'" name="'.$i.'">'.$text.'</a>'.
           '</td>'.
           '<td valign="middle" width="15px" height="20px" background="'.$back_img.'">'.
           '  <a href="'.$up_link.'" name="'.$i.'u"><img border=0 src="'.$up_img.'"></a>'.
           '</td>'.
           '<td valign="middle" width="15px" height="20px" background="'.$back_img.'">'.
           '  <a href="'.$down_link.'" name="'.$i.'d"><img border=0 src="'.$down_img.'"></a>'.
           '</td>'.
           '<td valign="middle" width="15px" height="20px" background="'.$back_img.'">'.
           '  <a href="'.$del_link.'" name="'.$i.'x"><img border=0 src="'.$del_img.'"></a>'.
           '</td>'.
         '</tr>';
  }

  if (($page+1)*MAX_PER_PAGE < $num_tracks)
    echo '<tr><td align="center" valign="middle" width="'.$width.'px" height="10px">'.
         '<a href="edit_pl.php?page='.($page+1).'" TVID="PGDN" ONFOCUSLOAD><img border=0 src="'.$navdown_img.'"></a></td></tr>';

  echo '</table></center>';

  $buttons[] = array('text'=>'Finished', 'url'=>'manage_pl.php');
  page_footer( 'manage_pl.php', $buttons );


/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
