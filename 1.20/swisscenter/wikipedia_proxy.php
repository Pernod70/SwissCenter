<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/base/page.php'));

//-------------------------------------------------------------------------------------------------
// Converts a link from within the wikipedia page to use the proxy if necessary.
//-------------------------------------------------------------------------------------------------

function convert_link ( $wiki, $link )
  {
    $color = style_value('PAGE_TEXT_BOLD_COLOUR','#00FFFF');
    $found = preg_match('~href="(.*?)".*?>(.*?)</a>~s',$link,$matches);

    if ($found == 0)
      return $link;
    elseif ( $matches[2] == 'edit')
      return 'edit';
    elseif ( strpos($matches[1],'http://') !== false )
      return'<a href="'.$matches[1].'"><img src="/images/extlink.gif"> <font color="'.$color.'">'.$matches[2].'</font></a>';
    elseif ( $matches[1][0] == '#')
      return'<a href="'.current_url().$matches[1].'"><font color="'.$color.'">'.$matches[2].'</font></a>';
    else 
      return '<a href="/wikipedia_proxy.php?wiki='.urlencode($wiki).'&url='.urlencode($matches[1]).'"><font color="'.$color.'">'.$matches[2].'</font></a>';
  }
  
//-------------------------------------------------------------------------------------------------
// Main code
//-------------------------------------------------------------------------------------------------  
  
  // Get the page parameters, decode them and assign to variables.
  $wiki = urldecode($_REQUEST["wiki"]); 
  $url  = urldecode($_REQUEST["url"]);
  if (isset($_REQUEST["search"]))
    $url .= '?search='.urlencode(urldecode($_REQUEST["search"]));

  // Fake the browser type and download (file_get_contents only supports HTTP/1.0 not HTTP/1.1)
  ini_set('user_agent','MSIE 4\.0b2;'); 
  $html = utf8_decode(file_get_contents('http://'.$wiki.$url));
   
  if ($html === false)
  {
    page_header('XXX');
    echo 'Page not found';
    page_footer('');
  }
  else 
  {
    // Determine the page title
    preg_match('~<title>(.*?) - .*?</title>~s',$html,$title);
    
    // Strip the unwatned information from the top and bottom of the file
    $content_start = strpos($html,'<!-- start content -->');
    $content_end   = strpos($html,'<!-- end content -->');
    $html          = substr($html, $content_start, $content_end-$content_start);
    
    // Search for all links and process them
    $start = 0;
    while ( ($pos = strpos($html,'<a ',$start)) !== false)
    {
      $link_end  = strpos($html,'</a>',$pos) + 4;
      $link      = convert_link($wiki, substr($html,$pos,$link_end-$pos));
      $html      =  substr($html,0,$pos).$link.substr($html,$link_end);
      $start     = $pos + strlen($link);
    }
    
    // Remove hidden structures
    $html = preg_replace('~<tr class="hiddenStructure">.*?</tr>~s','',$html);    
    
    // Output the page
    page_header('Wikipedia : '.$title[1]);
    
    if (is_hardware_player())
      echo str_replace('[edit]','',$html);
    else
      echo '<div style="height:'.convert_y(750).'; overflow:scroll;">'.str_replace('[edit]','',$html).'</div>';
    
    page_footer('');
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
