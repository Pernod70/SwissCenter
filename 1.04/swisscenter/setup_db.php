<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/mysql.php");
  

  // Open the file
  if (($fsp = file('setup.sql')) !== false)
  {
    // Join all lines together (with an extra space) then split into an array of
    // SQL commands (each command ends with a semi-colon);
    $commands = split(";",implode(" ",$fsp));

    // Loop through the commands, executing them
    foreach ($commands as $sql)
      if ( strlen(trim($sql)) > 0 ) 
        db_sqlcommand($sql);

    header("Location: /do_refresh.php?type=all");
  }
  else
  {
    page_header('Unable to refresh database');
    echo '<center>The setup.sql file is missing from the config directory</center>';
    page_footer('/index.php');
  }
  

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
