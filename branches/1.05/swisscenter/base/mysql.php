<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

#-------------------------------------------------------------------------------------------------
# Converts all keys to uppercase
#  array     - The array to work on
#-------------------------------------------------------------------------------------------------

function array_toupper( &$array )
{
  reset($array);
  while( list($key,$value) = each($array) )
    if (strtoupper($key) != $key)
    {
      $array[strtoupper($key)]=$value;
      unset($array[$key]);
    }
}

#-------------------------------------------------------------------------------------------------
# Escapes the given string for insertion into the database.
#-------------------------------------------------------------------------------------------------

function db_escape_str( $text )
{
  return str_replace("'","\'",$text);
}

#-------------------------------------------------------------------------------------------------
# Tests the connection to the database using the details provided 
#-------------------------------------------------------------------------------------------------

function test_db($host, $username, $password, $database)
{
  if (! $this->db_handle = @mysql_pconnect( $host, $username, $password))
    return "!Unable to connect to MySQL using the Host, Username and Password Specified";
  elseif (! mysql_select_db($database, $this->db_handle) )
    return "!Connection to MySQL established, but unable to select the specified database";
  else 
    return 'OK';
}

#-------------------------------------------------------------------------------------------------
# Selects the results of the query ($sql) into the specified array (&$data).
#
# Returns an array if the query completed successfully, otherwise the funtions returns FALSE 
#-------------------------------------------------------------------------------------------------

function db_toarray( $sql)
{
  $data = array();

  $recs    = new db_query( $sql );
  $success = $recs->db_success();

  if ($success)
    while ($row = $recs->db_fetch_row())
      $data[] = $row;

  $recs->destroy();
  return ($success ? $data : false );
}

#-------------------------------------------------------------------------------------------------
# Uses the results of the query ($sql) to build an array (&$data) where each entry in the array
# is the value of the first column selected by the query.
# 
# EG: "Select username from users;" might return array('Rod','Jane','Freddy')
#-------------------------------------------------------------------------------------------------

function db_col_to_list( $sql)
{
  $data = array();

  $recs     = new db_query( $sql );
  $success  = $recs->db_success();

  if ($success)
    while ($row = $recs->db_fetch_row())
      $data[] = @array_pop($row);

  $recs->destroy();
  return ($success ? $data : false );
}


#-------------------------------------------------------------------------------------------------
# Executes the command passed in the $sql variable. This function does not return any results,
# so cannot be used for a SELECT statement.
#
# Returns TRUE if the query completed successfully, otherwise the funtions returns FALSE 
#-------------------------------------------------------------------------------------------------

function db_sqlcommand( $sql)
{
  $recs     = new db_query( $sql);
  $success  = $recs->db_success();
  $recs->destroy();
  return ($success ? true : false );
}

#-------------------------------------------------------------------------------------------------
# Function to run a SQL command as the "root" user in MySQL (for building databases, etc)
# Returns TRUE is the query completed successfully, otherwise returns FALSE
#-------------------------------------------------------------------------------------------------

function db_root_sqlcommand( $root_password, $sql )
{
  // Connect to the Database
  if ( ! $link = mysql_connect( 'localhost', 'root', $root_password ));

  // Execute the query
  if (! ($result = mysql_query($sql)))
    return false;
  else
  {
    // Clean up and disconnect link
    @mysql_free_result($result);
    mysql_close($link);
    return true;
  }
}

#-------------------------------------------------------------------------------------------------
# Executes the command passed in the $sql variable and returns the first column of the first
# row in the result set.
#
# NOTE: This function should be used when the SQL is expected to return only one value, such
#       as a "SELECT COUNT(*) FROM tablename;" statement
#-------------------------------------------------------------------------------------------------

function db_value( $sql)
{
  $recs    = new db_query( $sql );
  $success = $recs->db_success();
  $result  = '';

  if (!$success)
    echo "Unable to query database - ".$recs->db_get_error();

  $result = @array_pop($recs->db_fetch_row());
  $recs->destroy();

  return ($success ? $result : false );
}

#-------------------------------------------------------------------------------------------------
# Inserts row into the database.
#
# Note that the fields are given as an associative array. Each KEY value in the
# array is the column name, and each VALUE in the array is the value to insert for
# that particular field.
#
# NOTE: All strings will be automatically escaped.
#
# Returns TRUE on success, FALSE otherwise (and populates the $errmsg variable)
#
# table   - the table to insert the row into.
# fields  - an associative array containing the values to insert into the table.
#-------------------------------------------------------------------------------------------------

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
    {
      $vlist = $vlist.",'".db_escape_str( stripslashes($value))."'";
    }
    else
      $vlist = $vlist.",$value";
  }

  $sql = "insert into $table (".trim($flist,',').") values (".trim($vlist,',').")";

  return db_sqlcommand($sql);
}

/**************************************************************************************************
  DB_QUERY class definition.
*************************************************************************************************/

class db_query
{
  #-------------------------------------------------------------------------------------------------
  # Functions:
  #-------------------------------------------------------------------------------------------------

  # db_query( $sql )          -- Constructor
  # destroy()                 -- Destructor
  # db_fetch_row()            -- Fetches the next row from the query results
  # db_get_rows_fetched()     -- Returns the number of rows returned so far
  # db_get_error()            -- Returns the text of the last error encountered

  #-------------------------------------------------------------------------------------------------
  # Member Variables
  #-------------------------------------------------------------------------------------------------

  var $db_handle;
  var $stmt_handle;
  var $rows_fetched;

  #-------------------------------------------------------------------------------------------------
  # Constructor
  #-------------------------------------------------------------------------------------------------

  function db_query($sql = '', $dbname = '')
  {
    if ($dbname == '')
      $dbname = DB_DATABASE;
      
    if ($this->db_handle = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD ) )
    {
      if (mysql_select_db($dbname, $this->db_handle) )
      {
        $this->rows_fetched = 0;
        if (! empty($sql) )
         $this->stmt_handle = mysql_query( $sql, $this->db_handle);
      }
    }
  }

  #-------------------------------------------------------------------------------------------------
  # Destructor
  #-------------------------------------------------------------------------------------------------

  function destroy()
  {
    return true;
  }

  #-------------------------------------------------------------------------------------------------
  # Fetches a row from the query and returns it as an associative array.
  #-------------------------------------------------------------------------------------------------

  function db_fetch_row()
  {
    if ($this->stmt_handle)
    {
      if ($row = mysql_fetch_array( $this->stmt_handle, MYSQL_ASSOC ))
      {
        $this->rows_fetched++;
        array_toupper($row);
      }
      return  $row;
    }
    else
      return false;
  }

  #-------------------------------------------------------------------------------------------------
  # Return values of member variables.
  #-------------------------------------------------------------------------------------------------

  function db_execute_sql($sql)
  { return ( $this->stmt_handle = mysql_query( $sql, $this->db_handle) ); }

  function db_get_rows_fetched()
  { return $this->rows_fetched; }

  function db_get_error()
  { return mysql_error($this->db_handle); }

  function db_success()
  { return $this->stmt_handle; }

}

/**************************************************************************************************
                                               End of file
***************************************************************************************************/
?>
