<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/prefs.php'));
require_once( realpath(dirname(__FILE__).'/utils.php'));

// ----------------------------------------------------------------------------------
// Returns true if a MusicIP webservice is available on the local machine using the
// port defined in the config screen. The
// ----------------------------------------------------------------------------------

function musicip_check( $port )
{
  $temp = '';
  $result = false;

  if ( $sock = @fsockopen('localhost', $port , $temp, $temp, 0.5))
  {
    fclose($sock);
    $result = true; 
  }

  return$result;
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

function musicip_mix_link( $type, $value )
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
                 );
                       
  // Save the playlist generating URL into the session for when the playlist is needed.
  $_SESSION["musicip_playlist"] = url_add_params('http://localhost:'.get_sys_pref('MUSICIP_PORT','10002').'/api/mix', $params);
  
  // Output a link to play a MusicIP playlist.
  $params = 'spec_type=musicip&'.current_session().'&seed='.mt_rand(); 
  $extra = 'pod="'.now_playing_sync_type().',1,'.server_address().'playing_list.php?'.$params.'" ';
  return 'href="gen_playlist.php?'.$params.'" '.$extra;
}

function musicip_mix_song( $song )
{
  return musicip_mix_link('song',$song);
}

function musicip_mix_artist( $artist )
{
  return musicip_mix_link('artist',$artist);
}

function musicip_mix_album ( $album )
{
  return musicip_mix_link('album',$album);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>