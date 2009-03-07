<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../base/mysql.php'));
require_once( realpath(dirname(__FILE__).'/../base/server.php'));
require_once( realpath(dirname(__FILE__).'/../base/image.php'));

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
            result=\''.form_list_dynamic_html('title','select distinct title id, title name from themes order by 1','',true,true,'','select_title(this.value);').'\';
            switch(value) {
            case "'.MEDIA_TYPE_TV.'":
              result=\''.form_list_dynamic_html(MEDIA_TYPE_TV, 'select distinct title id, title name from themes where media_type='.MEDIA_TYPE_TV.' order by 1','',true,true,'','select_title(this.value);').'\';
              break;
            case "'.MEDIA_TYPE_VIDEO.'":
              result=\''.form_list_dynamic_html(MEDIA_TYPE_VIDEO, 'select distinct title id, title name from themes where media_type='.MEDIA_TYPE_VIDEO.' order by 1','',true,true,'','select_title(this.value);').'\';
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

  echo '<p>'.str('THEMES_PROMPT');
  echo '<table cellspacing="4"><tr>';
  echo '  <td>'.str('MEDIA_TYPE').' : </td><td>'.form_list_static_html('media_type',$media_type_list,'',true,true,true,'select_type(this.value);').'</td>';
  echo '  <td>'.str('TITLE').' : </td><td><div id="titlelookup">'.form_list_dynamic_html('title_list','select distinct title id, title name from themes order by 1','',true,true,'','select_title(this.value);').'</div></td>';
  echo '</tr></table>';
  echo '<table>';
  echo '<tr><td width=900 ><div id="picturegui"></div></td></tr>';

  echo '<script type="text/javascript" > { set_image("notselected") } </script>';

  echo '<tr><td><div id="picturechooser"></div></td></tr>';
  echo '</table>';
}

//*************************************************************************************************
// Main Code
//*************************************************************************************************

$server = server_address();

if ( isset($_REQUEST["action"]) )
{
  switch ($_REQUEST["action"])
  {
    case 'showthumbs' :
      // Display thumbnails of all fanart for selected title
      $title = un_magic_quote($_REQUEST["title"]);
      $data = db_toarray("select * from themes where title='".db_escape_str($title)."'");

      echo '<p>'.str('THEME_PREVIEW_THUMBS','<b>'.htmlentities($title).'</b>').'</p>';
      echo '<table><tr>';

      foreach ($data as $i=>$theme)
      {
        // Start new row
        if ( $i % 3 == 0 && $i > 0 ) { echo "</tr><tr>"; }
        echo "<td><img ".(($theme['USE_SYNOPSIS'] || $theme['USE_SERIES']) ? 'style="border:5px solid green"' : '')." src='".$server."config/config_themes.php?action=image&file_id=".$theme['FILE_ID']."&x=300&y=168' onclick=\"window.media_type=".$theme['MEDIA_TYPE']."; window.flip=".$theme['FLIP_IMAGE']."; window.greyscale=".$theme['GREYSCALE'].";window.use_series=".$theme['USE_SERIES']."; window.use_synopsis=".$theme['USE_SYNOPSIS']."; window.show_banner=".$theme['SHOW_BANNER']."; window.show_image=".$theme['SHOW_IMAGE']."; set_image('".$theme['FILE_ID']."')\"></td>\n";
      }
      echo '</tr></table>';
      break;

    case 'thumbgui' :
      // Thumbnail selected
      $file_id      = $_REQUEST["file_id"];
      $media_type   = $_REQUEST["media_type"];
      $flip         = $_REQUEST["flip"];
      $greyscale    = $_REQUEST["greyscale"];
      $show_banner  = $_REQUEST["show_banner"];
      $show_image   = $_REQUEST["show_image"];
      $use_synopsis = $_REQUEST["use_synopsis"];
      $use_series   = $_REQUEST["use_series"];

      if ( $file_id!='pause' )
        echo '<script type="text/javascript">window.show_banner='.$show_banner.';window.show_image='.$show_image.';window.file_id='.$file_id.";window.flip=".$flip.";window.greyscale=".$greyscale.";window.use_synopsis=".$use_synopsis.";window.use_series=".$use_series.";</script>" ;

      echo "<table><tr>";
      echo "<td width=460 height=260 ><center><div id=image>" ;

      if ( $file_id !='pause')
        echo "<img src='".$server."config/config_themes.php?action=image&file_id=".$file_id."&flip=".$flip."&greyscale=".$greyscale."&x=450&y=252'>";
      else
        echo "<img src='".style_img('ANIM_AJAX',true)."'>";

      echo '</div></center></td>';
      echo '<td width="316"><form name=pictureguiform>';
      echo '<input type=hidden name="media_type">';

      echo str('THEME_EFFECTS')."<br>\n";
      echo "<input type=checkbox onClick='config_gui_flip()' name=flip ".( $flip==1 ? 'checked' : '' ).">";
      echo str('THEME_FLIP_IMAGE')."<br>\n";

      echo "<input type=checkbox onClick='config_gui_greyscale()' name=greyscale ".( $greyscale==1 ? 'checked' : '' ).">";
      echo str('THEME_GREYSCALE')."<br><br>\n";

      echo str('THEME_SETTINGS')."<br>\n";
      echo "<input type=checkbox onClick='window.show_banner=config_inverse(window.show_banner)' name=show_banner ".( $show_banner==1 ? 'checked' : '' ).">";
      echo str('THEME_SHOW_BANNER')."<br>\n";

      echo "<input type=checkbox onClick='window.show_image=config_inverse(window.show_image)' name=show_image ".( $show_image==1 ? 'checked' : '' ).">";
      echo str('THEME_SHOW_IMAGE')."<br>\n";

      echo "<input type=checkbox onClick='window.use_series=config_inverse(window.use_series)' name=use_series ".( $use_series==1 ? 'checked ' : '' ).( $media_type==MEDIA_TYPE_VIDEO ? 'disabled ' : '' ).">";
      echo str('THEME_ON_SERIES')."<br>\n";

      echo "<input type=checkbox onClick='window.use_synopsis=config_inverse(window.use_synopsis)' name=use_synopsis " .( $use_synopsis==1 ? 'checked' : '' ).">";
      echo str('THEME_ON_SYNOPSIS')."<br>\n";

      echo "</form>";
      echo '<button onClick="config_write_to_db()" type="button">'.str('THEME_APPLY').'</button>';

      echo "</td></tr>";
      echo "</table>" ;
      break;

    case 'image' :
      // Display required image
      $file_id   = $_REQUEST["file_id"];
      $flip      = (isset($_REQUEST["flip"]) ? $_REQUEST["flip"] : 0);
      $greyscale = (isset($_REQUEST["greyscale"]) ? $_REQUEST["greyscale"] : 0);

      // Create a new image
      $img = new CImage();

      if ( $file_id == "notselected" )
      {
        // This path should not be hard coded //
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
      $img->output('gif', false);
      break;

    case 'apply' :
      // Save the theme to the database
      $file_id = $_REQUEST["file_id"];
      $data = db_row("select thumb_cache, original_url, original_cache from themes where file_id=$file_id");
      $thumb_cache    = $data['THUMB_CACHE'];
      $original_url   = $data['ORIGINAL_URL'];
      $original_cache = $data['ORIGINAL_CACHE'];

      // Download original image to cache
      if ( ($_REQUEST["use_series"] || $_REQUEST["use_synopsis"]) ) //&& empty($original_cache) )
      {
        $original_cache = dirname($thumb_cache).'/original/'.basename($thumb_cache);
        if (!file_exists($original_cache))
        {
          if(!file_exists(dirname($thumb_cache).'/original')) { @mkdir(dirname($thumb_cache).'/original'); }
          file_download_and_save( $original_url, $original_cache, true );
        }
    	}

    	// Process the original image
    	$theme_dir = get_sys_pref('cache_dir').'/themes';
    	if(!file_exists($theme_dir)) { @mkdir($theme_dir); }
      $processed = $theme_dir.'/'.basename($thumb_cache);

      // Create a new image
      $img = new CImage();
      $img->load_from_file($original_cache);

      // Apply flip and greyscale if selected
      if ( $_REQUEST["flip_image"] ) { $img->flip_horizontal(); }
      if ( $_REQUEST["greyscale"] )  { $img->greyscale(); }

      // Save processed image
      $img->output('jpg', false, $processed);

      $data = array();
      $data["flip_image"]      = $_REQUEST["flip"];
      $data["greyscale"]       = $_REQUEST["greyscale"];
      $data["use_series"]      = $_REQUEST["use_series"];
      $data["use_synopsis"]    = $_REQUEST["use_synopsis"];
      $data["show_banner"]     = $_REQUEST["show_banner"];
      $data["show_image"]      = $_REQUEST["show_image"];
      $data["original_cache"]  = $original_cache;
      $data["processed_image"] = $processed;
      db_update_row( "themes", $file_id, $data );

      break;
    }
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
