<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

class cache_api_request {

  var $response;
  var $cache_table = 'cache_api_request';
  var $cache_expire = null;

  function cache_api_request ($service, $cache_expire = 3600)
  {
    $this->service = $service;
    $this->cache_expire = $cache_expire;

    // Purge all expired data
    db_sqlcommand("DELETE FROM $this->cache_table WHERE expiration < NOW()");
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
    $result = db_value("SELECT response FROM $this->cache_table WHERE request = '$reqhash'");
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
    if (db_value("SELECT COUNT(*) FROM $this->cache_table WHERE request = '$reqhash'"))
      db_sqlcommand("UPDATE $this->cache_table SET response = '".db_escape_str(serialize($response))."', expiration = DATE_ADD(NOW(), INTERVAL ".(int) $this->cache_expire." SECOND) WHERE request = '$reqhash' AND service = '$this->service'");
    else
      db_sqlcommand("INSERT INTO $this->cache_table (request, service, response, expiration) VALUES ('$reqhash', '$this->service', '".db_escape_str(serialize($response))."', DATE_ADD(NOW(), INTERVAL ".(int) $this->cache_expire." SECOND))");
  }
}
?>
