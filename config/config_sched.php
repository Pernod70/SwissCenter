<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  define('SIMESE_SCHEDULE','simese/SimeseSchedule.ini');

  // ----------------------------------------------------------------------------------
  // Display current config
  // ----------------------------------------------------------------------------------

  function sched_display( $message = '')
  {
    if (is_windows())
    {
      if (is_server_simese() && version_compare(simese_version(), '1.31', '>='))
        sched_display_simese( $message );
      else
        sched_display_win( $message);
    }
    else
      sched_display_linux( $message);
  }

  /**
   * Returns array of media search types
   *
   * @return array
   */
  function sched_scan_options()
  {
    return array(str('SCHEDULE_ALL')        => 'media_search.php',
                 str('SCHEDULE_MEDIA_ONLY') => 'media_search.php?scan_type=MEDIA',
                 str('SCHEDULE_RSS_ONLY')   => 'media_search.php?scan_type=RSS');
  }

  function sched_display_win( $message = '')
  {
    $schedule = array();

    // Get the current schedule information
    $sched = syscall('at');
    foreach(explode("\n",$sched) as $line)
    {
      if (strpos($line,'media_search.php') && strpos($line,str('SCHEDULE_AT_EVERY').' '))
      {
        // Get individual schedule details
        $task_id = preg_get('/(\d+)/',$line);
        $sched_detail = syscall('at '.$task_id);
        $line_details = explode("\n",$sched_detail);
        $schedule[] = array("at_days" => explode(' ',preg_get('/'.str('SCHEDULE_AT_EVERY').' (.*)/',$line_details[3])),
                            "at_hrs"  => preg_get('/(\d\d):/',$line_details[4]),
                            "at_mins" => preg_get('/:(\d\d)/',$line_details[4]),
                            "url"     => trim(substr($line_details[6],strpos($line_details[6],'media_search.php'))));
      }
    }

    // Blank line for extra schedule information, or in case no schedule exists
    $schedule[] = array( "at_days"=>array(), "at_hrs"=>'12', "at_mins"=>'00', "url"=>'media_search.php');

    echo "<h1>".str('SCHEDULE_TITLE')."</h1>";
    message($message);

    echo '<p><b>'.str('SCHEDULE_AUTO_TITLE').'</b><p>'.str('SCHEDULE_AUTO_TXT').'
          <center>
             <form name="" enctype="multipart/form-data" action="index.php" method="post">
               <input type=hidden name="section" value="SCHED">
               <input type=hidden name="action" value="UPDATE_WIN">
               <table width="450" class="form_select_tab" border=0 >
               <tr>
                 <th style="text-align:center;">'.str('TIME').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_MON').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_TUE').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_WED').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_THU').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_FRI').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_SAT').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_SUN').'</th>
                 <th style="text-align:center;">'.str('MEDIA_SCAN_TYPE').'</th>
               </tr>';
    $line = 0;
    foreach ($schedule as $entry)
    {
      $line++;
      echo '   <tr>
                 <td style="text-align:center;">
                   <input size="1" name="hr'.$line.'" value="'.$entry["at_hrs"].'">
                   <input size="1" name="mi'.$line.'" value="'.$entry["at_mins"].'">
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_MON').'" '. (in_array(str('SCHEDULE_AT_MON'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_TUE').'" '. (in_array(str('SCHEDULE_AT_TUE'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_WED').'" '. (in_array(str('SCHEDULE_AT_WED'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_THU').'" '. (in_array(str('SCHEDULE_AT_THU'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_FRI').'" '. (in_array(str('SCHEDULE_AT_FRI'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_SAT').'" '. (in_array(str('SCHEDULE_AT_SAT'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="'.str('SCHEDULE_AT_SUN').'" '. (in_array(str('SCHEDULE_AT_SUN'),$entry["at_days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   '.form_list_static_html('url'.$line, sched_scan_options(), $entry["url"], false, false, false).'
                 </td>
               </tr>';
    }

    echo '     </tr>
               </table><br>
                 <input type="hidden" name="lines" value="'.$line.'">
                 <input type="submit" value="'.str('SCHEDULE_UPDATE_BUTTON').'">
             </form></center>
             ';
  }

  function sched_display_simese( $message = '')
  {
    // Simese 2.5.9 supports assigning a url to each task
    if ( version_compare(simese_version(), '2.5.8', '>=') )
      $use_scan_type = true;
    else
      $use_scan_type = false;

    $schedule = array();

    // Parse the schedule file
    if ( file_exists(SIMESE_SCHEDULE) )
    {
      foreach ( file(SIMESE_SCHEDULE) as $line )
      {
        if ( $use_scan_type && strpos($line, 'media_search') !== false)
        {
          preg_match('/.*=(.*) (.*):(.*),(.*)/', $line, $results);
          $schedule[] = array( "days"=>explode(',',$results[1]) , "hr"=>$results[2], "mi"=>$results[3], "url"=>trim($results[4]));
        }
        else
        {
          preg_match('/.*=(.*) (.*):(.*)/', $line, $results);
          $schedule[] = array( "days"=>explode(',',$results[1]) , "hr"=>$results[2], "mi"=>$results[3], "url"=>"media_search.php");
        }
      }
    }

    // Blank line for extra schedule information, or in case no schedule exists
    $schedule[] = array( "days"=>array(), "hr"=>'', "mi"=>'', "url"=>'media_search.php');

    echo "<h1>".str('SCHEDULE_TITLE')."</h1>";
    message($message);

    echo '<p><b>'.str('SCHEDULE_AUTO_TITLE').'</b><p>'.str('SCHEDULE_AUTO_TXT').'
          <center>
             <form name="" enctype="multipart/form-data" action="index.php" method="post">
               <input type=hidden name="section" value="SCHED">
               <input type=hidden name="action" value="UPDATE_SIMESE">
               <table width="450" class="form_select_tab" border=0 >
               <tr>
                 <th style="text-align:center;">'.str('TIME').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_MON').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_TUE').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_WED').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_THU').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_FRI').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_SAT').'</th>
                 <th style="text-align:center;">'.str('SCHEDULE_AT_SUN').'</th>';
    if ( $use_scan_type )
      echo      '<th style="text-align:center;">'.str('MEDIA_SCAN_TYPE').'</th>';
    echo '     </tr>';

    $line = 0;
    foreach ($schedule as $entry)
    {
      $line++;
      echo '   <tr>
                 <td style="text-align:center;">
                   <input size="1" name="hr'.$line.'" value="'.$entry["hr"].'">
                   <input size="1" name="mi'.$line.'" value="'.$entry["mi"].'">
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="M" '. (in_array('M',$entry["days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="T" '. (in_array('T',$entry["days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="W" '. (in_array('W',$entry["days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="Th" '.(in_array('Th',$entry["days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="F" '. (in_array('F',$entry["days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="S" '. (in_array('S',$entry["days"]) ? 'checked' : '').'>
                 </td>
                 <td style="text-align:center;">
                   <input type="checkbox" name="day'.$line.'[]" value="Su" '.(in_array('Su',$entry["days"]) ? 'checked' : '').'>
                 </td>';
      if ( $use_scan_type )
        echo    '<td style="text-align:center;">
                   '.form_list_static_html('url'.$line, sched_scan_options(), $entry["url"], false, false, false).'
                 </td>';
      echo '   </tr>';
    }

    echo '     </tr>
               </table><br>
                 <input type="hidden" name="lines" value="'.$line.'">
                 <input type="submit" value="'.str('SCHEDULE_UPDATE_BUTTON').'">
             </form></center>
             ';
  }

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

  // ----------------------------------------------------------------------------------
  // Update the schedule
  // ----------------------------------------------------------------------------------

  function sched_update_win()
  {
    $schedule = array();
    $message = '';

    for ($line=1; $line <= $_REQUEST["lines"]; $line++)
    {
      $hrs  = $_REQUEST["hr".$line];
      $mins = $_REQUEST["mi".$line];
      $days = $_REQUEST["day".$line];
      $url  = $_REQUEST["url".$line];

      if ($hrs <0 || $hrs > 23 || !is_numeric($hrs))
      {
        $message = '!'.str('SCHEDULE_ERROR_HOUR');
        break;
      }
      elseif ($mins <0 || $mins > 59 || !is_numeric($mins))
      {
        $message = '!'.str('SCHEDULE_ERROR_MIN');
        break;
      }
      elseif ($mins != '' && $hrs != '' && count($days) >0 )
      {
        $schedule[] = array("hrs"=>$hrs, "mins"=>$mins, "days"=>$days, "url"=>$url);
      }
    }

    if ($message == '')
    {
      // Find and remove old schedule entries
      $sched = syscall('at');
      foreach(explode("\n",$sched) as $line)
        if (strpos($line,'media_search.php') && strpos($line,str('SCHEDULE_AT_EVERY').' '))
         syscall('at '.substr(ltrim($line),0,strpos(ltrim($line),' ')).' /delete');

      if ( count($schedule) > 0 )
      {
        foreach ($schedule as $entry)
        {
          // Create an "at" job to run at the specified time on the specified days
          exec ('at '.$entry["hrs"].':'.$entry["mins"].' /every:'.implode(',',$entry["days"]).' CMD /C "'.wget_location().'" -T 0 -O :null '.server_address().$entry["url"]);
        }
        sched_display(str('SCHEDULE_UPDATED'));
      }
      else
        sched_display(str('SCHEDULE_NONE'));
    }
    else
      sched_display('!'.$message);
  }

  function sched_update_simese()
  {
    // Simese 2.5.9 supports assigning a url to each task
    if ( version_compare(simese_version(), '2.5.8', '>=') )
      $use_scan_type = true;
    else
      $use_scan_type = false;

    $file_contents = '';
    $message = '';

    for ($line=1; $line <= $_REQUEST["lines"]; $line++)
    {
      $hrs  = $_REQUEST["hr".$line];
      $mins = $_REQUEST["mi".$line];
      $days = $_REQUEST["day".$line];
      $url  = $_REQUEST["url".$line];

      if ($hrs != '' && ($hrs <0 || $hrs > 23 || !is_numeric($hrs)) )
      {
        $message = str('SCHEDULE_ERROR_HOUR');
        break;
      }
      elseif ($mins != '' && ($mins <0 || $mins > 59 || !is_numeric($mins)) )
      {
        $message = str('SCHEDULE_ERROR_MIN');
        break;
      }
      elseif ($mins != '' && $hrs != '' && count($days) >0 )
      {
        if ( $use_scan_type )
          $file_contents .= "MediaRefresh=".implode(',',$days)." $hrs:$mins,$url".newline();
        else
          $file_contents .= "MediaRefresh=".implode(',',$days)." $hrs:$mins".newline();
      }
    }

    if ($message == '')
    {
      // Remove old schedule file
      @unlink(SIMESE_SCHEDULE);

      if (strlen($file_contents)>0)
      {
        write_binary_file( SIMESE_SCHEDULE, $file_contents );
        sched_display(str('SCHEDULE_UPDATED'));
      }
      else
        sched_display(str('SCHEDULE_NONE'));
    }
    else
      sched_display('!'.$message);
  }

  function sched_update_linux()
  {
    $hrs    = ($_REQUEST["hour"] =='' ? '*' : $_REQUEST["hour"]);
    $mins   = $_REQUEST["minute"];
    $dates  = ($_REQUEST["date"] =='' ? '*' : $_REQUEST["date"]);
    $months = ($_REQUEST["month"]=='' ? '*' : $_REQUEST["month"]);
    $days   = ($_REQUEST["day"]  =='' ? '*' : $_REQUEST["day"]);

    if ( preg_match("/[^-,*0123456789]/",($hrs.$mins.$dates.$months.$days)) != 0)
      sched_display('!'.str('SCHEDULE_ERROR_CHARS','"0123456789-,*"'));
    elseif ($mins == '')
      sched_display('!'.str('SCHEDULE_ERROR_FIELDS'));
    else
    {
      // Find and replace old crontab entry
      syscall('crontab -l | grep -v "'.SC_LOCATION.'media_search.php" | grep -v "^#" > /tmp/swisscron');
      syscall("echo '$mins $hrs $dates $months $days ".'"'.wget_location().'" -T 0 -O /dev/null "'.server_address().'media_search.php"\' >> /tmp/swisscron');
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
