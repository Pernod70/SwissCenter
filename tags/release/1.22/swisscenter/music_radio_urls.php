<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  $data = db_toarray("select title, url from internet_urls where type=".MEDIA_TYPE_RADIO." order by title");

  for ($i=0; $i<count($data); $i++)
    $array[] = array("name"=>$data[$i]["TITLE"], "url"=>play_internet_radio($data[$i]["URL"],$data[$i]["TITLE"]));

  $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
  $url  = url_remove_param(current_url(), 'page');

  page_header(str('LISTEN_RADIO'),'','',1,false,'',MEDIA_TYPE_RADIO);  
  browse_array($url,$array,$page,MEDIA_TYPE_RADIO);
  page_footer( 'music_radio.php' );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
