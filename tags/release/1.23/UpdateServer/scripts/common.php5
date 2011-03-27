<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/
   
  define ('DIR_TO_ARRAY_SHOW_FILES',1);
  define ('DIR_TO_ARRAY_SHOW_DIRS', 2);
  define ('DIR_TO_ARRAY_FULL_PATH',4);
  
  function dir_to_array ($dir, $pattern = '.*', $opts = 7 )
  {
    $dir = os_path($dir,true);
  
    $contents = array();
    if ($dh = @opendir($dir))
    {
      while (($file = readdir($dh)) !== false)
      {
        if ( preg_match('/'.$pattern.'/',$file) && 
             (  (is_dir($dir.$file)  && ($opts & DIR_TO_ARRAY_SHOW_DIRS))
             || (is_file($dir.$file) && ($opts & DIR_TO_ARRAY_SHOW_FILES)) ) )
        {
          if ($opts & DIR_TO_ARRAY_FULL_PATH)
            $contents[] = os_path($dir.$file);
          else 
            $contents[] = $file;
        }
      }
      closedir($dh);
    }
    
    sort($contents);
    return $contents;
  }

function newline()
{
  if ( substr(PHP_OS,0,3)=='WIN' )
    return "\r\n";
  else 
    return "\n";
}

function array2file( $array, $filename)
  {
    $success = false;
    $str = implode(newline(), $array);
    if ( $handle = @fopen($filename, 'wt') )
    {
       if ( fwrite($handle, $str) !== FALSE)
         $success = true;
       fclose($handle);
    }
    return $success;
  }

  function os_path( $dir, $addslash=false )
  {
    if ( is_windows() )
    {
      $delim1 = '/';
      $delim2 = '\\';
    }
    else
    {
      $delim1 = '\\';
      $delim2 = '/';
    }
  
    $dir = str_replace($delim1, $delim2, $dir);
    $dir = rtrim($dir, $delim2);
    
    if ($addslash)
      $dir = $dir.$delim2;
    
    return $dir;
  }
  
  function array_to_table( $data, $width = '100%' )
  {  
    if (count($data) !=0)
    { 
      echo '<table class="form_select_tab" width="'.$width.'"><tr>';
  
      foreach ($data[0] as $heading => $value)
        echo '<th>'.ucwords(str_replace('_',' ',strtolower($heading))).'</th>';
  
      foreach ($data as $row)
      {
        echo '</tr><tr>';
        foreach ($row as $cell)
          echo '<td>'.$cell.'</td>';
      }  
  
      echo '</tr></table>';
    }
  }

  function get_os_type()
  {
    if ( substr(PHP_OS,0,3)=='WIN' )
      return 'WINDOWS';
    else
      return 'UNIX';
  }
  
  function is_windows()
  { return get_os_type() == "WINDOWS"; }
  
  function is_unix()
  { return get_os_type() == "UNIX"; } 
    
  function message ($text)
  {
    if (!empty($text))
    {
      if ($text[0] == '!')
        echo '<p class="warning">'.substr($text,1).'</p>';
      else 
        echo '<p class="message">'.$text.'</p>';
    }
  }    
    
  function paginate( $url, $total_items, $per_page, $current_page)
  {
    echo '<table width="100%" border=0 cellpadding=0 cellspacing=8><tr>
          <td width="60" align="left">';
          if ($current_page > 1)
            echo '<a href="'.$url.($current_page-1).'">Previous</a>';      
    echo '&nbsp;</td><td align="center">&nbsp;';
          for ($i=1; $i <= ceil($total_items/$per_page); $i++)
            echo '&nbsp;<a href="'.$url.$i.'">'.$i.'</a>&nbsp;';      
    echo '&nbsp;</td><td width="60" align="right">&nbsp;';
          if ($current_page < ceil($total_items/$per_page))
            echo '<a href="'.$url.($current_page+1).'">Next</a>';      
    echo '</tr></table>';
  }  
  
  function menu_item($text, $params, $background = 'menu_bgr.png')
  {
   echo '<tr><td width="5"></td>
         <td class="menu" background="'.$background.'">
         <a href="?'.$params.'">'.$text.'</a>
         </td></tr>';
  }
 
  function un_magic_quote( $text )
  {
    if ( get_magic_quotes_gpc() == 1)
      return stripslashes($text);
    else
      return $text;
  }
  
  function debug($var)
  {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
  }

function file_ext( $filename )
{
  return strtolower(array_pop(explode( '.' , $filename)));
}

function file_noext( $filename )
{
  $parts = explode( '.' , $filename);
  unset($parts[count($parts)-1]);
  return basename(implode('.',$parts));
}
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
