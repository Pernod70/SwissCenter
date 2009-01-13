<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/../base/media.php'));

  // ----------------------------------------------------------------------------------
  // Display current expressions
  // ----------------------------------------------------------------------------------
  
  function tv_expr_display($msg = '', $edit_id = '')
  {
    $list = array(str('ENABLED')=>'YES',str('DISABLED')=>'NO');
    $data = db_toarray("select pos, pos 'Priority', expression from tv_expressions order by 1");
    
    echo "<h1>".str('TV_EXPRESSIONS')."</h1>";
    message($msg);
    
    echo '<p>'.str('TV_EXPR_PROMPT');
    form_start('index.php', 150, 'expr');
    form_hidden('section','TV_EXPR');
    form_hidden('action','MODIFY');
    form_select_table('pos',$data,str('PRIORITY').','.str('EXPRESSION'),
                      array('class'=>'form_select_tab','width'=>'100%'),'pos',
                      array('POS'=>'','EXPRESSION'=>''), $edit_id, 'expr');
    if (!$edit_id)
    {
      echo '<p><tr><td align="center">
              <input type="Submit" name="subaction" value="'.str('TV_EXPR_DEL_BUTTON').'"> &nbsp; 
              <input type="Submit" name="subaction" value="'.str('TV_EXPR_DEFAULTS').'"> &nbsp; 
            </td></tr>';
    }
    form_end();
  
    echo '<p><h1>'.str('TV_EXPR_ADD').'<p>';
    form_start('index.php');
    form_hidden('section','TV_EXPR');
    form_hidden('action','NEW');
    form_input('pos',str('PRIORITY'),5,'',un_magic_quote($_REQUEST['pos']));
    form_input('expr',str('EXPRESSION'),50,'',un_magic_quote($_REQUEST['expr']));
    form_submit(str('TV_EXPR_ADD_BUTTON'),2);
    form_end();
    
    echo '<p><h1>'.str('TV_EXPR_TEST').'<p>';
    form_start('index.php', 150, 'conn');
    form_hidden('section', 'TV_EXPR');
    form_hidden('action', 'TEST');
    form_input('filename',str('FILENAME'),80,'',un_magic_quote($_REQUEST['filename']));
    form_label(str('TV_EXPR_TEST_PROMPT'));
    form_submit(str('TV_EXPR_TEST_BUTTON'), 2);
    form_end();
  }   

  // ----------------------------------------------------------------------------------
  // Modify or delete existing expressions
  // ----------------------------------------------------------------------------------
  
  function tv_expr_modify()
  {
    $selected = form_select_table_vals('pos');
    $edit_id = form_select_table_edit('pos', 'expr');
    $update_data = form_select_table_update('pos', 'expr');
    
    if ($_REQUEST["subaction"] == str('TV_EXPR_DEFAULTS'))
    {
      db_sqlcommand("DELETE FROM tv_expressions");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 1,'".addslashes('{p}/[^/]*/.*\W+s{s}e{e}\W+{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 2,'".addslashes('{p}\W+s{s}e{e}\W+{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 3,'".addslashes('{p}\W+{s}x{e}\W+{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 4,'".addslashes('{p}/series {s}/{e}\W+{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 5,'".addslashes('{p}/season {s}/{e}\W+{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 6,'".addslashes('{p}/{s}/{e}\W*{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 7,'".addslashes('{p}/{e}\W+{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 8,'".addslashes('{p}/{t}\W+\(?s{s}e{e}\)?')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES ( 9,'".addslashes('{p}/{t}\W+\(?{s}x{e}\)?')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES (10,'".addslashes('{p}\W+s{s}e{e}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES (11,'".addslashes('{p}\W+{s}x{e}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES (12,'".addslashes('{p}/{t}')."' )");
      db_sqlcommand("INSERT INTO tv_expressions (pos, expression) VALUES (13,'".addslashes('{t}')."' )");
      tv_expr_display();
    }
    elseif (!empty($edit_id))
    {
      tv_expr_display('', $edit_id);
    }
    elseif(!empty($update_data))
    {
      $oldpos = $update_data["POS"];
      $pos    = $update_data["PRIORITY"];
      $expr   = un_magic_quote($update_data["EXPRESSION"]);
      if ( !is_numeric($pos) || empty($expr) )
        tv_expr_display("!".str('TV_EXPR_ERROR_MISSING'));
      elseif ( @preg_match(tv_expand_pattern($expr),'') === false )
        tv_expr_display("!".str('TV_EXPR_ERROR_INVALID'));
      else
      {
        // Build new expression list
        $exprs   = db_col_to_list("select expression from tv_expressions where pos!=$oldpos and pos<$pos order by pos");
        $exprs[] = $expr;
        $exprs   = array_merge($exprs, db_col_to_list("select expression from tv_expressions where pos!=$oldpos and pos>=$pos order by pos"));
       
        db_sqlcommand("delete from tv_expressions");
        foreach ($exprs as $pos=>$pattern)
          db_sqlcommand("insert into tv_expressions (pos, expression) values ($pos+1, '".addslashes($pattern)."')");

        tv_expr_display(str('TV_EXPR_UPDATE_OK'));
      }
    }
    elseif(!empty($selected))
    {
      foreach ($selected as $id)
        db_sqlcommand("delete from tv_expressions where pos=$id");

      tv_expr_display(str('TV_EXPR_DELETE_OK'));
    }
    else
      tv_expr_display();
  }
  
  // ----------------------------------------------------------------------------------
  // Add a new expression
  // ----------------------------------------------------------------------------------
  
  function tv_expr_new()
  {
    $pos  = $_REQUEST["pos"];
    $expr = un_magic_quote($_REQUEST["expr"]);
 
    if ( !is_numeric($pos) || empty($expr) )
      tv_expr_display("!".str('TV_EXPR_ERROR_MISSING'));
    elseif ( @preg_match(tv_expand_pattern($expr),'') === false )
      tv_expr_display("!".str('TV_EXPR_ERROR_INVALID'));
    else 
    {
      // Build new expression list
      $exprs   = db_col_to_list("select expression from tv_expressions where pos<$pos order by pos");
      $exprs[] = $expr;
      $exprs   = array_merge($exprs, db_col_to_list("select expression from tv_expressions where pos>=$pos order by pos"));
 
      db_sqlcommand("delete from tv_expressions");
      foreach ($exprs as $pos=>$pattern)
        db_sqlcommand("insert into tv_expressions (pos, expression) values ($pos+1, '".addslashes($pattern)."')");

      tv_expr_display(str('TV_EXPR_ADDED_OK'));
    }
  }
  
  // ----------------------------------------------------------------------------------
  // Test a filename against expressions
  // ----------------------------------------------------------------------------------
  
  function tv_expr_test()
  {
    $fsp  = un_magic_quote($_REQUEST["filename"]);
    $meta_fsp = dirname($fsp).'/'.file_noext($fsp);
    $data = get_tvseries_info($meta_fsp);
    
    if ( !isset($data["rule"]) )
      tv_expr_display("!".str('TV_EXPR_TEST_FAILED'));
    else
      tv_expr_display(str('TV_EXPR_TEST_SUCCESS',$data["rule"],$data["programme"],
                                                 $data["series"],$data["episode"],$data["title"]));
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
