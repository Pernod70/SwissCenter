<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/media.php'));
require_once( realpath(dirname(__FILE__).'/../base/sched.php'));
require_once( realpath(dirname(__FILE__).'/../base/xml_sidecar.php'));
require_once( realpath(dirname(__FILE__).'/../video_obtain_info.php'));

// ----------------------------------------------------------------------------------
// Displays the details for movies
// ----------------------------------------------------------------------------------

function movie_display_info( $message = '' )
{
  // Get actor/director/genre lists
  $movie_id    = $_REQUEST["movie_id"];
  $details     = db_row("select * from movies where file_id=".$movie_id);
  $actors      = db_toarray("select actor_name name from actors a, actors_in_movie aim where aim.actor_id = a.actor_id and movie_id=".$movie_id);
  $directors   = db_toarray("select director_name name from directors d, directors_of_movie dom where dom.director_id = d.director_id and movie_id=".$movie_id);
  $genres      = db_toarray("select genre_name name from genres g, genres_of_movie gom where gom.genre_id = g.genre_id and movie_id=".$movie_id);
  $languages   = db_toarray("select language name from languages l, languages_of_movie lom where lom.language_id = l.language_id and movie_id=".$movie_id);
  $filename    = $details["DIRNAME"].$details["FILENAME"];

  // Display movies that will be affected.
  echo '<h1>'.$details["TITLE"].'</h1><center>
         ( <a href="'.$_SESSION["last_search_page"].'">'.str('RETURN_TO_LIST').'</a>
         | <a href="?section=MOVIE&action=UPDATE_FORM_SINGLE&movie[]='.$movie_id.'">'.str('DETAILS_EDIT').'</a>
         ) </center>';
        message($message);
  echo '<table class="form_select_tab" width="100%" cellspacing=4><tr>
          <th colspan="4">'.str('SYNOPSIS').'</th>
        </tr><tr>
          <td colspan="4">';

  // DVD Video details are stored in the parent folder
  if ( strtoupper($details["FILENAME"]) == 'VIDEO_TS.IFO' )
    $filename = rtrim($details["DIRNAME"],'/').".dvd";

  $folder_img = file_albumart($filename);
  if (!empty($folder_img))
    echo img_gen($folder_img,100,200,false,false,false,array('hspace'=>0,'vspace'=>4,'align'=>'left') );

  echo  $details["SYNOPSIS"].'<br>&nbsp;</td>
        </tr><tr>
        <th width="25%">'.str('ACTOR').'</th>
        <th width="25%">'.str('DIRECTOR').'</th>
        <th width="25%">'.str('GENRE').'</th>
        <th width="25%">'.str('SPOKEN_LANGUAGE').'</th>
        </tr><tr>
        <td valign="top">';

        foreach ($actors as $name)
          echo $name["NAME"].'<br>';

  echo '<br>&nbsp;</td><td valign="top">';

        foreach ($directors as $name)
          echo $name["NAME"].'<br>';

  echo '<br>&nbsp;</td><td valign="top">';

        foreach ($genres as $name)
          echo $name["NAME"].'<br>';

  echo '<br>&nbsp;</td><td valign="top">';

        foreach ($languages as $name)
          echo $name["NAME"].'<br>';

  echo '<br>&nbsp;</td></tr></tr><tr>
          <th>'.str('CERTIFICATE').'</th>
          <th>'.str('YEAR').'</th>
          <th>'.str('RATING').'</th>
          <th>'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td valign="top">'.get_cert_name(get_nearest_cert_in_scheme($details["CERTIFICATE"])).'&nbsp;</td>
          <td valign="top">'.$details["YEAR"].'</td>
          <td valign="top">'.nvl($details["EXTERNAL_RATING_PC"]/10,'-').'/10</td><td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    if (viewings_count(3, $details["FILE_ID"], $row["USER_ID"])>0)
      echo $row["NAME"].'<br>';

  echo '</td></tr><tr>
          <th colspan="4">'.str('TRAILER_LOCATION').'</th>
        </tr><tr>
          <td colspan="4">'.(empty($details["TRAILER"]) ? '' : '<a href="'.$details["TRAILER"].'" target="_blank">').$details["TRAILER"].'&nbsp;</td>
        </tr><tr>
          <th colspan="4">'.str('LOCATION_ON_DISK').'</th>
        </tr><tr>
          <td colspan="4">'.$details["DIRNAME"].$details["FILENAME"].'&nbsp;</td>
        </tr></table>
        <p align="center">';

  // Get movie information from online source
  echo '<table width="100%"><tr>
        <td align="center">
          <form enctype="multipart/form-data" action="" method="post">
          <input type="hidden" name="section" value="MOVIE">
          <input type="hidden" name="action" value="LOOKUP">
          <input type="hidden" name="movie_id" value="'.$movie_id.'">
          <input type="Submit" name="subaction" value="'.str('LOOKUP_MOVIE').'">
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
  $details  = db_row("select * from movies where file_id=$movie_id");

  // DVD Video details are stored in the parent folder
  if ( strtoupper($details["FILENAME"]) == 'VIDEO_TS.IFO' )
    $filename = rtrim($details["DIRNAME"],'/').".xml";
  else
    $filename = $details["DIRNAME"].$details["FILENAME"];

  // Clear old details first
  purge_movie_details($movie_id);

  // Lookup movie
  $lookup = ParserMovieLookup($movie_id, $filename, array('TITLE'   => $details["TITLE"],
                                                          'YEAR'    => $details["YEAR"],
                                                          'IMDB_ID' => $details["IMDB_ID"]));

  // Was lookup successful?
  if ( $lookup )
  {
    // Export to XML
    if ( get_sys_pref('movie_xml_save','NO') == 'YES' )
      export_video_to_xml($movie_id);

    if ( is_array($lookup) )
      movie_display_info( str('LOOKUP_SUCCESS_MISSING', implode(', ', $lookup)) );
    else
      movie_display_info( str('LOOKUP_SUCCESS') );
  }
  else
    movie_display_info( '!'.str('LOOKUP_FAILURE') );
}

/**
 * Displays all the details for an array of movies in a table. Each row shows
 *a selection box, film name, actors, directors, genres, year and rating.
 *
 * @param array $movie
 */

function movie_display_list($movie_list)
{
  echo '<table class="form_select_tab" width="100%"><tr>
          <th width="4%">&nbsp;</th>
          <th width="24%"> '.str('Title').' </th>
          <th width="18%"> '.str('Actor').' </th>
          <th width="18%"> '.str('Director').' </th>
          <th width="18%"> '.str('Genre').' </th>
          <th width="18%"> '.str('Spoken_Language').' </th>
        </tr></table>';

  foreach ($movie_list as $movie)
  {
    $actors    = db_col_to_list("select actor_name from actors a,actors_in_movie aim where a.actor_id=aim.actor_id ".
                                "and movie_id=".$movie["FILE_ID"]." order by 1");
    $directors = db_col_to_list("select director_name from directors d, directors_of_movie dom where d.director_id = dom.director_id ".
                                "and movie_id=".$movie["FILE_ID"]." order by 1");
    $genres    = db_col_to_list("select genre_name from genres g, genres_of_movie gom where g.genre_id = gom.genre_id ".
                                "and movie_id=".$movie["FILE_ID"]." order by 1");
    $languages = db_col_to_list("select language from languages l, languages_of_movie lom where l.language_id = lom.language_id ".
                                "and movie_id=".$movie["FILE_ID"]." order by 1");
    $cert      = db_value("select name from certificates where cert_id=".nvl($movie["CERTIFICATE"],-1));

    echo '<table class="form_select_tab" width="100%"><tr>
          <td valign="top" width="4%"><input type="checkbox" name="movie[]" value="'.$movie["FILE_ID"].'"></input></td>
          <td valign="top" width="24%">
             <a href="?section=movie&action=display_info&movie_id='.$movie["FILE_ID"].'">'.highlight($movie["TITLE"], un_magic_quote($_REQUEST["search"])).'</a><br>'.
             str('IMDB_ID').' : '.nvl($movie["IMDB_ID"]).'<br>'.
             str('CERTIFICATE').' : '.nvl($cert).'<br>'.
             str('YEAR').' : '.nvl($movie["YEAR"]).'<br>'.
             str('RATING').' : '.nvl($movie["EXTERNAL_RATING_PC"]/10,'-').'/10<br>'.
             str('VIEWED_BY').' : '.implode(', ',db_col_to_list("select u.name from users u, viewings v where ".
                                                       "v.user_id=u.user_id and v.media_type=".MEDIA_TYPE_VIDEO." and v.media_id=".$movie["FILE_ID"])).'
           </td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$actors)).'</td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$directors)).'</td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$genres)).'</td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$languages)).'</td>
          </tr></table>';
  }
}

function movie_display_thumbs($movie_list)
{
  $cnt = 0;
  $thumb_html = '';
  $title_html = '';

  foreach ($movie_list as $movie)
  {
    if ($cnt++ % 4 == 0)
    {
      echo '<table class="form_select_tab" width="100%"><tr>'.$thumb_html.'</tr><tr>'.$title_html.'</table>';
      $thumb_html = '';
      $title_html = '';
    }

    // Form dummy filename for DVD folders
    $filename = $movie["DIRNAME"].$movie["FILENAME"];
    if (isdir($filename))
      $filename = str_suffix($filename,'/').basename($filename).'.dvd';

    $img_url     = img_gen(file_albumart($filename) ,130,400,false,false,false,array('hspace'=>0,'vspace'=>4) );
    $edit_url    = '?section=movie&action=display_info&movie_id='.$movie["FILE_ID"];
    $thumb_html .= '<td valign="top"><input type="checkbox" name="movie[]" value="'.$movie["FILE_ID"].'"></input></td>
                    <td valign="middle"><a href="'.$edit_url.'">'.$img_url.'</a></td>';
    $title_html .= '<td width="25%" colspan="2" align="center" valign="middle"><a href="'.$edit_url.'">'.highlight($movie["TITLE"], $_REQUEST["search"]).'</a></td>';
  }

  // and last row...
  for ($i=0; $i<(4 - $cnt % 4); $i++)
  {
    $thumb_html .= '<td></td>';
    $title_html .= '<td width="25%"></td>';
  }
  echo '<table class="form_select_tab" width="100%"><tr>'.$thumb_html.'</tr><tr>'.$title_html.'</table>';
}

// ----------------------------------------------------------------------------------
// Displays the movie details for editing
// ----------------------------------------------------------------------------------

function movie_display( $message = '')
{
  $_SESSION["last_search_page"] = url_remove_param(current_url( true ), 'message');
  $per_page    = get_user_pref('PC_PAGINATION',20);
  $page        = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);
  $start       = ($page-1)*$per_page;
  $where       = '';

  if (empty($message) && isset($_REQUEST["message"]))
    $message = urldecode($_REQUEST["message"]);

  // Changing List type?
  if (!empty($_REQUEST["list"]) )
    set_sys_pref('CONFIG_VIDEO_LIST',$_REQUEST["list"]);

    // Extra filters on the media (for categories and search).
  if (!empty($_REQUEST["cat_id"]) )
    $where .= "and ml.cat_id = $_REQUEST[cat_id] ";

  if (!empty($_REQUEST["search"]) )
    $where .= "and m.title like '%".db_escape_str(un_magic_quote($_REQUEST[search]))."%' ";

  if (!empty($_REQUEST["filter"]) )
  {
    switch ($_REQUEST["filter"])
    {
      case "NODETAILS"  : $where .= "and (ifnull(m.details_available,'N')='N')"; break;
      case "NOSYNOPSIS" : $where .= "and (ifnull(m.synopsis,'')='')"; break;
      case "NOCERT"     : $where .= "and (ifnull(m.certificate,'')='')"; break;
      case "NOYEAR"     : $where .= "and (ifnull(m.year,'')='')"; break;
      case "NORATING"   : $where .= "and (ifnull(m.external_rating_pc,0)=0)"; break;
      case "NOTRAILER"  : $where .= "and (ifnull(m.trailer,'')='')"; break;
      case "NOIMDB"     : $where .= "and (ifnull(m.imdb_id,'')='')"; break;
    }
  }

  if (empty($_REQUEST["sort"]) || $_REQUEST["sort"] == 'TITLE')
    $sort = "sort_title";
  else
    $sort = strtolower($_REQUEST["sort"]).' desc';

  // If the user has changed category, then shunt them back to page 1.
  if (un_magic_quote($_REQUEST["last_where"]) != $where)
  {
    $page = 1;
    $start = 0;
  }

  // SQL to fetch matching rows
  $movie_count = db_value("select count(*) from movies m, media_locations ml where ml.location_id = m.location_id ".$where);
  $movie_list  = db_toarray("select m.* from movies m, media_locations ml where ml.location_id = m.location_id ".$where.
                            " order by $sort limit $start,$per_page");

  $list_type = get_sys_pref('CONFIG_VIDEO_LIST','THUMBS');
  echo '<h1>'.str('ORG_TITLE').'  ('.str('PAGE',$page).')</h1>';
  message($message);

  $this_url = '?last_where='.urlencode($where).'&filter='.$_REQUEST["filter"].'&search='.un_magic_quote($_REQUEST["search"]).'&cat_id='.$_REQUEST["cat_id"].'&sort='.$_REQUEST["sort"].'&section=MOVIE&action=DISPLAY&page=';
  $filter_list = array( str('FILTER_MISSING_DETAILS')=>"NODETAILS" , str('FILTER_MISSING_SYNOPSIS')=>"NOSYNOPSIS"
                      , str('FILTER_MISSING_CERT')=>"NOCERT"       , str('FILTER_MISSING_YEAR')=>"NOYEAR"
                      , str('FILTER_MISSING_RATING')=>"NORATING"   , str('FILTER_MISSING_TRAILER')=>"NOTRAILER"
                      , str('FILTER_MISSING_IMDB')=>"NOIMDB" );

  $sort_list = array( str('TITLE')=>"TITLE", str('DISCOVERED')=>"DISCOVERED" );

  echo '<form enctype="multipart/form-data" action="" method="post">
        <table width="100%"><tr><td width="70%">';
  form_hidden('section','MOVIE');
  form_hidden('action','DISPLAY');
  form_hidden('last_where',$where);
  echo  str('CATEGORY').' :
        '.form_list_dynamic_html("cat_id","select distinct c.cat_id,c.cat_name from categories c left join media_locations ml on c.cat_id=ml.cat_id where ml.media_type=".MEDIA_TYPE_VIDEO." order by c.cat_name",$_REQUEST["cat_id"],true,true,str('CATEGORY_LIST_ALL')).'<br>
        '.str('FILTER').' :
        '.form_list_static_html("filter",$filter_list,$_REQUEST["filter"],true,true,str('VIEW_ALL')).'&nbsp;
        '.str('SORT').' :
        '.form_list_static_html("sort",$sort_list,$_REQUEST["sort"],false,true,false).'&nbsp;
        <a href="'.url_set_param($this_url,'list','LIST').'"><img align="absbottom" border="0"  src="/images/details.gif"></a>
        <a href="'.url_set_param($this_url,'list','THUMBS').'"><img align="absbottom" border="0" src="/images/thumbs.gif"></a>
        <img align="absbottom" border="0" src="/images/select_all.gif" onclick=\'handleClick("movie[]", true)\'>
        <img align="absbottom" border="0" src="/images/select_none.gif" onclick=\'handleClick("movie[]", false)\'>
        </td><td width="50%" align="right">
        '.str('SEARCH').' :
        <input name="search" value="'.un_magic_quote($_REQUEST["search"]).'" size=10>
        </td></tr></table>
        </form>';

  echo '<form enctype="multipart/form-data" action="" method="post">';
  form_hidden('section','MOVIE');
  form_hidden('action','UPDATE');

  paginate($this_url,$movie_count,$per_page,$page);

  if ($list_type == 'THUMBS')
    movie_display_thumbs($movie_list);
  else
    movie_display_list($movie_list);

  paginate($this_url,$movie_count,$per_page,$page);

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
  $movie_list = $_REQUEST["movie"];
  if (count($movie_list) == 0)
    movie_display("!".str('MOVIE_ERROR_NO_SELECT'));
  else
  {
    foreach ($movie_list as $value)
    {
      db_sqlcommand('delete from actors_in_movie where movie_id = '.$value);
      db_sqlcommand('delete from directors_of_movie where movie_id = '.$value);
      db_sqlcommand('delete from genres_of_movie where movie_id = '.$value);
      db_sqlcommand('delete from languages_of_movie where movie_id = '.$value);
      db_sqlcommand('update movies set year=null, certificate=null, external_rating_pc=null, trailer=null where file_id = '.$value);
    }
    scdb_remove_orphans();
    movie_display(str('DETAILS_CLEARED_OK'));
  }
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
  $details     = db_row("select * from movies where file_id=".$movie_id);
  $actors      = db_toarray("select actor_name name, actor_id id from actors order by 1");
  $directors   = db_toarray("select director_name name, director_id id from directors order by 1");
  $genres      = db_toarray("select genre_name name, genre_id id from genres order by 1");
  $languages   = db_toarray("select language name, language_id id from languages order by 1");

  // Because we can't use subqueries for the above lists, we now need to determine which
  // rows should be shown as selected.
  $a_select = db_col_to_list("select actor_id from actors_in_movie where movie_id=".$movie_id);
  $d_select = db_col_to_list("select director_id from directors_of_movie where movie_id=".$movie_id);
  $g_select = db_col_to_list("select genre_id from genres_of_movie where movie_id=".$movie_id);
  $l_select = db_col_to_list("select language_id from languages_of_movie where movie_id=".$movie_id);

  // Display movies that will be affected.
  echo '<h1>'.str('DETAILS_EDIT').'</h1>
        <form enctype="multipart/form-data" action="" method="post">
        <input type="hidden" name="section" value="MOVIE">
        <input type="hidden" name="action" value="UPDATE_SINGLE">
        <input type="hidden" name="movie[]" value="'.$movie_id.'">
        <table class="form_select_tab" width="100%" cellspacing="4">
        <tr>
        <th colspan="3" align="center"><input type="hidden" name="update_title" value="yes">'
        .str('TITLE').'</th>
        <th><input type="hidden" name="update_imdb" value="yes">'.str('IMDB_ID').'</th>
        </tr><tr>
        <td colspan="3"><input name="title" size="90" value="'.$details["TITLE"].'"></td>
        <td><input name="imdb_id" size="10" value="'.$details["IMDB_ID"].'"></td>
        </tr><tr>
        <td colspan="4" align="center">&nbsp<br>
        '.str('MOVIE_ADD_PROMPT').'
        <br>&nbsp;</td>
        </tr><tr>
        <th width="25%"><input type="hidden" name="update_actors" value="yes">'.str('ACTOR').'</th>
        <th width="25%"><input type="hidden" name="update_directors" value="yes">'.str('DIRECTOR').'</th>
        <th width="25%"><input type="hidden" name="update_genres" value="yes">'.str('GENRE').'</th>
        <th width="25%"><input type="hidden" name="update_languages" value="yes">'.str('SPOKEN_LANGUAGE').'</th>
        </tr><tr>
        <td><select name="actors[]" multiple size="8">
        '.list_option_elements($actors, $a_select).'
        </select></td><td><select name="directors[]" multiple size="8">
        '.list_option_elements($directors,$d_select).'
        </select></td><td><select name="genres[]" multiple size="8">
        '.list_option_elements($genres,$g_select).'
        </select></td><td><select name="languages[]" multiple size="8">
        '.list_option_elements($languages,$l_select).'
        </select></td></tr></tr><tr><td colspan="4" align="center">&nbsp<br>
        '.str('MOVIE_NEW_PROMPT').'
        <br>&nbsp;</td>
        </tr><tr>
        <td width="25%"><input name="actor_new" size=25></td>
        <td width="25%"><input name="director_new" size=25></td>
        <td width="25%"><input name="genre_new" size=25></td>
        <td width="25%"><input name="language_new" size=25></td>
        </tr><tr>
          <th colspan="4"><input type="hidden" name="update_synopsis" value="yes">'.str('Synopsis').'</th>
        </tr><tr>
          <td colspan="4">'.form_text_html('synopsis',90,6,$details["SYNOPSIS"],true).'</td>
        </tr><tr>
          <th><input type="hidden" name="update_cert" value="yes">'.str('CERTIFICATE').'</th>
          <th><input type="hidden" name="update_year" value="yes">'.str('YEAR').'</th>
          <th><input type="hidden" name="update_rating" value="yes">'.str('RATING').'</th>
          <th><input type="hidden" name="update_viewed" value="yes">'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td>'.form_list_dynamic_html("cert",get_cert_list_sql(),$details["CERTIFICATE"],true).'</td>
          <td><input name="year" size="6" value="'.$details["YEAR"].'"></td>
          <td><input name="rating" size="6" value="'.($details["EXTERNAL_RATING_PC"]/10).'"</td>
          <td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    echo '<input type="checkbox" name="viewed[]" value="'.$row["USER_ID"].'" '.
         (viewings_count( MEDIA_TYPE_VIDEO, $details["FILE_ID"], $row["USER_ID"])>0 ? 'checked' : '').
         '>'.$row["NAME"].'<br>';

  echo '  </td>
        </tr><tr>
          <th colspan="4" align="center"><input type="hidden" name="update_trailer" value="yes">'.str('TRAILER_LOCATION').'</th>
        </tr><tr>
          <td colspan="4"><input name="trailer" size="90" value="'.$details["TRAILER"].'"></td>
        </tr>';

  echo '</table>
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
  $languages = db_toarray("select language name from languages order by 1");
  $synopsis  = db_toarray("select distinct synopsis from movies where file_id in (".implode(',',$movie_list).")");
  $cert      = db_toarray("select distinct certificate from movies where file_id in (".implode(',',$movie_list).")");
  $year      = db_toarray("select distinct year from movies where file_id in (".implode(',',$movie_list).")");
  $rating    = db_toarray("select distinct external_rating_pc from movies where file_id in (".implode(',',$movie_list).")");
  $trailer   = db_toarray("select distinct trailer from movies where file_id in (".implode(',',$movie_list).")");

  // Display movies that will be affected.
  echo '<h1>'.str('MOVIE_UPD_TTILE').'</h1>
        <input type="hidden" name="update_title" value="no">
        <input type="hidden" name="update_imdb" value="no">
       <center>'.str('MOVIE_UPD_TEXT').'<p>';
       array_to_table(db_toarray("select title, filename from movies where file_id in (".implode(',',$movie_list).")"),str('Title').','.str('Filename'));

  echo '</center>
        <form enctype="multipart/form-data" action="" method="post">
        <input type="hidden" name="section" value="MOVIE">
        <input type="hidden" name="action" value="UPDATE_MULTIPLE">';

  foreach ($movie_list as $movie_id)
    echo '<input type="hidden" name="movie[]" value="'.$movie_id.'">';

  echo '<table class="form_select_tab" width="100%" cellspacing="4"><tr>
        <th width="25%"><input type="checkbox" name="update_actors" value="yes">'.str('ACTOR').'</th>
        <th width="25%"><input type="checkbox" name="update_directors" value="yes">'.str('DIRECTOR').'</th>
        <th width="25%"><input type="checkbox" name="update_genres" value="yes">'.str('GENRE').'</th>
        <th width="25%"><input type="checkbox" name="update_languages" value="yes">'.str('SPOKEN_LANGUAGE').'</th>
        </tr><tr>
        <tr><td colspan="4" align="center">'.str('MOVIE_ADD_PROMPT').'</td></tr>
        <td><select name="actors[]" multiple size="8">
        '.list_option_elements($actors).'
        </select></td><td><select name="directors[]" multiple size="8">
        '.list_option_elements($directors).'
        </select></td><td><select name="genres[]" multiple size="8">
        '.list_option_elements($genres).'
        </select></td><td><select name="languages[]" multiple size="8">
        '.list_option_elements($languages).'
        </select></td></tr></tr><tr><td colspan="4" align="center">
        '.str('MOVIE_NEW_PROMPT').'
        </td></tr><tr>
        <td width="25%"><input name="actor_new" size=25></td>
        <td width="25%"><input name="director_new" size=25></td>
        <td width="25%"><input name="genre_new" size=25></td>
        <td width="25%"><input name="language_new" size=25></td>
        </tr><tr>
          <th colspan="4"><input type="checkbox" name="update_synopsis" value="yes">'.str('Synopsis').'</th>
        </tr><tr>
          <td colspan="4">'.form_text_html('synopsis',90,6,(count($synopsis)==1 ? $synopsis[0]["SYNOPSIS"] : ''),true).'</td>
        </tr><tr>
          <th><input type="checkbox" name="update_cert" value="yes">'.str('CERTIFICATE').'</th>
          <th><input type="checkbox" name="update_year" value="yes">'.str('YEAR').'</th>
          <th><input type="checkbox" name="update_rating" value="yes">'.str('RATING').'</th>
          <th><input type="checkbox" name="update_viewed" value="yes">'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td>'.form_list_dynamic_html("cert",get_cert_list_sql(),(count($cert)==1 ? $cert[0]["CERTIFICATE"] : ''),true).'</td>
          <td><input name="year" size="6" value="'.(count($year)==1 ? $year[0]["YEAR"] : '').'"></td>
          <td><input name="rating" size="6" value="'.(count($rating)==1 ? ($rating[0]["EXTERNAL_RATING_PC"]/10) : '').'"</td>
          <td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    echo '<input type="checkbox" name="viewed[]" value="'.$row["USER_ID"].'">'.$row["NAME"].'<br>';

  echo '  </td>
        </tr><tr>
          <th colspan="4"><input type="checkbox" name="update_trailer" value="yes">'.str('TRAILER_LOCATION').'</th>
        </tr><tr>
          <td colspan="4"><input name="trailer" size="90" value="'.(count($trailer)==1 ? $trailer[0]["TRAILER"] : '').'"></td>
        </tr>';

  echo '</table>
        <p align="center"><input type="submit" value="'.str('MOVIE_ADD_BUTTON').'">
        </form>';
}

// ----------------------------------------------------------------------------------
// Processes the input from the single movie form
// ----------------------------------------------------------------------------------

function movie_update_single()
{
  movie_update_multiple();
}

// ----------------------------------------------------------------------------------
// Processes the input from the multiple movie update form
// ----------------------------------------------------------------------------------

function movie_update_multiple()
{
  $movie_list = $_REQUEST["movie"];
  $columns    = array();

  if ($_REQUEST["update_year"] == 'yes')
    $columns["YEAR"] = $_REQUEST["year"];
  if ($_REQUEST["update_cert"] == 'yes' && !empty($_REQUEST["cert"]))
    $columns["CERTIFICATE"] = $_REQUEST["cert"];
  if ($_REQUEST["update_synopsis"] == 'yes')
    $columns["SYNOPSIS"] = $_REQUEST["synopsis"];
  if ($_REQUEST["update_title"] == 'yes')
    $columns["TITLE"] = $_REQUEST["title"];
  if ($_REQUEST["update_rating"] == 'yes')
    $columns["EXTERNAL_RATING_PC"] = $_REQUEST["rating"] * 10;
  if ($_REQUEST["update_trailer"] == 'yes')
    $columns["TRAILER"] = $_REQUEST["trailer"];
  if ($_REQUEST["update_imdb"] == 'yes')
    $columns["IMDB_ID"] = preg_get('/(\d+)/', $_REQUEST["imdb_id"]);

  // Add Actors/Genres/Directors?
  if ($_REQUEST["update_actors"] == 'yes')
  {
    db_sqlcommand("delete from actors_in_movie where movie_id in (".implode(',',$movie_list).")");
    if (count($_REQUEST["actors"]) >0)
      scdb_add_actors($movie_list,un_magic_quote($_REQUEST["actors"]));
    if (!empty($_REQUEST["actor_new"]))
      scdb_add_actors($movie_list, explode(',',un_magic_quote($_REQUEST["actor_new"])));
  }

  if ($_REQUEST["update_directors"] == 'yes')
  {
    db_sqlcommand("delete from directors_of_movie where movie_id in (".implode(',',$movie_list).")");
    if (count($_REQUEST["directors"]) >0)
      scdb_add_directors($movie_list,un_magic_quote($_REQUEST["directors"]));
    if (!empty($_REQUEST["director_new"]))
      scdb_add_directors($movie_list, explode(',',un_magic_quote($_REQUEST["director_new"])));
  }

  if ($_REQUEST["update_genres"] == 'yes')
  {
    db_sqlcommand("delete from genres_of_movie where movie_id in (".implode(',',$movie_list).")");
    if (count($_REQUEST["genres"]) >0)
      scdb_add_genres($movie_list,un_magic_quote($_REQUEST["genres"]));
    if (!empty($_REQUEST["genre_new"]))
      scdb_add_genres($movie_list, explode(',',un_magic_quote($_REQUEST["genre_new"])));
  }

  if ($_REQUEST["update_languages"] == 'yes')
  {
    db_sqlcommand("delete from languages_of_movie where movie_id in (".implode(',',$movie_list).")");
    if (count($_REQUEST["languages"]) >0)
      scdb_add_languages($movie_list,un_magic_quote($_REQUEST["languages"]));
    if (!empty($_REQUEST["language_new"]))
      scdb_add_languages($movie_list, explode(',',un_magic_quote($_REQUEST["language_new"])));
  }

  // Update the MOVIES attributes
  scdb_set_movie_attribs($movie_list, $columns);

  // Process the "Viewed" checkboxes
  if ($_REQUEST["update_viewed"] == 'yes')
  {
    foreach ( db_toarray("select * from users order by name") as $row)
    {
      if (in_array($row["USER_ID"],$_REQUEST["viewed"]))
      {
        // Set viewed status for these movies for this user
        foreach ($movie_list as $movie)
        {
          if (viewings_count(MEDIA_TYPE_VIDEO, $movie, $row["USER_ID"]) == 0)
            db_insert_row('viewings',array("user_id"=>$row["USER_ID"], "media_type"=>MEDIA_TYPE_VIDEO, "media_id"=>$movie, "total_viewings"=>1));
        }
      }
      else
      {
        // Remove all viewing information about these movies for this user
        db_sqlcommand("delete from viewings where media_type=".MEDIA_TYPE_VIDEO." and user_id=".$row["USER_ID"].
                      " and media_id in (".implode(',',$movie_list).")");
      }
    }
  }

  // Export to XML
  if ( get_sys_pref('movie_xml_save','NO') == 'YES' )
    foreach ($movie_list as $movie)
      export_video_to_xml($movie);

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
  $list    = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
  $parsers = get_parsers_list('movie');

  if (!empty($_REQUEST["downloads"]))
  {
    set_rating_scheme_name($_REQUEST['scheme']);
    set_sys_pref('movie_parser_retry_count', $_REQUEST['parser_retry_count']);
    set_sys_pref('use_smartsearch', $_REQUEST['use_smartsearch']);

    $retrycount = $_REQUEST['parser_retry_count'];
    for ($i = 0; $i < count(ParserConstants :: $allMovieConstants); $i++)
    {
      $parser_pref = array();
      for ($x = 0; $x < $retrycount; $x++)
      {
        if ( isset($_REQUEST['parser_' . $x . '_' . ParserConstants :: $allMovieConstants[$i]['ID']]) )
        $parser_pref[] = $_REQUEST['parser_' . $x . '_' . ParserConstants :: $allMovieConstants[$i]['ID']];
      }
      set_sys_pref('movie_parser_' . ParserConstants :: $allMovieConstants[$i]['ID'], implode(',', $parser_pref));
    }

    set_sys_pref('movie_check_enabled', $_REQUEST["downloads"]);
    set_sys_pref('movie_xml_save', $_REQUEST["xml_save"]);
    $message = str('SAVE_SETTINGS_OK');
  }

  if (!empty($_REQUEST["refresh"]))
  {
    db_sqlcommand('update movies set year = null, certificate = null, match_pc = null, details_available = null, synopsis = null, external_rating_pc = null');
    db_sqlcommand('delete from directors_of_movie');
    db_sqlcommand('delete from actors_in_movie');
    db_sqlcommand('delete from genres_of_movie');
    db_sqlcommand('delete from languages_of_movie');
    set_sys_pref('MEDIA_SCAN_TYPE','MEDIA');
    set_sys_pref('MEDIA_SCAN_MEDIA_TYPE',MEDIA_TYPE_VIDEO);
    media_refresh_now();
    $message = str('MOVIE_EXTRA_REFRESH_OK');
  }

  if (!empty($_REQUEST["export"]))
  {
    set_sys_pref('EXPORT_XML','VIDEO');
    run_background('media_export_xml.php');
    $message = str('MOVIE_EXTRA_EXPORT_OK');
  }

  echo "<h1>".str('MOVIE_OPTIONS')."</h1>";
  message($message);

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'MOVIE');
  form_hidden('action', 'INFO');
  echo '<p><b>'.str('MOVIE_EXTRA_DL_TITLE').'</b>
           <p>'.str('MOVIE_EXTRA_DL_PROMPT');

  $retrycount = get_sys_pref('movie_parser_retry_count', 1);
  echo '<tr>';
  for ($i = 0; $i < count(ParserConstants :: $allMovieConstants); $i++)
  {
    echo '</tr><tr>';
    $parser_pref = explode(',', get_sys_pref('movie_parser_' . ParserConstants :: $allMovieConstants[$i]['ID'],
                                                               ParserConstants :: $allMovieConstants[$i]['DEFAULT']));
    $supported_parsers = array();
    for ($y = 0; $y < $retrycount; $y++)
    {
      // Add no parser option
      $supported_parsers[NoParser :: getName()] = 'NoParser';

      // Determine all parsers that support this property
      foreach ($parsers as $parser)
      {
        $movieparser = new $parser();
        if ($movieparser->isSupportedProperty(ParserConstants :: $allMovieConstants[$i]['ID']))
          $supported_parsers[$movieparser->getName()] = $parser;
      }

      // Display parsers for this property
      if ( count($supported_parsers) > 1 )
      {
        echo (($y == 0) ? ('<td>' . form_prompt(str(ParserConstants :: $allMovieConstants[$i]['TEXT']), true) . '</td>' ) : '');
        echo '<td>' . form_list_static_html('parser_' . $y . '_' . ParserConstants :: $allMovieConstants[$i]['ID'],
                                            $supported_parsers,
                                            (isset($parser_pref[$y]) ? $parser_pref[$y] : 'NoParser'),
                                            false, false, false) .
             '</td>';
      }
      else
      {
        echo '<input type="hidden" name="parser_'.$y.'_'.ParserConstants :: $allMovieConstants[$i]['ID'].' value="NoParser">';
      }
    }
  }

  form_list_static('parser_retry_count', str('PARSER_RETRIES'), array( 1=>1, 2, 3, 4, 5), get_sys_pref('movie_parser_retry_count', 1), false, true, false);
  form_radio_static('use_smartsearch', str('MOVIE_PARSER_SMARTSEARCH'), $list, get_sys_pref('use_smartsearch', 'YES'), false, true);
  form_list_dynamic('scheme', str('RATING_SCHEME_PROMPT'), get_rating_scheme_list_sql(), get_rating_scheme_name(), false, false, null);
  form_radio_static('downloads', str('STATUS'), $list, get_sys_pref('movie_check_enabled', 'YES'), false, true);
  form_radio_static('xml_save', str('XML_SAVE'), $list, get_sys_pref('movie_xml_save', 'NO'), false, true);
  form_submit(str('SAVE_SETTINGS'), 2, 'left', 150);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'MOVIE');
  form_hidden('action', 'INFO');
  form_hidden('refresh', 'YES');
  echo '<p>&nbsp;<br><b>'.str('EXTRA_REFRESH_TITLE').'</b>
        <p>'.str('EXTRA_REFRESH_DETAILS').'
        <p><span class="stdformlabel">'.str('EXTRA_REFRESH_WARNING','"'.str('ORG_TITLE').'"').'</span>'.'<br>&nbsp;';
  form_submit(str('EXTRA_REFRESH_GO'),2,'Left',240);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'MOVIE');
  form_hidden('action', 'INFO');
  form_hidden('export', 'YES');
  echo '<p>&nbsp;<br><b>'.str('EXTRA_EXPORT_TITLE').'</b>
        <p>'.str('EXTRA_EXPORT_DETAILS');
  form_submit(str('EXTRA_EXPORT_GO'),2,'Left',240);
  form_end();
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
