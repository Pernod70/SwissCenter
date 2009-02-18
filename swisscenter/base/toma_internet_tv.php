<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/mysql.php'));

// Decides which include path delimiter to use.  Windows should be using a semi-colon
// and everything else should be using a colon.  If this isn't working on your system,
// comment out this if statement and manually set the correct value into $path_delimiter.
if (strpos(__FILE__, ':') !== false) {
	$path_delimiter = ';';
} else {
	$path_delimiter = ':';
}

// This will add the packaged PEAR files into the include path for PHP, allowing you
// to use them transparently.  This will prefer officially installed PEAR files if you
// have them.  If you want to prefer the packaged files (there shouldn't be any reason
// to), swap the two elements around the $path_delimiter variable.  If you don't have
// the PEAR packages installed, you can leave this like it is and move on.

ini_set('include_path', ini_get('include_path') . $path_delimiter . dirname(__FILE__) . '/../ext/PEAR');

require_once( realpath(dirname(__FILE__).'/../ext/PEAR/HTTP/Request.php'));

/**
 * Get the current channel list from TOMA server, and store in database.
 *
 */

function update_channel_list()
{
  // Define GET request to retrieve the channel data
	$req =& new HTTP_Request();
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->setURL('http://www.online-media-archive.net/scripts/tv/get3.php?adult=0');
	// Must identify as JLC's Internet TV application
	$req->addHeader('User-Agent', 'INTERNETTV');

	// Send request
	if ($req->sendRequest() && $req->getResponseCode()==200)
  	$channel_data = $req->getResponseBody();
  else
	  return false;

  // Clear the channel data table
  db_sqlcommand("delete from toma_channels");

  // Parse the channel data and store in the database
  $channels = explode("\n", str_replace("\r",'',$channel_data));
  foreach ($channels as $channel)
  {
    if ( strlen($channel) > 0 )
    {
      $channel_info = explode("|", $channel);
      db_insert_row( 'toma_channels', array("id"          => $channel_info[0],
                                            "name"        => $channel_info[1],
                                            "stream_url"  => $channel_info[2],
                                            "bitrate"     => $channel_info[3],
                                            "media_type"  => $channel_info[4],
                                            "homepage"    => $channel_info[5],
                                            "country"     => $channel_info[6],
                                            "category"    => $channel_info[7],
                                            "description" => $channel_info[8],
                                            "rating"      => $channel_info[9],
                                            "votes"       => $channel_info[10],
                                            "works_fine"  => $channel_info[11],
                                            "not_working" => $channel_info[12],
                                            "wrong_info"  => $channel_info[13],
                                            "duplicate"   => $channel_info[14],
                                            "spam"        => $channel_info[15]) );
    }
  }

  return count($channels);
}

/**
 * Returns the SQL needed to filter selected media types.
 *
 * @return string
 */

function toma_filter_media_sql()
{
  return ' and media_type in (""'.(get_sys_pref('TOMA_SHOW_MEDIAPLAYER') == 'YES' ? ',"mp"' : '').
                                  (get_sys_pref('TOMA_SHOW_REALPLAYER')  == 'YES' ? ',"rp"' : '').
                                  (get_sys_pref('TOMA_SHOW_WINAMP')      == 'YES' ? ',"wa"' : '').')';
}

/**
 * Returns the SQL needed to filter by country.
 *
 * @return string
 */

function toma_filter_country_sql( $country )
{
  return (empty($country) ? '' : ' and country="'.$country.'"');
}

/**
 * Returns the SQL needed to filter by category.
 *
 * @return string
 */

function toma_filter_category_sql( $category )
{
  return (empty($category) ? '' : ' and category="'.$category.'"');
}

/**
 * Count available channels with selected filters.
 *
 * @return integer - number of available channels
 */

function toma_channels_count( $category='', $country='' )
{
  return db_value("select count(name) from toma_channels where 1=1".toma_filter_media_sql().
                                                                    toma_filter_category_sql($category).
                                                                    toma_filter_country_sql($country));
}
?>
