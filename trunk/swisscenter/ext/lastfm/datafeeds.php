<?

require_once( realpath(dirname(__FILE__).'/../xml/XPath.class.php'));
    
function tagcloud( $tags, $linkto_url, $colours, $sizes )
{
  if (!is_array($colours))
    $colours = explode(',',$colours);
  if (!is_array($sizes))
    $sizes = explode(',',$sizes);
  
  // get the largest and smallest array values
  $max_qty = max(array_values($tags));
  $min_qty = min(array_values($tags));

  // find the range of values (detect for divide by zero)
  $spread = $max_qty - $min_qty;
  if ($spread == 0)
    $spread = 1;

  // determine the font-size increment
  $col_step = (count($colours)-1)/($spread);
  $size_step = (count($sizes)-1)/($spread);

  // loop through our tag array
  foreach ($tags as $tag => $value) 
  {
    $col_idx = (int)(($value - $min_qty) * $col_step);
    $size_idx = (int)(($value - $min_qty) * $size_step);

    echo '<a href="'.str_replace('###',urlencode($tag),$linkto_url).'">'.
         '<font size="'.$sizes[$size_idx].'" color="'.$colours[$col_idx].'">'.
        $tag.
        '</font></a> &nbsp; ';
  }
}

function tagcloud_test()
{
// $time = time();
// echo -$time + ($time =time()).'s<br>';

$cloud       = array();
$xmlOptions  = array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE);
$xml         = &new XPath('test.xml', $xmlOptions);

/*
// Track TopTags
foreach ($xml->match('/toptags/tag') as $abspath)
  $cloud[$xml->getData($abspath.'/name')] = $xml->getData($abspath.'/count');
*/


// Overall TopTags
foreach ($xml->match('/toptags/tag') as $abspath)
{
  $node = $xml->getNode($abspath);
  $cloud[$node["attributes"]["NAME"]] = $node["attributes"]["COUNT"];
}

// Generate a tag cloud from the values in the XML document for the top 50 tags.

asort($cloud);
$toptags = array_slice($cloud,0,50);

$colour_list = '#bbbbbb,#999999,#666666,#333333,#000000';
$size_list   = '3,4,5,6';  

ksort($toptags);
tagcloud( $toptags, 'http://www.last.fm/tag/###', $colour_list, $size_list);
}

function lastfm_toptags ()
{    
  $cache_time = get_sys_pref('LASTFM_CACHE_TIMEOUT_TOPTAGS',0);
  $ws_url     = 'http://ws.audioscrobbler.com/1.0/tag/toptags.xml';

  // Download the latest "Top Tags" from audioscrobbler.com every 24 hours
  if ( $cache_time < time() && ($datafeed = file_get_contents($ws_url)) !== false)
  {
    set_sys_pref('LASTFM_CACHE_TIMEOUT_TOPTAGS',(time()+86400));
    db_sqlcommand("delete from lastfm_tags");
    
    $xmlOptions  = array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE);
    $xml         = &new XPath(FALSE, $xmlOptions);

    $xml->importFromString($datafeed);      
    foreach ($xml->match('/toptags/tag') as $abspath)
    {
      $node = $xml->getNode($abspath);
      db_insert_row('lastfm_tags', array( 'tag'   => $node["attributes"]["NAME"]
                                        , 'count' => $node["attributes"]["COUNT"]
                                        , 'url'   => $node["attributes"]["URL"]
                                        ) );
    }
  }
  
  return db_col_to_list("select tag from lastfm_tags");
}    
 

?>