<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/infotab.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/page.php'));
require_once( realpath(dirname(__FILE__).'/rating.php'));

//-------------------------------------------------------------------------------------------------
// Sets the $media_type and $file_id to the values for the media file specified in $fsp.
// returns $file_id and $media_type set to '' if nothing is found.
//-------------------------------------------------------------------------------------------------

function find_media_in_db( $fsp, &$media_type, &$file_id)
{
  // Try music first
  $media_type = MEDIA_TYPE_MUSIC;
  $file_id    = db_value("select file_id from mp3s where concat(dirname,filename)='".db_escape_str($fsp)."' limit 1");

  if (is_null($file_id))
  {
    // Nothing found, so try movies
    $media_type = MEDIA_TYPE_VIDEO;
    $file_id    = db_value("select file_id from movies where concat(dirname,filename)='".db_escape_str($fsp)."' limit 1");
  }

  if (is_null($file_id))
  {
    // still nothing... try photos
    $media_type = MEDIA_TYPE_PHOTO;
    $file_id    = db_value("select file_id from photos where concat(dirname,filename)='".db_escape_str($fsp)."' limit 1");
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
          $array      = db_toarray($spec.' LIMIT '.max_playlist_size());
          break;
          
    case 'file':
          $spec       = $_REQUEST["spec"];
          $table      = db_value("select media_table from media_types where media_id = $media_type");          
          $array      = db_toarray("select * from $table where file_id = $spec");
          break;

    case 'dir':
          $array      = array();
          $dir        = rawurldecode($_REQUEST["spec"]);
          $table      = db_value("select media_table from media_types where media_id = $media_type");          

          // Get all media locations for this filetype, append the $dir and escapge them for DB access
          $all_dirs = db_col_to_list("select name from media_locations where media_type = $media_type");
          for ($i=0 ; $i<count($all_dirs) ; $i++)
            $all_dirs[$i] = db_escape_str($all_dirs[$i].'/'.$dir);

          $predicate = "dirname like '".implode("%' or dirname like '",$all_dirs)."%' ".get_rating_filter();
          $array     = db_toarray("select * from $table media ".get_rating_join()."where $predicate order by filename"); 
          break;

  }

  if ($shuffle && count($array)>1 && $spec_type != 'dir')
    shuffle_fisherYates($array,$seed);

  return $array;
}

//-------------------------------------------------------------------------------------------------
// Returns the correct style of slideshow link for photos, depending on the browser
//-------------------------------------------------------------------------------------------------

function slideshow_link_by_browser( $params )
{  
  if (is_hardware_player())
  {
   // We send the href as MUTE becasue we don't want any music playing. Otherwise (I guess) we would
   // send a link to a page that generates a music playlist
   $link .= 'href="MUTE" pod="1,1,'.server_address().'gen_photolist.php?'.$params.'" ';
  }
  else 
  {
   // On the PC we want to open a new window (of the right size) and run a little javascript picture slideshow.
   $args = "'".server_address().'gen_photolist.php?'.$params."','Slideshow','scrollbars=0, toolbar=0, width=".(convert_x(1000)).", height=".(convert_y(1000))."'";
   $link = 'href="#" onclick="window.open('.$args.')"';
  }
  
  return $link;
}

//-------------------------------------------------------------------------------------------------
// Returns a link to play a loaded or custom playlist (held in the session)
//-------------------------------------------------------------------------------------------------

function play_playlist()
{
  $params = 'spec_type=playlist&'.current_session().'&seed='.mt_rand(); 
  $skip_type = ( support_now_playing() ? 3 : 2);

  // If all music, then generate the "Now Playing" images to accompany the tracks.
  if ( playlist_all_music())
    $extra = 'pod="'.$skip_type.',1,'.server_address().'playing_list.php?'.$params.'" ';
  else 
    $extra = 'vod="playlist" ';

  return 'href="gen_playlist.php?'.$params.'" '.$extra;
}

//-------------------------------------------------------------------------------------------------
// Returns a link to play the directory $dir of files of type $media_type (constants defined above)
//-------------------------------------------------------------------------------------------------

function play_dir( $media_type, $dir )
{
  $params = 'spec_type=dir&spec='.rawurlencode($dir).'&media_type='.$media_type.'&seed='.mt_rand().'&'.current_session();
  $skip_type = ( support_now_playing() ? 3 : 2);
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.$skip_type.',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         $link   = slideshow_link_by_browser( $params );
         break;
  }
  
  return $link;
}
  
//-------------------------------------------------------------------------------------------------
// Returns a link to RESUME playing a single file of type $media_type (constants defined above)
// which has a file ID held in $file_id
//-------------------------------------------------------------------------------------------------

function resume_file( $media_type, $file_id )
{
  $params = 'resume=Y&spec_type=file&'.current_session().'&spec='.$file_id.'&media_type='.$media_type;
  $skip_type = ( support_now_playing() ? 3 : 2);
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.$skip_type.',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         send_to_log(1,"Attempting to resume playback of a photo don't not make sense.");
         $link   = false;
         break;
  }
  
  return $link;
}

//-------------------------------------------------------------------------------------------------
// Returns a link to play a single file of type $media_type (constants defined above) which has
// a file ID held in $file_id
//-------------------------------------------------------------------------------------------------

function play_file( $media_type, $file_id )
{
  $params = 'spec_type=file&'.current_session().'&spec='.$file_id.'&media_type='.$media_type;
  $skip_type = ( support_now_playing() ? 3 : 2);
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.$skip_type.',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         $link   = slideshow_link_by_browser( $params);
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
  $skip_type = ( support_now_playing() ? 3 : 2);
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.$skip_type.',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         $link   = slideshow_link_by_browser( $params);
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
          ,'<font color="'.style_value("PAGE_TEXT_BOLD_COLOUR".'#FFFFFF').'">'.str('HOME').'</font>'
          ,'<font color="'.style_value("PAGE_TEXT_BOLD_COLOUR",'#FFFFFF').'">"'.str('MANAGE_PLAYLISTS').'"</font>');

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
      $entry = rtrim($l);
      if (!empty($entry) && $entry[0] != '#')
      {
        if ($entry[1] == ':')
          $fsp = $entry;                                          
        elseif ($entry[0] == '\\')
          $fsp = substr(get_sys_pref("playlists"),0,2).$entry;   
        else 
          $fsp = make_abs_file($entry,get_sys_pref("playlists")); 

        // Get all the details from the database that match this filename
        $fsp = str_replace('\\','/',$fsp);
        $info_music = db_toarray("select * from mp3s where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");
        $info_movie = db_toarray("select * from movies where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");
        $info_photo = db_toarray("select * from photos where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");      
        $all = $info_movie + $info_music + $info_photo;
        
        // To quote highlander..."there can be only one".
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
