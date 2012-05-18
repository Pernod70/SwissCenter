<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));

/**
 * Get the current channel list from TOMA server, and store in database.
 *
 */

function update_channel_list()
{
  // Define GET request to retrieve the channel data
  // Must identify as JLC's Internet TV application
  $opts = array('http' => array('method'     => "GET",
                                'user_agent' => "INTERNETTV"));
  $context = stream_context_create($opts);

	// Send request
	if (($channel_data = file_get_contents('http://www.online-media-archive.net/scripts/tv/get3.php?adult=0', false, $context)) !== false)
	{
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
	else
	  return false;
}

/**
 * Returns the SQL needed to filter selected media types.
 *
 * @return string
 */

function toma_filter_media_sql()
{
  return ' and media_type in (""'.(get_sys_pref('TOMA_SHOW_MEDIAPLAYER', 'YES') == 'YES' ? ',"mp"' : '').
                                  (get_sys_pref('TOMA_SHOW_REALPLAYER', 'NO')   == 'YES' ? ',"rp"' : '').
                                  (get_sys_pref('TOMA_SHOW_WINAMP', 'NO')       == 'YES' ? ',"wa"' : '').')';
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
