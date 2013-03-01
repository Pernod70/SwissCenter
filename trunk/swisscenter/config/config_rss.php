<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/rss.php'));

// ----------------------------------------------------------------------------------
// Display currently defined RSS feeds
// ----------------------------------------------------------------------------------

function rss_display($delete = '', $new = '', $edit_id = 0)
{
  $data = db_toarray("select id, (CASE type
                                  WHEN 1 THEN '".str('RSS_AUDIO')."'
                                  WHEN 2 THEN '".str('RSS_IMAGE')."'
                                  WHEN 3 THEN '".str('RSS_VIDEO')."'
                                  ELSE 'Unknown'
                                  END) type,
                                  title, url, cache
                                  from rss_subscriptions rss, categories cat
                                  where rss.cat_id = cat.cat_id order by 2,3");

  $rss_types = array( array("VAL"=>1,"NAME"=> str('RSS_AUDIO') )
                    , array("VAL"=>2,"NAME"=> str('RSS_IMAGE') )
                    , array("VAL"=>3,"NAME"=> str('RSS_VIDEO') ));

  echo "<h1>".str('RSS_FEEDS')."</h1>";
  message($delete);
  form_start('index.php', 150, 'feeds');
  form_hidden('section','RSS');
  form_hidden('action','MODIFY');

  form_select_table('rss_ids', $data, str('RSS_TYPE').','.str('RSS_TITLE').','.str('RSS_URL').','.str('RSS_CACHE')
                     ,array('class'=>'form_select_tab','width'=>'100%'), 'id'
                     ,array('TYPE'=>$rss_types,'TITLE'=>'','URL'=>'','CACHE'=>''), $edit_id, 'feeds');

  if (!$edit_id)
    form_submit(str('RSS_DEL_BUTTON'),1,'center');
  form_end();

  echo '<p><h1>'.str('RSS_ADD_TITLE').'<p>';
  message($new);
  form_start('index.php');
  form_hidden('section','RSS');
  form_hidden('action','NEW');
  form_list_static('type', str('RSS_TYPE'),array( str('RSS_AUDIO')=>1,str('RSS_IMAGE')=>2,str('RSS_VIDEO')=>3),$_REQUEST['type']);
  form_input('title', str('RSS_TITLE'),50,'',$_REQUEST['title']);
  form_input('url', str('RSS_URL'),50,'',$_REQUEST['url']);
  form_input('cache', str('RSS_CACHE'),5,'',$_REQUEST['cache']);
  form_label(str('RSS_CACHE_LABEL'));
  form_submit(str('RSS_ADD_BUTTON'),2);
  form_end();
}

// ----------------------------------------------------------------------------------
// Delete an existing RSS feed
// ----------------------------------------------------------------------------------

function rss_modify()
{
  $selected = form_select_table_vals('rss_ids');            // Get the selected items
  $edit     = form_select_table_edit('rss_ids', 'feeds');   // Get the id of the edited row
  $update   = form_select_table_update('rss_ids', 'feeds'); // Get the updates from an edit

  if(!empty($edit))
  {
    // There was an edit, display the feeds with the table in edit mode on the selected row
    rss_display('', '', $edit);
  }
  elseif(!empty($update))
  {
    // Update the row given in the database and redisplay the feeds
    $id      = $update["RSS_IDS"];
    $type    = $update["TYPE"];
    $url     = $update["URL"];
    $title   = $update["TITLE"];
    $cache   = $update["CACHE"];

    send_to_log(4,'Updating rss feed',$update);

    if (empty($type))
      rss_display('',"!".str('RSS_ERROR_TYPE'));
    elseif (empty($url))
      rss_display('',"!".str('RSS_ERROR_URL'));
    elseif (empty($title))
      rss_display('',"!".str('RSS_ERROR_TITLE'));
    elseif (!is_numeric($cache))
      rss_display('',"!".str('RSS_ERROR_CACHE'));
    else
    {
      db_sqlcommand("update rss_subscriptions set type=$type,url='".db_escape_str($url)."',title='$title',cache=$cache where id=$id");
      rss_display(str('RSS_UPDATE_OK'));
    }
  }
  elseif(!empty($selected))
  {
    // Delete the selected rss feeds

    foreach ($selected as $id)
    {
      // Delete any linked files from the cache
      $cache_items = rss_get_subscription_items($id);
      for ($i = 0; $i < (count($cache_items) ); $i++)
      {
        $linked_file = db_value("select linked_file from rss_items where id=".$cache_items[$i]['ID']);
        send_to_log(5,'Deleting cached linked file: '.$linked_file);
        if (!empty($linked_file)) unlink($linked_file);
      }
      // Delete subscription and related items
      db_sqlcommand("delete from rss_items where subscription_id=".$id);
      db_sqlcommand("delete from rss_subscriptions where id=".$id);
    }

    rss_display(str('RSS_DEL_OK'));
  }
  else
    rss_display();
}

// ----------------------------------------------------------------------------------
// Add a new RSS feed
// ----------------------------------------------------------------------------------

function rss_new()
{
  // Process the rss feed passed in
  $url = rtrim(str_replace('\\','/',$_REQUEST["url"]),'/');

  if (empty($_REQUEST["type"]))
    rss_display('',"!".str('RSS_ERROR_TYPE'));
  elseif (empty($_REQUEST["url"]))
    rss_display('',"!".str('RSS_ERROR_URL'));
  elseif (empty($_REQUEST["title"]))
    rss_display('',"!".str('RSS_ERROR_TITLE'));
  elseif (!is_numeric($_REQUEST["cache"]))
    rss_display('',"!".str('RSS_ERROR_CACHE'));
  else
  {
    $new_row = array( 'type'             => $_REQUEST["type"]
                    , 'url'              => $_REQUEST["url"]
                    , 'title'            => $_REQUEST["title"]
                    , 'cache'            => $_REQUEST["cache"]
                    , 'update_frequency' => 0);

    if ( db_insert_row('rss_subscriptions', $new_row) === false)
      rss_display(db_error());
    else
      rss_display('',str('RSS_ADD_OK'));
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
