<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/
  
// ----------------------------------------------------------------------------------
// Display currently defined URLS
// ----------------------------------------------------------------------------------

function bookmarks_display($delete = '', $new = '', $edit_id = 0)
{ 
  $data = db_toarray("select id, (CASE type
                                  WHEN 4 THEN '".str('URL_AUDIO')."'
                                  WHEN 5 THEN '".str('URL_WEB')."'
                                  WHEN 7 THEN '".str('URL_VIDEO')."'
                                  ELSE 'Unknown' 
                                  END) type,
                                  title, url
                                  from internet_urls order by 2,3");

  $url_types = array( array("VAL"=>MEDIA_TYPE_RADIO,       "NAME"=> str('URL_AUDIO') )
                    , array("VAL"=>MEDIA_TYPE_WEB,         "NAME"=> str('URL_WEB') )
                    , array("VAL"=>MEDIA_TYPE_INTERNET_TV, "NAME"=> str('URL_VIDEO') ));

  echo "<h1>".str('INTERNET_URLS')."</h1>";
  message($delete);
  form_start('index.php', 150, 'urls');
  form_hidden('section','BOOKMARKS');
  form_hidden('action','MODIFY');

  form_select_table('url_ids', $data, str('URL_TYPE').','.str('URL_TITLE').','.str('URL')
                     ,array('class'=>'form_select_tab','width'=>'100%'), 'id'
                     ,array('TYPE'=>$url_types,'TITLE'=>'','URL'=>''), $edit_id, 'urls');

  if (!$edit_id)
    form_submit(str('URL_DEL_BUTTON'),1,'center');
  form_end();

  echo '<p><h1>'.str('URL_ADD_TITLE').'<p>';
  message($new);
  form_start('index.php');
  form_hidden('section','BOOKMARKS');
  form_hidden('action','NEW');
  form_list_static('type', str('URL_TYPE'),array( str('URL_AUDIO')=>MEDIA_TYPE_RADIO, str('URL_WEB')=>MEDIA_TYPE_WEB, str('URL_VIDEO')=>MEDIA_TYPE_INTERNET_TV), $_REQUEST['type']);
  form_input('title', str('URL_TITLE'),50,'',un_magic_quote($_REQUEST['title']));
  form_input('url', str('URL'),50,'',un_magic_quote($_REQUEST['url']));
  form_submit(str('URL_ADD_BUTTON'),2);
  form_end();
}

// ----------------------------------------------------------------------------------
// Delete an existing URL
// ----------------------------------------------------------------------------------

function bookmarks_modify()
{
  $selected = form_select_table_vals('url_ids');           // Get the selected items
  $edit     = form_select_table_edit('url_ids', 'urls');   // Get the id of the edited row
  $update   = form_select_table_update('url_ids', 'urls'); // Get the updates from an edit

  if(!empty($edit))
  {
    // There was an edit, display the urls with the table in edit mode on the selected row
    bookmarks_display('', '', $edit);
  }
  elseif(!empty($update))
  {
    // Update the row given in the database and redisplay the feeds
    $id      = $update["URL_IDS"];
    $type    = $update["TYPE"];
    $url     = $update["URL"];
    $title   = $update["TITLE"];

    send_to_log(4,'Updating internet url',$update);

    if (empty($type))
      bookmarks_display('',"!".str('URL_ERROR_TYPE'));
    elseif (empty($url))
      bookmarks_display('',"!".str('URL_ERROR_URL'));
    elseif (empty($title))
      bookmarks_display('',"!".str('URL_ERROR_TITLE'));
    else
    {
      db_sqlcommand("update internet_urls set type=$type,url='".db_escape_str($url)."',title='$title' where id=$id");
      bookmarks_display(str('URL_UPDATE_OK')); 
    }
  }
  elseif (!empty($selected))
  {
    // Delete the selected urls
    foreach ($selected as $id)
      db_sqlcommand("delete from internet_urls where id=".$id);

    bookmarks_display(str('URL_DEL_OK'));
  }
  else
    bookmarks_display();
}

// ----------------------------------------------------------------------------------
// Add a new URL
// ----------------------------------------------------------------------------------

function bookmarks_new()
{
  // Process the url passed in
  $url = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["url"])),'/');

  if (empty($_REQUEST["type"]))
    bookmarks_display('',"!".str('URL_ERROR_TYPE'));
  elseif (empty($_REQUEST["url"]))
    bookmarks_display('',"!".str('URL_ERROR_URL'));
  elseif (empty($_REQUEST["title"]))
    bookmarks_display('',"!".str('URL_ERROR_TITLE'));
  else
  {
    $new_row = array( 'type'  => $_REQUEST["type"]
                    , 'url'   => $_REQUEST["url"]
                    , 'title' => $_REQUEST["title"]);

    if ( db_insert_row('internet_urls', $new_row) === false)
      bookmarks_display(db_error());
    else
      bookmarks_display('',str('URL_ADD_OK'));
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
