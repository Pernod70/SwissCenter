<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

/**************************************************************************************************
   Main page output
 *************************************************************************************************/

  // Get actor/director/genre lists
  $file_id    = $_REQUEST["file_id"];
  $media_type = $_REQUEST["media_type"];
  $table      = db_value("select media_table from media_types where media_id = $media_type");
  $data       = db_row("select * from $table where file_id = $file_id");

  // Where to return to?
  $history  = search_hist_pop();
  $back_url = url_add_param($history["url"], 'add','Y');
  
  if (!empty($data["YEAR"]))
    page_header( $data["TITLE"].' ('.$data["YEAR"].')' ,'');
  else 
    page_header( $data["TITLE"] );
  
  $menu = new menu();
  $menu->add_item(str('RETURN_TO_SELECTION'), $back_url);
    
  echo $data["SYNOPSIS"];
  $menu->display();
  
  page_footer( $back_url );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
