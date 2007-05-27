<?
  require_once("scripts/mysql.php5"); 
  $tables = db_toarray("select * from swiss_messages where added > '".$_REQUEST["last_check"]."'");
  echo serialize($tables);
?>