<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  /**
   * Lists information on the version of PHP installed.
   *
   */
  function phpinfo_display()
  {
    echo "<h1>".str('PHP_INFO')."</h1>";
    echo '<p>'.str('PHP_INFO_DESC');

    ob_start();
    phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_VARIABLES);
    $phpinfo = ob_get_clean();

    $phpinfo = trim($phpinfo);

    // Here we play around a little with the PHP Info HTML to try and stylise
    // it along SwissCenter's lines ... hopefully without breaking anything.
    preg_match_all('#<body[^>]*>(.*)</body>#si', $phpinfo, $output);
    $output = $output[1][0];
    $output = preg_replace('#<a[^>]*><img[^>]*></a>#i', '', $output);
    $output = preg_replace('#<table[^>]+>#i', '<table class="form_select_tab" width="600">', $output);
    $output = str_replace(array('class="e"', 'class="v"', 'class="h"', '<hr />'), array('class="stdformlabel"', '', '', ''), $output);

    // Fix invalid anchor names (eg "module_Zend Optimizer")
    $output = str_replace('module_Zend Optimizer', 'module_Zend_Optimizer', $output);

    echo '<center>'.$output.'</center>';
  }
?>