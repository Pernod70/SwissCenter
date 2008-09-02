<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

// ----------------------------------------------------------------------------------
// Returns the URL for the MusicIP api
// ----------------------------------------------------------------------------------

function musicip_address()
{
  return 'http://localhost:'.get_sys_pref('MUSICIP_PORT','10002').'/';
}

// ----------------------------------------------------------------------------------
// Tells the MusicIP server to rescan the specified directory for music files to add
// to it's library.
// ----------------------------------------------------------------------------------

function musicip_server_add_dir( $dir )
{
  if (musicip_available())    
    $temp = @file_get_contents(musicip_address().'server/add?root='.urlencode(os_path($dir)));
}

// ----------------------------------------------------------------------------------
// Tells the MusicIP server to refresh it's cache.
// ----------------------------------------------------------------------------------

function musicip_server_refresh_cache()
{
  if (musicip_available())    
    $temp = @file_get_contents(musicip_address().'server/refresh');  
}

// ----------------------------------------------------------------------------------
// Tells the MusicIP server to start validating any tracks that require it in the
// background.
// ----------------------------------------------------------------------------------

function musicip_server_validate()
{
  if (musicip_available())    
    $temp = @file_get_contents(musicip_address().'server/validate?action=Start');
}

// ----------------------------------------------------------------------------------
// Returns true if a MusicIP webservice is available on the local machine using the
// port defined in the config screen. The
// ----------------------------------------------------------------------------------

function musicip_check( $port )
{
  $temp = '';
  $result = false;

  if ( $sock = @fsockopen('localhost', $port , $temp, $temp, 1.5))
  {
    fclose($sock);
    $status = @file_get_contents("http://localhost:$port/api/getstatus");
    $result = ( $status !== FALSE );
  }

  return $result;
}

function musicip_available( $recheck = false)
{
  if ( !isset($_SESSION["MUSICIP_AVAILABLE"]) || $recheck )
    $_SESSION["MUSICIP_AVAILABLE"] = musicip_check( get_sys_pref('MUSICIP_PORT','10002') );
  return $_SESSION["MUSICIP_AVAILABLE"];
}

// ----------------------------------------------------------------------------------
// Returns a link to a MusicIP playlist.
// ----------------------------------------------------------------------------------

function musicip_mix_link( $tables, $predicate )
{
  $num_rows    = db_value("select count(*) from $tables $predicate");
  $num_artists = db_value("select count(distinct artist) from $tables $predicate");
  $num_albums  = db_value("select count(distinct album) from $tables $predicate");
  
  if (  $num_rows == 1 )
    return musicip_mix_song( db_value("select concat(dirname,filename) from $tables $predicate"));
  elseif ( $num_albums == 1 && $num_artists == 1 )
    return musicip_mix_album( db_value("select distinct concat(artist,'@@',album) from $tables $predicate"));
  elseif ( $num_albums == 1)
    return musicip_mix_album( db_value("select distinct album from $tables $predicate"));
  elseif ( $num_artists == 1)
    return musicip_mix_artist( db_value("select distinct artist from $tables $predicate"));
  else 
  {
    $tracks = array();
    $fsp = musicip_tempplaylist_name();
    $count = 0;

    // Fetch the tracks the user has written and prepare to write the first 200 (for speed) into a temporary playlist file.
    foreach (db_toarray("select dirname,filename from $tables $predicate") as $row)
    {
      $tracks[$count++] = os_path($row["DIRNAME"].$row["FILENAME"]);
      if ($count > 200)
        break;
    }

    // Write the playlist.
    array2file($tracks, $fsp);
    return musicip_mix_playlist( $fsp );
  }
}

function musicip_api_call( $type, $value )
{
  // Settings for the mix...
  $params = array( 'content'    => 'm3u'
                 , 'mixgenre'   => 'false'
                 , 'size'       => get_sys_pref('MUSICIP_SIZE',20)
                 , 'sizetype'   => get_sys_pref('MUSICIP_SIZE_TYPE','tracks')
                 , 'rejectsize' => get_sys_pref('MUSICIP_REJECT',5)
                 , 'rejecttype' => get_sys_pref('MUSICIP_REJECT_TYPE','tracks')
                 , 'style'      => get_sys_pref('MUSICIP_STYLE',20)
                 , 'variety'    => get_sys_pref('MUSICIP_VARIETY',0)
                 , $type        => urlencode($value)
                 , 'ext'        => '.m3u'
                 );
                       
  // Save the playlist generating URL into the session for when the playlist is needed.
  $_SESSION["musicip_playlist"] = url_add_params( musicip_address().'api/mix', $params);
  
  // Output a link to play a MusicIP playlist.
  $params = 'spec_type=musicip&'.current_session().'&seed='.mt_rand(); 
  $extra = 'pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
  return 'href="gen_playlist.php?'.$params.'" '.$extra;
}

function musicip_mix_song( $song )
{
  send_to_log(6,'MusicIP mix for song = '.$filelist);
  return musicip_api_call('song',$song);
}

function musicip_mix_artist( $artist )
{
  send_to_log(6,'MusicIP mix for artist = '.$artist);
  return musicip_api_call('artist',$artist);
}

function musicip_mix_album ( $album )
{
  send_to_log(6,'MusicIP mix for album = '.$album);
  return musicip_api_call('album',$album);
}

function musicip_mix_playlist ( $album )
{
  send_to_log(6,'MusicIP mix for currently selected tracks');
  return musicip_api_call('playlist', musicip_tempplaylist_name());
}

function musicip_tempplaylist_name()
{
  return get_sys_pref('cache_dir', SC_LOCATION).'/MusicIP_TempPlaylist_'.$_SESSION["device"]["ip_address"].'.m3u';
}

// ----------------------------------------------------------------------------------
// Returns a percentage of songs which can be used in a mix out of the total number
// of available songs.
// ----------------------------------------------------------------------------------

function musicip_mixable_percent()
{
  if ( musicip_available() )
  {
    $matches = array();
    $html = @file_get_contents( musicip_address().'server' );
    
    // Page was successfully retrieved
    if ($html !== false)
    {
      $html = strip_tags($html);
      
      // Total number of songs
      preg_match_all('/Total Songs *([0-9,]*)/i',$html,$matches);
      $songs = str_replace(',','',$matches[1][0]);
  
      // Total number of songs
      preg_match_all('/Mixable Songs *([0-9,]*)/i',$html,$matches);
      $mixable = str_replace(',','',$matches[1][0]);
  
      return (int)($mixable/max($songs,1)*100);
    }
    else 
      return false;
  }
  else 
    return false;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
