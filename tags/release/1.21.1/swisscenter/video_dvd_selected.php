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
  $info = new infotab();

  // Displays the details for a single dvd (identified by the movie ID) including the title, year, certificate
  // and synopsis (where available).

  function movie_details ($movie, $num_menu_items)
  {
    $info      = array_pop(db_toarray("select * from movies where file_id=$movie"));
    $directors = db_toarray("select d.director_name from directors_of_movie dom, directors d where dom.director_id = d.director_id and dom.movie_id=$movie");
    $actors    = db_toarray("select a.actor_name from actors_in_movie aim, actors a where aim.actor_id = a.actor_id and aim.movie_id=$movie");
    $genres    = db_toarray("select g.genre_name from genres_of_movie gom, genres g where gom.genre_id = g.genre_id and gom.movie_id=$movie");
    $synlen    = $_SESSION["device"]["browser_x_res"] * 0.625 * (9-$num_menu_items);

    // Synopsis
    if ( !is_null($info["SYNOPSIS"]) )
    {
      $text = shorten($info["SYNOPSIS"],$synlen);
      if (strlen($text) != strlen($info["SYNOPSIS"]))
        $text = $text.' <a href="/video_synopsis.php?media_type='.MEDIA_TYPE_DVD.'&file_id='.$movie.'">'.font_colour_tags('PAGE_TEXT_BOLD_COLOUR','[more]').'</a>';
    }
    else
      $text = str('NO_SYNOPSIS_AVAILABLE');

    echo '<p>'.font_tags(32).$text.'</font>';
  }

  // Function that checks to see if the given attribute ($filter) is unique, and if so it
  // populates the information table.

  function distinct_info ($filter, $sql_table, $predicate)
  {
    if (db_value("select count(distinct $filter) from $sql_table $predicate") == 1)
      return db_value("select $filter from $sql_table limit 1,1");
    else
      return '';
  }

  // Function that checks to see if the supplied SQL contains a filter for the specified type.
  // If it doesn't, then we can output a menu item which allows the user to filter on that type.

  function check_filters ($filter_list, $sql_table, $predicate, &$menu )
  {
    foreach ($filter_list as $filter)
    {
      $num_rows  = db_value("select count(distinct $filter) from $sql_table $predicate");
      if ($num_rows>1)
      {
        switch ($filter)
        {
          case 'title'         : $menu->add_item( str('REFINE_TITLE')       ,"video_dvd_search.php?sort=title",true); break;
          case 'year'          : $menu->add_item( str('REFINE_YEAR')        ,"video_dvd_search.php?sort=year",true);   break;
          case 'certificate'   : $menu->add_item( str('REFINE_CERTIFICATE') ,"video_dvd_search.php?sort=certificate",true);  break;
          case 'genre_name'    : $menu->add_item( str('REFINE_GENRE') 	    ,"video_dvd_search.php?sort=genre",true);  break;
          case 'actor_name'    : $menu->add_item( str('REFINE_ACTOR')       ,"video_dvd_search.php?sort=actor",true);  break;
          case 'director_name' : $menu->add_item( str('REFINE_DIRECTOR')    ,"video_dvd_search.php?sort=director",true);  break;
        }
      }
    }
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.
  $sql_table_all = 'movies media '.
                   'left outer join directors_of_movie dom on media.file_id = dom.movie_id '.
                   'left outer join genres_of_movie gom on media.file_id = gom.movie_id '.
                   'left outer join actors_in_movie aim on media.file_id = aim.movie_id '.
                   'left outer join actors a on aim.actor_id = a.actor_id '.
                   'left outer join directors d on dom.director_id = d.director_id '.
                   'left outer join genres g on gom.genre_id = g.genre_id'.
                    get_rating_join().' where 1=1 and ml.media_type='.MEDIA_TYPE_DVD;
  $select_fields = "file_id, dirname, filename, title, year, length";
  $predicate     = search_process_passed_params();
  $sql_table     = "movies media ";
  // Only join tables that are actually required
  if (strpos($predicate,'director_name like') > 0)
    $sql_table  .= 'left outer join directors_of_movie dom on media.file_id = dom.movie_id '.
                   'left outer join directors d on dom.director_id = d.director_id ';
  if (strpos($predicate,'actor_name like') > 0)
    $sql_table  .= 'left outer join actors_in_movie aim on media.file_id = aim.movie_id '.
                   'left outer join actors a on aim.actor_id = a.actor_id ';
  if (strpos($predicate,'genre_name like') > 0)
    $sql_table  .= 'left outer join genres_of_movie gom on media.file_id = gom.movie_id '.
                   'left outer join genres g on gom.genre_id = g.genre_id ';
  $sql_table    .= get_rating_join().' where 1=1 and ml.media_type='.MEDIA_TYPE_DVD;
  $file_ids      = db_col_to_list("select distinct media.file_id from $sql_table $predicate");
  $playtime      = db_value("select sum(length) from movies where file_id in (".implode(',',$file_ids).")");
  $num_unique    = db_value("select count( distinct synopsis) from movies where file_id in (".implode(',',$file_ids).")");
  $num_rows      = count($file_ids);
  $this_url      = url_set_param(current_url(),'add','N');
  $cert_img      = '';

  //
  // A single movie has been matched/selected by the user, so display as much information as possible
  // on the screen, along with commands to "Play Now".
  //

  if ($num_rows == 1 || $num_unique == 1)
  {
    // Single match, so get the details from the database and display them
    if ( ($data = db_toarray("select media.*, ".get_cert_name_sql()." certificate_name from $sql_table $predicate")) === false)
      page_error( str('DATABASE_ERROR'));

    if (!empty($data[0]["YEAR"]))
      page_header( $data[0]["TITLE"].' ('.$data[0]["YEAR"].')' ,'');
    else
      page_header( $data[0]["TITLE"] );

    // Play now
    $menu->add_item( str('PLAY_NOW') , play_file(7, $data[0]["FILE_ID"]) );

    // Add a link to search wikipedia
    if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES' )
      $menu->add_item( str('SEARCH_WIKIPEDIA'), lang_wikipedia_search( ucwords(strip_title($data[0]["TITLE"])) ) ,true);

    // Link to full cast & directors
    if ($data[0]["DETAILS_AVAILABLE"] == 'Y')
      $menu->add_item( str('VIDEO_INFO'), 'video_info.php?movie='.$data[0]["FILE_ID"],true);

    // Display thumbnail (DVD Video image will be in parent folder)
    if ( strtoupper($data[0]["FILENAME"]) == 'VIDEO_TS.IFO' )
      $folder_img = file_albumart(rtrim($data[0]["DIRNAME"],'/').".dvd");
    else
      $folder_img = file_albumart($data[0]["DIRNAME"].$data[0]["FILENAME"]);
  }

  //
  // There are multiple dvd's which match the criteria entered by the user. Therefore, we should
  // display the information that is common to all dvd's, and provide links to refine the search
  // further.
  //
  else
  {

    // More than one dvd matches, so output filter details and menu options to add new filters
    page_header( str('MANY_ITEMS',$num_unique),'');

    if ( ($data = db_toarray("select file_id, dirname from $sql_table $predicate group by dirname")) === false )
      page_error( str('DATABASE_ERROR') );

    if ( count($data)==1)
      $folder_img = file_albumart($data[0]["DIRNAME"]);

    $info->add_item( str('TITLE')       , distinct_info('title',$sql_table, $predicate));
    $info->add_item( str('YEAR')        , distinct_info('year',$sql_table, $predicate));
    $info->add_item( str('CERTIFICATE') , distinct_info(/*'certificate'*/get_cert_name_sql(),$sql_table, $predicate));

    check_filters( array('title','year','certificate','genre_name','actor_name','director_name'), $sql_table_all, $predicate, $menu);

    $info->display();
  }

  // Delete media (limited to a small number of files)
  if (is_user_admin() && $num_rows<=8 )
    $menu->add_item( str('DELETE_MEDIA'), 'video_delete.php?del='.implode(',',$file_ids),true);

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

              // Movie synopsis
              movie_details($data[0]["FILE_ID"],$menu->num_items());

              // Running Time
              if (!is_null($playtime))
                echo   '<p>'.font_tags(32).str('RUNNING_TIME').': '.hhmmss($playtime).'</font>';
              $menu->display(1, 480);
    echo '    </td></table>';
  }
  else
  {
    $menu->display();
  }

  page_footer( url_add_params( search_picker_most_recent(), array("p_del"=>"y","del"=>"y") ) );

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
