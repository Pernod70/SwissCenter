<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/media.php'));

  /**
   * Displays a screen showing the progress of the search for new media
   *
   */

  function media_progress()
  {
    $status  = get_sys_pref('MEDIA_SCAN_STATUS');
    $scan_type = $_REQUEST["type"];

    // Inform the user that it is happening.
    echo '<h1>'.str('SETUP_SEARCH_NEW_MEDIA').'</h1>'.
         '<p>'.str('REFRESH_RUNNING').'<p>';
    
    echo '<h1>'.str('MEDIA_SCAN_PROGRESS').'</h1><p><center>';

    // Show a table of current progress.
    switch ($scan_type)
    {
      case "MEDIA":
        $sql = "select mt.media_name, c.cat_name, ml.name, ml.percent_scanned
                  from media_locations ml, categories c, media_types mt
                 where ml.media_type = mt.media_id
                   and ml.cat_id = c.cat_id
                   and mt.media_table<>'' order by 1,2,3";
    
        $headings = str('MEDIA_TYPE').','.str('CATEGORY').','.str('LOCATION').','.str('PERCENT_SCANNED');
        array_to_table( db_toarray($sql), $headings,'95%');             

        echo '<p><table width="50%" class="form_select_tab"><tr><th width="60%">'.str('MEDIA_SCAN_STATUS').'</th><td align="right">'.$status.'</td>';

        // Overall status 
        $overall = db_value("select avg(percent_scanned) from media_locations");
        break;
      case "RSS":
        $sql = "select (CASE type
                        WHEN 1 THEN '".str('RSS_AUDIO')."'
                        WHEN 2 THEN '".str('RSS_IMAGE')."'
                        WHEN 3 THEN '".str('RSS_VIDEO')."'
                        ELSE 'Unknown' 
                        END) type, title, percent_scanned
                   from rss_subscriptions order by 1,2";
    
        $headings = str('RSS_TYPE').','.str('RSS_TITLE').','.str('PERCENT_SCANNED');
        array_to_table( db_toarray($sql), $headings,'95%');             

        echo '<p><table width="50%" class="form_select_tab"><tr><th width="60%">'.str('MEDIA_SCAN_STATUS').'</th><td align="right">'.$status.'</td>';

        // Overall status 
        $overall = db_value("select avg(percent_scanned) from rss_subscriptions");
        break;
    }
    
    if ($status == str('MEDIA_SCAN_STATUS_RUNNING'))
      echo '<tr><th>'.str('MEDIA_SCAN_OVERALL').'</th><td align="right">'.(int)$overall.'%</td>';
    
    echo '</table></center>';
    
    // Refresh the page to update the progress if the scan is still running
    if ($status != str('MEDIA_SCAN_STATUS_COMPLETE'))
      echo '<script type="text/javascript"> setTimeout("location.replace(\'index.php?section=MEDIA&action=PROGRESS&type='.$scan_type.'\')",10000); </script>';
  }
  
  /**
   * Starts a search for new media and then redirects the usere to the media progress page
   *
   */

  function media_search()
  {
    // Store the parameters to the media search (catgory, media tyoe) in the system_prefs table
    // as this is the only way of passing the info to the background process in Simese.
    set_sys_pref('MEDIA_SCAN_TYPE',$_REQUEST["scan_type"]);
    set_sys_pref('MEDIA_SCAN_MEDIA_TYPE',$_REQUEST["type"]);
    set_sys_pref('MEDIA_SCAN_CATEGORY',$_REQUEST["cat"]);
    set_sys_pref('REFRESH_METADATA',$_REQUEST["refresh"]);
    set_sys_pref('MEDIA_SCAN_ITUNES',$_REQUEST["itunes"]);
    set_sys_pref('MEDIA_SCAN_RSS',$_REQUEST["rss_id"]);
    set_sys_pref('MEDIA_SCAN_STATUS',str('MEDIA_SCAN_STATUS_PENDING'));
 
    // Call the media search in the background.    
    media_refresh_now();

    // Show progress
    header('Location: /config/index.php?section=MEDIA&action=PROGRESS&type='.$_REQUEST["scan_type"]);
  }
  
  /**
   * Displays a mednu to the user asking them which media types and/or categories they would like to search.
   *
   */

  function media_refresh()
  {  
    $option_vals = array( str('YES')=>'YES',str('NO')=>'NO');
    
    echo '<h1>'.str('SETUP_SEARCH_NEW_MEDIA').'</h1>
          <p>'.str('MEDIA_SEARCH_PROMPT').'<br>&nbsp;';

    form_start('index.php', 150, 'conn');
    form_hidden('section', 'MEDIA');
    form_hidden('action', 'SEARCH');
    form_hidden('scan_type', 'MEDIA');
    form_list_dynamic('type',str('MEDIA_TYPE'),"select media_id,media_name from media_types where media_table<>'' order by 2",$_REQUEST['type'],true,false,str('ALL_MEDIA_TYPES'));
    echo '<tr><td></td><td> &nbsp; &nbsp; '.str('OR').'</td></tr>';
    form_list_dynamic('cat', str('CATEGORY'),"select distinct(c.cat_id), c.cat_name from media_locations ml, categories c
                                              where ml.cat_id = c.cat_id order by c.cat_name", $_REQUEST['cat'],true,false,str('ALL_CATEGORIES'));
    echo '<tr><td></td><td>&nbsp;</td></tr>';
    form_radio_static('refresh',str('REFRESH_METADATA'),$option_vals,get_sys_pref('REFRESH_METADATA','NO'),false,true);
    form_label(str('REFRESH_METADATA_PROMPT'));
    form_radio_static('itunes',str('REFRESH_ITUNES'),$option_vals,get_sys_pref('MEDIA_SCAN_ITUNES','NO'),false,true);
    form_label(str('REFRESH_ITUNES_PROMPT'));
    echo '<tr><td></td><td>&nbsp;</td></tr>';
    form_submit(str('SETUP_SEARCH_NEW_MEDIA'),2);
    form_end();
    form_start('index.php', 150, 'rss');
    form_hidden('section', 'MEDIA');
    form_hidden('action', 'SEARCH');
    form_hidden('scan_type', 'RSS');
    form_list_dynamic('rss_id',str('RSS_FEEDS'),"select id,title from rss_subscriptions order by 2",$_REQUEST['rss_id'],true,false,str('ALL_RSS_FEEDS'));
    echo '<tr><td></td><td>&nbsp;</td></tr>';
    form_submit(str('RSS_REFRESH'),2);
    form_end();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
