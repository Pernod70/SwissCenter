<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/media.php'));

  function media_search()
  {
    media_refresh_now();
    echo "<h1>".str('SETUP_SEARCH_NEW_MEDIA')."</h1>";
    message(str('REFRESH_DATABASE_PROMPT'));
    echo '<p>'.str('REFRESH_RUNNING');
  }

  function media_refresh()
  {  
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'MEDIA');
    form_hidden('action', 'SEARCH');
    echo '<h1>'.str('SETUP_SEARCH_NEW_MEDIA').'</h1>
          <p>'.str('MEDIA_SEARCH_PROMPT').'<br>&nbsp;';
    form_submit(str('SETUP_SEARCH_NEW_MEDIA'),2,'left',240);
    form_end();
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
