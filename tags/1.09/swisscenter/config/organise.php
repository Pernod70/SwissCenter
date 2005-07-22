<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  ob_start();
  
  include_once('../base/db_abstract.php');
  include_once('../base/file.php');
  include_once('../base/html_form.php');
  include_once('../base/prefs.php');
  include_once('../base/server.php');
  include_once('common.php');
  
  //
  // Simple function to output all elements of an array as a drop-down or multi-select list.
  //
  
  function list_option_elements ($array, $selected = array() )
  {
    $list = '';
    foreach ($array as $row)
      $list .= '<option '.(in_array($row["ID"],$selected) ? ' selected ' : '').'value="'.$row["NAME"].'">'.$row["NAME"].'</option>';

   return $list;
  }

  //*************************************************************************************************
  // OPTIONS section
  //*************************************************************************************************
  
  function options_display( $message = '')
  {
    $per_page = get_user_pref('PC_PAGINATION',10);
    $opts     = array(10=>"10" , 25=>"25" , 50=>"50" , 100=>"100");
  
    echo '<p><h1>Options<p>';
    message($message);
    form_start('organise.php');
    form_hidden('section','OPTIONS');
    form_hidden('action','SET');
    form_list_static('items','Movies Per Page',$opts,$per_page,false,false);
    form_label('Please specify the number of movies you wish to display on each page.');
    form_submit('Submit',2);
    form_end();
  }
  
  function options_set()
  {
    set_user_pref('PC_PAGINATION',$_REQUEST["items"]);
    options_display('Settings Saved');
  }  
  
  //*************************************************************************************************
  // MOVIE section
  //*************************************************************************************************
  
  function movie_display( $message = '')
  {
    $per_page    = get_user_pref('PC_PAGINATION',10);
    $page        = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);
    $start       = ($page-1)*$per_page;
    $movie_list  = db_toarray("select * from movies order by title limit $start,$per_page");
    $movie_count = db_value("select count(*) from movies");
       
    echo "<h1>Movie Information</h1>";
    message($message);
    
    echo '<form enctype="multipart/form-data" action="" method="post">';
    echo '<input type=hidden name="section" value="MOVIE">';
    echo '<input type=hidden name="action" value="UPDATE">';

    paginate('organise.php?section=MOVIE&action=DISPLAY&page=',$movie_count,$per_page,$page);
    echo '<table class="form_select_tab" width="100%"><tr>
            <th width="3%">&nbsp;</th>
            <th width="34%"> Title </th>
            <th width="21%"> Actors </th>
            <th width="21%"> Directors </th>
            <th width="21%"> Genres </th>
          </tr></table>';

    foreach ($movie_list as $movie)
    {
      $actors    = db_col_to_list("select actor_name from actors a,actors_in_movie aim where a.actor_id=aim.actor_id and movie_id=$movie[FILE_ID] order by 1");
      $directors = db_col_to_list("select director_name from directors d, directors_of_movie dom where d.director_id = dom.director_id and movie_id=$movie[FILE_ID] order by 1");
      $genres    = db_col_to_list("select genre_name from genres g, genres_of_movie gom where g.genre_id = gom.genre_id and movie_id=$movie[FILE_ID] order by 1");

      echo '<table class="form_select_tab" width="100%"><tr>
            <td valign="top" width="3%"><input type="checkbox" name="movie[]" value="'.$movie["FILE_ID"].'"></input><td>
            <td valign="top" width="34%">
               <b>'.$movie["TITLE"].'</b><br>
               Certificate : '.nvl($movie["RATING"]).'<br>
               Year : '.nvl($movie["YEAR"]).'<br>
             </td>
             <td valign="top" width="21%">'.nvl(implode("<br>",$actors)).'</td>
             <td valign="top" width="21%">'.nvl(implode("<br>",$directors)).'</td>
             <td valign="top" width="21%">'.nvl(implode("<br>",$genres)).'</td>
            </tr></table>';  	
    }
    
    echo '<table width="100%"><tr><td align="center">
          <input type="Submit" name="submit" value="Update Details"> &nbsp; 
          <input type="Submit" name="submit" value="Clear Details"> &nbsp; 
          </td></tr></table>';
    
    paginate('organise.php?section=MOVIE&action=DISPLAY&page=',$movie_count,$per_page,$page);
    
    echo '</form>';
  }
  
  function movie_update()
  {
    if ($_REQUEST["submit"] == 'Clear Details')
      movie_clear_details();
    elseif ($_REQUEST["submit"] == 'Update Details')
      movie_update_form();
    else 
      echo 'Unknown value recieved for "submit" parameter';
  }
  
  function movie_clear_details()
  {
    $cleared = false;
    foreach ($_REQUEST["movie"] as $value)
    {
      db_sqlcommand('delete from actors_in_movie where movie_id = '.$value);
      db_sqlcommand('delete from directors_of_movie where movie_id = '.$value);
      db_sqlcommand('delete from genres_of_movie where movie_id = '.$value);
      db_sqlcommand('update movies set year=null,rating=null where file_id = '.$value);
      scdb_remove_orphans();
      $cleared = true;
    }
    if ($cleared)
      movie_display('Details cleared.');
    else 
      movie_display();
  }

  function movie_update_form()
  {
    $movie_list = $_REQUEST["movie"];
    if (count($movie_list) == 0)
      echo "You have not selected any movies to update.";
    elseif (count($movie_list) == 1)
      movie_update_form_single();
    else
      movie_update_form_multiple($movie_list);
  }
  
  function movie_update_form_single()
  {
    // Get actor/director/genre lists
    $movie_id    = $_REQUEST["movie"][0];
    $movie_title = db_value("select title from movies where file_id=".$movie_id);
    $actors      = db_toarray("select actor_name name, actor_id id from actors order by 1");
    $directors   = db_toarray("select director_name name, director_id id from directors order by 1");
    $genres      = db_toarray("select genre_name name, genre_id id from genres order by 1");
    $ratings     = db_col_to_list("select name from ratings");
    
    // Because we can't use subqueries for the above lists, we now need to determine which
    // rows should be shown as selected.
    $a_select = db_col_to_list("select actor_id from actors_in_movie where movie_id=".$movie_id);
    $d_select = db_col_to_list("select director_id from directors_of_movie where movie_id=".$movie_id);
    $g_select = db_col_to_list("select genre_id from genres_of_movie where movie_id=".$movie_id);

    // Display movies that will be affected.
    echo '<h1>Update <em>'.$movie_title.'</em></h1>
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="MOVIE">
          <input type=hidden name="action" value="UPDATE_SINGLE">
          <input type=hidden name="movie[]" value="'.$movie_id.'">
          <table class="form_select_tab" width="100%" cellspacing=4><tr><td colspan="3" align="center">
          Please enter the details that you would like to <em>add</em> to each of the movies listed above
          <br>(holding down the CTRL key will allow you to select multiple entries in the lists)
          </td></tr><tr>
          <th width="33%">Actors</th>
          <th width="33%">Directors</th>
          <th width="33%">Genres</th>   
          </tr><tr>
          <td><select name="actors[]" multiple size="8">
          '.list_option_elements($actors, $a_select).'
          </select></td><td><select name="directors[]" multiple size="8">
          '.list_option_elements($directors,$d_select).'
          </select></td><td><select name="genres[]" multiple size="8">
          '.list_option_elements($genres,$g_select).'
          </select></td></tr></tr><tr><td colspan="3" align="center">
          You may enter new Actors, Directors or Genres that are not listed into the boxes below. 
          <br>To add more than one new entry, separateeach entry with a comma.
          </td></tr><tr>
          <td width="33%"><input name="actor_new" size=25></td>
          <td width="33%"><input name="director_new" size=25></td>
          <td width="33%"><input name="genre_new" size=25></td>
          </tr><tr>
            <th>Certificate</th>
            <th>Year</th>
          </tr><tr>
            <td><input name="rating" size="6"></td>
            <td><input name="year" size="6"></td>
          </tr></table>
          <p align="center"><input type="submit" value="Add/Update Details">
          </form>';    
  }
  
  function movie_update_form_multiple( $movie_list )
  {
    $actors    = db_toarray("select actor_name name from actors order by 1");
    $directors = db_toarray("select director_name name from directors order by 1");
    $genres    = db_toarray("select genre_name name from genres order by 1");
    $ratings   = db_col_to_list("select name from ratings");

    // Display movies that will be affected.
    echo '<h1>Update Movies</h1>
         <center>The following movies will be updated :<p>';
         array_to_table(db_toarray("select title from movies where file_id in (".implode(',',$movie_list).")"),'50%');      
      
    echo '</center>
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="MOVIE">
          <input type=hidden name="action" value="UPDATE_MULTIPLE">';

    foreach ($movie_list as $movie_id)
      echo '<input type=hidden name="movie[]" value="'.$movie_id.'">';
            
    echo '<table class="form_select_tab" width="100%" cellspacing=4><tr><td colspan="3" align="center">
          Please enter the details that you would like to <em>add</em> to each of the movies listed above
          <br>(holding down the CTRL key will allow you to select multiple entries in the lists)
          </td></tr><tr>
          <th width="33%">Actors</th>
          <th width="33%">Directors</th>
          <th width="33%">Genres</th>   
          </tr><tr>
          <td><select name="actors[]" multiple size="8">
          '.list_option_elements($actors).'
          </select></td><td><select name="directors[]" multiple size="8">
          '.list_option_elements($directors).'
          </select></td><td><select name="genres[]" multiple size="8">
          '.list_option_elements($genres).'
          </select></td></tr></tr><tr><td colspan="3" align="center">
          You may enter new Actors, Directors or Genres that are not listed into the boxes below. 
          <br>To add more than one new entry, separateeach entry with a comma.
          </td></tr><tr>
          <td width="33%"><input name="actor_new" size=25></td>
          <td width="33%"><input name="director_new" size=25></td>
          <td width="33%"><input name="genre_new" size=25></td>
          </tr><tr>
            <th>Certificate</th>
            <th>Year</th>
          </tr><tr>
            <td><input name="rating" size="6"></td>
            <td><input name="year" size="6"></td>
          </tr></table>
          <p align="center"><input type="submit" value="Add/Update Details">
          </form>';    
  }

  function movie_update_single()
  {
    // Clear the existing details for this movie, as they will be reinserted by
    // calling the update_multiple function.
    $movie_id = $_REQUEST["movie"][0];
    db_sqlcommand("delete from actors_in_movie where movie_id=".$movie_id);
    db_sqlcommand("delete from directors_of_movie where movie_id=".$movie_id);
    db_sqlcommand("delete from genres_of_movie where movie_id=".$movie_id);
    movie_update_multiple();
  }
  
  function movie_update_multiple()
  {
    $movie_list = $_REQUEST["movie"];
    $columns    = array();
    
    if (!empty($_REQUEST["year"]))
      $columns["YEAR"] = $_REQUEST["year"];
    if (!empty($_REQUEST["rating"]))
      $columns["RATING"] = $_REQUEST["rating"];

    // Update the MOVIES table?
    if (count($columns)>0)
    {
      $columns["DETAILS_AVAILABLE"] = 'Y';
      scdb_set_movie_attribs($movie_list, $columns);
    }
   
    // Add Actors/Genres/Directors?
    if (count($_REQUEST["actors"]) >0)
      scdb_add_actors($movie_list,$_REQUEST["actors"]);
    if (!empty($_REQUEST["actor_new"]))
      scdb_add_actors($movie_list, explode(',',$_REQUEST["actor_new"]));

    if (count($_REQUEST["directors"]) >0)
      scdb_add_directors($movie_list,$_REQUEST["directors"]);
    if (!empty($_REQUEST["director_new"]))
      scdb_add_directors($movie_list, explode(',',$_REQUEST["director_new"]));

    if (count($_REQUEST["genres"]) >0)
      scdb_add_genres($movie_list,$_REQUEST["genres"]);   
    if (!empty($_REQUEST["genre_new"]))
      scdb_add_genres($movie_list, explode(',',$_REQUEST["genre_new"]));

    scdb_remove_orphans();
    movie_display("Changes Made");
   }

  //*************************************************************************************************
  // Populate main sections of the webpage
  //*************************************************************************************************
  
  //
  // Create and amanager the menu (static menu)
  //

  function display_menu()
  {
   echo '<table width="160">';
   menu_item('List Movies','section=MOVIE&action=DISPLAY','menu_bgr.png');
   menu_item('Options','section=OPTIONS&action=DISPLAY','menu_bgr.png');
   echo '</table>';
  }
  
  //
  // Calls the correct function for displaying content on the page.
  //
  
  function display_content()
  {
   $db_stat = test_db(DB_HOST,DB_USERNAME,DB_PASSWORD,DB_DATABASE); 
   if (!is_server_iis() && $_SERVER["REMOTE_ADDR"] != $_SERVER["SERVER_ADDR"] )
   {
     echo '<br><h1>Access Denied</h1><p align="center">Remote access to the <i>\'SwissCenter Configuration Utility\'</i> is disabled for security reasons.';
   }
   elseif ($db_stat == 'OK')
   {
     session_start();
     include_once('../base/settings.php');
     if (!empty($_REQUEST["section"]))
     {
       $func = (strtoupper($_REQUEST["section"]).'_'.strtoupper($_REQUEST["action"]));
       @$func();
     }
     else 
        movie_display();  
   }
   else
   {
     echo '<br><h1>No Database</h1><p align="center">Unable to access the SwissCenter database.</p>';
   }
  }

  // Load database settings...
  
  if (file_exists('swisscenter.ini'))
  {
    foreach( parse_ini_file('swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }

  $page_title = 'Organise Movies';
  $page_width = '100%';
  include("config_template.php");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
