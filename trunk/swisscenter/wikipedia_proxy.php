<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

//-------------------------------------------------------------------------------------------------
// Converts a link from within the wikipedia page to use the proxy if necessary.
//-------------------------------------------------------------------------------------------------

  function convert_link ( $wiki, $link, $back_url )
  {
    $color = style_value('PAGE_TEXT_BOLD_COLOUR','#00FFFF');
    $found = preg_match('~href="(.*?)".*?>(.*?)</a>~s',$link,$matches);

    if ($found == 0)
      return $link;
    elseif ( in_array($matches[2], array('edit', 'redig�r', 'Bearbeiten', 'editar', 'modifier', 'modifica', 'bewerken', 'rediger', 'edytuj', 'redigera')) )
      return 'edit';
    elseif ( strpos($matches[1],'http://') !== false )
      return'<a href="'.$matches[1].'"><img src="/images/extlink.gif"> <font color="'.$color.'">'.$matches[2].'</font></a>';
    elseif ( $matches[1][0] == '#')
      return'<a href="'.current_url().$matches[1].'"><font color="'.$color.'">'.$matches[2].'</font></a>';
    else
      return '<a href="/wikipedia_proxy.php?wiki='.urlencode($wiki).'&page='.urlencode($matches[1]).'&back_url='.urlencode($back_url).'"><font color="'.$color.'">'.$matches[2].'</font></a>';
  }

//-------------------------------------------------------------------------------------------------
// Main code
//-------------------------------------------------------------------------------------------------

  // Get the page parameters, decode them and assign to variables.
  $back_url = isset($_REQUEST["back_url"]) ? $_REQUEST["back_url"] : page_hist_previous();
  $wiki = urldecode($_REQUEST["wiki"]);
  $page = isset($_REQUEST["page"]) ? urldecode($_REQUEST["page"]) : '';
  $page = str_replace('/wiki/', '', $page);
  $url  = '/w/api.php?format=json&action=parse&page='.urlencode($page).'&redirects';

  // Fake the browser type and download (file_get_contents only supports HTTP/1.0 not HTTP/1.1)
  ini_set('user_agent','Mozilla/5.0');
  $html = file_get_contents('http://'.$wiki.$url);

  if ($html === false)
  {
    page_header('XXX');
    echo 'Page not found';
    page_footer($back_url);
  }
  else
  {
    // Decode the json response
    $data = json_decode($html, true);

    // Get page html
    $html = $data['parse']['text']['*'];

    // Search for all links and process them
    $start = 0;
    while ( ($pos = strpos($html,'<a ',$start)) !== false)
    {
      $link_end  = strpos($html,'</a>',$pos) + 4;
      $link      = convert_link($wiki, substr($html,$pos,$link_end-$pos), $back_url);
      $html      = substr($html,0,$pos).$link.substr($html,$link_end);
      $start     = $pos + strlen($link);
    }

    // Remove [edit] prompts
    $html = str_replace('[edit]','',$html);

    // Output the page
    page_header('Wikipedia : '.$data['parse']['title']);

    if (is_hardware_player())
      echo $html;
    else
      echo '<div style="height:'.convert_y(750).'; overflow:scroll;">'.$html.'</div>';

    page_footer($back_url);
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
