<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/playlist.php'));
  require_once( realpath(dirname(__FILE__).'/base/rating.php'));
  require_once( realpath(dirname(__FILE__).'/base/search.php'));

  $menu = new menu();
  
  /**
   * Displays the synopsis for a single tv episode (identified by the file_id).
   *
   * @param int $tv file_id
   */
  function tv_details ($tv)
  {
    $info      = array_pop(db_toarray("select synopsis from tv where file_id=$tv"));
    $synlen    = 3500;
    
    // This is a temporary fixkludge until the font sizing in the shorten() function is fixed.
    if ( is_screen_hdtv()) $synlen = 7000;    

    // Synopsis
    if ( !is_null($info["SYNOPSIS"]) )
      echo '<p>'.font_tags(32).shorten($info["SYNOPSIS"],$synlen).'</font>';
    else 
      echo '<p>'.font_tags(32).str('NO_SYNOPSIS_AVAILABLE').'</font>';
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.
  $sql_table     = "tv media".get_rating_join().' where 1=1 ';  
  $select_fields = "file_id, dirname, filename, title, year, length";
  $file_id       = $_REQUEST["file_id"];
  $cert_img      = '';
  $this_url      = url_set_param(current_url(),'add','N');
  
  // Should we delete the last entry on the history stack?
  if (isset($_REQUEST["del"]) && strtoupper($_REQUEST["del"]) == 'Y')
    search_hist_pop();
    
  $back_url      = search_hist_most_recent();
  
  if (isset($_REQUEST["add"]) && strtoupper($_REQUEST["add"]) == 'Y')
    search_hist_push( $this_url, '' );
  
  // Single match, so get the details from the database and display them
  if ( ($data = db_toarray("select media.*, ".get_cert_name_sql()." certificate_name from $sql_table and file_id=$file_id")) === false)
    page_error( str('DATABASE_ERROR'));

  if (!empty($data[0]["YEAR"]))
    page_header( $data[0]["TITLE"].' ('.$data[0]["YEAR"].')' ,'');
  else 
    page_header( $data[0]["TITLE"] );

  // Play now
  $menu->add_item( str('PLAY_NOW'), play_file( MEDIA_TYPE_TV, $data[0]["FILE_ID"]));

  // Resume playing
  if ( support_resume() && file_exists( bookmark_file($data[0]["DIRNAME"].$data[0]["FILENAME"]) ))
    $menu->add_item( str('RESUME_PLAYING') , resume_file(MEDIA_TYPE_TV,$file_id), true);
        
  // Add to your current playlist
  if (pl_enabled())
    $menu->add_item( str('ADD_PLAYLIST') ,'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table and file_id=$file_id"),true);

  // Add a link to search wikipedia
  if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES' )
    $menu->add_item( str('SEARCH_WIKIPEDIA'), lang_wikipedia_search( ucwords(strip_title($data[0]["PROGRAMME"])) ) ,true);
      
  // Link to full cast & directors
  if ($data[0]["DETAILS_AVAILABLE"] == 'Y')
    $menu->add_item( str('VIDEO_INFO'), 'video_info.php?tv='.$file_id,true);
    
  // Display thumbnail
  $folder_img = file_albumart($data[0]["DIRNAME"].$data[0]["FILENAME"]);

  // Delete media (limited to a small number of files)
  if (is_user_admin())
    $menu->add_item( str('DELETE_MEDIA'), 'video_delete.php?del='.$file_id.'&media_type=6',true);    

  // Certificate? Get the appropriate image.
  $scheme    = get_rating_scheme_name();
  if (!empty($data[0]["CERTIFICATE"]))
    $cert_img  = img_gen(SC_LOCATION.'images/ratings/'.$scheme.'/'.get_cert_name( get_nearest_cert_in_scheme($data[0]["CERTIFICATE"], $scheme)).'.gif', convert_x(250), convert_y(180));
  
  // Is there a picture for us to display?
  if (! empty($folder_img) )
  {
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(280).'" align="left">
              '.img_gen($folder_img,280,550).'<br><center>'.$cert_img.'</center>
              </td><td width="'.convert_x(20).'"></td>
              <td valign="top">';
              // Episode synopsis
              tv_details($data[0]["FILE_ID"]);
              // Running Time
    if (!is_null($data[0]["LENGTH"]))
      echo   '<p>'.font_tags(32).str('RUNNING_TIME').': '.hhmmss($data[0]["LENGTH"]).'</font>';
              $menu->display(1, 480);
    echo '    </td></table>';
  }
  else
  {
    $menu->display();
  }

  page_footer( url_add_param( $back_url["url"] ,'del','y') );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
