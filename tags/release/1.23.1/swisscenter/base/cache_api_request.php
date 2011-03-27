<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

class cache_api_request {

  var $response;
  var $cache_table = 'cache_api_request';
  var $cache_expire = null;

  /*
   * When your database cache table hits this many rows, a cleanup
   * will occur to get rid of all of the old rows and cleanup the
   * garbage in the table.  For most personal apps, 1000 rows should
   * be more than enough.  If your site gets hit by a lot of traffic
   * or you have a lot of disk space to spare, bump this number up.
   * You should try to set it high enough that the cleanup only
   * happens every once in a while, so this will depend on the growth
   * of your table.
   */
  var $max_cache_rows = 1000;

  function cache_api_request ($service, $cache_expire = 600)
  {
    $this->service = $service;
    $this->cache_expire = $cache_expire;

    if (db_value("SELECT COUNT(*) FROM $this->cache_table WHERE service = '$service'") > $this->max_cache_rows)
    {
      db_sqlcommand("DELETE FROM $this->cache_table WHERE service = '$service' AND expiration < DATE_SUB(NOW(), INTERVAL $cache_expire second)");
      db_sqlcommand('OPTIMIZE TABLE '.$this->cache_table);
    }
  }

  /**
   * Checks the database for a cached result to the request.
   * If there is no cache result, it returns a value of false. If it finds one, it returns the response.
   *
   * @param string $request
   * @return string
   */
  function getCached ($request)
  {
    $reqhash = md5(serialize($request));
    $result = db_value("SELECT response FROM ".$this->cache_table." WHERE request = '$reqhash' AND DATE_SUB(NOW(), INTERVAL " . (int) $this->cache_expire . " SECOND) < expiration");
    if (!empty($result))
      return unserialize($result);
    else
      return false;
  }

  /**
   * Caches the response.
   *
   * @param string $request
   * @param string $response
   */
  function cache ($request, $response)
  {
    $reqhash = md5(serialize($request));
    if (db_value("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'"))
      db_sqlcommand( "UPDATE ".$this->cache_table." SET response = '".db_escape_str(serialize($response))."', expiration = '".strftime("%Y-%m-%d %H:%M:%S")."' WHERE request = '$reqhash' AND service = '".$this->service."'");
    else
      db_sqlcommand( "INSERT INTO ".$this->cache_table." (request, service, response, expiration) VALUES ('$reqhash', '".$this->service."', '".db_escape_str(serialize($response))."', '".strftime("%Y-%m-%d %H:%M:%S")."')");
  }

}
?>
