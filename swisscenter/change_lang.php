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
    load_lang($_REQUEST["lang"]);
    page_inform(2,'index.php',str('LANG_CHANGE'),str('SAVE_SETTINGS_OK'));
  }
  else 
  {
    $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
    $array = array();
    foreach (explode("\n",str_replace("\r",null,file_get_contents(SC_LOCATION.'lang/languages.txt'))) as $line)
    {
      $lang = explode(',',$line);
      if ( !empty($lang[0]) )
        $array[] = array("text"=>$lang[0],
                         "thumb"=>file_albumart(SC_LOCATION.'images/flags/'.$lang[1].'.xml'),
                         "url"=>'change_lang.php?lang='.$lang[1]);
    }

    page_header( str('LANG_CHANGE'), '');
    browse_array_thumbs(url_remove_param(current_url(),'page'), $array, $page);
    page_footer( 'config.php' );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
