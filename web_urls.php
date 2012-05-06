<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));

  function display_web_menu($cat_id)
  {
    // > 0 indicates normal categories, < 0 indicates all sub-categories
    if($cat_id > 0)
      $category_select_sql = ' and cat_id='.$cat_id;
    elseif ($cat_id < 0)
      $category_select_sql = ' and cat_id in ('.implode(",",category_children(-$cat_id)).')';
    else
      $category_select_sql = '';

    $data = db_toarray("select title, url from internet_urls media
                        left outer join certificates media_cert on media_cert.cert_id=media.certificate
                        where media.type=".MEDIA_TYPE_WEB.$category_select_sql." AND IFNULL(media_cert.rank,0) <= ".get_current_user_rank()." order by title");

    for ($i=0; $i<count($data); $i++)
      $array[] = array("name"=>$data[$i]["TITLE"], "url"=>$data[$i]["URL"]);

    $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
    $url  = url_remove_param(current_url(), 'page');

    browse_array($url,$array,$page,MEDIA_TYPE_WEB);

    // Make sure the "back" button goes to the correct page:
    page_footer( page_hist_previous() );
  }

/*************************************************************************************************
   Main page output
 *************************************************************************************************/

  page_header(str('BROWSE_WEB'),'','',1,false,'',MEDIA_TYPE_WEB);

  if( category_count(MEDIA_TYPE_WEB)==1 || isset($_REQUEST["cat"]) )
    display_web_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('web_urls.php', MEDIA_TYPE_WEB, $_REQUEST["subcat"], page_hist_previous());
  else
    display_categories('web_urls.php', MEDIA_TYPE_WEB, 0, page_hist_previous());

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
