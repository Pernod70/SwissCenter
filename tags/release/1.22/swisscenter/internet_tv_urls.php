<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  $data = db_toarray("select title, url from internet_urls where type=".MEDIA_TYPE_INTERNET_TV." order by title");

  for ($i=0; $i<count($data); $i++)
    $array[] = array("name"=>$data[$i]["TITLE"], "url"=>play_internet_tv($data[$i]["URL"]));

  $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
  $url  = url_remove_param(current_url(), 'page');

  page_header(str('WATCH_INTERNET_TV'),'','',1,false,'',MEDIA_TYPE_INTERNET_TV);  
  browse_array($url,$array,$page,MEDIA_TYPE_INTERNET_TV);
  page_footer('index.php?submenu=internet');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
