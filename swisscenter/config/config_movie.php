<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
require_once( realpath(dirname(__FILE__).'/../base/media.php'));
require_once( realpath(dirname(__FILE__).'/../ext/xml/export_movie.php'));

// ----------------------------------------------------------------------------------
// Get an array of online movie parsers for displaying in a form drop-down list.
// ----------------------------------------------------------------------------------

function get_parsers_list()
{
  $parsers = dir_to_array( realpath(dirname(__FILE__).'/../ext/parsers') , '.*\.php' );
  $sites_list = array();
  
  foreach ($parsers as $file)
    $sites_list[file_noext($file)] = basename($file);  
  
  return $sites_list;
}

// ----------------------------------------------------------------------------------
// Displays the details for movies
// ----------------------------------------------------------------------------------

function movie_display_info(  $message = '' )
{
  // Get actor/director/genre lists
  $movie_id    = $_REQUEST["movie_id"];
  $details     = db_toarray("select * from movies where file_id=".$movie_id);
  $actors      = db_toarray("select actor_name name from actors a, actors_in_movie aim where aim.actor_id = a.actor_id and movie_id=".$movie_id);
  $directors   = db_toarray("select director_name name from directors d, directors_of_movie dom where dom.director_id = d.director_id and movie_id=".$movie_id);
  $genres      = db_toarray("select genre_name name from genres g, genres_of_movie gom where gom.genre_id = g.genre_id and movie_id=".$movie_id);
  $filename    = $details[0]["DIRNAME"].$details[0]["FILENAME"];
  $sites_list  = get_parsers_list();
  $exists_js   = '';

  // If a movie XML file already exists, use javascript to ask the user whether to overwrite it.
  if ( file_exists(substr($filename,0,strrpos($filename,'.')).".xml") )
    $exists_js = 'onClick="javascript:return confirm(\''.addslashes(str_replace('"','',str('MOVIE_EXPORT_OVERWRITE'))).'?\')"';  
    
  // Display movies that will be affected.
  echo '<h1>'.$details[0]["TITLE"].'</h1><center>
         ( <a href="'.$_SESSION["last_search_page"].'">'.str('RETURN_TO_LIST').'</a> 
         | <a href="?section=MOVIE&action=UPDATE_FORM_SINGLE&movie[]='.$movie_id.'">'.str('DETAILS_EDIT').'</a> 
         | <a href="?section=MOVIE&action=EXPORT&movie_id='.$movie_id.'" '.$exists_js.'>'.str('DETAILS_EXPORT').'</a> 
         )
        </center>';
        message($message);
  echo '<table class="form_select_tab" width="100%" cellspacing=4><tr>
          <th colspan="3">'.str('SYNOPSIS').'</th>
        </tr><tr>
          <td colspan="3">';
  
  $folder_img = file_albumart($details[0]["DIRNAME"].$details[0]["FILENAME"]);
  if (!empty($folder_img))
    echo img_gen($folder_img,100,200,false,false,false,array('hspace'=>0,'vspace'=>4,'align'=>'left') );
  
  echo  $details[0]["SYNOPSIS"].'<br>&nbsp;</td>
        </tr><tr>
        <th width="33%">'.str('ACTOR').'</th>
        <th width="33%">'.str('DIRECTOR').'</th>
        <th width="33%">'.str('GENRE').'</th>   
        </tr><tr>
        <td rowspan=3 valign=top>';
  
        foreach ($actors as $name)
          echo $name["NAME"].'<br>';
          
  echo '<br>&nbsp;</td><td valign=top>';
  
        foreach ($directors as $name)
          echo $name["NAME"].'<br>';
          
  echo '<br>&nbsp;</td><td valign=top>';
  
        foreach ($genres as $name)
          echo $name["NAME"].'<br>';
          
  echo '<br>&nbsp;</td></tr></tr><tr>
          <th>'.str('CERTIFICATE').'</th>
          <th>'.str('YEAR').'</th>
        </tr><tr>
          <td valign=top>'.get_cert_name(get_nearest_cert_in_scheme($details[0]["CERTIFICATE"])).'</td>
          <td valign=top>'.$details[0]["YEAR"].'</td>
        </tr></table>
        <p align="center">';

  // Get movie information from online source
  echo '<table width="100%"><tr>
        <td align="center">
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="MOVIE">
          <input type=hidden name="action" value="LOOKUP">
          <input type=hidden name="movie_id" value="'.$movie_id.'">
          '.form_list_static_html('parser',$sites_list, get_sys_pref('movie_info_script','movie_lovefilm.php'),false,false).'
          &nbsp; <input type="Submit" name="subaction" value="'.str('LOOKUP_MOVIE').'"> &nbsp; 
          </form>
        </td>
        </tr></table>';  
}

// ----------------------------------------------------------------------------------
// Uses the selected parser script to update the movie details in the database
// ----------------------------------------------------------------------------------

function movie_lookup()
{
  $movie_id = $_REQUEST["movie_id"];
  $details  = db_toarray("select * from movies where file_id=$movie_id");
  $filename = $details[0]["DIRNAME"].$details[0]["FILENAME"];
  $title    = file_noext($details[0]["FILENAME"]);

  require_once( realpath(dirname(__FILE__).'/../video_obtain_info.php'));
  if ( extra_get_movie_details($movie_id, $filename,$title) )
    movie_display_info( str('LOOKUP_SUCCESS') );
  else 
    movie_display_info( '!'.str('LOOKUP_FAILURE') );
}

// ----------------------------------------------------------------------------------
// Displays the movie details for editing
// ----------------------------------------------------------------------------------

function movie_display( $message = '')
{
  $_SESSION["last_search_page"] = current_url( true );
  $per_page    = get_user_pref('PC_PAGINATION',20);
  $page        = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);
  $start       = ($page-1)*$per_page;    
  $where       = '';
  
  if (empty($message) && isset($_REQUEST["message"]))
    $message = urldecode($_REQUEST["message"]);

  // Extra filters on the media (for categories and search).
  if (!empty($_REQUEST["cat_id"]) )
    $where .= "and ml.cat_id = $_REQUEST[cat_id] ";
 
  if (!empty($_REQUEST["search"]) )
    $where .= "and m.title like '%$_REQUEST[search]%' ";
    
  // If the user has changed category, then shunt them back to page 1.
  if ($_REQUEST["last_where"] != $where)
  {
    $page = 1;
    $start = 0;
  }
  
  // SQL to fetch matching rows
  $movie_count = db_value("select count(*) from movies m, media_locations ml where ml.location_id = m.location_id ".$where);
  $movie_list  = db_toarray("select m.* from movies m, media_locations ml where ml.location_id = m.location_id ".$where.
                            " order by title limit $start,$per_page");        
  
  echo '<h1>'.str('ORG_TITLE').'  ('.str('PAGE',$page).')</h1>';
  message($message);
  
  echo '<form enctype="multipart/form-data" action="" method="post">
        <table width="100%"><tr><td width="50%">
        <input type=hidden name="section" value="MOVIE">
        <input type=hidden name="action" value="DISPLAY">
        <input type=hidden name="last_where" value="'.$where.'">
        '.str('CATEGORY').' : 
        '.form_list_dynamic_html("cat_id","select cat_id,cat_name from categories",$_REQUEST["cat_id"],true,true,str('CATEGORY_LIST_ALL')).'
        </td><td width"50% align="right">
        '.str('SEARCH').' : 
        <input name="search" value="'.$_REQUEST["search"].'" size=10>
        </form>
        </td></tr></table>';
  
  echo '<form enctype="multipart/form-data" action="" method="post">
        <input type=hidden name="section" value="MOVIE">
        <input type=hidden name="action" value="UPDATE">';

  paginate('?last_where='.$where.'&search='.$_REQUEST["search"].'&cat_id='.$_REQUEST["cat_id"].'&section=MOVIE&action=DISPLAY&page='
          ,$movie_count,$per_page,$page);

  echo '<table class="form_select_tab" width="100%"><tr>
          <th width="3%">&nbsp;</th>
          <th width="34%"> '.str('Title').' </th>
          <th width="21%"> '.str('Actor').' </th>
          <th width="21%"> '.str('Director').' </th>
          <th width="21%"> '.str('Genre').' </th>
        </tr></table>';

  foreach ($movie_list as $movie)
  {
    $actors    = db_col_to_list("select actor_name from actors a,actors_in_movie aim where a.actor_id=aim.actor_id 
                                 and movie_id=$movie[FILE_ID] order by 1");
    $directors = db_col_to_list("select director_name from directors d, directors_of_movie dom where d.director_id = dom.director_id 
                                 and movie_id=$movie[FILE_ID] order by 1");
    $genres    = db_col_to_list("select genre_name from genres g, genres_of_movie gom where g.genre_id = gom.genre_id 
                                 and movie_id=$movie[FILE_ID] order by 1");
    $cert      = db_value("select name from certificates where cert_id=".nvl($movie["CERTIFICATE"],-1));

    echo '<table class="form_select_tab" width="100%"><tr>
          <td valign="top" width="3%"><input type="checkbox" name="movie[]" value="'.$movie["FILE_ID"].'"></input><td>
          <td valign="top" width="34%">
             <a href="?section=movie&action=display_info&movie_id='.$movie["FILE_ID"].'">'.$movie["TITLE"].'</a><br>
             Certificate : '.nvl($cert).'<br>
             Year : '.nvl($movie["YEAR"]).'<br>
           </td>
           <td valign="top" width="21%">'.nvl(implode("<br>",$actors)).'</td>
           <td valign="top" width="21%">'.nvl(implode("<br>",$directors)).'</td>
           <td valign="top" width="21%">'.nvl(implode("<br>",$genres)).'</td>
          </tr></table>';  	
  }
  paginate('?last_where='.$where.'&search='.$_REQUEST["search"].'&cat_id='.$_REQUEST["cat_id"].'&section=MOVIE&action=DISPLAY&page='
          ,$movie_count,$per_page,$page);

  echo '<p><table width="100%"><tr><td align="center">
        <input type="Submit" name="subaction" value="'.str('DETAILS_EDIT').'"> &nbsp; 
        <input type="Submit" name="subaction" value="'.str('DETAILS_CLEAR').'"> &nbsp; 
        </td></tr></table>
        </form>';
}

// ----------------------------------------------------------------------------------
// Calls the relevant function to make a modification to the movie details
// ----------------------------------------------------------------------------------

function movie_update()
{
  if ($_REQUEST["subaction"] == str('DETAILS_CLEAR'))
    movie_clear_details();
  elseif ($_REQUEST["subaction"] == str('DETAILS_EDIT'))
    movie_update_form();
  elseif (empty($_REQUEST["subaction"])) 
    movie_display();
  else
    send_to_log(1,'Unknown value recieved for "subaction" parameter : '.$_REQUEST["subaction"]);
}

// ----------------------------------------------------------------------------------
// Clears the movie details
// ----------------------------------------------------------------------------------

function movie_clear_details()
{
  $cleared = false;
  foreach ($_REQUEST["movie"] as $value)
  {
    db_sqlcommand('delete from actors_in_movie where movie_id = '.$value);
    db_sqlcommand('delete from directors_of_movie where movie_id = '.$value);
    db_sqlcommand('delete from genres_of_movie where movie_id = '.$value);
    db_sqlcommand('update movies set year=null,certificate=null where file_id = '.$value);
    remove_orphaned_movie_info();
    scdb_remove_orphans();
    $cleared = true;
  }
  if ($cleared)
    movie_display(str('DETAILS_CLEARED_OK'));
  else 
    movie_display();
}

// ----------------------------------------------------------------------------------
// Calls the releveant function to display the correct type of update form
// ----------------------------------------------------------------------------------

function movie_update_form()
{
  $movie_list = $_REQUEST["movie"];
  if (count($movie_list) == 0)
    movie_display("!".str('MOVIE_ERROR_NO_SELECT'));
  elseif (count($movie_list) == 1)
    movie_update_form_single();
  else
    movie_update_form_multiple($movie_list);
}

// ----------------------------------------------------------------------------------
// Displays a form for updating a single movie
// ----------------------------------------------------------------------------------

function movie_update_form_single()
{
  // Get actor/director/genre lists
  $movie_id    = $_REQUEST["movie"][0];
  $details     = db_toarray("select * from movies where file_id=".$movie_id);
  $actors      = db_toarray("select actor_name name, actor_id id from actors order by 1");
  $directors   = db_toarray("select director_name name, director_id id from directors order by 1");
  $genres      = db_toarray("select genre_name name, genre_id id from genres order by 1");
  
  // Because we can't use subqueries for the above lists, we now need to determine which
  // rows should be shown as selected.
  $a_select = db_col_to_list("select actor_id from actors_in_movie where movie_id=".$movie_id);
  $d_select = db_col_to_list("select director_id from directors_of_movie where movie_id=".$movie_id);
  $g_select = db_col_to_list("select genre_id from genres_of_movie where movie_id=".$movie_id);

  // Display movies that will be affected.
  echo '<h1>'.$details[0]["TITLE"].'</h1>
        <form enctype="multipart/form-data" action="" method="post">
        <input type=hidden name="section" value="MOVIE">
        <input type=hidden name="action" value="UPDATE_SINGLE">
        <input type=hidden name="movie[]" value="'.$movie_id.'">
        <table class="form_select_tab" width="100%" cellspacing=4><tr><td colspan="3" align="center">&nbsp<br>
        '.str('MOVIE_ADD_PROMPT').'
        <br>&nbsp;</td></tr><tr>
        <th width="33%">'.str('ACTOR').'</th>
        <th width="33%">'.str('DIRECTOR').'</th>
        <th width="33%">'.str('GENRE').'</th>   
        </tr><tr>
        <td><select name="actors[]" multiple size="8">
        '.list_option_elements($actors, $a_select).'
        </select></td><td><select name="directors[]" multiple size="8">
        '.list_option_elements($directors,$d_select).'
        </select></td><td><select name="genres[]" multiple size="8">
        '.list_option_elements($genres,$g_select).'
        </select></td></tr></tr><tr><td colspan="3" align="center">&nbsp<br>
        '.str('MOVIE_NEW_PROMPT').'
        <br>&nbsp;</td></tr><tr>
        <td width="33%"><input name="actor_new" size=25></td>
        <td width="33%"><input name="director_new" size=25></td>
        <td width="33%"><input name="genre_new" size=25></td>
        </tr><tr>
          <th colspan="3">'.str('Synopsis').'</th>
        </tr><tr>
          <td colspan="3">'.form_text_html('synopsis',90,6,$details[0]["SYNOPSIS"],true).'</td>
        </tr><tr>
          <th>'.str('CERTIFICATE').'</th>
          <th>'.str('YEAR').'</th>
        </tr><tr>
          <td>
          '.form_list_dynamic_html("rating",get_cert_list_sql(),$details[0]["CERTIFICATE"],true).'
          </td>
          <td><input name="year" size="6" value="'.$details[0]["YEAR"].'"></td>
        </tr></table>
        <p align="center"><input type="submit" value="'.str('MOVIE_ADD_BUTTON').'">
        </form>';    
}

// ----------------------------------------------------------------------------------
// Displaus a form for updating multiple movies
// ----------------------------------------------------------------------------------

function movie_update_form_multiple( $movie_list )
{
  $actors    = db_toarray("select actor_name name from actors order by 1");
  $directors = db_toarray("select director_name name from directors order by 1");
  $genres    = db_toarray("select genre_name name from genres order by 1");

  // Display movies that will be affected.
  echo '<h1>'.str('MOVIE_UPD_TTILE').'</h1>
       <center>'.str('MOVIE_UPD_TEXT').'<p>';
       array_to_table(db_toarray("select title from movies where file_id in (".implode(',',$movie_list).")"),str('Title'));      
    
  echo '</center>
        <form enctype="multipart/form-data" action="" method="post">
        <input type=hidden name="section" value="MOVIE">
        <input type=hidden name="action" value="UPDATE_MULTIPLE">';

  foreach ($movie_list as $movie_id)
    echo '<input type=hidden name="movie[]" value="'.$movie_id.'">';
          
  echo '<table class="form_select_tab" width="100%" cellspacing=4><tr>
        <th width="33%">'.str('ACTOR').'</th>
        <th width="33%">'.str('DIRECTOR').'</th>
        <th width="33%">'.str('GENRE').'</th>   
        </tr><tr>
        <tr><td colspan="3" align="center">'.str('MOVIE_ADD_PROMPT').'</td></tr>
        <td><select name="actors[]" multiple size="8">
        '.list_option_elements($actors).'
        </select></td><td><select name="directors[]" multiple size="8">
        '.list_option_elements($directors).'
        </select></td><td><select name="genres[]" multiple size="8">
        '.list_option_elements($genres).'
        </select></td></tr></tr><tr><td colspan="3" align="center">
        '.str('MOVIE_NEW_PROMPT').'
        </td></tr><tr>
        <td width="33%"><input name="actor_new" size=25></td>
        <td width="33%"><input name="director_new" size=25></td>
        <td width="33%"><input name="genre_new" size=25></td>
        </tr><tr>
          <th colspan="3">'.str('Synopsis').'</th>
        </tr><tr>
          <td colspan="3">'.form_text_html('synopsis',65,3,'',true).'</td>
        </tr><tr>
          <th>'.str('CERTIFICATE').'</th>
          <th>'.str('YEAR').'</th>
        </tr><tr>
          <td>
          '.form_list_dynamic_html("rating",get_cert_list_sql(),'',true).'
          </td>
          <td><input name="year" size="6"></td>
        </tr></table>
        <p align="center"><input type="submit" value="'.str('MOVIE_ADD_BUTTON').'">
        </form>';    
}

// ----------------------------------------------------------------------------------
// Processes the input from the single movie form
// ----------------------------------------------------------------------------------

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

// ----------------------------------------------------------------------------------
// Processes the input from the multiple movie update form
// ----------------------------------------------------------------------------------  

function movie_update_multiple()
{
  $movie_list = $_REQUEST["movie"];
  $columns    = array();
  
  if (!empty($_REQUEST["year"]))
    $columns["YEAR"] = $_REQUEST["year"];
  if (!empty($_REQUEST["rating"]))
    $columns["CERTIFICATE"] = $_REQUEST["rating"];
  if (!empty($_REQUEST["synopsis"]))
    $columns["SYNOPSIS"] = $_REQUEST["synopsis"];

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
  
  $redirect_to = $_SESSION["last_search_page"];
  $redirect_to = url_add_param($redirect_to, 'message',   str('MOVIE_CHANGES_MADE'));
  $redirect_to = url_set_param($redirect_to ,'subaction', '');
  header("Location: $redirect_to");
 }

// ----------------------------------------------------------------------------------
// Extra Movie Information options
// ----------------------------------------------------------------------------------

function movie_info( $message = "")
{
  $list       = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
  $sites_list = get_parsers_list();
      
  if (!empty($_REQUEST["downloads"]))
  {
    set_rating_scheme_name($_REQUEST['scheme']);
    set_sys_pref('movie_info_script',$_REQUEST['site']);
    set_sys_pref('movie_check_enabled',$_REQUEST["downloads"]);
    $message = str('SAVE_SETTINGS_OK');
  }
  
  if (!empty($_REQUEST["refresh"]))
  {
    db_sqlcommand('update movies set year = null, certificate = null, match_pc = null, details_available = null, synopsis = null');
    db_sqlcommand('delete from directors_of_movie');
    db_sqlcommand('delete from actors_in_movie');
    db_sqlcommand('delete from genres_of_movie');
    media_refresh_now();
    $message = str('MOVIE_EXTRA_REFRESH_OK');
  }
  
  echo "<h1>".str('MOVIE_OPTIONS')."</h1>";
  message($message);
  
  form_start('index.php', 150, 'conn');
  form_hidden('section', 'MOVIE');
  form_hidden('action', 'INFO');
  echo '<p><b>'.str('MOVIE_EXTRA_DL_TITLE').'</b>
        <p>'.str('MOVIE_EXTRA_DL_PROMPT');
  form_list_static('site',str('MOVIE_EXTRA_SITE_PROMPT'),$sites_list,get_sys_pref('movie_info_script','movie_lovefilm.php'),false,false);
  form_list_dynamic('scheme',str('RATING_SCHEME_PROMPT'),get_rating_scheme_list_sql(),get_rating_scheme_name(),false,false,null);
  form_radio_static('downloads',str('STATUS'),$list,get_sys_pref('movie_check_enabled','YES'),false,true);
  form_submit(str('SAVE_SETTINGS'),2,'left',240);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'MOVIE');
  form_hidden('action', 'INFO');
  form_hidden('refresh','YES');
  echo '<p>&nbsp;<br><b>'.str('EXTRA_REFRESH_TITLE').'</b>
        <p>'.str('EXTRA_REFRESH_DETAILS').'
        <p><span class="stdformlabel">'.str('EXTRA_REFRESH_WARNING','"'.str('ORG_TITLE').'"').'</span>'.'<br>&nbsp;';
  form_submit(str('EXTRA_REFRESH_GO'),2,'Left',240);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'MEDIA');
  form_hidden('action', 'SEARCH');
  echo '<p>&nbsp;<br><b>'.str('SETUP_SEARCH_NEW_MEDIA').'</b>
        <p>'.str('MEDIA_SEARCH_PROMPT').'<br>&nbsp;';
  form_submit(str('SETUP_SEARCH_NEW_MEDIA'),2,'left',240);
  form_end();
}
  
// ----------------------------------------------------------------------------------
// Exports the movie details to a file
// ----------------------------------------------------------------------------------

function movie_export()
{
  $movie = array_pop(db_toarray("select * from movies where file_id = ".$_REQUEST["movie_id"]));
  $filename = substr($movie["DIRNAME"].$movie["FILENAME"],0,strrpos($movie["DIRNAME"].$movie["FILENAME"],'.')).".xml";

  if ( ! is_writable(dirname($filename)) )
    movie_display_info("!".str('MOVIE_EXPORT_NOT_WRITABLE'));
  elseif ( export_movie_to_xml($movie["FILE_ID"], $filename))
    movie_display_info(str('MOVIE_EXPORT_SUCCESS'));
  else
    movie_display_info("!".str('MOVIE_EXPORT_FAILURE'));
}
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
