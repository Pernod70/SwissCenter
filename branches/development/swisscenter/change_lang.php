<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  require_once("base/page.php");
  require_once("base/menu.php");
  require_once("base/mysql.php");
  require_once("base/language.php");

  if (isset($_REQUEST["lang"]))
  {
  	set_user_pref('LANGUAGE',$_REQUEST["lang"]);
  	load_lang($_REQUEST["lang"]);
  	page_inform(2,'index.php',str('LANG_CHANGE'),str('SAVE_SETTINGS_OK'));
  }
  else 
  {
    page_header( str('LANG_CHANGE'), '', 'LOGO_CONFIG' );

    echo '<p align="center">'.str('LANG_SELECT');
    $menu = new menu();

    foreach (explode("\n",str_replace("\r",null,file_get_contents(SC_LOCATION.'lang/languages.txt'))) as $line)
    {
      $lang = explode(',',$line);
      $menu->add_item($lang[0],'change_lang.php?lang='.$lang[1]);
    }

    $menu->display();
    page_footer( 'config.php' );
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
