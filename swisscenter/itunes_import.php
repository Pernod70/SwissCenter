<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/utils.php'));
  require_once( realpath(dirname(__FILE__).'/base/file.php'));
  require_once( realpath(dirname(__FILE__).'/base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/base/media.php'));

  /**
   * Callback functions to perform parsing of the iTunes XML library/playlists files. We are using
   * the more difficult EXPAT parser as it does not require us to load the entire file into memory
   * to parse it. On my system, the iTunes library file is over 13 Mbytes - not good!
   *
   */
  function start_tag_itunes($parser, $name, $attribs)
  {
     global $tag, $key, $level, $current_section, $key_info, $dict_info, $contents;
     $tag = $name;
     $contents = '';

     if (++$level == 4)
     {
       $dict_info = array();
       $key_info = array();
     }
  }

  function end_tag_itunes($parser, $name)
  {
     global $tag, $key, $level, $current_section, $key_info, $dict_info, $contents;

     // iTunes' XML file is horrible to parse. Instead of storing information in the normal
     //  XML format (Eg: "<artist>Madonna</artist>") Apple insist on using two key-value pairs
     // together. (eg: "<key>artist</key><string>Madonna</string>").

     if ($tag == 'KEY')
     {
       $key = $contents;
       if ( in_array($contents, array('Tracks','Playlists')) )
         $current_section = $contents;
     }
     else
     {
       if ( $current_section == 'Tracks' && $level == 5 )
         $dict_info[$key] = $contents;
       elseif ( $current_section == 'Playlists' && $level == 7 )
         $dict_info[] = $contents;
       else
         $key_info[$key] = $contents;
     }

     // If we are exiting level 4 then we have a complete track/playlist object.
     if ($level-- == 4 && ! empty($dict_info) )
     {
       if ($current_section == 'Tracks')
         process_itunes_track( $dict_info );
       elseif ($current_section == 'Playlists')
         process_itunes_playlist( $key_info, $dict_info);
       else
         send_to_log(6,'Unknown iTunes section encountered',$current_section);
     }
  }

  function tag_contents_itunes($parser, $data)
  {
     global $contents;
     $contents .= $data;
  }

  /**
   * Converts a full URI specification for a local file into an actual PHP file specification (with
   * forward slashes, regardless of operating system).
   *
   * @param string $url
   * @return string
   */
  function path_from_file_url( $url )
  {
    // Only converts URI's using the "file://" system
    if ( strpos($url,'file://') !== false)
    {
      $url = str_replace('\\','/',rawurldecode($url));
      $url = substr($url, strpos($url,'://')+3);
      $url = substr($url, strpos($url,'/')+1);
      // If not a local Windows path then make UNC
      if ( strpos($url,':/') === false ) $url = '/'.$url;
    }
    return $url;
  }

  /**
   * Creates an "m3u" playlist identical to the iTunes playlist, and with the same name which
   * contains all of the songs in the iTunes playlist that are available on the showcenter (ie: are
   * within a defined media location).
   *
   * @param array $attribs
   * @param array $values
   */
  function process_itunes_playlist( $attribs, $values )
  {
    $file  = get_sys_pref("PLAYLISTS", SC_LOCATION.'playlists').'/'.$attribs["Name"].'.m3u';
    $sql   = 'select m.* from mp3s m, itunes_map i where i.swisscenter_id = m.file_id and i.itunes_id = ';
    $items = 0;

    $playlist = array('#EXTM3U');
    foreach ($values as $itunes_id)
    {
      $mp3 = array_pop(db_toarray($sql.$itunes_id));
      if (!empty($mp3))
      {
        $items++;
        $playlist[] = '#EXTINF:'.$mp3["LENGTH"].','.$mp3["TITLE"];
        $playlist[] = os_path($mp3["DIRNAME"].$mp3["FILENAME"]);
      }
    }

    if ($items>0)
    {
      array2file($playlist, $file);
      send_to_log(4,"Writing playlist: ".$file);
    }
  }

  /**
   * Uses the track details found in the iTunes file and adds/updates the database
   *
   * @param array $values
   */
  function process_itunes_track( $values)
  {
    $fsp = utf8_decode(path_from_file_url($values["Location"]));
    $location_id = db_value("select location_id from media_locations where instr('".db_escape_str($fsp)."',name)>0 and media_type=".MEDIA_TYPE_MUSIC);
    $swiss_id = db_value("select file_id from mp3s where dirname='".db_escape_str(dirname($fsp))."/' and filename='".db_escape_str(basename($fsp))."'");

    // Perform some sanity checking on the file
    if (!is_file($fsp) )
      send_to_log(5,'File found in iTunes library cannot be located on disk',$fsp);
    elseif ( !is_readable($fsp) )
      send_to_log(5,'SwissCenter does not have permissions to read the file found in the iTunes library',$fsp);
    elseif ( !in_array(file_ext($fsp), media_exts_music()) )
      send_to_log(5,'SwissCenter does not support files of type "'.$values["Kind"].'"',$fsp);
    elseif ( empty($location_id) )
      send_to_log(5,'File found in iTunes library is not within a SwissCenter media location',$fsp);
    else
    {
      if ( empty($swiss_id) )
      {
        process_mp3( dirname($fsp).'/' , $location_id, basename($fsp));
        $swiss_id = db_value("select file_id from mp3s where dirname='".db_escape_str(dirname($fsp))."/' and filename='".db_escape_str(basename($fsp))."'");
      }

      // Record the mapping between the iTunes ID and the swisscenter ID
      db_insert_row('itunes_map', array( "ITUNES_ID"=>$values["Track ID"], "SWISSCENTER_ID"=>$swiss_id) );
    }
  }

  /**
   * Takes an iTunes XML file (either the entire library file, or an exported playlist) and parses
   * the track and playlist information contained within. Track details are added/updated within the
   * SwissCenter database and playlists are crreated in the user's specified playlist directory for
   * each iTunes playlist.
   *
   * NOTE: Tracks are only processed if they are located within a defined media location. If they
   *       are stored elsewhere on the filesystem then they will be ignored.
   *
   * @param string $filename
   */
  function parse_itunes_file( $filename )
  {
    // Initialize global variables
    $current_section = '';
    $key_info        = array();
    $dict_info       = array();
    $level           = 0;
    $tag             = '';
    $key             = '';

    send_to_log(4,'Parsing the iTunes Music Library for Playlists');

    // Clear the iTunes mapping table before generating any playlists
    db_sqlcommand("delete from itunes_map");

    // Create XML parser
    $xmlparser = xml_parser_create("UTF-8");
    if ($xmlparser !== false)
    {
      xml_set_element_handler($xmlparser, "start_tag_itunes", "end_tag_itunes");
      xml_set_character_data_handler($xmlparser, "tag_contents_itunes");

      // Read and process XML file
      $fp = fopen($filename, "r");
      if ($fp !== false)
      {
        while ($data = fread($fp, 8192))
        {
          $data = eregi_replace(">"."[[:space:]]+"."<","><",$data);
          if (!xml_parse($xmlparser, $data , feof($fp)))
          {
            send_to_log(8,'XML parse error: '.xml_error_string(xml_get_error_code($xmlparser)).xml_get_current_line_number($xmlparser));
            break;
          }
        }
      }
      else
        send_to_log(5,'Unable to read the specified file',$filename);

      xml_parser_free($xmlparser);
    }
    else
      send_to_log(5,'Unable to create an expat XML parser - is the "xml" extension loaded into PHP?');
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
