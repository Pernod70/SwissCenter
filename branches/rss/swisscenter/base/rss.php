<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/utils.php'));
  require_once( realpath(dirname(__FILE__).'/users.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));

  define("RSS_OK", 0);
  define("RSS_FAIL_DIR", -1);
  define("RSS_FAIL_DB", -2);
  define("RSS_FAIL_XFER", -3);
  
  ///////////////////////////////////////////////////////////////
  // General subscription management functions
  ///////////////////////////////////////////////////////////////
  function rss_create_subscription($title, $url, $update_frequency)
  {
    if(db_insert_row('rss_subscriptions', array('title'=>$title,
                                                'url'=>$url,
                                                'update_frequency'=>$update_frequency,
                                                'last_update'=>db_datestr())) === false)
    {
      return RSS_FAIL_DB;
    }
    
    // Create the directory for this subscription
    $sub_id = db_insert_id();
    
    if(!mkdir(rss_get_sub_dir($sub_id)))
    {
      rss_delete_subscription($sub_id);
      return RSS_FAIL_DIR;
    }
    
    if(rss_download_subscription($sub_id, $url) === false)
      return RSS_FAIL_XFER;
    
    return RSS_OK;
  }

  function rss_delete_subscription($sub_id)
  {
    if(!delete_dir(rss_get_sub_dir($sub_id)))
      return RSS_FAIL_DIR;
    
    if(db_sqlcommand("delete from rss_subscriptions where id=$sub_id") === false)
      return RSS_FAIL_DB;
    
    return RSS_OK;
  }

  function rss_update_subscription($id, $title, $url, $update_frequency)
  {
    $existing = db_row("select url, title, update_frequency from rss_subscriptions where id=$id");
    
    if(!empty($existing))
    {
      $sql = "update rss_subscriptions
                     set url='" . db_escape_str($url) . "',
                         title='" . db_escape_str($title) ."',
                         update_frequency=$update_frequency
                     where id=$id";

      if(db_sqlcommand($sql) === false)
        return RSS_FAIL_DB;
        
      if($array["url"] != $url)
      {
        if(rss_download_subscription($id, $url) === false)
          return RSS_FAIL_XFER;
      }
    }
      
    return RSS_OK;
  }
  
  function rss_get_sub_dir($id)
  {
    return realpath(dirname(__FILE__).'/../rss_store')."/$id";
  }



  ///////////////////////////////////////////////////////////////
  // XML download and parsing functions
  ///////////////////////////////////////////////////////////////
  
  // Download the subscription from the specified url or lookup in the db if not specified
  function rss_download_subscription($id, $url = '')
  {
    // May have to do something with set_time_limit() here if there are problems with time
  
    $rss_file = str_suffix(rss_get_sub_dir($id), '/').'rss.xml';
    $fd = fopen($rss_file, 'w');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FILE, $fd);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_exec($ch);
    
    $error_code = curl_errno($ch);
    
    curl_close($ch);
    fclose($fd);
    
    if($error_code != 0)
    {
      @unlink($rss_file);
      
      return false;
    }
    
    return true;
  }

//http://feeds.feedburner.com/tapestrydilbert

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
