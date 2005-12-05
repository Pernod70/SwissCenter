<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------
  
  function sched_display( $message = '')
  {   
    if (is_windows())
    {
      if (is_server_simese() && simese_version() >= 1.31)
      {
        echo "<h1>".str('SCHEDULE_TITLE')."</h1>";
        echo str("SIMESE_SCHED_PROMPT");
      }
      else
        sched_display_win( $message);
    }
    else
      sched_display_linux( $message);
  }
   
  function sched_display_win( $message = '')
  {
    $at_hrs ='12';
    $at_mins='00';
  
    // Get the current schedule information
    $sched = syscall('at');
    foreach(explode("\n",$sched) as $line)
      if (strpos($line,os_path(SC_LOCATION.'media_search.php')) && strpos($line,'Each '))
      {
         $at_days = explode(' ',trim(substr($line,17,19)));
         $at_hrs  = trim(substr($line,36,2));
         $at_mins = trim(substr($line,39,2));
      }

    echo "<h1>".str('SCHEDULE_TITLE')."</h1>";
    message($message);

    echo '<p><b>'.str('SCHEDULE_AUTO_TITLE').'</b><p>'.str('SCHEDULE_AUTO_TXT').'
          <center>
             <form name="" enctype="multipart/form-data" action="index.php" method="post">
               <input type=hidden name="section" value="SCHED">
               <input type=hidden name="action" value="UPDATE_WIN">
               <table width="400" class="form_select_tab" border=0 >
               <tr>
                 <th style="text-align=center;">'.str('TIME').'</th>
                 <th style="text-align=center;">'.str('DAY_1').'</th>
                 <th style="text-align=center;">'.str('DAY_2').'</th>
                 <th style="text-align=center;">'.str('DAY_3').'</th>
                 <th style="text-align=center;">'.str('DAY_4').'</th>
                 <th style="text-align=center;">'.str('DAY_5').'</th>
                 <th style="text-align=center;">'.str('DAY_6').'</th>
                 <th style="text-align=center;">'.str('DAY_7').'</th>
               </tr>
               <tr>
                 <td style="text-align=center;">
                   <input size="1" name="hr" value="'.$at_hrs.'"> 
                   <input size="1" name="mi" value="'.$at_mins.'">
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="M" '. (in_array('M',$at_days) ? 'checked' : '').'>
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="T" '. (in_array('T',$at_days) ? 'checked' : '').'>
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="W" '. (in_array('W',$at_days) ? 'checked' : '').'>
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="Th" '.(in_array('Th',$at_days) ? 'checked' : '').'>
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="F" '. (in_array('F',$at_days) ? 'checked' : '').'>
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="S" '. (in_array('S',$at_days) ? 'checked' : '').'>
                 </td>
                 <td style="text-align=center;">
                   <input type="checkbox" name="day[]" value="Su" '.(in_array('Su',$at_days) ? 'checked' : '').'>
                 </td>
               </tr>
               </tr>
               </table><br>
                 <input type="submit" value="'.str('SCHEDULE_UPDATE_BUTTON').'">
             </form></center>
             ';
  }
  
  // ----------------------------------------------------------------------------------
  // Update the schedule 
  // ----------------------------------------------------------------------------------

  function sched_display_linux( $message = '')
  {
    $cron = split(" ",syscall('crontab -l | grep "'.SC_LOCATION.'media_search.php" | head -1 | awk \'{ print $1" "$2" "$3" "$4" "$5 }\''));

    echo "<h1>".str('SCHEDULE_TITLE')."</h1>";
    message($message);

    echo '<p><b>'.str('SCHEDULE_AUTO_TITLE').'</b><p>'.str('SCHEDULE_AUTO_TXT').'
          <p align=center>
            <form name="" enctype="multipart/form-data" action="index.php" method="post">
               <input type=hidden name="section" value="SCHED">
               <input type=hidden name="action" value="UPDATE_LINUX">
               
               <center><table class="form_select_tab" border=0 width="95%" >
               <tr>
                 <th height="25"></th>
                 <th width="75">&nbsp;'.str('VALUE').'</th>
                 <th width="60">&nbsp;'.str('RANGE').'</th>
                 <th width="50">&nbsp;'.str('NOTES').'</th>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">'.str('MONTH').': &nbsp;</th>
                 <td>&nbsp;<input size="6" name="month" value="'.$cron[3].'"></td>
                 <td>&nbsp;1-12 </td>
                 <td>&nbsp;'.str('SCHEDULE_MONTH_PROMPT').'</td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">'.str('DATE').': &nbsp;</th>
                 <td>&nbsp;<input size="6" name="date" value="'.$cron[2].'"></td>
                 <td>&nbsp;1-31 </td>
                 <td>&nbsp;'.str('SCHEDULE_DATE_PROMPT').'</td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">'.str('HOUR').': &nbsp;</th>
                 <td>&nbsp;<input size="6" name="hour" value="'.$cron[1].'"></td>
                 <td>&nbsp;0-23 </td>
                 <td>&nbsp;'.str('SCHEDULE_HOUR_PROMPT').'</td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">'.str('MINUTE').': &nbsp;</th>
                 <td>&nbsp;<input size="6" name="minute" value="'.$cron[0].'"></td>
                 <td>&nbsp;0-59 </td>
                 <td>&nbsp;'.str('SCHEDULE_MINUTE_PROMPT').'</td>
               </tr>
               <tr>
                 <th width="100" style="text-align=right;">'.str('WEEKDAY').': &nbsp;</th>
                 <td>&nbsp;<input size="6" name="day" value="'.$cron[4].'"></td>
                 <td>&nbsp;1-7 </td>
                 <td>&nbsp;'.str('SCHEDULE_WEEKDAY_PROMPT').'</td>
               </tr>
               </table><br>
          
               <input type="submit" value="'.str('SCHEDULE_UPDATE_BUTTON').'">
             </form></center>

          <p><b>'.str('NOTES').'</b>
          <p>
          <ul>'.str('SCHEDULE_NOTES').'</ul>
          ';
  }

  function sched_update_win()
  {   
    $hrs  = $_REQUEST["hr"];
    $mins = $_REQUEST["mi"];
    $days = $_REQUEST["day"];
    
    if ($hrs <0 || $hrs > 23 || !is_numeric($hrs))
      sched_display('!'.str('SCHEDULE_ERROR_HOUR'));
    elseif ($mins <0 || $mins > 59 || !is_numeric($mins))
      sched_display('!'.str('SCHEDULE_ERROR_MIN'));
    else
    {
      // Find and remove old schedule entry
      $sched = syscall('at');
      foreach(explode("\n",$sched) as $line)
        if (strpos($line,os_path(SC_LOCATION.'media_search.php')) && strpos($line,'Each '))
         syscall('at '.substr(ltrim($line),0,strpos(ltrim($line),' ')).' /delete');

      if (count($days)>0)
      {
      	media_schedule_refresh(implode(',',$days), $hrs.':'.$mins);  
        sched_display(str('SCHEDULE_UPDATED'));        
      }
      else
        sched_display(str('SCHEDULE_NONE'));
    }
  }

  function sched_update_linux()
  {   
    $hrs    = $_REQUEST["hour"];
    $mins   = $_REQUEST["minute"];
    $dates  = $_REQUEST["date"];
    $months = $_REQUEST["month"];
    $days   = $_REQUEST["day"];
    
    if ( preg_match("/[^-,*0123456789]/",($hrs.$mins.$dates.$months.$days)) != 0)
      sched_display('!'.str('SCHEDULE_ERROR_CHARS','"0123456789-,*"'));
    elseif ($hrs == '' || $mins == '' || $dates == '' || $months == '' || $days == '')
      sched_display('!'.str('SCHEDULE_ERROR_FIELDS'));
    else
    {
      // Find and replace old crontab entry
      syscall('crontab -l | grep -v "'.SC_LOCATION.'media_search.php" | grep -v "^#" > /tmp/swisscron');
      syscall("echo '$mins $hrs $dates $months $days ".'"'.os_path(PHP_LOCATION).'" "'.SC_LOCATION.'media_search.php"\' >> /tmp/swisscron');
      syscall("crontab /tmp/swisscron");

      // Was it successfully added?
      $cron = split(" ",syscall('crontab -l | grep "'.SC_LOCATION.'media_search.php" | awk \'{ print $1" "$2" "$3" "$4" "$5 }\''));
      if (count($cron)>0)
        sched_display(str('SCHEDULE_UPDATED'));        
      else
        sched_display(str('SCHEDULE_NONE'));
    }
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>