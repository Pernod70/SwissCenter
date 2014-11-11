<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/cache_api_request.php'));

/*
 * geolookup 	  Returns the the city name, zip code / postal code, latitude-longitude coordinates and nearby personal weather stations.
 * conditions 	Returns the current temperature, weather condition, humidity, wind, 'feels like' temperature, barometric pressure, and visibility.
 * forecast 	  Returns a summary of the weather for the next 3 days. This includes high and low temperatures, a string text forecast and the conditions.
 * astronomy 	  Returns The moon phase, sunrise and sunset times.
 * radar        Returns a URL link to the .gif radar image.
 * satellite    Returns a URL link to .gif visual and infrared satellite images.
 * webcams      Returns locations of nearby Personal Weather Stations and URL's for images from their web cams.
 * history      history_YYYYMMDD returns a summary of the observed weather for the specified date.
 * alerts       Returns the short name description, expiration time and a long text description of a severe alert - If one has been issued for the searched upon location.
 * hourly       Returns an hourly forecast for the next 36 hours immediately following the API request.
 * hourly7day   Returns an hourly forecast for the next 7 days
 * forecast7day Returns a summary of the weather for the next 7 days. This includes high and low temperatures, a string text forecast and the conditions.
 * yesterday 	  Returns a summary of the observed weather history for yesterday.
 * planner 	    planner_MMDDMMDD returns a weather summary based on historical information between the specified dates (30 days max).
 * autocomplete
 * almanac 	    Historical average temperature for today
 *
 * the location for which you want weather information. Examples:
 *
 *     CA/San_Francisco
 *     60290 (U.S. zip code)
 *     Australia/Sydney
 *     37.8,-122.4 (latitude,longitude)
 *     KJFK (airport code)
 *     pws:KCASANFR70 (PWS id)
 *     autoip (AutoIP address location)
 *     autoip.json?geo_ip=38.102.136.138 (Specific IP address location)
 */

define('WUNDERGROUND_URL', 'http://api.wunderground.com/api/');
define('API_KEY', '5f3581a08749f281'); // Unique key for SwissCenter

class Wunderground {
  private $service = 'wunderground';
  private $cache_expire = 3600;
  private $cache;

  function Wunderground ()
  {
    $this->cache = new cache_api_request($this->service, $this->cache_expire);
  }

  /**
   * Return requested feed.
   *
   * @param array $feature
   * @param string $location
   * @return array
   */
  public function getRequest($feature, $location)
  {
    // Form the request URL
    if (!is_array($feature))
      $feature = array($feature);
    $request = WUNDERGROUND_URL.API_KEY.'/'.implode('/', $feature).'/q/'.$location.'.json';

    //Sends a request to Wunderground
    send_to_log(6,'Wunderground feed request', $request);

    // Use a cached response if available
    if (!($response = $this->cache->getCached($request))) {
      if (($response = file_get_contents($request)) !== false) {
        $this->cache->cache($request, $response);
      } else {
        send_to_log(2,"There has been a problem sending your command to the server.", $request);
        return false;
      }
    }
    return json_decode($response, true);
  }

  public function wunderground_home()
  {
      return 'autoip';
  }
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
