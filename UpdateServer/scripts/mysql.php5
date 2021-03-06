<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/

require_once( '/home/swisscenter/private/db_prefs.php');

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
# Returns the given UNIX timestamp (or current time if not specified) as a MySQL compatible date
#-------------------------------------------------------------------------------------------------

function db_datestr( $time = '')
{
  return date('Y-m-d H:i:s',(empty($time) ? time() : $time));
}

#-------------------------------------------------------------------------------------------------
# Escapes the given string for insertion into the database.
#-------------------------------------------------------------------------------------------------

function db_escape_str( $text )
{
  return stripslashes($text);
}

#-------------------------------------------------------------------------------------------------
# Tests the connection to the database using the details provided 
#-------------------------------------------------------------------------------------------------

function test_db()
{
//  echo DB_HOST.'<br>';
//  echo DB_USERNAME.'<br>';
//  echo DB_PASSWORD.'<br>';
//  echo DB_DATABASE.'<br>';
  $db = new db_query();
  if (! $tdb->db_handle = @mysql_pconnect( DB_HOST, DB_USERNAME, DB_PASSWORD))
    return "!Unable to connect to MySQL using the Host, Username and Password Specified";
  elseif (! mysql_select_db(DB_DATABASE, $db->db_handle) )
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
# Selects the results of the query ($sql) into the specified array (&$data).
#
# Returns an array of columns for the first row returned by the sql,
# otherwise the function returns FALSE 
#-------------------------------------------------------------------------------------------------

function db_row($sql)
{
  $recs    = new db_query( $sql );
  $success = $recs->db_success();

  if($success && ($row = $recs->db_fetch_row()))
      $data = $row;

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

function db_sqlcommand ($sql)
{
  $recs     = new db_query( $sql);
  $success  = $recs->db_success();
  $recs->destroy();
  return ($success ? true : false );
}

#-------------------------------------------------------------------------------------------------
# Executes all the SQL commands contained within the file specified, returning the number of 
# errors that were encountered.
#-------------------------------------------------------------------------------------------------

function db_sqlfile ($fsp)
{
  $errors = 0;
  if ($contents = @file($fsp))
  {
    $commands = split(";",implode(" ",$contents));
    foreach ($commands as $sql)
      if ( strlen(trim($sql)) > 0 ) 
        if (!db_sqlcommand($sql))
          $errors++;
  }

  return $errors;
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
# Takes the elements passed in the array and converts them to the SET section of a SQL update
# command (taking into account the type of variable)
#-------------------------------------------------------------------------------------------------

function db_array_to_set_list( $array)
{
  $columns = array();

  foreach( $array as $key => $value )
  {
    if     (!is_numeric($value) and empty($value))
      $columns[] = $key."=null";
    elseif (is_string($value))
      $columns[] = $key ."='".db_escape_str(stripslashes($value))."'";
    else
      $columns[] = $key."=".$value;
  }

  return implode(', ',$columns);
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
      $vlist = $vlist.",'".db_escape_str( un_magic_quote($value))."'";
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
