<?
//*************************************************************************************************
// CMD section
//************************************************************************************************* 

  function write_script($fsp, $text)
  {
    $array = split("\n",$text);
    $out = fopen($fsp, "w");
    fwrite($out,'#!/bin/bash'."\n");
    fwrite($out,'cd ..'."\n");
    foreach ($array as $line)
      if (!empty($line))
      {
        fwrite($out, "echo '<hr noshade size=1><font color=red>".trim($line)."</font>'\n");
        fwrite($out, trim($line)."\n" );
      }
    fclose($out);
    chmod($fsp,0755);
  }
  
  function cmd_display()
  {
    set_time_limit(86400); 
    $cmd = un_magic_quote($_REQUEST["cmd"]);
  
    echo '<p><h1>Enter UNIX Commands</h1><p>';
    message($new);
    form_start('index.php5');
    form_hidden('section','CMD');
    form_hidden('action','DISPLAY');
    form_text('cmd','Command',70,5,$cmd);
    form_submit('Run Commands',1);
    form_end();

    echo '<p><h1>Output</h1><p><pre>';
    write_script("_script.sh",$cmd);
    passthru('/bin/bash _script.sh 2>&1');
    echo '</pre>';
  }

?>