<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../resources/subtitles/opensubtitles.php'));

/**
 * Displays available subtitles for selected videos
 *
 * @param string $message
 */
function subtitles_display( $message = '')
{
  $_SESSION["last_search_page"] = url_remove_param(current_url( true ), 'message');
  $per_page    = get_user_pref('PC_PAGINATION',20);
  $page        = (empty($_REQUEST["page"]) ? 1 : $_REQUEST["page"]);
  $start       = ($page-1)*$per_page;
  $where       = '';
  $user_lang   = substr(get_sys_pref('DEFAULT_LANGUAGE','en'),0,2);
  $search_lang = isset($_REQUEST["lang"]) ? $_REQUEST["lang"] : '';
  $method      = isset($_REQUEST["method"]) ? $_REQUEST["method"] : 'hash';
  $media_type  = isset($_REQUEST["media_type"]) ? $_REQUEST["media_type"] : MEDIA_TYPE_VIDEO;
  $media_table = db_value("select media_table from media_types where media_id=$media_type");

  if (empty($message) && isset($_REQUEST["message"]))
    $message = urldecode($_REQUEST["message"]);

  // Extra filters on the media (for categories and search).
  if (isset($_REQUEST["cat_id"]) && !empty($_REQUEST["cat_id"]) )
    $where .= " and ml.cat_id = ".$_REQUEST["cat_id"];

  if (isset($_REQUEST["search"]) && !empty($_REQUEST["search"]) )
  {
    if ($media_type == MEDIA_TYPE_VIDEO)
      $where .= " and m.title like '%".db_escape_str($_REQUEST["search"])."%'";
    else
      $where .= " and (m.title like '%".db_escape_str($_REQUEST["search"])."%' or m.programme like '%".db_escape_str($_REQUEST["search"])."%')";
  }

  // If the user has changed category, then shunt them back to page 1.
  if ($_REQUEST["last_where"] != $where)
  {
    $page = 1;
    $start = 0;
  }

  // Form list of media types
  $media_type_opts = db_toarray("select media_id, media_name from media_types where media_id in (".MEDIA_TYPE_TV.",".MEDIA_TYPE_VIDEO.") order by 2");

  // Use language translation for MEDIA_NAME
  for ($i = 0; $i<count($media_type_opts); $i++)
  {
    $media_type_opts[$i]["MEDIA_NAME"] = str('MEDIA_TYPE_'.strtoupper($media_type_opts[$i]["MEDIA_NAME"]));
    $media_type_list[$media_type_opts[$i]["MEDIA_NAME"]] = $media_type_opts[$i]["MEDIA_ID"];
  }

  // Initialise the OpenSubtitles API
  $os = new OpenSubtitles();

  if (empty($os->token))
    $message = "!".str('OS_NOT_AVAILABLE');

  // Get list of subtitle languages
  $os_languages = $os->GetSubLanguages($user_lang);
  $lang_list = array('<'.str('LANAGUAGE_ALL').'>' => 'all');
  if (is_array($os_languages))
  {
    foreach ($os_languages["data"] as $os_lang)
    {
      $lang_list[$os_lang["LanguageName"]] = $os_lang["SubLanguageID"];
      if ( $search_lang == '' && $os_lang["ISO639"] == $user_lang) { $search_lang = $os_lang["SubLanguageID"]; }
    }
  }

  // SQL to fetch matching rows
  $sort = ($media_type == MEDIA_TYPE_VIDEO) ? 'sort_title' : 'sort_programme, series, episode';
  $video_list  = db_toarray("select m.* from $media_table m, media_locations ml where ml.location_id = m.location_id ".$where.
                            " order by $sort");
  $video_count = count($video_list);
  $video_list = array_slice($video_list, $start, $per_page);

  echo '<table width="100%"><tr>
          <td width=100% align="center"><a href="http://www.opensubtitles.org/search/sublanguageid-auto" target="_blank" title="Subtitles database - OpenSubtitles.org"><img src="http://www.opensubtitles.org/gfx/banners/banner_1_468x60.jpg" alt="Subtitles database - OpenSubtitles.org" width="468" height="60" hspace="20" vspace="16" border="0"></a></td>
        </tr></table>';

  message($message);

  echo '<p>'.str('OS_ABOUT','<a href="http://www.opensubtitles.org/upload" target="_blank">www.opensubtitles.org/upload</a>');

  $this_url = '?last_where='.urlencode($where).'&lang='.$_REQUEST["lang"].'&search='.$_REQUEST["search"].'&cat_id='.$_REQUEST["cat_id"].'&media_type='.$_REQUEST["media_type"].'&method='.$_REQUEST["method"].'&section=SUBTITLES&action=DISPLAY&page=';

  echo '<form enctype="multipart/form-data" action="" method="post">';
  form_hidden('section','SUBTITLES');
  form_hidden('action','DISPLAY');
  form_hidden('last_where',$where);

  echo '<table width="100%">'.
       '<tr><td>'.
         str('MEDIA_TYPE').' : '.
         form_list_static_html("media_type",$media_type_list,$media_type,false,true,false).
       '</td><td>'.
         str('CATEGORY').' : '.
         form_list_dynamic_html("cat_id","select distinct c.cat_id,c.cat_name from categories c left join media_locations ml on c.cat_id=ml.cat_id where ml.media_type=".$media_type." order by c.cat_name",$_REQUEST["cat_id"],true,true,str('CATEGORY_LIST_ALL')).
       '</td><td>'.
         str('SEARCH').' : '.
         '<input name="search" value="'.$_REQUEST["search"].'" size=10>'.
       '</td><td>'.
         str('LANG_SELECT').' : '.
         form_list_static_html("lang",$lang_list,$search_lang,false,true,false).
       '</td></tr>'.
       '<tr><td colspan="3">'.
         '<input type="radio" name="method" value="hash" '.($method=='hash' ? 'checked' : '').'>'.str('OS_SEARCH_HASH').
         '<input type="radio" name="method" value="title" '.($method=='title' ? 'checked' : '').'>'.str('OS_SEARCH_TITLE').
       '</td></tr>'.
       '</table></form>';

  paginate($this_url,$video_count,$per_page,$page);

  echo '<form enctype="multipart/form-data" action="" method="post">';
  form_hidden('section','SUBTITLES');
  form_hidden('action','DOWNLOAD');
  form_hidden('last_where',$where);
  form_hidden('method',$method);
  form_hidden('lang',$search_lang);
  if (isset($_REQUEST["cat_id"]) && !empty($_REQUEST["cat_id"]) )
    form_hidden('cat_id',$_REQUEST["cat_id"]);
  if (isset($_REQUEST["search"]) && !empty($_REQUEST["search"]) )
    form_hidden('search',$_REQUEST["search"]);

  echo '<table class="form_select_tab" width="100%">'.
          '<tr><th> '.str('Title').' </th></tr>'.
        '</table>';

  foreach ($video_list as $video)
  {
    // Reset timeout for each video
    set_time_limit(30);

    // Get the hash of current file
    if ( empty($video["OS_HASH"]) )
    {
      $videohash = OpenSubtitlesHash($video["DIRNAME"].$video["FILENAME"]);
      $success = db_update_row( $media_table, $video["FILE_ID"], array('os_hash' => $videohash) );
    }
    else
    {
      $videohash = $video["OS_HASH"];
    }

    // Search for subtitles for current movie
    if ($method == 'hash')
      $search = array( array('sublanguageid' => $search_lang,
                             'moviehash'     => $videohash,
                             'moviebytesize' => $video["SIZE"],
                             'imdbid'        => '',
                             'query'         => '') );
    else
      $search = array( array('sublanguageid' => $search_lang,
                             'moviehash'     => '',
                             'moviebytesize' => '',
                             'imdbid'        => '',
                             'query'         => $video["TITLE"]) );
    $subs = $os->SearchSubtitles($search);

    // Search the directory for a file with the same name as that given, but with a subtitle extension
    $subsize = 0;
    $subhash = '';
    foreach (media_ext_subtitles() as $type)
      if (($sub_file = find_in_dir( $video["DIRNAME"],file_noext($video["FILENAME"]).'.'.$type )) !== false )
      {
        $subsize = filesize($sub_file);
        $subhash = md5_file($sub_file);
        break;
      }

    $title = ($media_type == MEDIA_TYPE_VIDEO) ? $video["TITLE"] : $video["PROGRAMME"].' - '.$video["TITLE"];
    echo '<table class="form_select_tab" width="100%"><tr>
          <td valign="top">'.
            '<b>'.highlight($title, $_REQUEST["search"]).'</b>'.
            '&nbsp;[<a href="http://www.opensubtitles.org/search/sublanguageid-all/moviebytesize-'.$video["SIZE"].'/moviehash-'.$videohash.'" target="_blank">'.$video["FILENAME"].'</a>]';

    // List available subtitles for current file
    if (is_array($subs["data"]))
    {
      foreach ($subs["data"] as $sub)
      {
        echo '<br><input type="radio" name="subtitle['.$video["FILE_ID"].']" value="'.implode(',', array($sub["IDSubtitleFile"], file_ext($sub["SubFileName"]))).'">';
        // Language with flag
        echo '<img src="../images/flags/icons/'.$sub["ISO639"].'.gif">['.$sub["LanguageName"].'] ';
        // Subtitle filename (with link to OpenSubtitles.org download page)
        echo '<a href="'.$sub["SubtitlesLink"].'" target="_blank">'.$sub["SubFileName"].'</a> ';
        // Rating
        echo $sub["SubRating"] == '0.0' ? '' : '['.str('RATE').':'.$sub["SubRating"].'] ';
        // Subtitle already downloaded
        if ($subhash == $sub["SubHash"] && $subsize == $sub["SubSize"])
          echo '<font color="green">'.str('DOWNLOADED').'</font>';
        else
        {
          // Uploader
          echo '['.str('UPLOADER').':'.($sub["UserNickName"] == '' ? 'Anonymous' : encode_utf8($sub["UserNickName"])).'] ';
          // Uploader comment
          echo $sub["SubAuthorComment"] == '' ? '' : '['.str('COMMENT').':'.encode_utf8($sub["SubAuthorComment"]).'] ';
        }
      }
    }

    echo '<br><input type="radio" name="subtitle['.$video["FILE_ID"].']" value="0">'.str('OS_NO_SUBTITLES');

    echo '</td>
          </tr></table>';
  }

  paginate($this_url,$video_count,$per_page,$page);

  echo '<p><table width="100%"><tr><td align="center">
        <input type="Submit" name="subaction" value="'.str('OS_DOWNLOAD').'"> &nbsp;
        </td></tr></table>
        </form>';
}

/**
 * Download the selected subtitles
 *
 */
function subtitles_download()
{
  $subtitle    = $_REQUEST["subtitle"];
  $media_type  = isset($_REQUEST["media_type"]) ? $_REQUEST["media_type"] : MEDIA_TYPE_VIDEO;
  $media_table = db_value("select media_table from media_types where media_id=$media_type");

  // Initialise the OpenSubtitles API
  $os = new OpenSubtitles('', '');

  if ($os->token)
  {
    foreach ($subtitle as $video_id=>$subtitle_id)
    {
      $subtitle_id = explode(',', $subtitle_id);
      if ( !empty($subtitle_id[0]) )
      {
        // Reset the timeout counter for each subtitle request
        set_time_limit(30);

        // Download the selected subtitle file
        send_to_log(7,'Downloading subtitle id',$subtitle_id[0]);
        $subtitle = $os->DownloadSubtitles(array($subtitle_id[0]));

        // Decode and inflate the subtitles
        if (!function_exists('gzinflate'))
          subtitles_display("!".str('OS_GZIP_REQUIRED'));
        else
        {
          $sub_data = base64_decode($subtitle["data"][0]["data"]);
          $sub_data = @gzinflate(substr($sub_data,10)); // Ignore gz header

          // Save subtitles to file
          $data = db_row("select dirname, filename from $media_table where file_id=$video_id");
          $sub_file = $data["DIRNAME"].file_noext($data["FILENAME"]).'.'.$subtitle_id[1];
          Fsw::file_put_contents($sub_file, $sub_data);
        }
      }
    }
    subtitles_display(str('OS_DOWNLOADED_OK'));
  }
  else
  {
    subtitles_display("!".str('OS_NOT_AVAILABLE'));
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
