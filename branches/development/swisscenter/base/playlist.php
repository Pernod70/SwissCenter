<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once("infotab.php");
require_once("utils.php");
require_once("file.php");
require_once("mysql.php");
require_once("page.php");
require_once("ext/getid3/getid3.php");

define('MEDIA_TYPE_MUSIC',1);
define('MEDIA_TYPE_PHOTO',2);
define('MEDIA_TYPE_VIDEO',3);
define('MEDIA_TYPE_RADIO',4);

//-------------------------------------------------------------------------------------------------
// Sets the $media_type and $file_id to the values for the media file specified in $fsp.
// returns $file_id and $media_type set to '' if nothing is found.
//-------------------------------------------------------------------------------------------------

function find_media_in_db( $fsp, &$media_type, &$file_id)
{
  // Try music first
  $media_type = MEDIA_TYPE_MUSIC;
  $file_id    = db_value("select file_id from mp3s where concat(dirname,filename)='$fsp' limit 1");

  if (is_null($file_id))
  {
    // Nothing found, so try movies
    $media_type = MEDIA_TYPE_VIDEO;
    $file_id    = db_value("select file_id from movies where concat(dirname,filename)='$fsp' limit 1");
  }

  if (is_null($file_id))
  {
    // still nothing... try photos
    $media_type = MEDIA_TYPE_PHOTO;
    $file_id    = db_value("select file_id from photos where concat(dirname,filename)='$fsp' limit 1");
  }

  if (is_null($file_id))
  {
    // Still nothing... set the variables to '' to indicate nothing could be found
    $media_type = '';
    $file_id    = '';
  }
}

//-------------------------------------------------------------------------------------------------
// Generates the array of tracks to play for the current playlist
//-------------------------------------------------------------------------------------------------

function get_tracklist_to_play()
{  
  $seed       = nvl($_REQUEST["seed"],0);
  $shuffle    = ($_SESSION["shuffle"] == "on" ? true : false);
  $spec_type  = $_REQUEST["spec_type"];
  $media_type = $_REQUEST["media_type"];

  switch ($spec_type)
  {
    case 'playlist':
          $array      = $_SESSION["playlist"];
          break;
          
    case 'sql':
          $spec       = $_SESSION["play_now"]["spec"];
          $array      = db_toarray($spec.' LIMIT '.MAX_PLAYLIST_SIZE);
          break;
          
    case 'file':
          $spec       = $_REQUEST["spec"];
          $table      = db_value("select media_table from media_types where media_id = $media_type");          
          $array      = db_toarray("select * from $table where file_id = $spec");
          break;
  }

  if ($shuffle && count($array)>1)
    shuffle_fisherYates($array,$seed);

  return $array;
}

//-------------------------------------------------------------------------------------------------
// Returns a link to play a loaded or custom playlist (held in the session)
//-------------------------------------------------------------------------------------------------

function play_playlist()
{
  $params = 'spec_type=playlist&'.current_session().'&seed='.mt_rand();
  
  // If all music, then generate the "Now Playing" images to accompany the tracks.
  if ( playlist_all_music())
    $extra = 'pod="3,1,'.server_address().'playing_list.php?'.$params.'" ';
  else 
    $extra = 'vod="playlist" ';

  return 'href="gen_playlist.php?'.$params.'" '.$extra;
}

//-------------------------------------------------------------------------------------------------
// Returns a link to play a single file of type $media_type (constants defined above) which has
// a file ID held in $file_id
//-------------------------------------------------------------------------------------------------

function play_file( $media_type, $file_id )
{
  $params = 'spec_type=file&'.current_session().'&spec='.$file_id.'&media_type='.$media_type;
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="3,1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         if (is_showcenter())
         {
           // We send the href as MUTE becasue we don't want any music playing. Otherwise (I guess) we would
           // send a link to a page that generates a music playlist
           $link .= 'href="MUTE" pod="1,1,'.$server.'gen_photolist.php?'.$params.'" ';
         }
         else 
         {
           // On the PC we want to open a new window (of the right size) and run a little javascript picture slideshow.
           $args = "'".$server.'gen_photolist.php?'.$params."','Slideshow','scrollbars=0, toolbar=0, width=".(SCREEN_WIDTH).", height=".(SCREEN_HEIGHT)."'";
           $link = 'href="#" onclick="window.open('.$args.')"';
         }
         break;
  }
  
  return $link;
}

//-------------------------------------------------------------------------------------------------
// Returns a link to play a collection of $media_type items (constrants defined above) which are
// selected using the SQL statement in $spec
//-------------------------------------------------------------------------------------------------

function play_sql_list( $media_type, $spec)
{
  $_SESSION["play_now"]["spec"] = $spec;
  $params = 'spec_type=sql&'.current_session().'&seed='.mt_rand().'&media_type='.$media_type;
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="3,1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         if (is_showcenter())
         {
           // We send the href as MUTE becasue we don't want any music playing. Otherwise (I guess) we would
           // send a link to a page that generates a music playlist
           $link .= 'href="MUTE" pod="1,1,'.$server.'gen_photolist.php?'.$params.'" ';
         }
         else 
         {
           // On the PC we want to open a new window (of the right size) and run a little javascript picture slideshow.
           $args = "'".$server.'gen_photolist.php?'.$params."','Slideshow','scrollbars=0, toolbar=0, width=".(SCREEN_WIDTH).", height=".(SCREEN_HEIGHT)."'";
           $link = 'href="#" onclick="window.open('.$args.')"';
         }
         break;
  }
  
  return $link;
}

//-------------------------------------------------------------------------------------------------
// Creates a "Quick Play" link
//-------------------------------------------------------------------------------------------------

function quick_play_link ( $media_type, $where)
{
  $_SESSION["shuffle"] = 'on';
  $table = db_value("select media_table from media_types where media_id = $media_type");          
  return play_sql_list($media_type, "select * from $table media ".get_rating_join()." where 1=1 ".$where);
}

//-------------------------------------------------------------------------------------------------
// returns true if the current user (Apache) is able to read and write to the
// playlists directory.
//-------------------------------------------------------------------------------------------------

function pl_enabled()
{
  return (is_readable(os_path(get_sys_pref("playlists"))) && is_writeable(os_path(get_sys_pref("playlists"))));
}

//-------------------------------------------------------------------------------------------------
// Stores the details of the current music selection in the SESSION playlist.
//-------------------------------------------------------------------------------------------------

function build_pl($sql)
{
  $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
  $data      = array();

  // Get the file details from the database and add to the session playlist
  if ( ($data = db_toarray($sql)) === false)
    page_error(str('DATABASE_ERROR'));
  else
  {
    $_SESSION["playlist_name"] = "&lt;".str('CUSTOM')."&gt;";

    foreach ($data as $row)
      $_SESSION["playlist"][] = $row;
  }

  page_header(str('TRACKS_ADDED_TITLE'));

  echo str('TRACKS_ADDED_TEXT'
          ,'<font color="'.style_value("BUTTON_DESC_COLOUR".'#FFFFFF').'">'.str('HOME').'</font>'
          ,'<font color="'.style_value("MENU_OPTION_REF_COLOUR",'#FFFFFF').'">"'.str('MANAGE_PLAYLISTS').'"</font>');

  $menu = new menu();
  $menu->add_item(str('MANAGE_PLAYLISTS'),'manage_pl.php');
  $menu->add_item(str('RETURN_TO_SELECTION'),$back_url);
  $menu->display();
  page_footer($back_url);
}

//-------------------------------------------------------------------------------------------------
// Outputs the information about the current music playlist
// (takes an infotab() object as a paramter)
//-------------------------------------------------------------------------------------------------

function pl_info ()
{
  $info       = new infotab();
  $num_tracks = count($_SESSION["playlist"]);
  $play_name  = $_SESSION["playlist_name"];
  $playtime   = 0;

  if (!is_null( $play_name ))
    $info->add_item( 'Playlist', $play_name );

  if ($num_tracks > 0 )
  {
    foreach ($_SESSION["playlist"] as $row)
      $playtime += $row["LENGTH"];

    $info->add_item( str('TRACKS'), ($num_tracks ==0 ? str('ONE_TRACK',$num_tracks) : str('MANY_TRACKS',$num_tracks)) );
    $info->add_item( 'Play Time', hhmmss($playtime) );
  }
  else
  {
    echo '<center>'.str('NO_PLAYLIST').'</center>';
  }
  $info->display();
}

//-------------------------------------------------------------------------------------------------
// Clears the current playlist from the session
//-------------------------------------------------------------------------------------------------

function clear_pl()
{
  unset($_SESSION["playlist_name"]);
  unset($_SESSION["playlist"]);
}

//-------------------------------------------------------------------------------------------------
// Loads the contents of the file into the current session.
//-------------------------------------------------------------------------------------------------

function load_pl ($file, $action)
{
  if ($action == "replace")
  {
    clear_pl();
    $_SESSION["playlist_name"] = file_noext(basename($file));
  }
  else
  {
    $_SESSION["playlist_name"] = "&lt;".str('CUSTOM')."&gt;";
  }

  if (($lines = file($file)) !== false)
  {
    foreach ($lines as $l)
    {
      $fsp = rtrim($l);
      if (!empty($fsp) && $fsp[0]!='#')
      {
        $fsp = str_replace('\\','/',make_abs_file($fsp,get_sys_pref("playlists")));       
        $info_music = db_toarray("select * from mp3s where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");
        $info_movie = db_toarray("select * from movies where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");
        $info_photo = db_toarray("select * from photos where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");      
        $all = $info_movie + $info_music + $info_photo;
        
        if ( count($all) == 1)
          $_SESSION["playlist"][] = array_pop($all);
      }
    }
  }
}

//-------------------------------------------------------------------------------------------------
// Save a Playlist
//-------------------------------------------------------------------------------------------------

function save_pl ($file)
{
  $playlist= array();

  foreach ($_SESSION["playlist"] as $row)
    $playlist[] = os_path($row["DIRNAME"].$row["FILENAME"]);

  array2file($playlist, $file);
  $_SESSION["playlist_name"] = file_noext(basename($file));
}

//-------------------------------------------------------------------------------------------------
//  Returns TRUE if the current playlist contains only music
//-------------------------------------------------------------------------------------------------

function playlist_all_music()
{
  if (empty($_SESSION["playlist"]) || count($_SESSION["playlist"])==0)
    return false;
  
  foreach ($_SESSION["playlist"] as $row)
    if ( db_value("select count(*) from mp3s where file_id=$row[FILE_ID] and dirname='".db_escape_str($row["DIRNAME"])."' and filename='".db_escape_str($row["FILENAME"])."'") == 0 )
      return false;

  return true;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
