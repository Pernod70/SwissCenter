<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  require_once("base/utils.php");
  require_once("base/file.php");

  $server     = 'http://'.$_SERVER['SERVER_ADDR'].":".$_SERVER['SERVER_PORT'].'/';
  $data       = array();
  $type       = un_magic_quote($_REQUEST["type"]);
  $spec       = un_magic_quote($_REQUEST["spec"]);
  $item_count = 0;

  //
  // Retuns a single line of a playlist in the format that the showcenter expects
  //

  function pl_entry( $server, $row )
  {
    global $item_count;

    // The showcenter can only cope with playlists of 4000 items (which should be enough for
    // anyone to sit and listen to!). However, the user shouldn't notice as shuffle is done 
    // before the truncate of the playlist (if the user has selected shuffle).

    if ($item_count < 4000)
    {
      if ( is_null($row["TITLE"]) )
        $title = rtrim(file_noext(basename($row["DIRNAME"].$row["FILENAME"])));
      else
        $title = rtrim($row["ARTIST"]).' - '.rtrim($row["ALBUM"]).' - '.rtrim($row["TITLE"]);

      if (is_showcenter())
        echo  $title.'|0|0|'.$server.make_url_path(ucfirst($row["DIRNAME"]).$row["FILENAME"])."|\n";
      else
        echo  $server.make_url_path(ucfirst($row["DIRNAME"]).$row["FILENAME"]).newline();

      $item_count++;
    }
  }


//*************************************************************************************************
// Main logic
//*************************************************************************************************

  if ($type == "playlist")
  {
    $array = $_SESSION["playlist"];

    if ($_SESSION["shuffle"] == "on")
      shuffle($array);

    foreach ( $array as $row)
      pl_entry($server, $row);
  }
  elseif ($type == "sql")
  {
    if (($data = db_toarray($spec)) !== false)
    {
      if ($_SESSION["shuffle"] == "on")
        shuffle($data);

      foreach ($data as $row)
        pl_entry($server, $row);
    }
  }
  elseif ($type == "file")
  {
    $row = array("FILENAME"=>$spec);
    pl_entry($server, $row);
  }

  // Ifthis is a non-showcenter browser then we need to output some headers
  
  if (!is_showcenter())
  {
    header('Content-Disposition: attachment; filename=Playlist.m3u');
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
    header('Content-Type: audio/x-mpegurl');
    header("Content-Length: ".ob_get_length());
    ob_flush();
  }


  /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
