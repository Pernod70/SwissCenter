<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/media.php'));
require_once( realpath(dirname(__FILE__).'/../base/sched.php'));
require_once( realpath(dirname(__FILE__).'/../base/xml_sidecar.php'));

// ----------------------------------------------------------------------------------
// Get an array of online tv parsers for displaying in a form drop-down list.
// ----------------------------------------------------------------------------------

function get_parsers_list()
{
  $parsers = dir_to_array( realpath(dirname(__FILE__).'/../ext/parsers/tv') , '.*\.php' );
  $sites_list = array();

  foreach ($parsers as $file)
    $sites_list[file_noext($file)] = basename($file);

  return $sites_list;
}

// ----------------------------------------------------------------------------------
// Displays the details for TV episodes
// ----------------------------------------------------------------------------------

function tv_display_info(  $message = '' )
{
  // Get actor/director/genre lists
  $tv_id       = $_REQUEST["tv_id"];
  $details     = db_toarray("select * from tv where file_id=".$tv_id);
  $actors      = db_toarray("select actor_name name from actors a, actors_in_tv ait where ait.actor_id = a.actor_id and tv_id=".$tv_id);
  $directors   = db_toarray("select director_name name from directors d, directors_of_tv dot where dot.director_id = d.director_id and tv_id=".$tv_id);
  $genres      = db_toarray("select genre_name name from genres g, genres_of_tv got where got.genre_id = g.genre_id and tv_id=".$tv_id);
  $filename    = $details[0]["DIRNAME"].$details[0]["FILENAME"];
  $sites_list  = get_parsers_list();
  $exists_js   = '';

  // If a tv XML file already exists, use javascript to ask the user whether to overwrite it.
  if ( file_exists(substr($filename,0,strrpos($filename,'.')).".xml") )
    $exists_js = 'onClick="javascript:return confirm(\''.addslashes(str_replace('"','',str('MOVIE_EXPORT_OVERWRITE'))).'?\')"';

  // Display tv shows that will be affected.
  echo '<h1>'.$details[0]["PROGRAMME"].(empty($details[0]["TITLE"]) ? '' : ' - '.$details[0]["TITLE"]).'</h1><center>
         ( <a href="'.$_SESSION["last_search_page"].'">'.str('RETURN_TO_LIST').'</a>
         | <a href="?section=TV&action=UPDATE_FORM_SINGLE&tv[]='.$tv_id.'">'.str('DETAILS_EDIT').'</a>
         <!--| <a href="?section=TV&action=EXPORT&tv_id='.$tv_id.'" '.$exists_js.'>'.str('DETAILS_EXPORT').'</a> -->
         )
        </center>';
        message($message);
  echo '<table class="form_select_tab" width="100%" cellspacing=4>
        <tr>
          <th>'.str('PROGRAMME').'</th>
          <th>'.str('SERIES').'</th>
          <th>'.str('EPISODE').'</th>
        </tr><tr>
          <td valign=top>'.$details[0]["PROGRAMME"].'</td>
          <td valign=top>'.$details[0]["SERIES"].'</td>
          <td valign=top>'.$details[0]["EPISODE"].'</td>
        </tr><tr>
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
        <td valign=top>';

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
          <th>'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td valign=top>'.get_cert_name(get_nearest_cert_in_scheme($details[0]["CERTIFICATE"])).'&nbsp;</td>
          <td valign=top>'.$details[0]["YEAR"].'</td><td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    if (viewings_count(6, $details[0]["FILE_ID"], $row["USER_ID"])>0)
      echo $row["NAME"].'<br>';

  echo '</td></tr>
        <tr><th colspan=3>'.str('LOCATION_ON_DISK').'</th></tr>
        <tr><td colspan=3>'.$details[0]["DIRNAME"].$details[0]["FILENAME"].'&nbsp;</td></tr>
        </table>
        <p align="center">';

  // Get tv information from online source
  echo '<table width="100%"><tr>
        <td align="center">
          <form enctype="multipart/form-data" action="" method="post">
          <input type=hidden name="section" value="TV">
          <input type=hidden name="action" value="LOOKUP">
          <input type=hidden name="tv_id" value="'.$tv_id.'">
          '.form_list_static_html('parser',$sites_list, get_sys_pref('tv_info_script','www.TheTVDB.com.php'),false,false,false).'
          &nbsp; <input type="Submit" name="subaction" value="'.str('LOOKUP_TV').'"> &nbsp;
          </form>
        </td>
        </tr></table>';
}

// ----------------------------------------------------------------------------------
// Uses the selected parser script to update the tv episode details in the database
// ----------------------------------------------------------------------------------

function tv_lookup()
{
  require_once( realpath(dirname(__FILE__).'/../video_obtain_info.php'));

  $tv_id    = $_REQUEST["tv_id"];
  $details  = db_toarray("select * from tv where file_id=$tv_id");
  $filename = $details[0]["DIRNAME"].$details[0]["FILENAME"];

  // Clear old details first
  purge_tv_details($tv_id);

  // Lookup tv show
  if ( extra_get_tv_details($tv_id, $filename, $details[0]["PROGRAMME"], $details[0]["SERIES"], $details[0]["EPISODE"], $details[0]["TITLE"]) )
  {
    // Export to XML
    if ( get_sys_pref('tv_xml_save','NO') == 'YES' )
      export_tv_to_xml($tv_id);
    tv_display_info( str('LOOKUP_SUCCESS') );
  }
  else
    tv_display_info( '!'.str('LOOKUP_FAILURE') );
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
          <th width="33%"> '.str('Title').' </th>
          <th width="21%"> '.str('Actor').' </th>
          <th width="21%"> '.str('Director').' </th>
          <th width="21%"> '.str('Genre').' </th>
        </tr></table>';

  foreach ($tv_list as $tv)
  {
    $actors    = db_col_to_list("select actor_name from actors a,actors_in_tv ait where a.actor_id=ait.actor_id ".
                                "and tv_id=$tv[FILE_ID] order by 1");
    $directors = db_col_to_list("select director_name from directors d, directors_of_tv dot where d.director_id = dot.director_id ".
                                "and tv_id=$tv[FILE_ID] order by 1");
    $genres    = db_col_to_list("select genre_name from genres g, genres_of_tv got where g.genre_id = got.genre_id ".
                                "and tv_id=$tv[FILE_ID] order by 1");
    $cert      = db_value("select name from certificates where cert_id=".nvl($tv["CERTIFICATE"],-1));

    echo '<table class="form_select_tab" width="100%"><tr>
          <td valign="top" width="4%"><input type="checkbox" name="tv[]" value="'.$tv["FILE_ID"].'"></input></td>
          <td valign="top" width="33%">
             <a href="?section=tv&action=display_info&tv_id='.$tv["FILE_ID"].'">'.$tv["PROGRAMME"].' - '.$tv["TITLE"].'</a><br>
             Series : '.nvl($tv["SERIES"]).'<br>
             Episode : '.nvl($tv["EPISODE"]).'<br>
             Year : '.nvl($tv["YEAR"]).'<br>
             Certificate : '.nvl($cert).'<br>
             Viewed by : '.implode(', ',db_col_to_list("select u.name from users u, viewings v where ".
                                                       "v.user_id=u.user_id and v.media_type=".MEDIA_TYPE_TV." and v.media_id=".$tv["FILE_ID"])).'
           </td>
           <td valign="top" width="21%">'.nvl(implode("<br>",$actors)).'</td>
           <td valign="top" width="21%">'.nvl(implode("<br>",$directors)).'</td>
           <td valign="top" width="21%">'.nvl(implode("<br>",$genres)).'</td>
          </tr></table>';
  }
}

function tv_display_thumbs($tv_list)
{
  $cnt = 0;

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
    $title_html .= '<td width="25%" colspan="2" align="center" valign="middle"><a href="'.$edit_url.'">'.$tv["PROGRAMME"].' - '.$tv["TITLE"].(empty($tv["EPISODE"]) ? '' : str('EPISODE_SUFFIX',$tv["EPISODE"])).'</a></td>';
  }

  // and last row...
  echo '<table class="form_select_tab" width="100%"><tr>'.$thumb_html.'</tr><tr>'.$title_html.'</table>';
}

// ----------------------------------------------------------------------------------
// Displays the tv episode details for editing
// ----------------------------------------------------------------------------------

function tv_display( $message = '')
{
  $_SESSION["last_search_page"] = current_url( true );
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
    $where .= "and (t.programme like '%$_REQUEST[search]%' or t.title like '%$_REQUEST[search]%') ";

  // If the user has changed category, then shunt them back to page 1.
  if (un_magic_quote($_REQUEST["last_where"]) != $where)
  {
    $page = 1;
    $start = 0;
  }

  // SQL to fetch matching rows
  $tv_count = db_value("select count(*) from tv t, media_locations ml where ml.location_id = t.location_id ".$where);
  $tv_list  = db_toarray("select t.* from tv t, media_locations ml where ml.location_id = t.location_id ".$where.
                            " order by programme, series, episode limit $start,$per_page");

  $list_type = get_sys_pref('CONFIG_VIDEO_LIST','THUMBS');
  echo '<h1>'.str('TV_DETAILS').'  ('.str('PAGE',$page).')</h1>';
  message($message);

  $this_url = '?last_where='.urlencode($where).'&search='.$_REQUEST["search"].'&cat_id='.$_REQUEST["cat_id"].'&section=TV&action=DISPLAY&page=';

  echo '<form enctype="multipart/form-data" action="" method="post">
        <table width="100%"><tr><td width="70%">';
  form_hidden('section','TV');
  form_hidden('action','DISPLAY');
  form_hidden('last_where',$where);
  echo  str('CATEGORY').' :
        '.form_list_dynamic_html("cat_id","select distinct c.cat_id,c.cat_name from categories c left join media_locations ml on c.cat_id=ml.cat_id where ml.media_type=6 order by c.cat_name",$_REQUEST["cat_id"],true,true,str('CATEGORY_LIST_ALL')).'&nbsp;
        <a href="'.url_set_param($this_url,'list','LIST').'"><img align="absbottom" border="0"  src="/images/details.gif"></a>
        <a href="'.url_set_param($this_url,'list','THUMBS').'"><img align="absbottom" border="0" src="/images/thumbs.gif"></a>
        <img align="absbottom" border="0" src="/images/select_all.gif" onclick=\'handleClick("tv[]", true)\'>
        <img align="absbottom" border="0" src="/images/select_none.gif" onclick=\'handleClick("tv[]", false)\'>
        </td><td width"50%" align="right">
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
      db_sqlcommand('update tv set year=null,certificate=null where file_id = '.$value);
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
  $details     = db_toarray("select * from tv where file_id=".$tv_id);
  $actors      = db_toarray("select actor_name name, actor_id id from actors order by 1");
  $directors   = db_toarray("select director_name name, director_id id from directors order by 1");
  $genres      = db_toarray("select genre_name name, genre_id id from genres order by 1");

  // Because we can't use subqueries for the above lists, we now need to determine which
  // rows should be shown as selected.
  $a_select = db_col_to_list("select actor_id from actors_in_tv where tv_id=".$tv_id);
  $d_select = db_col_to_list("select director_id from directors_of_tv where tv_id=".$tv_id);
  $g_select = db_col_to_list("select genre_id from genres_of_tv where tv_id=".$tv_id);

  // Display tv that will be affected.
  echo '<h1>'.str('DETAILS_EDIT').'</h1>
        <form enctype="multipart/form-data" action="" method="post">
        <input type=hidden name="section" value="TV">
        <input type=hidden name="action" value="UPDATE_SINGLE">
        <input type=hidden name="tv[]" value="'.$tv_id.'">
        <table class="form_select_tab" width="100%" cellspacing=4>
        <tr>
        <th width="33%"><input type="hidden" name="update_programme" value="yes">'.str('PROGRAMME').'</th>
        <th width="33%"><input type="hidden" name="update_series" value="yes">'.str('SERIES').'</th>
        <th width="33%"><input type="hidden" name="update_episode" value="yes">'.str('EPISODE').'</th>
        </tr>
        <td><input name="programme" size=25 value="'.$details[0]["PROGRAMME"].'"></td>
        <td><input name="series" size=6 value="'.$details[0]["SERIES"].'"></td>
        <td><input name="episode" size=6 value="'.$details[0]["EPISODE"].'"></td>
        <tr><th colspan="3" align=center"><input type="hidden" name="update_title" value="yes">'
        .str('TITLE').'
        </th></tr>
        <tr><td colspan="3"><input name="title" size=90 value="'.$details[0]["TITLE"].'"></td></tr>
        <tr><td colspan="3" align="center">&nbsp<br>
        '.str('MOVIE_ADD_PROMPT').'
        <br>&nbsp;</td></tr><tr>
        <th width="33%"><input type="hidden" name="update_actors" value="yes">'.str('ACTOR').'</th>
        <th width="33%"><input type="hidden" name="update_directors" value="yes">'.str('DIRECTOR').'</th>
        <th width="33%"><input type="hidden" name="update_genres" value="yes">'.str('GENRE').'</th>
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
          <th colspan="3"><input type="hidden" name="update_synopsis" value="yes">'.str('Synopsis').'</th>
        </tr><tr>
          <td colspan="3">'.form_text_html('synopsis',90,6,$details[0]["SYNOPSIS"],true).'</td>
        </tr><tr>
          <th><input type="hidden" name="update_rating" value="yes">'.str('CERTIFICATE').'</th>
          <th><input type="hidden" name="update_year" value="yes">'.str('YEAR').'</th>
          <th><input type="hidden" name="update_viewed" value="yes">'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td>
          '.form_list_dynamic_html("rating",get_cert_list_sql(),$details[0]["CERTIFICATE"],true).'
          </td>
          <td><input name="year" size="6" value="'.$details[0]["YEAR"].'"></td>
          <td>';

  foreach ( db_toarray("select * from users order by name") as $row)
    echo '<input type="checkbox" name="viewed[]" value="'.$row["USER_ID"].'" '.
         (viewings_count( 6, $details[0]["FILE_ID"], $row["USER_ID"])>0 ? 'checked' : '').
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
  $synopsis  = db_toarray("select distinct synopsis from tv where file_id in (".implode(',',$tv_list).")");
  $cert      = db_toarray("select distinct certificate from movies where file_id in (".implode(',',$tv_list).")");
  $year      = db_toarray("select distinct year from tv where file_id in (".implode(',',$tv_list).")");

  // Display tv shows that will be affected.
  echo '<h1>'.str('MOVIE_UPD_TTILE').'</h1>
        <input type="hidden" name="update_title" value="no">
       <center>'.str('MOVIE_UPD_TEXT').'<p>';
       array_to_table(db_toarray("select concat(programme,IF(title IS NULL,'',concat(' - ',title))), series, episode, ".
                                 "filename from tv where file_id in (".implode(',',$tv_list).")"),str('Programme').','.str('Series').','.str('Episode').','.str('Filename'));

  echo '</center>
        <form enctype="multipart/form-data" action="" method="post">
        <input type=hidden name="section" value="TV">
        <input type=hidden name="action" value="UPDATE_MULTIPLE">';

  foreach ($tv_list as $tv_id)
    echo '<input type=hidden name="tv[]" value="'.$tv_id.'">';

  echo '<table class="form_select_tab" width="100%" cellspacing=4><tr>
        <tr>
        <th width="33%"><input type="checkbox" name="update_programme" value="yes">'.str('PROGRAMME').'</th>
        <th width="33%"><input type="checkbox" name="update_series" value="yes">'.str('SERIES').'</th>
        <th width="33%"><input type="checkbox" name="update_episode" value="yes">'.str('EPISODE').'</th>
        </tr><tr>
        <td><input name="programme" size=25 value="'.(count($programme)==1 ? $programme[0]["PROGRAMME"] : '').'"></td>
        <td><input name="series" size=6 value="'.(count($series)==1 ? $series[0]["SERIES"] : '').'"></td>
        <td><input name="episode" size=6 value="'.(count($episode)==1 ? $episode[0]["EPISODE"] : '').'"></td>
        </tr><tr>
        <th width="33%"><input type="checkbox" name="update_actors" value="yes">'.str('ACTOR').'</th>
        <th width="33%"><input type="checkbox" name="update_directors" value="yes">'.str('DIRECTOR').'</th>
        <th width="33%"><input type="checkbox" name="update_genres" value="yes">'.str('GENRE').'</th>
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
          <th colspan="3"><input type="checkbox" name="update_synopsis" value="yes">'.str('Synopsis').'</th>
        </tr><tr>
          <td colspan="3">'.form_text_html('synopsis',90,6,(count($synopsis)==1 ? $synopsis[0]["SYNOPSIS"] : ''),true).'</td>
        </tr><tr>
          <th><input type="checkbox" name="update_rating" value="yes">'.str('CERTIFICATE').'</th>
          <th><input type="checkbox" name="update_year" value="yes">'.str('YEAR').'</th>
          <th><input type="checkbox" name="update_viewed" value="yes">'.str('VIEWED_BY').'</th>
        </tr><tr>
          <td>
          '.form_list_dynamic_html("rating",get_cert_list_sql(),(count($cert)==1 ? $cert[0]["CERTIFICATE"] : ''),true).'
          </td>
          <td><input name="year" size="6" value="'.(count($year)==1 ? $year[0]["YEAR"] : '').'"></td>
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
  // Clear the existing details for this tv show, as they will be reinserted by
  // calling the update_multiple function.
  $tv_id = $_REQUEST["tv"][0];
  db_sqlcommand("delete from actors_in_tv where tv_id=".$tv_id);
  db_sqlcommand("delete from directors_of_tv where tv_id=".$tv_id);
  db_sqlcommand("delete from genres_of_tv where tv_id=".$tv_id);
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
  if ($_REQUEST["update_rating"] == 'yes' && !empty($_REQUEST["rating"]))
    $columns["CERTIFICATE"] = $_REQUEST["rating"];
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

  // Update the TV table?
  if (count($columns)>0)
  {
    $columns["DETAILS_AVAILABLE"] = 'Y';
    scdb_set_tv_attribs($tv_list, $columns);
  }

  // Add Actors/Genres/Directors?
  if ($_REQUEST["update_actors"] == 'yes')
  {
    if (count($_REQUEST["actors"]) >0)
      scdb_add_tv_actors($tv_list,un_magic_quote($_REQUEST["actors"]));
    if (!empty($_REQUEST["actor_new"]))
      scdb_add_tv_actors($tv_list, explode(',',un_magic_quote($_REQUEST["actor_new"])));
  }

  if ($_REQUEST["update_directors"] == 'yes')
  {
    if (count($_REQUEST["directors"]) >0)
      scdb_add_tv_directors($tv_list,un_magic_quote($_REQUEST["directors"]));
    if (!empty($_REQUEST["director_new"]))
      scdb_add_tv_directors($tv_list, explode(',',un_magic_quote($_REQUEST["director_new"])));
  }

  if ($_REQUEST["update_genres"] == 'yes')
  {
    if (count($_REQUEST["genres"]) >0)
      scdb_add_tv_genres($tv_list,un_magic_quote($_REQUEST["genres"]));
    if (!empty($_REQUEST["genre_new"]))
      scdb_add_tv_genres($tv_list, explode(',',un_magic_quote($_REQUEST["genre_new"])));
  }

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
        db_sqlcommand("delete from viewings where media_type=".MEDIA_TYPE_TV." and user_id=$row[USER_ID] ".
                      "and media_id in (".implode(',',$tv_list).")");
      }
    }
  }

  scdb_remove_orphans();

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
  $list       = array( str('ENABLED')=>'YES',str('DISABLED')=>'NO');
  $sites_list = get_parsers_list();

  if (!empty($_REQUEST["downloads"]))
  {
    set_rating_scheme_name($_REQUEST['scheme']);
    set_sys_pref('tv_info_script',$_REQUEST['site']);
    set_sys_pref('tv_check_enabled',$_REQUEST["downloads"]);
    set_sys_pref('tv_xml_save',$_REQUEST["xml_save"]);
    set_sys_pref('tvseries_convert_dots_to_spaces',$_REQUEST["dots"]);
    $message = str('SAVE_SETTINGS_OK');
  }

  if (!empty($_REQUEST["refresh"]))
  {
    db_sqlcommand('update tv set year = null, certificate = null, details_available = null, synopsis = null');
    db_sqlcommand('delete from directors_of_tv');
    db_sqlcommand('delete from actors_in_tv');
    db_sqlcommand('delete from genres_of_tv');
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
  form_list_static('site',str('MOVIE_EXTRA_SITE_PROMPT'),$sites_list,get_sys_pref('tv_info_script','www.TheTVDB.com.php'),false,false,false);
  form_list_dynamic('scheme',str('RATING_SCHEME_PROMPT'),get_rating_scheme_list_sql(),get_rating_scheme_name(),false,false,null);
  form_radio_static('downloads',str('STATUS'),$list,get_sys_pref('tv_check_enabled','YES'),false,true);
  form_radio_static('xml_save',str('XML_SAVE'),$list,get_sys_pref('tv_xml_save','NO'),false,true);
  form_submit(str('SAVE_SETTINGS'),2,'left',240);
  form_end();

  form_start('index.php', 150, 'conn');
  form_hidden('section', 'TV');
  form_hidden('action', 'INFO');
  form_hidden('refresh','YES');
  echo '<p>&nbsp;<br><b>'.str('EXTRA_REFRESH_TITLE').'</b>
        <p>'.str('EXTRA_REFRESH_DETAILS').'
        <p><span class="stdformlabel">'.str('EXTRA_REFRESH_WARNING','"'.str('TV_DETAILS').'"').'</span>'.'<br>&nbsp;';
  form_submit(str('EXTRA_REFRESH_GO'),2,'Left',240);
  form_end();
  
  form_start('index.php', 150, 'conn');
  form_hidden('section', 'TV');
  form_hidden('action', 'INFO');
  form_hidden('export','YES');
  echo '<p>&nbsp;<br><b>'.str('EXTRA_EXPORT_TITLE').'</b>
        <p>'.str('EXTRA_EXPORT_DETAILS');
  form_submit(str('EXTRA_EXPORT_GO'),2,'Left',240);
  form_end();
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
