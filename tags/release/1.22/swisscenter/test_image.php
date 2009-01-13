<?php
  require_once( realpath(dirname(__FILE__).'/base/page.php'));
  require_once( realpath(dirname(__FILE__).'/base/image.php'));
  
  if (isset($_REQUEST["img"]))
  {
    $n100 = hexdec('#9999ff');
    $n50  = hexdec('#ff9999');
    $n10  = hexdec('#333333');
    $back  = hexdec('#000000');
    
    $img = new CImage( convert_x(1000,SCREEN_COORDS), convert_y(1000,SCREEN_COORDS));
    $img->rectangle(0,0, convert_x(1000,SCREEN_COORDS), convert_y(1000,SCREEN_COORDS), $back);
    for ($n=0; $n<=1000; $n+=10)
    {
      if ($n % 100 == 0)
        $c = $n100;
      elseif ($n % 50 ==0)
        $c = $n50;
      else 
        $c = $n10;
        
      $img->line(convert_x(0,SCREEN_COORDS),   convert_y($n,SCREEN_COORDS),  convert_x(1000,SCREEN_COORDS), convert_y($n,SCREEN_COORDS),    $c);      
      $img->line(convert_x($n,SCREEN_COORDS),  convert_y(0,SCREEN_COORDS),   convert_x($n,SCREEN_COORDS),   convert_y(1000,SCREEN_COORDS),  $c);
    }
    $img->output('jpg');
  }
  else 
  {
    echo '<html>
          <head>
          <meta SYABAS-FULLSCREEN>
          <meta SYABAS-PHOTOTITLE=0>
          <meta SYABAS-BACKGROUND="?img=dummy.jpg">
          <meta http-equiv="Content-Type" content="text/html; charset=Windows-1252">
          <title>'.$title.'</title>
            <style>
              body {font-family: arial; font-size: 14px; background-repeat: no-repeat; }
              a {color:'.style_value("PAGE_LINKS_COLOUR",'#FFFFFF').'; text-decoration: none;}
            </style>
          </head>
          <body
             text="#ffff00"
             TOPMARGIN="0" 
             LEFTMARGIN="0" 
             MARGINHEIGHT="0" 
             MARGINWIDTH="0"
             background="?img=dummy.jpg">
          <table cellspacing=10 cellpadding=0>
            <tr><td height="20">&nbsp</td></tr>';
    
    for ($i=1; $i<=3; $i++)
    {
      echo '<tr>
              <td width="20">&nbsp;</td>
              <td align="left" valign="top"><font size="'.($i*2-1).'">The Quick Brown<br>Fox Jumps Over<br>The Lazy Dog</font></td>
              <td align="left" valign="top"><font size="'.($i*2).'">The Quick Brown<br>Fox Jumps Over<br>The Lazy Dog</font></td>
            </tr>';
    }
    
    echo '</table>'.
          $_SERVER['HTTP_USER_AGENT'].'
          </body>
          </html>';
  }
?>
