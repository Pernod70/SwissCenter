<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));
  require_once( realpath(dirname(__FILE__).'/base/rss.php'));

  //*************************************************************************************************
  // Build page elements
  //*************************************************************************************************

  $sub_id   = $_REQUEST["sub_id"];
  $item_id  = $_REQUEST["item_id"];
  $sub_data = rss_get_subscription_details($sub_id);
  $items    = rss_get_subscription_items($sub_id, 'desc');

  // Find index of current, next and previous items
  $idx_next = -1;
  $idx_prev = -1;
  foreach ($items as $idx=>$item)
  {
    if ($item['ID'] == $item_id)
    {
      $idx_current = $idx;
      if ($idx_current > 0) $idx_next = $idx - 1;
      if ($idx_current < (count($items)-1)) $idx_prev = $idx + 1;
      break;
    }
  }

  page_header( $sub_data["TITLE"], $items[$idx_current]["TITLE"].' - '.date('Y-m-d H:i',strtotime($items[$idx_current]["PUBLISHED_DATE"])), '',get_tvid_pref( get_player_type(), 'KEY_A' ) );

  // Show the channel image for audio and video feeds.
  if ($sub_data["TYPE"]==MEDIA_TYPE_MUSIC || $sub_data["TYPE"]==MEDIA_TYPE_VIDEO)
  {
    if (!empty($sub_data["IMAGE"]) )
      $img = "select image from rss_subscriptions where id=$sub_id.sql";
    else
      $img = style_img('MISSING_RSS_ART',true,false);

    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(280).'" align="left">
              '.img_gen($img,280,450).'
              </td><td width="'.convert_x(20).'"></td>
              <td valign="top">';
              echo font_tags(FONTSIZE_BODY).$items[$idx_current]["DESCRIPTION"];
    echo '    </td></table>';
  }
  else
    echo font_tags(FONTSIZE_BODY).$items[$idx_current]["DESCRIPTION"];

  // Show links to next and previous items.
  echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>';
  if ($idx_next > -1)
  {
    echo '<tr><td align="center">'.up_link(url_add_params('rss_item_selected.php',array('item_id'=>$items[$idx_next]["ID"],
                                                                                         'sub_id'=>$sub_id,
                                                                                         'hist'=>PAGE_HISTORY_REPLACE)), false).'</td></tr>';
    echo '<tr><td align="center">'.font_tags(FONTSIZE_BODY).font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('NEXT').': ').$items[$idx_next]["TITLE"].'</td></tr>';
  }
  if ($idx_prev > -1)
  {
    echo '<tr><td align="center">'.font_tags(FONTSIZE_BODY).font_colour_tags('PAGE_TEXT_BOLD_COLOUR',str('PREVIOUS').': ').$items[$idx_prev]["TITLE"].'</td></tr>';
    echo '<tr><td align="center">'.down_link(url_add_params('rss_item_selected.php',array('item_id'=>$items[$idx_prev]["ID"],
                                                                                           'sub_id'=>$sub_id,
                                                                                           'hist'=>PAGE_HISTORY_REPLACE)), false).'</td></tr>';
  }
  echo '</table>';

  // Define buttons for linked file and url.
  $buttons = array();
  if (!empty($items[$idx_current]["LINKED_FILE"]))
  {
    $dirname = db_escape_str(dirname($items[$idx_current]["LINKED_FILE"]).'/');
    $filename = db_escape_str(basename($items[$idx_current]["LINKED_FILE"]));
    $image = db_value("select image_url from rss_subscriptions where id=$sub_id");
    if (empty($image)) $image=style_img('MISSING_RSS_ART',true,false);
    // Construct query used for Now Playing screen details.
    $sql = "select '$dirname' DIRNAME, '$filename' FILENAME, rs.title ALBUM, ri.title TITLE, ri.published_date YEAR, '$image' ALBUMART from rss_items ri, rss_subscriptions rs where rs.id=ri.subscription_id and ri.id=$item_id";
    $buttons[] = array('text'=>str('RSS_PLAY_LINK'), 'url'=> play_sql_list($sub_data["TYPE"], $sql) );
  }
  if (!empty($items[$idx_current]["URL"]))
  {
    $buttons[] = array('text'=>str('RSS_ITEM_URL'), 'url'=> $items[$idx_current]["URL"]);
  }

  page_footer( page_hist_previous(), $buttons );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
