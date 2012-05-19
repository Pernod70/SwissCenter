<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

// ----------------------------------------------------------------------------------
// Returns the URL for the MusicIP API
// ----------------------------------------------------------------------------------

function musicip_address()
{
  $host = '127.0.0.1';
  return 'http://'.$host.':'.get_sys_pref('MUSICIP_PORT','10002').'/';
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
// Updates the MusicIP server with the settings used in SwissCenter.
// ----------------------------------------------------------------------------------

function musicip_server_update( $style, $variety, $size, $size_type, $dupsize, $duptype )
{
  if (musicip_available())
    $temp = @file_get_contents(musicip_address().'server/updateMixSettings?style='.(int)($style/20).
                                                 '&variety='.$variety.'&size='.$size.'&type='.$size_type.
                                                 '&dupsize='.$dupsize.'&duptype='.$duptype);
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
// port defined in the config screen.
// ----------------------------------------------------------------------------------

function musicip_check( $port, $timeouts = 3 )
{
  for ($i=0; $i < $timeouts; $i++)
  {
    // fsockopen doesn't like localhost with PHP5 and Vista.
    if ( $sock = @fsockopen('127.0.0.1', $port , &$errno, &$errst, 2) )
    {
      fclose($sock);
      $status = @file_get_contents(musicip_address().'api/getstatus');
      send_to_log(6,'MusicIP status: '.$status);
      $success = true;
      break;
    }
    else
    {
      send_to_log(2,'MusicIP check failed: '.$errno, $errst);
      $success = false;
    }
  }
  return $success;
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

function musicip_mix_link( $tables, $predicate, $params = '' )
{
  $num_rows    = db_value("select count(*) from $tables $predicate");
  $num_artists = db_value("select count(distinct artist) from $tables $predicate");
  $num_albums  = db_value("select count(distinct album) from $tables $predicate");

  if (  $num_rows == 1 )
    return musicip_mix_song( db_value("select concat(dirname,filename) from $tables $predicate"), $params );
  elseif ( $num_albums == 1 && $num_artists == 1 )
    return musicip_mix_album( db_value("select distinct concat(artist,'@@',album) from $tables $predicate"), $params );
  elseif ( $num_albums == 1)
    return musicip_mix_album( db_value("select distinct album from $tables $predicate"), $params );
  elseif ( $num_artists == 1)
    return musicip_mix_artist( db_value("select distinct artist from $tables $predicate"), $params );
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
    return musicip_mix_playlist( $fsp, $params );
  }
}

function musicip_api_call( $type, $value, $params )
{
  // Settings for the mix...
  $params = array_merge( $params, array( 'content'    => 'm3u'
                                       , 'mixgenre'   => 'false'
                                       , 'size'       => get_sys_pref('MUSICIP_SIZE',20)
                                       , 'sizetype'   => get_sys_pref('MUSICIP_SIZE_TYPE','tracks')
                                       , 'rejectsize' => get_sys_pref('MUSICIP_REJECT',5)
                                       , 'rejecttype' => get_sys_pref('MUSICIP_REJECT_TYPE','tracks')
                                       , 'style'      => get_sys_pref('MUSICIP_STYLE',20)
                                       , 'variety'    => get_sys_pref('MUSICIP_VARIETY',0)
                                       , $type        => urlencode($value)
                                       , 'ext'        => '.m3u' )
                                       );

  // Save the playlist generating URL into the session for when the playlist is needed.
  $_SESSION["musicip_playlist"] = url_add_params( musicip_address().'api/mix', $params);

  // Output a link to play a MusicIP playlist.
  $params = 'spec_type=musicip&'.current_session().'&seed='.mt_rand();
  $extra = 'pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
  return 'href="gen_playlist.php?'.$params.'" '.$extra;
}

function musicip_status( $tables, $predicate )
{
  $num_rows    = db_value("select count(*) from $tables $predicate");
  $num_artists = db_value("select count(distinct artist) from $tables $predicate");

  if (  $num_rows == 1 )
    return musicip_status_song( db_value("select concat(dirname,filename) from $tables $predicate"));
  elseif ( $num_artists == 1)
    return musicip_status_artist( db_value("select distinct artist from $tables $predicate"));
  else
    return true;
}

function musicip_status_song( $song )
{
  $status = @file_get_contents(musicip_address().'api/status?song='.urlencode($song));
  send_to_log(6,'MusicIP status for song = '.$song, $status);
  return ( $status[0] === '1' ? true : false );
}

function musicip_status_artist( $artist )
{
  $status = @file_get_contents(musicip_address().'api/status?artist='.urlencode($artist));
  send_to_log(6,'MusicIP status for artist = '.$artist, $status);
  return ( $status[0] === '1' ? true : false );
}

function musicip_mix_song( $song, $params )
{
  send_to_log(6,'MusicIP mix for song = '.$song);
  return musicip_api_call('song',$song, $params);
}

function musicip_mix_artist( $artist, $params )
{
  send_to_log(6,'MusicIP mix for artist = '.$artist);
  return musicip_api_call('artist', $artist, $params);
}

function musicip_mix_album ( $album, $params )
{
  send_to_log(6,'MusicIP mix for album = '.$album);
  return musicip_api_call('album', $album, $params);
}

function musicip_mix_playlist ( $album, $params )
{
  send_to_log(6,'MusicIP mix for currently selected tracks');
  return musicip_api_call('playlist', musicip_tempplaylist_name(), $params);
}

function musicip_tempplaylist_name()
{
  return get_sys_pref('cache_dir', SC_LOCATION).'/MusicIP_TempPlaylist_'.$_SESSION["device"]["ip_address"].'.m3u';
}

function musicip_moods()
{
  $page = @file_get_contents(musicip_address().'api/moods');
  send_to_log(6,'MusicIP moods :', $page);
  if ( !empty($page) )
    $moods = explode( "\n", $page );
  else
    $moods = array();
  return $moods;
}

function musicip_playlists()
{
  $page = @file_get_contents(musicip_address().'api/playlists');
  send_to_log(6,'MusicIP playlists :', $page);
  if ( !empty($page) )
    $playlists = explode( "\n", $page );
  else
    $playlists = array();
  return $playlists;
}

function musicip_recipes()
{
  $page = @file_get_contents(musicip_address().'api/recipes');
  send_to_log(6,'MusicIP recipes :', $page);
  if ( !empty($page) )
    $recipes = explode( "\n", $page );
  else
    $recipes = array();
  return $recipes;
}

// ----------------------------------------------------------------------------------
// Returns a percentage of songs which can be used in a mix out of the total number
// of available songs.
// ----------------------------------------------------------------------------------

function musicip_mixable_percent( $timeouts = 3 )
{
  if ( musicip_available() )
  {
    for ($i=0; $i < $timeouts; $i++)
    {
      if (($html = @file_get_contents( musicip_address().'server' )) !== false)
      {
        // Page was successfully retrieved
        $html = strip_tags($html);

        // Total number of songs
        $matches = array();
        preg_match_all('/Total Songs.*?([0-9,]+)/i',$html,$matches);
        $songs = str_replace(',','',$matches[1][0]);

        // Mixable number of songs
        preg_match_all('/Mixable Songs.*?([0-9,]+)/i',$html,$matches);
        $mixable = str_replace(',','',$matches[1][0]);
        $percent = (int)($mixable/max($songs,1)*100);
        send_to_log(6,'MusicIP mixable percentage: '.$percent);
        break;
      }
      else
      {
        send_to_log(2,'MusicIP server failed to respond');
        $percent = false;
      }
    }
    return $percent;
  }
  else
    return false;
}

// ----------------------------------------------------------------------------------
// Returns the version number of the current mixer as text.
// ----------------------------------------------------------------------------------

function musicip_version( $timeouts = 3 )
{
  if ( musicip_available() )
  {
    for ($i=0; $i < $timeouts; $i++)
    {
      if (($version = @file_get_contents(musicip_address().'api/version')) !== false)
      {
        $version = preg_get('/Version (\d+\.\d+)/', $version);
        send_to_log(6,'MusicIP version: '.$version);
        break;
      }
      else
      {
        send_to_log(2,'MusicIP version failed: '.$errno, $errst);
        $version = false;
      }
    }
    return $version;
  }
  else
    return false;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
