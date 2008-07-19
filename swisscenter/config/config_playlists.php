<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/../base/browse.php'));

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function playlists_display( $message = '' )
  {
    // Get list of available playlists
    $dir_list  = array();
    $file_list = array();
    dir_contents_FS(str_suffix(get_sys_pref('PLAYLISTS'),'/'), media_exts_playlists(), $dir_list, $file_list);
    $playlists = array();
    foreach ($file_list as $file)
      $playlists[file_noext($file["filename"])] = $file["dirname"].$file["filename"];
    
    echo '<p><h1>'.str('PLAYLISTS').'<p>';
    message($message);
    form_start('index.php');
    form_hidden('section','PLAYLISTS');
    form_hidden('action','UPDATE');
    form_input('location',str('LOCATION'),50,'',get_sys_pref('PLAYLISTS'));
    form_label(str('PLAYLISTS_CONFIG_PROMPT'));
    form_input('itunes',str('ITUNES_LIBRARY'),50,'',get_sys_pref('ITUNES_LIBRARY'));
    form_label(str('ITUNES_CONFIG_PROMPT'));
    form_list_static('autoload',str('PLAYLIST_AUTOLOAD'),$playlists,get_sys_pref('PLAYLIST_AUTOLOAD',''),false,false);
    form_label(str('PLAYLIST_AUTOLOAD_PROMPT'));
    form_submit(str('SAVE_SETTINGS'),2);
    form_end();
  }
  
  // ----------------------------------------------------------------------------------
  // Saves the new parameter
  // ----------------------------------------------------------------------------------
  
  function playlists_update()
  {
    $dir = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["location"])),'/');
    $itunes_library = rtrim(str_replace('\\','/',un_magic_quote($_REQUEST["itunes"])),'/');
    $autoload = $_REQUEST["autoload"];
    
    if (empty($_REQUEST["location"]))
      playlists_display("!".str('PLAYLISTS_ERROR_DIR'));
    elseif (!file_exists($dir))
      playlists_display("!".str('PLAYLISTS_ERROR_INVALID'));
    elseif ( ($dir[0] != '/' && $dir[1] != ':') || $dir=='..' || $dir=='.')
      playlists_display("!".str('PLAYLISTS_ERROR_PATH'));
    elseif (!empty($_REQUEST["itunes"]) && !is_file($itunes_library))
      playlists_display("!".str('ITUNES_ERROR_PATH'));
    elseif (!empty($_REQUEST["itunes"]) && !strpos($itunes_library, 'iTunes Music Library.xml'))
      playlists_display("!".str('ITUNES_ERROR_INVALID'));
    else 
    {
      set_sys_pref('PLAYLISTS',$dir);
      set_sys_pref('ITUNES_LIBRARY',$itunes_library);
      if (!empty($autoload))
        set_sys_pref('PLAYLIST_AUTOLOAD',$autoload);
      else
        delete_sys_pref('PLAYLIST_AUTOLOAD');
      playlists_display(str('SAVE_SETTINGS_OK'));
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
