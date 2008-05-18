<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

 require_once( realpath(dirname(__FILE__).'/settings.php'));
 require_once( realpath(dirname(__FILE__).'/../ext/magpie/rss_fetch.inc'));

 // Define the cache location used for rss files
 define('MAGPIE_CACHE_DIR', get_sys_pref('CACHE_DIR').'/rss/feeds');
 define('RSS_CONTENT_DIR', get_sys_pref('CACHE_DIR').'/rss/content');

 define('RSS_TYPE_HTML', 2);
 define('RSS_TYPE_PODCAST', 1);
 define('RSS_TYPE_VODCAST', 3);
 
 define('RSS_MAX_FILE_LENGTH', 1073741824);  // 10 MB max length

 /**
  * Gets the rss subscription details from the database
  *
  * @param int $sub_id - subscription id to get (0=All)
  */
 function rss_get_subscriptions( $sub_id=0 )
 {
   $where = (empty($sub_id) ? '' : 'WHERE id='.$sub_id);
   return db_toarray("SELECT id, url, title, (CASE type
                                              WHEN 1 THEN '".str('RSS_AUDIO')."'
                                              WHEN 2 THEN '".str('RSS_IMAGE')."'
                                              WHEN 3 THEN '".str('RSS_VIDEO')."'
                                              ELSE 'Unknown' 
                                              END) type, cache,
                                              UNIX_TIMESTAMP(last_update + INTERVAL update_frequency MINUTE) 'next_update' 
                                              FROM rss_subscriptions $where ORDER BY title");
 }
 
 /**
 * Gets the rss subscription items from the database
 * 
 * @param integer $sub_id - subscription id to get (0=All)
 * @param string $order - order of returned items ('asc' or 'desc')
 */
 function rss_get_subscription_items($sub_id, $order='asc')
 {
   return db_toarray("SELECT * FROM rss_items WHERE subscription_id = $sub_id ORDER BY published_date $order"); 
 }
 
 /**
 * Update the RSS information in the database
 * 
 * @param int $sub_id - subscription id to update (0=All)
 */
 function rss_update_subscriptions( $sub_id )
 {
   send_to_log(4, "Updating RSS Subscriptions");
   $subs = rss_get_subscriptions( $sub_id );
   
   // Ensure local cache folders exist
   if (!file_exists(get_sys_pref('CACHE_DIR').'/rss'))
     @mkdir(get_sys_pref('CACHE_DIR').'/rss');
   if (!file_exists(MAGPIE_CACHE_DIR))
     @mkdir(MAGPIE_CACHE_DIR);
   if (!file_exists(RSS_CONTENT_DIR))
     @mkdir(RSS_CONTENT_DIR);
   
   foreach($subs as $sub)
   {
     if(empty($sub["NEXT_UPDATE"]) || ($sub["NEXT_UPDATE"] < time()))
     {
       send_to_log(4, "Fetching RSS data from ".$sub["URL"]);
       $rss = fetch_rss($sub['URL']);
       
       if (!empty($rss->ERROR))
         send_to_log(1, "MagpieRSS Error:", $rss->ERROR);
       if (!empty($rss->WARNING))
         send_to_log(2, "MagpieRSS Warning:", $rss->WARNING);
         
       rss_update_subscription($sub["ID"], $rss->channel);
       
       foreach($rss->items as $key=>$item)
       {
         rss_update_item($sub["ID"], $item, $sub["CACHE"]);
         // Set the percentage of this subscription updated.
         update_rss_progress($sub["ID"], (int)(($key+1)/$sub["CACHE"]*100));
       }
       update_rss_progress($sub["ID"], 100);
       db_sqlcommand("UPDATE rss_subscriptions SET last_update='".db_datestr()."' WHERE id=".$sub["ID"]);
     }
     else
     {
       send_to_log(4, "Skipping update for '".$sub["TITLE"]."', next update ".date("'Y.m.d H:i:s'", $sub["NEXT_UPDATE"]));
       update_rss_progress($sub["ID"], 100);
     }
   }
 }
 
 
 /**
 * Gets the existing item (if any) given an RSS item and subscription id
 *
 * @param int $sub_id - The subscription ID that this item relates to
 * @param object $item - The RSS item
 * @return array - The existing item if any, will be false if there isn't one
 */
 function rss_get_existing_item($sub_id, $item)
 {
    // Check to see if the item already exists
    //
    // Unfortunately not all feeds have guids as it's an optional field, in this instance
    // the link is used to check for uniqueness which isn't ideal but will have to do.
    //
    // Doing it this way allows the link to change and update the current entry as long as
    // there is a guid, if there is no guid then duplicates are minimized.
    
    $sql = "SELECT id, title, url, description, published_date, timestamp, guid, linked_file FROM rss_items WHERE";
    
    if(empty($item["guid"]))
      $sql .= " url = '".db_escape_str($item["link"])."'";
    else
      $sql .= " guid = '".db_escape_str($item["guid"])."'";
    
    $sql .= " AND subscription_id=$sub_id";

   return db_row($sql);
 }
 
 
 /**
 * Updates the subscription with an item. If the item exists it will be updated
 * if not then it will be created. The guid and subscription_id are used as the key.
 * 
 * @param integer $sub_id - The ID of the subscription that this item is for
 * @param object $item - The item to update
 */
 function rss_update_item($sub_id, $item, $cache)
 {
   send_to_log(4, "Updating item", $item["title"]);
   
   $item_data = array("subscription_id" => $sub_id,
                      "guid" => $item["guid"],
                      "title" => utf8_decode($item["title"]).' ',
                      "url" => $item["link"],
                      "timestamp" => (empty($item["date_timestamp"]) ? time() : $item["date_timestamp"]),
                      "published_date" => (empty($item["pubdate"]) ? db_datestr(strtotime("now")) : db_datestr(strtotime($item["pubdate"]))),
                      "description" => utf8_decode(empty($item["atom_content"]) ? $item["description"].' ' : $item["atom_content"])
                     );
   
   send_to_log(8, "Updating item details", $item);
   
   // Add the item to the database
   $existing_item = rss_get_existing_item($sub_id, $item);
   $existing_id = $existing_item["ID"];
   $cache_items = rss_get_subscription_items($sub_id); 
   
   // Only add the item if it is more recent than the oldest cached
   if ( ($cache == 0) || (count($cache_items) < $cache) || 
        ($item_data["published_date"] >= $cache_items[0]["PUBLISHED_DATE"]) )
   {
     if(empty($existing_id))
     {
       db_insert_row("rss_items", $item_data);
       
       $existing_id = db_insert_id();
     }
     else
     {
       $sql = "UPDATE rss_items SET ".db_array_to_set_list($item_data).
            " WHERE id=$existing_id";
            
       db_sqlcommand($sql);
     }
  
     // Download the linked item if needed
     if($url = rss_need_linkedfile($sub_id, $existing_item, $item))
       rss_fetch_linked_item($existing_id, $url);
   }
   
   // Delete expired items
   if ($cache > 0)
   {
     $cache_items = rss_get_subscription_items($sub_id);
     for ($i = 0; $i < (count($cache_items) - $cache); $i++) 
     {
       $linked_file = db_value("select linked_file from rss_items where id=".$cache_items[$i]['ID']);
       if (!empty($linked_file)) unlink($linked_file);
       db_sqlcommand("delete from rss_items where id=".$cache_items[$i]['ID']);
     }
   }
 }
 
 
 /**
 * Get the subscription details for the given subscription
 *
 * @param integer $sub_id - The subscription id
 * @return array - The subscription details
 */
 function rss_get_subscription_details($sub_id)
 {
   $sql = "SELECT * FROM rss_subscriptions WHERE id=$sub_id";
   
   return db_row($sql);
 }
 
 
 /**
 * Checks to see if the specified item has a linked file to download
 *
 * @param integer $sub_id - The subscription ID for this subscription
 * @param array $existing_item - The existing item from the db (if any)
 * @param array $new_item - The new RSS item
 * @return string - The url to download from if needed, false if not
 */
 function rss_need_linkedfile($sub_id, $existing_item, $new_item)
 {
   $sub = rss_get_subscription_details($sub_id);
 
   if((($sub["TYPE"] == RSS_TYPE_PODCAST) ||
       ($sub["TYPE"] == RSS_TYPE_VODCAST)) &&
      (!empty($new_item["guid"]) ||
       !empty($new_item["enclosure"])))
   {
     // We have a file to download, check if we need to
     if(empty($existing_item) ||
        ($existing_item["TIMESTAMP"] != $new_item["date_timestamp"]) ||
        empty($existing_item["LINKED_FILE"]))
     {
       if(!empty($new_item["enclosure"]["url"]))
         return $new_item["enclosure"]["url"];
       elseif(!empty($new_item["guid"]))
         return $new_item["guid"];
     }
     else
     {
       send_to_log(4, "Skipping download of file, not changed");
     }
   }
 }


 /**
 * Fetches a linked item from the given url and stores it on disk
 *
 * @param integer $item_id - The ID of the item that this item is linked to
 * @param string $url - The URL of the linked item
 *
 * @return bool - true if the file was downloaded ok, false if not
 */
 function rss_fetch_linked_item($item_id, $url)
 {
   $url_details = parse_url($url);
   
   $filename = $item_id."_".basename($url_details["path"]);
   $rss_file = str_suffix(RSS_CONTENT_DIR, '/').$filename;

   send_to_log(4, "Downloading linked file from '".$url."' to '".$rss_file."'");

   $fd = fopen($rss_file, 'wb');
   
   $snoopy = new Snoopy();
   $snoopy->output_fp = $fd;
   $snoopy->maxlength = RSS_MAX_FILE_LENGTH;
   
   if(($result = $snoopy->fetch($url)) != false)
   {
     // Update the item in the database with the new filename for the link
     $sql = "UPDATE rss_items SET ".db_array_to_set_list(array("linked_file" => addslashes(os_path($rss_file)))).
            " WHERE id=$item_id";

     db_sqlcommand($sql);
   }
   else
   {
     send_to_log(2, "Error downloading RSS linked file '".$snoopy->error."'");
   }
   
   fclose($fd);


   return $result;
 }

/**
 * Updates the subscription details.
 * 
 * @param integer $sub_id - The ID of the subscription
 * @param object $channel - The subscription details
 */
 function rss_update_subscription($sub_id, $channel)
 { 
   // use itunes image if available
   if ( !empty($channel['itunes']['image_href']) )
     $image = $channel['itunes']['image_href'];
   elseif ( !empty($channel['media']['thumbnail']) )
     $image = $channel['media']['thumbnail'];
   elseif (!empty($channel['image']['url']))
     $image = $channel['image']['url'];
   else 
     $image = '';
   if ( !empty($image) )
     $img = file_get_contents($image);
   else 
     $img = null;
   
   // use itunes summary if available
   if ( !empty($channel['itunes']['summary']) )
     $channel['description'] = $channel['itunes']['summary'];
   
   send_to_log(4, "Updating subscription details", array("description" => $channel["description"],
                                                         "image_url"   => $image));

   // Update the subscription in the database with description and image
   $sql = "UPDATE rss_subscriptions SET ".db_array_to_set_list(array("description" => utf8_decode($channel["description"]),
                                                                     "image_url"   => $image,
                                                                     "image"       => addslashes($img)))." WHERE id=$sub_id";
            
   db_sqlcommand($sql);
 }
 
 /**
 * Updates the percentage of a rss feed that has been updated
 *
 * @param integer $sub_id - The subscription ID being updated
 */
 function update_rss_progress( $sub_id, $percent )
 {
   db_sqlcommand("update rss_subscriptions set percent_scanned = $percent where id = $sub_id ");  
 }
 /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
