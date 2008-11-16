<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/infotab.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));
require_once( realpath(dirname(__FILE__).'/file.php'));
require_once( realpath(dirname(__FILE__).'/media.php'));
require_once( realpath(dirname(__FILE__).'/mysql.php'));
require_once( realpath(dirname(__FILE__).'/page.php'));
require_once( realpath(dirname(__FILE__).'/rating.php'));
require_once( realpath(dirname(__FILE__).'/../ext/xml/XPath.class.php'));

/**
 * Creates a playlist that will be sent to the hardware player based on the parameters
 * passed in. This is then stored in the current session so that it may be shared and
 * accessed by any content streaming pages that require it (stream, playing_image, etc)
 * 
 * @param integer $seed - The seed from which to generate a random sequence.
 * @param boolean $shuffle - If true, the playlist will be shuffled.
 * @param string $spec_type - dir | file | sql | playlist | musicip
 * @param mixed $spec - depends upon the $spec_type
 * @param enum $media_type - Only required for (dir|file) types. (MEDIA_TYPE_MUSIC, etc)
 */

function generate_tracklist( $seed, $shuffle, $spec_type, $spec, $media_type = null, $tracklist_name = '')
{
  $tracks = array();
  $details["seed"] = nvl($_REQUEST["seed"],0);
  $details["shuffle"] = $shuffle;
  $details["spec_type"] = $spec_type;
  $details["media_type"] = $media_type;
     
  switch ($spec_type)
  {
    case 'musicip':
          $tracks = load_pl( $_SESSION["musicip_playlist"]); // URL of the playlist generated by MusicIP
          break;
      
    case 'playlist':
          $tracks = $_SESSION["playlist"];
          break;
          
    case 'sql':
          $spec       = $_SESSION["play_now"]["spec"];
          $tracks     = db_toarray($spec.' LIMIT '.max_playlist_size());
          break;
          
    case 'file':
          $spec       = $_REQUEST["spec"];
          $table      = db_value("select media_table from media_types where media_id = $media_type");          
          $tracks     = db_toarray("select * from $table where file_id = $spec");
          break;

    case 'dir':
          $dir        = rawurldecode($_REQUEST["spec"]);
          $table      = db_value("select media_table from media_types where media_id = $media_type");          

          // Get all media locations for this filetype, append the $dir and escapge them for DB access
          $all_dirs = db_col_to_list("select name from media_locations where media_type = $media_type");
          for ($i=0 ; $i<count($all_dirs) ; $i++)
            $all_dirs[$i] = db_escape_str($all_dirs[$i].'/'.$dir);

          $predicate = "dirname like '".implode("%' or dirname like '",$all_dirs)."%' ".get_rating_filter();
          if ($media_type == 1)
            $tracks     = db_toarray("select * from $table media ".get_rating_join()."where $predicate order by album,lpad(disc,10,'0'),lpad(track,10,'0'),title"); 
          else
            $tracks     = db_toarray("select * from $table media ".get_rating_join()."where $predicate order by filename"); 
          break;

  }

  // Shuffle the tracks if required
  if ($shuffle && count($tracks)>1 && $spec_type != 'dir')
    shuffle_fisherYates($tracks,$seed);

  if ($tracklist_name == '')
    $tracklist_name = 'current_playlist';

  $_SESSION[$tracklist_name] = array("spec" => $details, "tracks" => $tracks);
  send_to_log(8,'Generated the following playlist', $_SESSION["current_playlist"]);
}

//-------------------------------------------------------------------------------------------------
// Returns an array containing all the tracks in the currently playing tracklist
//-------------------------------------------------------------------------------------------------

function get_tracklist( $tracklist_name = '')
{  
  if ($tracklist_name == '')
    $tracklist_name = 'current_playlist';
    
  return $_SESSION[$tracklist_name]["tracks"];
}

//-------------------------------------------------------------------------------------------------
// Returns the correct style of slideshow link for photos, depending on the browser
//-------------------------------------------------------------------------------------------------

function slideshow_link_by_browser( $params )
{  
  if (is_hardware_player())
  {
    $bg_music_spec = nvl($_SESSION["background_music"],'*');
    
    // At the moment we only support the current playlist. However, in the future we will allow the
    // user to specify a saved playlist.
    if ($bg_music_spec != '')
    {
      if (count($_SESSION["playlist"]) > 0)
      {
        $audio = 'gen_playlist.php?tracklist=background&spec_type=playlist&'.current_session().'&seed='.mt_rand(); 
        send_to_log(5,'Using current playlist as background music', $audio);
      }
      else 
        $audio = 'MUTE';
    }
    else 
    {
      $audio = "MUTE";
    }

   
   // We send the href as MUTE becasue we don't want any music playing. Otherwise (I guess) we would
   // send a link to a page that generates a music playlist
   $link .= 'href="'.$audio.'" pod="1,1,'.server_address().'gen_photolist.php?'.$params.'" ';
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

  // If all music, then generate the "Now Playing" images to accompany the tracks.
  if ( playlist_all_music())
    $extra = 'pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
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
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
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
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_TV:
    case MEDIA_TYPE_VIDEO:
         $link   = 'href="gen_playlist.php?'.$params.'" vod="playlist" ';
         break;
         
    case MEDIA_TYPE_PHOTO:
         send_to_log(1,"Attempting to resume playback of a photo doesn't make sense.");
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
  
  switch ($media_type)
  {
    case MEDIA_TYPE_MUSIC:
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_TV:
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
// Returns the href part of a link to play a radio station
//-------------------------------------------------------------------------------------------------

function play_internet_radio( $playlist_url, $station_name )
{
  return 'href="'.$playlist_url.'" pod="1,1,'.server_address().'music_radio_image.php?'.current_session().'&list=&station='.urlencode($station_name).'"';  
}

//-------------------------------------------------------------------------------------------------
// Returns the href part of a link which will play a LastFM radio station.
//-------------------------------------------------------------------------------------------------

define('LASTFM_ARTIST','artist');
define('LASTFM_TAG','globaltags');
define('LASTFM_NEIGHBOUR','neighbour');
define('LASTFM_USER','user');

function play_lastfm($station_type, $name = '' )
{
  $lastfm_url = server_address().'ext/lastfm/stream.php?'.current_session();
  $station_id = '';
  
  switch ($station_type)
  {
    case LASTFM_USER:
         $station_id = "lastfm://user/".get_user_pref('LASTFM_USERNAME')."/$name";
         break;
         
    case LASTFM_ARTIST:
         $station_id = "lastfm://artist/".urlencode($name)."/similarartists";
         break;
         
    case LASTFM_TAG:
         $station_id = "lastfm://globaltags/".urlencode($name)."";
         break;

    case LASTFM_NEIGHBOUR:
         if (get_user_pref('LASTFM_USERNAME','N/A') != 'N/A') 
           $station_id = "lastfm://artist/user/".get_user_pref('LASTFM_USERNAME')."/neighbours";
         break;
  }
  
  if ($station_id == '')
  {
    send_to_log(1,'Error in LastFM station name');
    return '';
  }
  else
    return 'href="'.$lastfm_url.'&generate_pls&station='.$station_id.'&x=.pls" pod="1,1,'.$lastfm_url.'&image_list"';
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
         $link   = 'href="gen_playlist.php?'.$params.'" pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
         break;
         
    case MEDIA_TYPE_TV:
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
// returns true if the current user (Apache) is able to read and write to the playlists directory.
//-------------------------------------------------------------------------------------------------

function pl_enabled()
{
  return (is_readable(os_path(get_sys_pref("playlists"))) && is_writeable(os_path(get_sys_pref("playlists"))));
}

//-------------------------------------------------------------------------------------------------
// Sets the "current" playlist (in the session to be the one specified
//-------------------------------------------------------------------------------------------------

function set_current_playlist( $name, $tracks )
{  
  $_SESSION["playlist_name"] = $name;
  $_SESSION["playlist"] = $tracks;
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

/**
 * Validates that the specified file (including path) exists in the SwussCenter database.
 * 
 * Returns FALSE on error, or if the file is not valid. 
 * Otherwise returns the media file details from the database.
 *
 * @param unknown_type $fsp
 */

function pl_validate_file( $fsp )
{
  // Convert windows path delimiters to PHP/Unix style (for the database comparison).
  if (is_windows())
    $fsp = str_replace('\\','/',$fsp);

  $info = find_media_get_full_details( $fsp);
  if ($info !== false)
    return $info;

  send_to_log(8,'The following file cannot be found in the SwissCenter database: '.$fsp);
  return false;
}

/**
 * Parses a M3U playlist file and extracts an array of files referenced by the file
 * with no further processing.
 *
 * @param filename $fsp
 * @return array
 */

function load_pl_m3u( $fsp )
{
  send_to_log(6,'Playlist is of type: M3U');
  $filelist = array();
  
  if (($lines = file($fsp)) !== false)
  {
    foreach ($lines as $l)
    {
      $entry = rtrim($l);
      if ($entry[0] != '#' && !empty($entry) )
        $filelist[] = $entry;
    }
  }
  else 
    send_to_log(1,'Unable to read playlist file.');

  return $filelist;  
}

/**
 * Parses a WPL playlist file (Windows Media Playlist) and extracts an array of files 
 * referenced by the file  with no further processing.
 *
 * @param filename $fsp
 * @return array
 */

function load_pl_wpl( $fsp )
{
  send_to_log(6,'Playlist is of type: WPL (Windows media player)');
  $filelist = array();

  $xml = new XPath(FALSE, array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE) );
  if ( $xml->importFromFile($fsp) !== false)
  {  
    foreach ($xml->match('/smil/body/seq/media') as $filepath)
    {
      $attrib = $xml->getAttributes($filepath);    
      $filelist[] = $attrib["SRC"];
    }
  }
  else 
    send_to_log(1,'Unable to read playlist file or format not recognised/supported.');  
    
  return $filelist;  
}

//-------------------------------------------------------------------------------------------------
// Loads the contents of the file into the current session.
//-------------------------------------------------------------------------------------------------

function load_pl ($file)
{
  send_to_log(5,'Attempting to load playlist : '.$file);
  
  switch ( file_ext($file) )
  {
    case 'm3u' : $filelist = load_pl_m3u( $file ); break;
    case 'wpl' : $filelist = load_pl_wpl( $file ); break;
    default    : send_to_log(1,'Unknown playlist format'); break;
  }

  send_to_log(6,'Playlist tracks',$filelist);
  
  // Search through the list of filenames and try to load them into the playlist.    
  $tracks = array();
  foreach( $filelist as $fsp )
  {
    $info = pl_validate_file( make_abs_file($fsp,dirname($file)) );
    if ($info !== false)
      $tracks[] = $info;    
  }

  return $tracks;
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