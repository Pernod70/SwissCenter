<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/categories.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/browse.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));

  function display_radio_menu($cat_id)
  {
    if(empty($cat_id))
      search_hist_init( 'music_radio_urls.php' );
    else
      search_hist_init( 'music_radio_urls.php?cat='.$cat_id );

    if ($cat_id <= 0)
      $prev_page = "music_radio_urls.php?subcat=".abs($cat_id);
    else
      $prev_page = "music_radio_urls.php?subcat=".db_value("select parent_id from categories where cat_id=$cat_id");

    // > 0 indicates normal categories, < 0 indicates all sub-categories
    if($cat_id > 0)
      $category_select_sql = ' and cat_id='.$cat_id;
    elseif ($cat_id < 0)
      $category_select_sql = ' and cat_id in ('.implode(",",category_children(-$cat_id)).')';
    else
      $category_select_sql = '';

    $data = db_toarray("select title, url from internet_urls media
                        left outer join certificates media_cert on media_cert.cert_id=media.certificate
                        where media.type=".MEDIA_TYPE_RADIO.$category_select_sql." AND IFNULL(media_cert.rank,0) <= ".get_current_user_rank()." order by title");

    for ($i=0; $i<count($data); $i++)
      $array[] = array("name"=>$data[$i]["TITLE"], "url"=>play_internet_radio($data[$i]["URL"],$data[$i]["TITLE"]));

    $page = isset($_REQUEST["page"]) ? $_REQUEST["page"] : 0;
    $url  = url_remove_param(current_url(), 'page');

    browse_array($url,$array,$page,MEDIA_TYPE_RADIO);

    // Make sure the "back" button goes to the correct page:
    if (category_count(MEDIA_TYPE_RADIO)==1)
      page_footer('index.php?submenu=internet');
    else
      page_footer($prev_page);
  }

/*************************************************************************************************
   Main page output
 *************************************************************************************************/

  page_header(str('LISTEN_RADIO'),'','',1,false,'',MEDIA_TYPE_RADIO);

  if( category_count(MEDIA_TYPE_RADIO)==1 || isset($_REQUEST["cat"]) )
    display_radio_menu($_REQUEST["cat"]);
  elseif ( isset($_REQUEST["subcat"]) )
    display_categories('music_radio_urls.php', MEDIA_TYPE_RADIO, $_REQUEST["subcat"], 'index.php?submenu=internet');
  else
    display_categories('music_radio_urls.php', MEDIA_TYPE_RADIO, 0, 'index.php?submenu=internet');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
