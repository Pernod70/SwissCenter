<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/media.php'));

  function media_search()
  {
    // Store the parameters to the media search (catgory, media tyoe) in the system_prefs table
    // as this is the only way of passing the info to the background process in Simese.
    set_sys_pref('MEDIA_SCAN_MEDIA_TYPE',$_REQUEST["type"]);
    set_sys_pref('MEDIA_SCAN_CATEGORY',$_REQUEST["cat"]);
    set_sys_pref('MEDIA_SCAN_STATUS','Pending');
 
    // Call the media search in the background.    
    media_refresh_now();
    
    // Inform the user that it is happening.
    echo "<h1>".str('SETUP_SEARCH_NEW_MEDIA')."</h1>";
    message(str('REFRESH_DATABASE_PROMPT'));
    echo '<p>'.str('REFRESH_RUNNING');
  }

  function media_refresh()
  {  
    echo '<h1>'.str('SETUP_SEARCH_NEW_MEDIA').'</h1>
          <p>'.str('MEDIA_SEARCH_PROMPT').'<br>&nbsp;';

    form_start('index.php', 150, 'conn');
    form_hidden('section', 'MEDIA');
    form_hidden('action', 'SEARCH');
    form_list_dynamic('type',str('MEDIA_TYPE'),"select media_id,media_name from media_types where media_id<>".MEDIA_TYPE_RADIO." order by 2",$_REQUEST['type'],true,false,'All Media Types');
    echo '<tr><td></td><td> &nbsp; &nbsp; '.str('OR').'</td></tr>';
    form_list_dynamic('cat', str('CATEGORY'),"select cat_id,cat_name from categories order by cat_name", $_REQUEST['cat'],true,false,'All Categories');
    echo '<tr><td></td><td>&nbsp;</td></tr>';    
    form_submit(str('SETUP_SEARCH_NEW_MEDIA'),2);
    form_end();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
