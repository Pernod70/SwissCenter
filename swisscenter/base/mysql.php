<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/logging.php'));

/**
 * Returns the given UNIX timestamp (or current time if not specified) as a MySQL compatible date
 *
 * @param string $time
 */
function db_datestr( $time = '')
{
  return date('Y-m-d H:i:s',(empty($time) ? time() : $time));
}

/**
 * Escapes the given string for insertion into the database.
 *
 * @param string $text
 */
function db_escape_str( $text )
{
  return db::getInstance()->real_escape_string($text);
}

function db_escape_wildcards( $text )
{
  return str_replace('%','\%',str_replace('_','\_',$text));
}

/**
 * Tests the connection to the database using the details provided.
 *
 * @param string $host
 * @param string $username
 * @param string $password
 * @param string $database
 */
function test_db($host = DB_HOST, $username = DB_USERNAME, $password = DB_PASSWORD, $database = DB_DATABASE)
{
  if ( !extension_loaded('mysqli') || !defined('DB_HOST') || !defined('DB_USERNAME') || !defined('DB_PASSWORD') || !defined('DB_DATABASE'))
    return 'FAIL';
  elseif (! $db_handle = @mysqli_connect($host, $username, $password))
    return '!'.str('DATABASE_NOCONNECT');
  elseif (! mysqli_select_db($db_handle, $database) )
    return '!'.str('DATABASE_NOSELECT');
  else
    return 'OK';
}

/**
 * Returns the version of the MySQL server.
 */
function db_server_info()
{
  return db::getInstance()->server_info;
}

/**
 * Selects the results of the query ($sql) into the specified array (&$data).
 *
 * Returns an array if the query completed successfully, otherwise the funtions returns FALSE
 *
 * @param string $sql
 */
function db_toarray($sql)
{
  $data = array();

  $success = db::getInstance()->query($sql);
  if ($success)
    while (!is_null($row = db::getInstance()->fetch_array()))
      $data[] = $row;

  db::getInstance()->free();
  return ($success ? $data : false );
}

/**
 * Searches the $col column of the $table table for the given $text.
 * If a matching row is found, then the $return_col column is returned, otherwise a null is returned.
 *
 * @param string $table
 * @param string $col
 * @param string $return_col
 * @param string $text
 */
function db_lookup( $table, $col, $return_col, $text )
{
  if (db_value("select count(*) from $table where $col = '".db_escape_str($text)."'") > 0)
    return db_value("select $return_col from $table where $col = '".db_escape_str($text)."'");
  else
    return null;
}

/**
 * Selects the results of the query ($sql) into the specified array (&$data).
 *
 * Returns an array of columns for the first row returned by the sql,
 * otherwise the function returns FALSE
 *
 * @param string $sql
 */
function db_row($sql)
{
  $success = db::getInstance()->query($sql);

  if ($success && !is_null($row = db::getInstance()->fetch_array()))
    $data = $row;

  db::getInstance()->free();
  return ($success ? $data : false );
}

/**
 * Uses the results of the query ($sql) to build an array (&$data) where each entry in the array
 * is the value of the first column selected by the query.
 *
 * EG: "Select username from users;" might return array('Rod','Jane','Freddy')
 *
 * @param string $sql
 */
function db_col_to_list($sql)
{
  $data = array();

  $success = db::getInstance()->query($sql);

  if ($success)
    while (!is_null($row = db::getInstance()->fetch_array()))
      $data[] = @array_pop($row);

  db::getInstance()->free();
  return ($success ? $data : false );
}

/**
 * Executes the command passed in the $sql variable. This function does not return any results,
 * so cannot be used for a SELECT statement.
 *
 * Returns TRUE if the query completed successfully, otherwise the funtions returns FALSE
 *
 * @param string  $sql
 * @param boolean $log_errors
 */
function db_sqlcommand($sql, $log_errors = true)
{
  $success = db::getInstance()->query($sql, $log_errors);
  db::getInstance()->free();
  return ($success ? true : false );
}

/**
 * Executes all the SQL commands contained within the file specified, returning the number of
 * errors that were encountered.
 *
 * @param string $fsp
 */
function db_sqlfile($fsp)
{
  $errors = 0;
  if (($contents = @file($fsp)) !== false)
  {
    // If the SQL script contains function or procedure definitions then we do not split
    // into separate commands.
    if (preg_match('/.*(function|procedure).*/i', implode(" ",$contents)) > 0)
      $commands = array(implode(" ",$contents));
    else
      $commands = split(";",implode(" ",$contents));

    foreach ($commands as $sql)
      if ( strlen(trim($sql)) > 0 )
        if (!db_sqlcommand($sql))
          $errors++;
  }

  return $errors;
}

/**
 * Function to run a SQL command as the "root" user in MySQL (for building databases, etc)
 * Returns TRUE is the query completed successfully, otherwise returns FALSE
 *
 * @param string $root_password
 * @param string $sql
 */
function db_root_sqlcommand($root_password, $sql)
{
  // Connect to the Database
  $link = new mysqli('localhost', 'root', $root_password);
  if (mysqli_connect_error())
  {
    send_to_log(1,"Connected Failed :: " . mysqli_connect_error());
    return false;
  }

  // Execute the query
  if (! ($result = @$link->query($sql)))
    return false;
  else
  {
    // Clean up and disconnect link
    @mysqli_free_result($result);
    mysqli_close($link);
    return true;
  }
}

/**
 * Executes the command passed in the $sql variable and returns the first column of the first
 * row in the result set.
 *
 * NOTE: This function should be used when the SQL is expected to return only one value, such
 *       as a "SELECT COUNT(*) FROM tablename;" statement
 *
 * @param string $sql
 */
function db_value($sql)
{
  $success = db::getInstance()->query($sql);
  $result  = '';

  if ($success)
  {
    $row    = db::getInstance()->fetch_array();
    $result = @array_pop($row);
  }

  db::getInstance()->free();
  return ($success ? $result : false);
}

/**
 * Takes the elements passed in the array and converts them to the SET section of a SQL update
 * command (taking into account the type of variable)
 *
 * @param array $array
 */
function db_array_to_set_list($array)
{
  $columns = array();

  foreach( $array as $key => $value )
  {
    if (!is_numeric($value) and empty($value))
      $columns[] = $key."=null";
    elseif (is_string($value))
      $columns[] = $key ."='".db_escape_str($value)."'";
    else
      $columns[] = $key."=".$value;
  }

  return implode(', ',$columns);
}

/**
 * Inserts row into the database.
 *
 * Note that the fields are given as an associative array. Each KEY value in the
 * array is the column name, and each VALUE in the array is the value to insert for
 * that particular field.
 *
 * NOTE: All strings will be automatically escaped.
 *
 * Returns TRUE on success, FALSE otherwise (and populates the $errmsg variable)
 *
 * @param string $table - the table to insert the row into.
 * @param array  $fields - an associative array containing the values to insert into the table.
 */
function db_insert_row( $table, $fields )
{
  $flist = '';
  $vlist = '';

  foreach( $fields as $key => $value )
  {
    $flist = $flist.",$key";
    if     (!is_numeric($value) and empty($value))
      $vlist = $vlist.",null";
    elseif (is_string($value))
      $vlist = $vlist.",'".db_escape_str($value)."'";
    else
      $vlist = $vlist.",$value";
  }

  $sql = "insert into $table (".trim($flist,',').") values (".trim($vlist,',').")";

  $success = db::getInstance()->query($sql);
  $insert_id = (db::getInstance()->insert_id == 0) ? true : db::getInstance()->insert_id;
  db::getInstance()->free();
  return ($success ? $insert_id : false);
}

/**
 * Updates row in the database.
 *
 * Note that the fields are given as an associative array. Each KEY value in the
 * array is the column name, and each VALUE in the array is the value to insert for
 * that particular field.
 *
 * NOTE: All strings will be automatically escaped.
 *
 * Returns TRUE on success, FALSE otherwise (and populates the $errmsg variable)
 *
 * @param string  $table - the table to insert the row into.
 * @param integer $id - id of row to update.
 * @param array   $fields - an associative array containing the values to insert into the table.
 * @param string  $key_id
 */
function db_update_row( $table, $id, $fields, $key_id = 'file_id' )
{
  $flist = '';
  $vlist = '';
  $sql = "update $table set ";

  foreach( $fields as $key => $value )
  {
    $flist = $key;
    if     (!is_numeric($value) and empty($value))
      $vlist = "null";
    elseif (is_string($value))
      $vlist = "'".db_escape_str($value)."'";
    else
      $vlist = $value;

    $sql = $sql.$flist."=".$vlist.",";
  }

  $sql = trim($sql,',')." where $key_id=$id";

  return db_sqlcommand($sql);
}

/**
 * Returns the primary key of the specified table.
 *
 * @param string $table
 */
function db_primary_key( $table )
{
  return db_value("SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS`
                   WHERE (`TABLE_SCHEMA` = '".DB_DATABASE."') AND (`TABLE_NAME` = '$table') AND (`COLUMN_KEY` = 'PRI')");
}

/**
 * Returns an array of columns in the specified table,
 * otherwise the function returns FALSE
 *
 * @param string $table
 * @param string $database
 */
function db_table_columns( $table, $database = DB_DATABASE )
{
  $data = array();

  $success = db::getInstance()->query("SHOW COLUMNS FROM $table FROM $database");

  if ($success)
    while (!is_null($row = db::getInstance()->fetch_array()))
      $data[] = $row["FIELD"];

  db::getInstance()->free();
  return ($success ? $data : false);
}

/**
 * Returns an array of tables in the specified database,
 * otherwise the function returns FALSE
 *
 * @param string $database
 */
function db_tables( $database = DB_DATABASE )
{
  $data = array();

  $success = db::getInstance()->query("SHOW TABLES FROM $database");

  if ($success)
    while (!is_null($row = db::getInstance()->fetch_array()))
      $data[] = $row[0];

  db::getInstance()->free();
  return ($success ? $data : false);
}

/**************************************************************************************************
  DB class definition, that extends mysqli.
 *************************************************************************************************/

class db extends mysqli
{
  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------

  protected $sql_to_execute;
  protected $result;

  protected static $instance;

  #-------------------------------------------------------------------------------------------------
  # Constructor
  #-------------------------------------------------------------------------------------------------

  private function __construct($host = DB_HOST, $username = DB_USERNAME, $password = DB_PASSWORD, $database = DB_DATABASE)
  {
    if (!extension_loaded('mysqli'))
    {
      @send_to_log(1,'PHP extension mysqli not enabled - cannot connect to database');
      return false;
    }
    else
    {
      @parent::__construct($host, $username, $password, $database);
      if (mysqli_connect_error())
      {
        send_to_log(1,"Connected Failed :: " . mysqli_connect_error());
        return false;
      }
      else
      {
        @parent::set_charset("utf8");
        return true;
      }
    }
  }

  /**
   * Creates an instance of mysqli, only if it doesn't exist.
   *
   */
  public static function getInstance()
  {
    if ( !self::$instance ) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   * Performs a query on the database, and logs any errors.
   *
   * @param string $sql SQL to execute
   * @param boolean $log_error SQL to execute
   * @return mysqli_result Object
   */
  public function query($sql, $log_error = true)
  {
    @send_to_log(9,"SQL> ".$sql);

    $this->sql_to_execute = $sql;
    $this->result = @parent::query($sql);

    if ($this->result) {
      $success = true;
    }
    else {
      $success = false;
      if ($log_error)
        send_to_log(1, $this->error, $this->sql_to_execute);
    }
    return $success;
  }

  /**
   * Fetch a result row as an associative, a numeric array, or both.
   *
   * @param constant $resulttype
   */
  public function fetch_array($resulttype = MYSQLI_ASSOC)
  {
    if ($this->result) {
      if (!is_null($row = $this->result->fetch_array($resulttype)))
        $row = array_change_key_case($row, CASE_UPPER);

      return $row;
    }
    else
      return false;
  }

  /**
   * Frees the memory associated with a result.
   */
  public function free()
  {
    while (@parent::next_result()) {
      if ($this->result = @parent::store_result()) {
        $this->result->free();
      }
    }
    return true;
  }

  /**
   * Starts the timer, for debugging purposes.
   *
   * @return true
   */
  function timer_start() {
    $mtime            = explode( ' ', microtime() );
    $this->time_start = $mtime[1] + $mtime[0];
    return true;
  }

  /**
   * Stops the debugging timer.
   *
   * @return int Total time spent on the query, in milliseconds
   */
  function timer_stop() {
    $mtime      = explode( ' ', microtime() );
    $time_end   = $mtime[1] + $mtime[0];
    $time_total = $time_end - $this->time_start;
    return $time_total;
  }
}

/**************************************************************************************************
                                               End of file
 ***************************************************************************************************/
?>
