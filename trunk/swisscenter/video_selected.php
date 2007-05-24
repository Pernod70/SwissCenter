<?
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
  
  // Displays the details for a single movie (identified by the movie ID) including the title, year, certificate
  // and synopsis (where available).
  
  function movie_details ($movie)
  {
    $info      = array_pop(db_toarray("select * from movies where file_id=$movie"));
    $directors = db_toarray("select d.director_name from directors_of_movie dom, directors d where dom.director_id = d.director_id and dom.movie_id=$movie");
    $actors    = db_toarray("select a.actor_name from actors_in_movie aim, actors a where aim.actor_id = a.actor_id and aim.movie_id=$movie");
    $genres    = db_toarray("select g.genre_name from genres_of_movie gom, genres g where gom.genre_id = g.genre_id and gom.movie_id=$movie");
    $scheme    = get_rating_scheme_name();
    $cert_img  = SC_LOCATION.'images/ratings/'.$scheme.'/'.get_cert_name( get_nearest_cert_in_scheme($info["CERTIFICATE"], $scheme)).'.gif';
    $synlen    = 3500;
    
    // This is a temporary fixkludge until the font sizing in the shorten() function is fixed.
    if ( is_screen_hdtv()) $synlen = 7000;    
       
    if (!empty($info["CERTIFICATE"]))
      echo img_gen($cert_img,convert_x(180),convert_y(180),false,false,false,array("align"=>"right"));

    if ( !is_null($info["SYNOPSIS"]) )
    {
      
      echo '<p>'.font_tags(30).shorten($info["SYNOPSIS"],$synlen).'</font>';
    }
    else 
      echo font_tags(30).str('NO_SYNOPSIS_AVAILABLE').'</font>';
      
      
  }
  
  // Function that checks to see if the given attribute ($filter) is unique, and if so it
  // populates the information table.

  function distinct_info ($filter, $sql_table, $predicate)
  {
    if (db_value("select count(distinct $filter) from $sql_table $predicate" == 1) )
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
          case 'title'         : $menu->add_item( str('REFINE_TITLE')       ,"video_search.php?sort=title",true); break;
          case 'year'          : $menu->add_item( str('REFINE_YEAR')        ,"video_search.php?sort=year",true);   break;
          case 'certificate'   : $menu->add_item( str('REFINE_CERTIFICATE') ,"video_search.php?sort=certificate",true);  break;
          case 'genre_name'    : $menu->add_item( str('REFINE_GENRE') 	    ,"video_search.php?sort=genre",true);  break;
          case 'actor_name'    : $menu->add_item( str('REFINE_ACTOR')       ,"video_search.php?sort=actor",true);  break;        
          case 'director_name' : $menu->add_item( str('REFINE_DIRECTOR')    ,"video_search.php?sort=director",true);  break;
        }
      }
    }
  }

//*************************************************************************************************
// Main Code
//*************************************************************************************************

  // Decode & assign page parameters to variables.
  $sql_table     = "movies media
                    left outer join directors_of_movie dom on media.file_id = dom.movie_id
                    left outer join genres_of_movie gom on media.file_id = gom.movie_id
                    left outer join actors_in_movie aim on media.file_id = aim.movie_id
                    left outer join actors a on aim.actor_id = a.actor_id
                    left outer join directors d on dom.director_id = d.director_id
                    left outer join genres g on gom.genre_id = g.genre_id".
                    get_rating_join().
                    ' where 1=1 ';  
  $select_fields = "file_id, dirname, filename, title location_id, certificate";
  $predicate     = search_process_passed_params();
  $num_rows      = db_value("select count( distinct media.file_id) from $sql_table $predicate");
  $this_url      = url_set_param(current_url(),'add','N');
  
  //
  // A single movie has been matched/selected by the user, so display as much information as possible
  // on the screen, along with commands to "Play Now" or "Add to Playlist".
  //
    
  if ($num_rows == 1)
  {    
    // Single match, so get the details from the database and display them
    if ( ($data = db_toarray("select media.*, a.actor_name, d.director_name, g.genre_name, ".get_cert_name_sql()." certificate_name from $sql_table $predicate")) === false)
      page_error( str('DATABASE_ERROR'));

    if (!empty($data[0]["YEAR"]))
      page_header( $data[0]["TITLE"].' ('.$data[0]["YEAR"].')' ,'');
    else 
      page_header( $data[0]["TITLE"] );

    // Play now
    $menu->add_item( str('PLAY_NOW')    , play_sql_list(MEDIA_TYPE_VIDEO,"select distinct $select_fields from $sql_table $predicate order by title"));

    // Resume playing
    if ( support_resume() && file_exists( bookmark_file($data[0]["DIRNAME"].$data[0]["FILENAME"]) ))
      $menu->add_item( str('RESUME_PLAYING') , resume_file(MEDIA_TYPE_VIDEO,$data[0]["FILE_ID"]), true);
        
    // Add to your current plpaylist
    if (pl_enabled())
      $menu->add_item( str('ADD_PLAYLIST') ,'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from movies where file_id=".$data[0]["FILE_ID"]),true);

    // Add a link to search wikipedia
    if (internet_available() && get_sys_pref('wikipedia_lookups','YES') == 'YES' )
      $menu->add_item( str('SEARCH_WIKIPEDIA'), lang_wikipedia_search( ucwords(strip_title($data[0]["TITLE"])) ) ,true);
      
    // TO-DO
    // Link to full cast & directors
    // $menu->add_item( str('MOVIE_INFO'), 'video_info.php?movie='.$data[0]["FILE_ID"],true);
    
    // Display thumbnail
    $folder_img = file_albumart($data[0]["DIRNAME"].$data[0]["FILENAME"]);
  }

  //
  // There are multiple movies which match the criteria enterede by the user. Therefore, we should
  // display the information that is common to all movies, and provide links to refine the search
  // further.
  //
  else
  {

    // More than one track matches, so output filter details and menu options to add new filters
    page_header( str('MANY_ITEMS',$num_rows),'');

    if ( ($data = db_toarray("select dirname from $sql_table $predicate group by dirname")) === false )
      page_error( str('DATABASE_ERROR') );

    if ( count($data)==1)
      $folder_img = file_albumart($data[0]["DIRNAME"]);

    $info->add_item( str('TITLE')       , distinct_info('title',$sql_table, $predicate));
    $info->add_item( str('YEAR')        , distinct_info('year',$sql_table, $predicate));
    $info->add_item( str('CERTIFICATE') , distinct_info(/*'certificate'*/get_cert_name_sql(),$sql_table, $predicate));
    $menu->add_item( str('PLAY_NOW')    , play_sql_list(MEDIA_TYPE_VIDEO,"select distinct $select_fields from $sql_table $predicate order by title"));

    if (pl_enabled())
      $menu->add_item( str('ADD_PLAYLIST') ,'add_playlist.php?sql='.rawurlencode("select distinct $select_fields from $sql_table $predicate order by title"),true);

    check_filters( array('title','year','certificate','genre_name','actor_name','director_name'), $sql_table, $predicate, $menu);

    $info->display();
  }
  
  // Is there a picture for us to display?
  if (! empty($folder_img) )
  {
    echo '<p><table width="100%" cellpadding=0 cellspacing=0 border=0>
          <tr><td valign=top width="'.convert_x(280).'" align="left">
              '.img_gen($folder_img,280,600).'
              </td><td width="'.convert_x(20).'"></td>
              <td valign="top">';
              movie_details($data[0]["FILE_ID"]);
              $menu->display(480);
    echo '    </td></table>';
  }
  else
  {
    $menu->display();
  }

  if (!isset($_SESSION["shuffle"]) || $_SESSION["shuffle"] == 'off')
    $buttons[] = array('text'=>str('SHUFFLE_ON'), 'url'=> url_set_param($this_url,'shuffle','on') );
  else
    $buttons[] = array('text'=>str('SHUFFLE_OFF'), 'url'=> url_set_param($this_url,'shuffle','off') );

  page_footer( url_add_param($_SESSION["last_picker"][count($_SESSION["history"])-1],'del','y'), $buttons );
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
