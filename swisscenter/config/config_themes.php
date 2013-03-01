<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/mysql.php'));
require_once( realpath(dirname(__FILE__).'/../base/server.php'));
require_once( realpath(dirname(__FILE__).'/../base/image.php'));
require_once( realpath(dirname(__FILE__).'/../base/page.php'));

// ----------------------------------------------------------------------------------
// Display themes
// ----------------------------------------------------------------------------------

function themes_display()
{
  // Script to update 'Title' dropdown when 'Media Type' is selected
  echo '<script type="text/javascript" src="config_themes.js"></script>';
  echo '<script type="text/javascript">
          select_type=function(value) {
            var result;
            switch(value) {
            case "'.MEDIA_TYPE_VIDEO.'":
              result=\''.form_list_dynamic_html(MEDIA_TYPE_VIDEO, "select distinct th.title id, m.sort_title name from themes th left join movies m on m.title=th.title where th.media_type=".MEDIA_TYPE_VIDEO." order by m.sort_title",'',true,true,'','select_title(this.value,'.MEDIA_TYPE_VIDEO.');').'\';
              break;
            default:
              result=\''.form_list_dynamic_html(MEDIA_TYPE_TV, "select distinct th.title id, t.sort_programme name from themes th left join tv t on t.programme=th.title where th.media_type=".MEDIA_TYPE_TV." order by t.sort_programme",'',true,true,'','select_title(this.value,'.MEDIA_TYPE_TV.');').'\';
              break;
            }
            document.getElementById("titlelookup").innerHTML=result;
          }
        </script>';

  // Form list of media types
  $media_type_opts = db_toarray("select media_id, media_name from media_types where media_id in (".MEDIA_TYPE_TV.",".MEDIA_TYPE_VIDEO.") order by 2");

  // Use language translation for MEDIA_NAME
  for ($i = 0; $i<count($media_type_opts); $i++)
  {
    $media_type_opts[$i]["MEDIA_NAME"] = str('MEDIA_TYPE_'.strtoupper($media_type_opts[$i]["MEDIA_NAME"]));
    $media_type_list[$media_type_opts[$i]["MEDIA_NAME"]] = $media_type_opts[$i]["MEDIA_ID"];
  }

  echo '<h1>'.str('THEMES').'</h1><p>';
  echo '<div id="message"></div>';
  echo '<p>'.str('THEMES_PROMPT');
  echo '<table cellspacing="4"><tr>';
  echo '  <td>'.str('MEDIA_TYPE').' : </td><td>'.form_list_static_html('media_type',$media_type_list,'',true,true,false,'select_type(this.value);').'</td>';
  echo '  <td>'.str('TITLE').' : </td><td><div id="titlelookup">'.form_list_dynamic_html('title_list',"select distinct th.title id, t.sort_programme name from themes th left join tv t on t.programme=th.title where th.media_type=".MEDIA_TYPE_TV." order by t.sort_programme",'',true,true,'','select_title(this.value,'.MEDIA_TYPE_TV.');').'</div></td>';
  echo '</tr></table>';
  echo '<table>';
  echo '<tr><td width=900 ><div id="picturegui"></div></td></tr>';

  echo '<script type="text/javascript"> { set_image("not_selected") } </script>';

  echo '<tr><td><div id="thumbnails"></div></td></tr>';
  echo '</table>';
}

function refresh_picturegui( $file_id, $opts, $media_type )
{
  header('Content-type: text/html; '.charset());

  if ( $file_id != 'wait' )
  {
    echo '<script type="text/javascript">'.
            'window.file_id='.$file_id.
           ';window.media_type='.$media_type.
           ';window.flip='.$opts["flip"].
           ';window.greyscale='.$opts["greyscale"].
           ';window.use_synopsis='.$opts["use_synopsis"].
           ';window.use_series='.$opts["use_series"].
           ';window.show_banner='.$opts["show_banner"].
           ';window.show_image='.$opts["show_image"].
        ';</script>';
  }

  echo "<table><tr>";
  echo "<td width=460 height=260 ><center><div id=image>" ;

  if ( $file_id != 'wait' )
    echo "<img src='".server_address()."config/config_themes.php?action=image&file_id=".$file_id."&flip=".$opts["flip"]."&greyscale=".$opts["greyscale"]."&x=450&y=252'>";
  else
    echo "<img src='".style_img('ANIM_AJAX',true)."'>";

  echo '</div></center></td>';
  echo '<td width="316"><form name=picturegui>';
  echo '<input type=hidden name="media_type">';

  echo str('THEME_EFFECTS')."<br>\n";
  echo "<input type=checkbox onClick='config_gui_flip()' name=flip ".(!is_numeric($file_id) ? 'disabled' : ( $opts["flip"]==1 ? 'checked' : '' )).">";
  echo str('THEME_FLIP_IMAGE')."<br>\n";

  echo "<input type=checkbox onClick='config_gui_greyscale()' name=greyscale ".(!is_numeric($file_id) ? 'disabled' : ( $opts["greyscale"]==1 ? 'checked' : '' )).">";
  echo str('THEME_GREYSCALE')."<br><br>\n";

  echo str('THEME_SETTINGS')."<br>\n";
  echo "<input type=checkbox onClick='window.show_banner=config_inverse(window.show_banner)' name=show_banner ".(!is_numeric($file_id) || $media_type==MEDIA_TYPE_VIDEO ? 'disabled' : ( $opts["show_banner"]==1 ? 'checked' : '' )).">";
  echo str('THEME_SHOW_BANNER')."<br>\n";

  echo "<input type=checkbox onClick='window.use_series=config_inverse(window.use_series)' name=use_series ".(!is_numeric($file_id) || $media_type==MEDIA_TYPE_VIDEO ? 'disabled' : ( $opts["use_series"]==1 ? 'checked ' : '' )).">";
  echo str('THEME_ON_SERIES')."<br>\n";

  echo "<input type=checkbox onClick='window.show_image=config_inverse(window.show_image)' name=show_image ".(!is_numeric($file_id) ? 'disabled' : ( $opts["show_image"]==1 ? 'checked' : '' )).">";
  echo str('THEME_SHOW_IMAGE')."<br>\n";

  echo "<input type=checkbox onClick='window.use_synopsis=config_inverse(window.use_synopsis)' name=use_synopsis ".(!is_numeric($file_id) ? 'disabled' : ( $opts["use_synopsis"]==1 ? 'checked' : '' )).">";
  echo str('THEME_ON_SYNOPSIS')."<br>\n";

  echo "</form>";
  echo '<button type=button onClick="save_theme_settings()" name=apply '.( !is_numeric($file_id) ? 'disabled' : '' ).'>'.str('THEME_APPLY').'</button>';

  echo "</td></tr>";
  echo "</table>" ;
}

function refresh_thumbnails( $title )
{
  header('Content-type: text/html; '.charset());

  echo '<p>'.str('THEME_PREVIEW_THUMBS','<b>'.htmlentities($title).'</b>').'</p>';
  echo '<table><tr>';

  $server = server_address();
  $data = db_toarray("select * from themes where title='".db_escape_str($title)."'");
  foreach ($data as $i=>$theme)
  {
    // Start new row
    if ( $i % 3 == 0 && $i > 0 ) { echo "</tr><tr>"; }
    echo "<td><img ".(($theme['USE_SYNOPSIS'] || $theme['USE_SERIES']) ? 'style="border:5px solid green"' : '')." src='".$server."config/config_themes.php?action=image&file_id=".$theme['FILE_ID']."&x=300&y=168'
            onclick=\"window.media_type=".$theme['MEDIA_TYPE']."; window.flip=".$theme['FLIP_IMAGE']."; window.greyscale=".$theme['GREYSCALE'].";window.use_series=".$theme['USE_SERIES']."; window.use_synopsis=".$theme['USE_SYNOPSIS']."; window.show_banner=".$theme['SHOW_BANNER']."; window.show_image=".$theme['SHOW_IMAGE']."; set_message(''); set_image('".$theme['FILE_ID']."')\">
          </td>\n";
  }
  echo '</tr></table>';
}

//*************************************************************************************************
// Main Code
//*************************************************************************************************

if ( isset($_REQUEST["action"]) )
{
  switch ($_REQUEST["action"])
  {
    case 'message' :
      // Display message
      $text = $_REQUEST["text"];
      if (!empty($text))
      {
        if ($text[0] == '!')
          echo '<p class="warning">'.substr(str($text),1).'</p>';
        else
          echo '<p class="message">'.str($text).'</p>';
      }
      break;

    case 'showthumbs' :
      // Display thumbnails of all fanart for selected title
      $title = $_REQUEST["title"];
      refresh_thumbnails( $title );
      break;

    case 'thumbgui' :
      // Thumbnail selected
      $file_id      = $_REQUEST["file_id"];
      $media_type   = $_REQUEST["media_type"];
      $opts         = array("flip"         => $_REQUEST["flip"],
                            "greyscale"    => $_REQUEST["greyscale"],
                            "show_banner"  => $_REQUEST["show_banner"],
                            "show_image"   => $_REQUEST["show_image"],
                            "use_synopsis" => $_REQUEST["use_synopsis"],
                            "use_series"   => $_REQUEST["use_series"]);
      refresh_picturegui( $file_id, $opts, $media_type );
      break;

    case 'image' :
      // Display required image
      $file_id   = $_REQUEST["file_id"];
      $flip      = (isset($_REQUEST["flip"]) ? $_REQUEST["flip"] : 0);
      $greyscale = (isset($_REQUEST["greyscale"]) ? $_REQUEST["greyscale"] : 0);

      // Create a new image
      $img = new CImage();

      if ( $file_id == "not_selected" )
      {
        // Use the 'Please select...' image
        $img->load_from_file(style_img('THEME_SELECT',true));
      }
      else
      {
        $thumbnail = db_value("select thumb_cache from themes where file_id=".$file_id );

        // Load the image from disk
        $img->load_from_file($thumbnail);

        $x = (isset($_REQUEST["x"]) ? $_REQUEST["x"] : $img->get_width());
        $y = (isset($_REQUEST["y"]) ? $_REQUEST["y"] : $img->get_height());

        // Resize it to the required size, whilst maintaining the correct aspect ratio
        $img->resize($x, $y, 0, true, 'RESAMPLE');

        // Apply flip and greyscale if selected
        if ( $flip )      { $img->flip_horizontal(); }
        if ( $greyscale ) { $img->greyscale(); }
      }
      $img->output('png', false);
      break;

    case 'apply' :
      // Save the theme to the database
      $file_id = $_REQUEST["file_id"];
      $data = db_row("select thumb_cache, original_url, original_cache from themes where file_id=$file_id");
      $thumb_cache    = $data['THUMB_CACHE'];
      $original_url   = $data['ORIGINAL_URL'];
      $original_cache = $data['ORIGINAL_CACHE'];

      // Download original image to cache
      if ( ($_REQUEST["use_series"] || $_REQUEST["use_synopsis"]) )
      {
        $original_cache = dirname($thumb_cache).'/original/'.basename($thumb_cache);
        if (!file_exists($original_cache))
        {
          if(!file_exists(dirname($thumb_cache).'/original'))
          {
            $oldumask = umask(0);
            @mkdir(dirname($thumb_cache).'/original',0777);
            umask($oldumask);
          }
          file_download_and_save( $original_url, $original_cache, true );
        }
      }

      // Process the original image
      $theme_dir = get_sys_pref('cache_dir').'/themes';
      if (!file_exists($theme_dir))
      {
        $oldumask = umask(0);
        @mkdir($theme_dir,0777);
      }
      $processed = $theme_dir.'/'.basename($thumb_cache);

      // Create a new image
      $img = new CImage();
      $img->load_from_file($original_cache);

      // Apply flip and greyscale if selected
      if ( $_REQUEST["flip"] )      { $img->flip_horizontal(); }
      if ( $_REQUEST["greyscale"] ) { $img->greyscale(); }

      // Save processed image
      $img->output('jpg', false, $processed);

      $data = array();
      $data["flip_image"]      = $_REQUEST["flip"];
      $data["greyscale"]       = $_REQUEST["greyscale"];
      $data["use_series"]      = $_REQUEST["use_series"];
      $data["use_synopsis"]    = $_REQUEST["use_synopsis"];
      $data["show_banner"]     = $_REQUEST["show_banner"];
      $data["show_image"]      = $_REQUEST["show_image"];
      $data["original_cache"]  = os_path($original_cache);
      $data["processed_image"] = os_path($processed);
      db_update_row( "themes", $file_id, $data );

      break;
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
