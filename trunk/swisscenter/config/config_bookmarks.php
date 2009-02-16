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
                                  cat_name 'Category', cert.name 'Certificate', title, url
                                  from internet_urls iu left outer join certificates cert on cert.cert_id = iu.certificate, categories cat
                                  where iu.cat_id = cat.cat_id order by 2,3,5");

  $url_types = array( array("VAL"=>MEDIA_TYPE_RADIO,       "NAME"=> str('URL_AUDIO') )
                    , array("VAL"=>MEDIA_TYPE_WEB,         "NAME"=> str('URL_WEB') )
                    , array("VAL"=>MEDIA_TYPE_INTERNET_TV, "NAME"=> str('URL_VIDEO') ));

  // Try to determine sensible default values for "Category" and "Certification".
  if (empty($_REQUEST["cat"]))
    $_REQUEST["cat"] = db_value("select cat_id from categories where cat_name='General'");
  if (empty($_REQUEST["cert"]))
    $_REQUEST["cert"] = db_value("select cert_id from certificates where scheme = '".get_rating_scheme_name()."' order by rank limit 1");

  echo "<h1>".str('INTERNET_URLS')."</h1>";
  message($delete);
  form_start('index.php', 150, 'urls');
  form_hidden('section','BOOKMARKS');
  form_hidden('action','MODIFY');

  form_select_table('url_ids', $data, str('URL_TYPE').','.str('CATEGORY').','.str('CERTIFICATE').','.str('URL_TITLE').','.str('URL')
                     ,array('class'=>'form_select_tab','width'=>'100%'), 'id'
                     ,array('TYPE'=>$url_types,'TITLE'=>'20','URL'=>'30',
                            'CATEGORY'=>'select cat_id,cat_name from categories where cat_id not in ('.
                                         implode(',', db_col_to_list('select distinct parent_id from categories')).') order by cat_name',
                            'CERTIFICATE'=>get_cert_list_sql()),$edit_id, 'urls');

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
  form_list_dynamic('cat', str('CATEGORY'),"select cat_id,cat_name from categories where cat_id not in (".
                                            implode(',', db_col_to_list('select distinct parent_id from categories')).")
                                            order by cat_name", $_REQUEST['cat']);
  form_list_dynamic('cert', str('CERTIFICATE'), get_cert_list_sql(), $_REQUEST['cert']);
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
    $cat_id  = $update["CATEGORY"];
    $cert    = $update["CERTIFICATE"];

    send_to_log(4,'Updating internet url',$update);

    if (empty($type))
      bookmarks_display('',"!".str('URL_ERROR_TYPE'));
    elseif (empty($url))
      bookmarks_display('',"!".str('URL_ERROR_URL'));
    elseif (empty($title))
      bookmarks_display('',"!".str('URL_ERROR_TITLE'));
    elseif (empty($cat_id))
      bookmarks_display('',"!".str('URL_ERROR_CAT'));
    elseif (empty($cert))
      bookmarks_display('',"!".str('URL_ERROR_CERT'));
    else
    {
      db_sqlcommand("update internet_urls set type=$type,url='".db_escape_str($url)."',title='$title',cat_id=$cat_id,certificate=$cert where id=$id");
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
  elseif (empty($_REQUEST["cat"]))
    bookmarks_display('',"!".str('URL_ERROR_CAT'));
  elseif (empty($_REQUEST["cert"]))
    bookmarks_display('',"!".str('URL_ERROR_CERT'));
  else
  {
    $new_row = array( 'type'        => $_REQUEST["type"]
                    , 'url'         => $_REQUEST["url"]
                    , 'title'       => $_REQUEST["title"]
                    , 'cat_id'      => $_REQUEST["cat"]
                    , 'certificate' => $_REQUEST["cert"] );

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
