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

//
// Returns the correct link to cause the showcenter to start obtaining playlists and displaying/play 
// media.
//

function pl_link( $type, $spec= '', $media = 'playlist')
{
  $link   = '';
  $seed   = mt_rand();
  $server = $_SESSION["opts"]["server_address"];

  switch ($media)
  {
    case 'audio' :
          // Ensure the display for audio will be in the correct place...
          if ($_SESSION["opts"]["screen"] == 'PAL')
            $dummy = @file_get_contents('http://'.$_SESSION["opts"]["current_client"].':2020/pod_audio_info.cgi?x=210&y=464');  
          else
            $dummy = @file_get_contents('http://'.$_SESSION["opts"]["current_client"].':2020/pod_audio_info.cgi?x=210&y=369');  
         
          // Build link
          $link .= 'href="gen_playlist.php?shuffle='.$_SESSION["shuffle"].'&seed='.$seed.'&type='.$type.'&spec='.rawurlencode($spec).'" ';
          $link .= 'pod="3,1,http://'.$server.'/playing_list.php?shuffle='.$_SESSION["shuffle"].'&seed='.$seed.'&type='.$type.'&spec='.rawurlencode($spec).'" ';
          break;
    case 'photo':
          $link .= 'href="MUTE" ';
          $link .= 'pod="1,1,http://'.$server.'/gen_photolist.php?shuffle='.$_SESSION["shuffle"].'&seed='.$seed.'&type='.$type.'&spec='.rawurlencode($spec).'" ';
          break;
    default :
          $link .= 'href="gen_playlist.php?shuffle='.$_SESSION["shuffle"].'&seed='.$seed.'&type='.$type.'&spec='.rawurlencode($spec).'" ';
          $link .= 'vod="playlist" ';
          break;
  }
  
  return $link;
}

//
// Generates the array of tracks to play for the current playlist
//

function pl_tracklist($type, $spec, $shuffle = false, $seed = 0)
{
  switch ($type)
  {
    case 'playlist':
          $array = $_SESSION["playlist"];
          break;
    case 'sql':
          $array = db_toarray($spec);
          break;
    case 'file':
          $array = array(array("FILENAME"=>$spec));
          break;
    case 'dir':
          $media_type = substr_between_strings($spec,'<<','>>');
          foreach ( db_col_to_list("select name from media_types, media_locations where media_id = media_type and media_name='".$media_type."'") as $path) 
            $array = array_merge($array,db_toarray(str_replace('<<'.$media_type.'>>',$path,$spec))); 
          break;
  }

  if ($shuffle && count($array)>1)
    shuffle_fisherYates($array,$seed);

  return $array;
}

//
// returns true if the current user (Apache) is able to read and write to the
// playlists directory.
//

function pl_enabled()
{
  return (is_readable(os_path(get_sys_pref("playlists"))) && is_writeable(os_path(get_sys_pref("playlists"))));
}

//
// Stores the details of the current music selection in the SESSION playlist.
//

function build_pl($server, $sql)
{
  $back_url  = $_SESSION["history"][count($_SESSION["history"])-1]["url"];
  $data      = array();

  // Get the file details from the database and add to the session playlist
  if ( ($data = db_toarray($sql)) === false)
    page_error('A database error occurred');
  else
  {
    $_SESSION["playlist_name"] = "&lt;Custom&gt;";

    foreach ($data as $row)
      $_SESSION["playlist"][] = $row;
  }

  page_header('Tracks Added');

  echo 'The tracks you selected have been added to the playlist.
        <p> To start playing the tracks in your playlist or to modify the playlist
            at any time, press the <font color="'.style_value("BUTTON_DESC_COLOUR".'#FFFFFF').'">HOME</font>
            button on your remote control and then select the <font color="'.style_value("MENU_OPTION_REF_COLOUR",'#FFFFFF').'">
            "Manage Playlists"</font> option.';
  $menu = new menu();
  $menu->add_item('Manage Playlists','manage_pl.php');
  $menu->display();
  page_footer($back_url);
}

// Outputs the information about the current music playlist
// (takes an infotab() object as a paramter)

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

    $info->add_item( 'Tracks', $num_tracks.($num_tracks ==0 ? ' Track' : ' Tracks') );
    $info->add_item( 'Play Time', hhmmss($playtime) );
  }
  else
  {
    echo '<center>You do not have a playlist defined at the moment.</center>';
  }
  $info->display();
}

//
// Clears the current playlist from the session
//
function clear_pl()
{
  session_unregister("playlist_name");
  session_unregister("playlist");
  unset($_SESSION["playlist_name"]);
  unset($_SESSION["playlist"]);
}

//
// Loads the contents of the file into the current session.
//

function load_pl ($file, $action)
{
  if ($action == "replace")
  {
    clear_pl();
    $_SESSION["playlist_name"] = file_noext(basename($file));
  }
  else
  {
    $_SESSION["playlist_name"] = "&lt;Custom&gt;";
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
// TO-DO: uncomment and subsequent line once photos have been added to the system (or at least the table!).
        $info_photo = db_toarray("select * from photos where dirname = '".db_escape_str(str_suffix(dirname($fsp),'/'))."' and filename='".db_escape_str(basename($fsp))."' ");      
        $all = $info_movie + $info_music + $info_photo;
//        $all = $info_movie + $info_music;
        
        if ( count($all) == 1)
          $_SESSION["playlist"][] = array_pop($all);
      }
    }
  }
}

//
// Save a Playlist
//

function save_pl ($file)
{
  $playlist= array();

  foreach ($_SESSION["playlist"] as $row)
    $playlist[] = os_path($row["DIRNAME"].$row["FILENAME"]);

  array2file($playlist, $file);
  $_SESSION["playlist_name"] = file_noext(basename($file));
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
