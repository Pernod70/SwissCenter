<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/language.php'));

  if (isset($_REQUEST["lang"]))
  {
    set_user_pref('LANGUAGE',$_REQUEST["lang"]);
    $_SESSION["language"] = $_REQUEST["lang"];
    page_inform(2,'index.php',str('LANG_CHANGE'),str('SAVE_SETTINGS_OK'));
  }
  else
  {
    $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
    $array = array();
    foreach (db_toarray('select ietf_tag, name from translate_languages order by name') as $lang)
    {
      $array[] = array("text"  => $lang['NAME'],
                       "thumb" => file_albumart(SC_LOCATION.'images/flags/'.$lang['IETF_TAG'].'.xml'),
                       "url"   => 'change_lang.php?lang='.$lang['IETF_TAG']);
    }

    page_header( str('LANG_CHANGE'), '');
    browse_array_thumbs(url_remove_param(current_url(),'page'), $array, $page);
    page_footer( page_hist_previous() );
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
