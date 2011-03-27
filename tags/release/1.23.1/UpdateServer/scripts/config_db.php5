<?
/**************************************************************************************************  
  
  /**
   * Executes SQL entered by the user and displays the results.
   *
   */
  
  function db_runsql()
  {
    $sql = un_magic_quote($_REQUEST["sql"]);

    echo "<h1>SQL Commands</h1>";

    if ( ($msg = test_db()) == 'OK' )
    {
      form_start('index.php5');
      form_hidden('section','DB');
      form_hidden('action','RUNSQL');
      form_text('sql','SQL statement',100,5,$sql);
      form_submit('Run SQL',1);
      form_end();
      
      $stmts = explode(';',$sql);
      
      foreach ($stmts as $sql)
      {    
        $sql=trim($sql);
        if (  in_array( strtolower(substr($sql,0,strpos($sql,' '))), array('select','show','desc')) )
        {          
          $data = array();
          $recs    = new db_query( $sql );
          $success = $recs->db_success();
          $heading = array();
        
          if ($success)
          {
            // Fetch data into an array
            while ($row = $recs->db_fetch_row())
              $data[] = $row;      
          
            // WOrk out what the headings are
            foreach($data[0] as $col=>$val)
              $heading[] = $col;
          
            // Display a pretty HTML table
            echo '<p>';
            array_to_table($data,join(',',$heading));
          }
          else
          {
            message('!SQL command failed.');
            echo $recs->db_get_error();
          }
            
          $recs->destroy();
        }
        elseif (!empty($sql))
        {
          $recs = new db_query();
          $recs->db_execute_sql($sql);
          if ( $recs->db_success() )
            message('SQL completed successfully.');
          else 
            message('!'.$recs->db_get_error());            
            
          $recs->destroy();
        }
      }
    }
    else
      message($msg);    
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>