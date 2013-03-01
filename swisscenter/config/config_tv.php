<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/media.php'));
require_once( realpath(dirname(__FILE__).'/../base/sched.php'));
require_once( realpath(dirname(__FILE__).'/../base/xml_sidecar.php'));
require_once( realpath(dirname(__FILE__).'/../video_obtain_info.php'));

// ----------------------------------------------------------------------------------
// Displays the details for TV episodes
// ----------------------------------------------------------------------------------

function tv_display_info( $message = '' )
{
  // Get actor/director/genre lists
  $tv_id       = $_REQUEST["tv_id"];
  $details     = db_row("select * from tv where file_id=".$tv_id);
  $actors      = db_toarray("select actor_name name from actors a, actors_in_tv ait where ait.actor_id = a.actor_id and tv_id=".$tv_id);
  $directors   = db_toarray("select director_name name from directors d, directors_of_tv dot where dot.director_id = d.director_id and tv_id=".$tv_id);
  $genres      = db_toarray("select genre_name name from genres g, genres_of_tv got where got.genre_id = g.genre_id and tv_id=".$tv_id);
  $languages   = db_toarray("select language name from languages l, languages_of_tv lot where lot.language_id = l.language_id and tv_id=".$tv_id);
  $filename    = $details["DIRNAME"].$details["FILENAME"];

  // Display tv shows that will be affected.
  echo '<h1>'.$details["PROGRAMME"].(empty($details["TITLE"]) ? '' : ' - '.$details["TITLE"]).'</h1><center>
         ( <a href="'.$_SESSION["last_search_page"].'">'.str('RETURN_TO_LIST').'</a>
         | <a href="?section=TV&action=UPDATE_FORM_SINGLE&tv[]='.$tv_id.'">'.str('DETAILS_EDIT').'</a>
         ) </center>';
        message($message);
  echo '<table class="form_select_tab" width="100%" cellspacing="4">
        <tr>
          <th colspan="2">'.str('PROGRAMME').'</th>
          <th>'.str('SERIES').'</th>
          <th>'.str('EPISODE').'</th>
        </tr><tr>
          <td colspan="2" valign="top">'.$details["PROGRAMME"].'</td>
          <td valign="top">'.$details["SERIES"].'</td>
          <td valign="top">'.$details["EPISODE"].'</td>
        </tr><tr>
          <th colspan="4">'.str('SYNOPSIS').'</th>
        </tr><tr>
          <td colspan="4">';

  $folder_img = file_albumart($filename);
  if (!empty($folder_img))
    echo img_gen($folder_img,100,200,false,false,false,array('hspace'=>4,'vspace'=>4,'align'=>'left') );

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
          <th colspan="2">'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td valign="top">'.get_cert_name(get_nearest_cert_in_scheme($details["CERTIFICATE"])).'&nbsp;</td>
          <td valign="top">'.$details["YEAR"].'</td>
          <td valign="top">'.nvl($details["EXTERNAL_RATING_PC"]/10,'-').'/10</td><td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    if (viewings_count(6, $details["FILE_ID"], $row["USER_ID"])>0)
      echo $row["NAME"].'<br>';

  echo '</td></tr>
        <tr><th colspan="4">'.str('LOCATION_ON_DISK').'</th></tr>
        <tr><td colspan="4">'.$details["DIRNAME"].$details["FILENAME"].'&nbsp;</td></tr>
        </table>
        <p align="center">';

  // Get tv information from online source
  echo '<table width="100%"><tr>
        <td align="center">
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="TV">
          <input type=hidden name="action" value="LOOKUP">
          <input type=hidden name="tv_id" value="'.$tv_id.'">
          <input type="Submit" name="subaction" value="'.str('LOOKUP_TV').'">
          </form>
        </td>
        </tr></table>';
}

// ----------------------------------------------------------------------------------
// Uses the selected parser script to update the tv episode details in the database
// ----------------------------------------------------------------------------------

function tv_lookup()
{
  $tv_id       = $_REQUEST["tv_id"];
  $details     = db_row("select * from tv where file_id=$tv_id");
  $filename    = $details["DIRNAME"].$details["FILENAME"];

  // Determine the part of the path to process for metadata about the episode.
  $media_loc   = db_value("select name from media_locations where location_id=".$details["LOCATION_ID"]);
  $meta_fsp    = mb_substr($details["DIRNAME"],mb_strlen($media_loc)+1).file_noext($details["FILENAME"]);
  $parsed      = get_tvseries_info( $meta_fsp );
  $details_str = $details["PROGRAMME"].$details["SERIES"].$details["EPISODE"].$details["TITLE"];
  $parsed_str  = $parsed["programme"].$parsed["series"].$parsed["episode"].$parsed["title"];

  // Clear old details first
  purge_tv_details($tv_id);

  // Lookup tv show using current database values
  $lookup = ParserTVLookup($tv_id, $filename, array('PROGRAMME' => $details["PROGRAMME"],
                                                    'SERIES'    => $details["SERIES"],
                                                    'EPISODE'   => $details["EPISODE"],
                                                    'TITLE'     => $details["TITLE"]));

  // Lookup tv show using values parsed from the filename (in case the parsing expressions have changed)
  if ( !$lookup && $parsed_str != '' && $parsed_str != $details_str )
  {
    send_to_log(5, "Re-parsed the filename to attempt the TV details search", array("Existing information"=>details, "Parsed from filename"=>$parsed));
    $lookup = ParserTVLookup($tv_id, $filename, array('PROGRAMME' => $parsed["programme"],
                                                      'SERIES'    => $parsed["series"],
                                                      'EPISODE'   => $parsed["episode"],
                                                      'TITLE'     => $parsed["title"]));
  }

  // Was lookup successful?
  if ( $lookup  )
  {
    // Export to XML
    if ( get_sys_pref('tv_xml_save','NO') == 'YES' )
      export_tv_to_xml($tv_id);

    if ( is_array($lookup) )
      tv_display_info( str('LOOKUP_SUCCESS_MISSING', implode(', ', $lookup)) );
    else
      tv_display_info( str('LOOKUP_SUCCESS') );
  }
  else
  {
    tv_display_info( '!'.str('LOOKUP_FAILURE') );
  }
}

/**
 * Displays all the details for an array of tv episodes in a table. Each row shows
 *a selection box, programme name, actors, directors, genres, year and rating.
 *
 * @param array $tv
 */

function tv_display_list($tv_list)
{
  echo '<table class="form_select_tab" width="100%"><tr>
          <th width="4%">&nbsp;</th>
          <th width="24%"> '.str('Title').' </th>
          <th width="18%"> '.str('Actor').' </th>
          <th width="18%"> '.str('Director').' </th>
          <th width="18%"> '.str('Genre').' </th>
          <th width="18%"> '.str('Spoken_Language').' </th>
        </tr></table>';

  foreach ($tv_list as $tv)
  {
    $actors    = db_col_to_list("select actor_name from actors a,actors_in_tv ait where a.actor_id=ait.actor_id ".
                                "and tv_id=".$tv["FILE_ID"]." order by 1");
    $directors = db_col_to_list("select director_name from directors d, directors_of_tv dot where d.director_id = dot.director_id ".
                                "and tv_id=".$tv["FILE_ID"]." order by 1");
    $genres    = db_col_to_list("select genre_name from genres g, genres_of_tv got where g.genre_id = got.genre_id ".
                                "and tv_id=".$tv["FILE_ID"]." order by 1");
    $languages = db_col_to_list("select language from languages l, languages_of_tv lot where l.language_id = lot.language_id ".
                                "and tv_id=".$tv["FILE_ID"]." order by 1");
    $cert      = db_value("select name from certificates where cert_id=".nvl($tv["CERTIFICATE"],-1));

    echo '<table class="form_select_tab" width="100%"><tr>
          <td valign="top" width="4%"><input type="checkbox" name="tv[]" value="'.$tv["FILE_ID"].'"></input></td>
          <td valign="top" width="24%">
             <a href="?section=tv&action=display_info&tv_id='.$tv["FILE_ID"].'">'.highlight($tv["PROGRAMME"], $_REQUEST["search"]).' - '.highlight($tv["TITLE"], $_REQUEST["search"]).'</a><br>'.
             str('SERIES').' : '.nvl($tv["SERIES"]).'<br>'.
             str('EPISODE').' : '.nvl($tv["EPISODE"]).'<br>'.
             str('YEAR').' : '.nvl($tv["YEAR"]).'<br>'.
             str('CERTIFICATE').' : '.nvl($cert).'<br>'.
             str('RATING').' : '.nvl($tv["EXTERNAL_RATING_PC"]/10,'-').'/10<br>'.
             str('VIEWED_BY').' : '.implode(', ',db_col_to_list("select u.name from users u, viewings v where ".
                                                       "v.user_id=u.user_id and v.media_type=".MEDIA_TYPE_TV." and v.media_id=".$tv["FILE_ID"])).'
           </td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$actors)).'</td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$directors)).'</td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$genres)).'</td>
           <td valign="top" width="18%">'.nvl(implode("<br>",$languages)).'</td>
          </tr></table>';
  }
}

function tv_display_thumbs($tv_list)
{
  $cnt = 0;
  $thumb_html = '';
  $title_html = '';

  foreach ($tv_list as $tv)
  {
    if ($cnt++ % 4 == 0)
    {
      echo '<table class="form_select_tab" width="100%"><tr>'.$thumb_html.'</tr><tr>'.$title_html.'</table>';
      $thumb_html = '';
      $title_html = '';
    }

    $img_url     = img_gen(file_albumart($tv["DIRNAME"].$tv["FILENAME"]) ,130,400,false,false,false,array('hspace'=>0,'vspace'=>4) );
    $edit_url    = '?section=tv&action=display_info&tv_id='.$tv["FILE_ID"];
    $thumb_html .= '<td valign="top"><input type="checkbox" name="tv[]" value="'.$tv["FILE_ID"].'"></input></td>
                    <td valign="middle"><a href="'.$edit_url.'">'.$img_url.'</a></td>';
    $title_html .= '<td width="25%" colspan="2" align="center" valign="middle"><a href="'.$edit_url.'">'.highlight($tv["PROGRAMME"], $_REQUEST["search"]).' - '.highlight($tv["TITLE"], $_REQUEST["search"]).(empty($tv["EPISODE"]) ? '' : ' '.str('EPISODE_SUFFIX',$tv["EPISODE"])).'</a></td>';
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
// Displays the tv episode details for editing
// ----------------------------------------------------------------------------------

function tv_display( $message = '')
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
  if (!empty($_REQUEST["prog"]) )
    $where .= "and t.programme = '".db_escape_str($_REQUEST["prog"])."' ";

  if (!empty($_REQUEST["search"]) )
    $where .= "and (t.programme like '%".db_escape_str($_REQUEST[search])."%' or t.title like '%".db_escape_str($_REQUEST[search])."%') ";

  if (!empty($_REQUEST["filter"]) )
  {
    switch ($_REQUEST["filter"])
    {
      case "NODETAILS"  : $where .= "and (ifnull(t.details_available,'N')='N')"; break;
      case "NOPROG"     : $where .= "and (ifnull(t.programme,'')='')"; break;
      case "NOSERIES"   : $where .= "and (ifnull(t.series,'')='')"; break;
      case "NOEPISODE"  : $where .= "and (ifnull(t.episode,'')='')"; break;
      case "NOSYNOPSIS" : $where .= "and (ifnull(t.synopsis,'')='')"; break;
      case "NOCERT"     : $where .= "and (ifnull(t.certificate,'')='')"; break;
      case "NOYEAR"     : $where .= "and (ifnull(t.year,'')='')"; break;
      case "NORATING"   : $where .= "and (ifnull(t.external_rating_pc,0)=0)"; break;
    }
  }

  if (empty($_REQUEST["sort"]) || $_REQUEST["sort"] == 'TITLE')
    $sort = "sort_programme, series, episode";
  else
    $sort = strtolower($_REQUEST["sort"]).' desc';

  // If the user has changed category, then shunt them back to page 1.
  if ($_REQUEST["last_where"] != $where)
  {
    $page = 1;
    $start = 0;
  }

  // SQL to fetch matching rows
  $tv_count = db_value("select count(*) from tv t, media_locations ml where ml.location_id = t.location_id ".$where);
  $tv_list  = db_toarray("select t.* from tv t, media_locations ml where ml.location_id = t.location_id ".$where.
                            " order by $sort, series, episode limit $start,$per_page");

  $list_type = get_sys_pref('CONFIG_VIDEO_LIST','THUMBS');
  echo '<h1>'.str('TV_DETAILS').'  ('.str('PAGE',$page).')</h1>';
  message($message);

  $this_url = '?last_where='.urlencode($where).'&filter='.$_REQUEST["filter"].'&search='.$_REQUEST["search"].'&prog='.$_REQUEST["prog"].'&sort='.$_REQUEST["sort"].'&section=TV&action=DISPLAY&page=';
  $filter_list = array( str('FILTER_MISSING_DETAILS')=>"NODETAILS"   , str('FILTER_MISSING_PROGRAMME')=>"NOPROG"
                      , str('FILTER_MISSING_SERIES')=>"NOSERIES"     , str('FILTER_MISSING_EPISODE')=>"NOEPISODE"
                      , str('FILTER_MISSING_SYNOPSIS')=>"NOSYNOPSIS" , str('FILTER_MISSING_CERT')=>"NOCERT"
                      , str('FILTER_MISSING_YEAR')=>"NOYEAR"         , str('FILTER_MISSING_RATING')=>"NORATING");

  $sort_list = array( str('TITLE')=>"TITLE", str('DISCOVERED')=>"DISCOVERED" );

  echo '<form enctype="multipart/form-data" action="" method="post">
        <table width="100%"><tr><td width="70%">';
  form_hidden('section','TV');
  form_hidden('action','DISPLAY');
  form_hidden('last_where',$where);
  echo  str('PROGRAMME').' :
        '.form_list_dynamic_html("prog","select distinct programme id, programme name from tv order by sort_programme",$_REQUEST["prog"],true,true,str('PROGRAMME_LIST_ALL')).'<br>
        '.str('FILTER').' :
        '.form_list_static_html("filter",$filter_list,$_REQUEST["filter"],true,true,str('VIEW_ALL')).'&nbsp;
        '.str('SORT').' :
        '.form_list_static_html("sort",$sort_list,$_REQUEST["sort"],false,true,false).'&nbsp;
        <a href="'.url_set_param($this_url,'list','LIST').'"><img align="absbottom" border="0"  src="/images/details.gif"></a>
        <a href="'.url_set_param($this_url,'list','THUMBS').'"><img align="absbottom" border="0" src="/images/thumbs.gif"></a>
        <img align="absbottom" border="0" src="/images/select_all.gif" onclick=\'handleClick("tv[]", true)\'>
        <img align="absbottom" border="0" src="/images/select_none.gif" onclick=\'handleClick("tv[]", false)\'>
        </td><td width="50%" align="right">
        '.str('SEARCH').' :
        <input name="search" value="'.$_REQUEST["search"].'" size=10>
        </td></tr></table>
        </form>';

  echo '<form enctype="multipart/form-data" action="" method="post">';
  form_hidden('section','TV');
  form_hidden('action','UPDATE');

  paginate($this_url,$tv_count,$per_page,$page);

  if ($list_type == 'THUMBS')
    tv_display_thumbs($tv_list);
  else
    tv_display_list($tv_list);

  paginate($this_url,$tv_count,$per_page,$page);

  echo '<p><table width="100%"><tr><td align="center">
        <input type="Submit" name="subaction" value="'.str('DETAILS_EDIT').'"> &nbsp;
        <input type="Submit" name="subaction" value="'.str('DETAILS_CLEAR').'"> &nbsp;
        </td></tr></table>
        </form>';
}

// ----------------------------------------------------------------------------------
// Calls the relevant function to make a modification to the tv episode details
// ----------------------------------------------------------------------------------

function tv_update()
{
  if ($_REQUEST["subaction"] == str('DETAILS_CLEAR'))
    tv_clear_details();
  elseif ($_REQUEST["subaction"] == str('DETAILS_EDIT'))
    tv_update_form();
  elseif (empty($_REQUEST["subaction"]))
    tv_display();
  else
    send_to_log(1,'Unknown value recieved for "subaction" parameter : '.$_REQUEST["subaction"]);
}

// ----------------------------------------------------------------------------------
// Clears the tv episode details
// ----------------------------------------------------------------------------------

function tv_clear_details()
{
  $tv_list = $_REQUEST["tv"];
  if (count($tv_list) == 0)
    tv_display("!".str('MOVIE_ERROR_NO_SELECT'));
  else
  {
    foreach ($tv_list as $value)
    {
      db_sqlcommand('delete from actors_in_tv where tv_id = '.$value);
      db_sqlcommand('delete from directors_of_tv where tv_id = '.$value);
      db_sqlcommand('delete from genres_of_tv where tv_id = '.$value);
      db_sqlcommand('delete from languages_of_tv where tv_id = '.$value);
      db_sqlcommand('update tv set year=null, certificate=null, external_rating_pc=null where file_id = '.$value);
    }
    scdb_remove_orphans();
    tv_display(str('DETAILS_CLEARED_OK'));
  }
}

// ----------------------------------------------------------------------------------
// Calls the releveant function to display the correct type of update form
// ----------------------------------------------------------------------------------

function tv_update_form()
{
  $tv_list = $_REQUEST["tv"];
  if (count($tv_list) == 0)
    tv_display("!".str('MOVIE_ERROR_NO_SELECT'));
  elseif (count($tv_list) == 1)
    tv_update_form_single();
  else
    tv_update_form_multiple($tv_list);
}

// ----------------------------------------------------------------------------------
// Displays a form for updating a single tv episode
// ----------------------------------------------------------------------------------

function tv_update_form_single()
{
  // Get actor/director/genre lists
  $tv_id       = $_REQUEST["tv"][0];
  $details     = db_row("select * from tv where file_id=".$tv_id);
  $actors      = db_toarray("select actor_name name, actor_id id from actors order by 1");
  $directors   = db_toarray("select director_name name, director_id id from directors order by 1");
  $genres      = db_toarray("select genre_name name, genre_id id from genres order by 1");
  $languages   = db_toarray("select language name, language_id id from languages order by 1");

  // Because we can't use subqueries for the above lists, we now need to determine which
  // rows should be shown as selected.
  $a_select = db_col_to_list("select actor_id from actors_in_tv where tv_id=".$tv_id);
  $d_select = db_col_to_list("select director_id from directors_of_tv where tv_id=".$tv_id);
  $g_select = db_col_to_list("select genre_id from genres_of_tv where tv_id=".$tv_id);
  $l_select = db_col_to_list("select language_id from languages_of_tv where tv_id=".$tv_id);

  // Display tv that will be affected.
  echo '<h1>'.str('DETAILS_EDIT').'</h1>
        <form enctype="multipart/form-data" action="" method="post">
        <input type="hidden" name="section" value="TV">
        <input type="hidden" name="action" value="UPDATE_SINGLE">
        <input type="hidden" name="tv[]" value="'.$tv_id.'">
        <table class="form_select_tab" width="100%" cellspacing=4>
        <tr>
        <th colspan="2" width="50%"><input type="hidden" name="update_programme" value="yes">'.str('PROGRAMME').'</th>
        <th width="25%"><input type="hidden" name="update_series" value="yes">'.str('SERIES').'</th>
        <th width="25%"><input type="hidden" name="update_episode" value="yes">'.str('EPISODE').'</th>
        </tr>
        <td colspan="2"><input name="programme" size="50" value="'.$details["PROGRAMME"].'"></td>
        <td><input name="series" size="6" value="'.$details["SERIES"].'"></td>
        <td><input name="episode" size="6" value="'.$details["EPISODE"].'"></td>
        <tr><th colspan="4" align="center"><input type="hidden" name="update_title" value="yes">'
        .str('TITLE').'
        </th></tr>
        <tr><td colspan="4"><input name="title" size="90" value="'.$details["TITLE"].'"></td></tr>
        <tr><td colspan="4" align="center">&nbsp<br>
        '.str('MOVIE_ADD_PROMPT').'
        <br>&nbsp;</td></tr><tr>
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
        <br>&nbsp;</td></tr><tr>
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
          <td>
          '.form_list_dynamic_html("cert",get_cert_list_sql(),$details["CERTIFICATE"],true).'
          </td>
          <td><input name="year" size="6" value="'.$details["YEAR"].'"></td>
          <td><input name="rating" size="6" value="'.($details["EXTERNAL_RATING_PC"]/10).'"</td>
          <td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    echo '<input type="checkbox" name="viewed[]" value="'.$row["USER_ID"].'" '.
         (viewings_count( MEDIA_TYPE_TV, $details["FILE_ID"], $row["USER_ID"])>0 ? 'checked' : '').
         '>'.$row["NAME"].'<br>';

  echo '</td>
        </tr></table>
        <p align="center"><input type="submit" value="'.str('MOVIE_ADD_BUTTON').'">
        </form>';
}

// ----------------------------------------------------------------------------------
// Displays a form for updating multiple tv episodes
// ----------------------------------------------------------------------------------

function tv_update_form_multiple( $tv_list )
{
  $programme = db_toarray("select distinct programme from tv where file_id in (".implode(',',$tv_list).")");
  $series    = db_toarray("select distinct series from tv where file_id in (".implode(',',$tv_list).")");
  $episode   = db_toarray("select distinct episode from tv where file_id in (".implode(',',$tv_list).")");
  $actors    = db_toarray("select actor_name name from actors order by 1");
  $directors = db_toarray("select director_name name from directors order by 1");
  $genres    = db_toarray("select genre_name name from genres order by 1");
  $languages = db_toarray("select language name from languages order by 1");
  $synopsis  = db_toarray("select distinct synopsis from tv where file_id in (".implode(',',$tv_list).")");
  $cert      = db_toarray("select distinct certificate from movies where file_id in (".implode(',',$tv_list).")");
  $year      = db_toarray("select distinct year from tv where file_id in (".implode(',',$tv_list).")");
  $rating    = db_toarray("select distinct external_rating_pc from movies where file_id in (".implode(',',$tv_list).")");

  // Display tv shows that will be affected.
  echo '<h1>'.str('MOVIE_UPD_TTILE').'</h1>
        <input type="hidden" name="update_title" value="no">
       <center>'.str('MOVIE_UPD_TEXT').'<p>';
       array_to_table(db_toarray("select concat(programme,IF(title IS NULL,'',concat(' - ',title))), series, episode, ".
                                 "filename from tv where file_id in (".implode(',',$tv_list).")"),str('Programme').','.str('Series').','.str('Episode').','.str('Filename'));

  echo '</center>
        <form enctype="multipart/form-data" action="" method="post">
        <input type="hidden" name="section" value="TV">
        <input type="hidden" name="action" value="UPDATE_MULTIPLE">';

  foreach ($tv_list as $tv_id)
    echo '<input type="hidden" name="tv[]" value="'.$tv_id.'">';

  echo '<table class="form_select_tab" width="100%" cellspacing="4"><tr>
        <tr>
        <th colspan="2" width="50%"><input type="checkbox" name="update_programme" value="yes">'.str('PROGRAMME').'</th>
        <th width="25%"><input type="checkbox" name="update_series" value="yes">'.str('SERIES').'</th>
        <th width="25%"><input type="checkbox" name="update_episode" value="yes">'.str('EPISODE').'</th>
        </tr><tr>
        <td colspan="2"><input name="programme" size="50" value="'.(count($programme)==1 ? $programme[0]["PROGRAMME"] : '').'"></td>
        <td><input name="series" size="6" value="'.(count($series)==1 ? $series[0]["SERIES"] : '').'"></td>
        <td><input name="episode" size="6" value="'.(count($episode)==1 ? $episode[0]["EPISODE"] : '').'"></td>
        </tr><tr>
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
        </select></td></tr></tr><tr><td colspan="3" align="center">
        '.str('MOVIE_NEW_PROMPT').'
        </td></tr><tr>
        <td width="25%"><input name="actor_new" size="25"></td>
        <td width="25%"><input name="director_new" size="25"></td>
        <td width="25%"><input name="genre_new" size="25"></td>
        <td width="25%"><input name="language_new" size="25"></td>
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
          <td>
          '.form_list_dynamic_html("cert",get_cert_list_sql(),(count($cert)==1 ? $cert[0]["CERTIFICATE"] : ''),true).'
          </td>
          <td><input name="year" size="6" value="'.(count($year)==1 ? $year[0]["YEAR"] : '').'"></td>
          <td><input name="rating" size="6" value="'.(count($rating)==1 ? ($rating[0]["EXTERNAL_RATING_PC"]/10) : '').'"</td>
          <td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    echo '<input type="checkbox" name="viewed[]" value="'.$row["USER_ID"].'">'.$row["NAME"].'<br>';

  echo '</td>
        </tr></table>
        <p align="center"><input type="submit" value="'.str('MOVIE_ADD_BUTTON').'">
        </form>';
}

// ----------------------------------------------------------------------------------
// Processes the input from the single tv episode form
// ----------------------------------------------------------------------------------

function tv_update_single()
{
  tv_update_multiple();
}

// ----------------------------------------------------------------------------------
// Processes the input from the multiple tv episode update form
// ----------------------------------------------------------------------------------

function tv_update_multiple()
{
  $tv_list = $_REQUEST["tv"];
  $columns = array();

  if ($_REQUEST["update_year"] == 'yes')
    $columns["YEAR"] = $_REQUEST["year"];
  if ($_REQUEST["update_cert"] == 'yes' && !empty($_REQUEST["cert"]))
    $columns["CERTIFICATE"] = $_REQUEST["cert"];
  if ($_REQUEST["update_synopsis"] == 'yes')
    $columns["SYNOPSIS"] = $_REQUEST["synopsis"];
  if ($_REQUEST["update_programme"] == 'yes')
    $columns["PROGRAMME"] = $_REQUEST["programme"];
  if ($_REQUEST["update_title"] == 'yes')
    $columns["TITLE"] = $_REQUEST["title"];
  if ($_REQUEST["update_series"] == 'yes' && is_numeric($_REQUEST["series"]))
    $columns["SERIES"] = $_REQUEST["series"];
  if ($_REQUEST["update_episode"] == 'yes' && is_numeric($_REQUEST["episode"]))
    $columns["EPISODE"] = $_REQUEST["episode"];
  if ($_REQUEST["update_rating"] == 'yes')
    $columns["EXTERNAL_RATING_PC"] = $_REQUEST["rating"] * 10;

  // Add Actors/Genres/Directors?
  if ($_REQUEST["update_actors"] == 'yes')
  {
    db_sqlcommand("delete from actors_in_tv where tv_id in (".implode(',',$tv_list).")");
    if (count($_REQUEST["actors"]) >0)
      scdb_add_tv_actors($tv_list,$_REQUEST["actors"]);
    if (!empty($_REQUEST["actor_new"]))
      scdb_add_tv_actors($tv_list, explode(',',$_REQUEST["actor_new"]));
  }

  if ($_REQUEST["update_directors"] == 'yes')
  {
    db_sqlcommand("delete from directors_of_tv where tv_id in (".implode(',',$tv_list).")");
    if (count($_REQUEST["directors"]) >0)
      scdb_add_tv_directors($tv_list,$_REQUEST["directors"]);
    if (!empty($_REQUEST["director_new"]))
      scdb_add_tv_directors($tv_list, explode(',',$_REQUEST["director_new"]));
  }

  if ($_REQUEST["update_genres"] == 'yes')
  {
    db_sqlcommand("delete from genres_of_tv where tv_id in (".implode(',',$tv_list).")");
    if (count($_REQUEST["genres"]) >0)
      scdb_add_tv_genres($tv_list,$_REQUEST["genres"]);
    if (!empty($_REQUEST["genre_new"]))
      scdb_add_tv_genres($tv_list, explode(',',$_REQUEST["genre_new"]));
  }

  if ($_REQUEST["update_languages"] == 'yes')
  {
    db_sqlcommand("delete from languages_of_tv where tv_id in (".implode(',',$tv_list).")");
    if (count($_REQUEST["languages"]) >0)
      scdb_add_tv_languages($tv_list,$_REQUEST["languages"]);
    if (!empty($_REQUEST["language_new"]))
      scdb_add_tv_languages($tv_list, explode(',',$_REQUEST["language_new"]));
  }

  // Update the TV attributes
  scdb_set_tv_attribs($tv_list, $columns);

  // Process the "Viewed" checkboxes
  if ($_REQUEST["update_viewed"] == 'yes')
  {
    foreach ( db_toarray("select * from users order by name") as $row)
    {
      if (in_array($row["USER_ID"],$_REQUEST["viewed"]))
      {
        // Set viewed status for these movies for this user
        foreach ($tv_list as $tv)
          if (viewings_count(MEDIA_TYPE_TV, $tv, $row["USER_ID"]) == 0)
            db_insert_row('viewings',array("user_id"=>$row["USER_ID"], "media_type"=>MEDIA_TYPE_TV, "media_id"=>$tv, "total_viewings"=>1));
      }
      else
      {
        // Remove all viewing information about these tv shows for this user
        db_sqlcommand("delete from viewings where media_type=".MEDIA_TYPE_TV." and user_id=".$row["USER_ID"].
                      " and media_id in (".implode(',',$tv_list).")");
      }
    }
  }

  // Export to XML
  if ( get_sys_pref('tv_xml_save','NO') == 'YES' )
    foreach ($tv_list as $tv)
      export_tv_to_xml($tv);

  $redirect_to = $_SESSION["last_search_page"];
  $redirect_to = url_add_param($redirect_to, 'message', str('MOVIE_CHANGES_MADE'));
  $redirect_to = url_set_param($redirect_to ,'subaction', '');
  header("Location: $redirect_to");
 }

// ----------------------------------------------------------------------------------
// Extra TV Show Information options
// ----------------------------------------------------------------------------------

function tv_info( $message = "")
{
  $list    = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
  $parsers = get_parsers_list('tv');

  if (!empty($_REQUEST["downloads"]))
  {
    set_rating_scheme_name($_REQUEST['scheme']);
    set_sys_pref('tv_parser_retry_count', $_REQUEST['parser_retry_count']);

    $retrycount = $_REQUEST['parser_retry_count'];
    for ($i = 0; $i < count(ParserConstants :: $allTvConstants); $i++)
    {
      $parser_pref = array();
      for ($x = 0; $x < $retrycount; $x++)
      {
        if ( isset($_REQUEST['parser_' . $x . '_' . ParserConstants :: $allTvConstants[$i]['ID']]) )
        $parser_pref[] = $_REQUEST['parser_' . $x . '_' . ParserConstants :: $allTvConstants[$i]['ID']];
      }
      set_sys_pref('tv_parser_' . ParserConstants :: $allTvConstants[$i]['ID'], implode(',', $parser_pref));
    }

    set_sys_pref('tv_check_enabled',$_REQUEST["downloads"]);
    set_sys_pref('tv_xml_save',$_REQUEST["xml_save"]);
    set_sys_pref('tv_use_banners',$_REQUEST["use_banner"]);
    set_sys_pref('tvseries_convert_dots_to_spaces',$_REQUEST["dots"]);
    $message = str('SAVE_SETTINGS_OK');
  }

  if (!empty($_REQUEST["refresh"]))
  {
    db_sqlcommand('update tv set year = null, certificate = null, details_available = null, synopsis = null');
    db_sqlcommand('delete from directors_of_tv');
    db_sqlcommand('delete from actors_in_tv');
    db_sqlcommand('delete from genres_of_tv');
    db_sqlcommand('delete from languages_of_tv');
    set_sys_pref('MEDIA_SCAN_TYPE','MEDIA');
    set_sys_pref('MEDIA_SCAN_MEDIA_TYPE',MEDIA_TYPE_TV);
    media_refresh_now();
    $message = str('MOVIE_EXTRA_REFRESH_OK');
  }

  if (!empty($_REQUEST["export"]))
  {
    set_sys_pref('EXPORT_XML','TV');
    run_background('media_export_xml.php');
    $message = str('MOVIE_EXTRA_EXPORT_OK');
  }

  echo "<h1>".str('TV_OPTIONS')."</h1>";
  message($message);

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'TV');
  form_hidden('action', 'INFO');
  echo '<p><b>'.str('MOVIE_EXTRA_DL_TITLE').'</b>
           <p>'.str('MOVIE_EXTRA_DL_PROMPT');

  $retrycount = get_sys_pref('tv_parser_retry_count', 1);
  echo '<tr>';
  for ($i = 0; $i < count(ParserConstants :: $allTvConstants); $i++)
  {
    echo '</tr><tr>';
    $parser_pref = explode(',', get_sys_pref('tv_parser_' . ParserConstants :: $allTvConstants[$i]['ID'],
                                                            ParserConstants :: $allTvConstants[$i]['DEFAULT']));
    $supported_parsers = array();
    for ($y = 0; $y < $retrycount; $y++)
    {
      // Add no parser option
      $supported_parsers[NoParser :: getName()] = 'NoParser';

      // Determine all parsers that support this property
      foreach ($parsers as $parser)
      {
        $tvparser = new $parser();
        if ($tvparser->isSupportedProperty(ParserConstants :: $allTvConstants[$i]['ID']))
          $supported_parsers[$tvparser->getName()] = $parser;
      }

      // Display parsers for this property
      if ( count($supported_parsers) > 1 )
      {
        echo (($y == 0) ? ('<td>' . form_prompt(str(ParserConstants :: $allTvConstants[$i]['TEXT']), true) . '</td>' ) : '');
        echo '<td>' . form_list_static_html('parser_' . $y . '_' . ParserConstants :: $allTvConstants[$i]['ID'],
                                            $supported_parsers,
                                            (isset($parser_pref[$y]) ? $parser_pref[$y] : 'NoParser'),
                                            false, false, false) .
             '</td>';
      }
      else
      {
        echo '<input type="hidden" name="parser_'.$y.'_'.ParserConstants :: $allTvConstants[$i]['ID'].' value="NoParser">';
      }
    }
  }

  form_list_static('parser_retry_count', str('PARSER_RETRIES'), array( 1=>1, 2, 3, 4, 5), get_sys_pref('tv_parser_retry_count', 1), false, true, false);
  form_list_dynamic('scheme', str('RATING_SCHEME_PROMPT'), get_rating_scheme_list_sql(), get_rating_scheme_name(), false, false, null);
  form_radio_static('downloads', str('STATUS'), $list, get_sys_pref('tv_check_enabled','YES'), false, true);
  form_radio_static('xml_save', str('XML_SAVE'), $list, get_sys_pref('tv_xml_save','NO'), false, true);
  form_radio_static('use_banner', str('USE_BANNER'), $list, get_sys_pref('tv_use_banners','YES'), false, true);
  form_submit(str('SAVE_SETTINGS'),2,'left',150);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'TV');
  form_hidden('action', 'INFO');
  form_hidden('refresh', 'YES');
  echo '<p>&nbsp;<br><b>'.str('EXTRA_REFRESH_TITLE').'</b>
        <p>'.str('EXTRA_REFRESH_DETAILS').'
        <p><span class="stdformlabel">'.str('EXTRA_REFRESH_WARNING','"'.str('TV_DETAILS').'"').'</span>'.'<br>&nbsp;';
  form_submit(str('EXTRA_REFRESH_GO'),2,'Left',240);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'TV');
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
