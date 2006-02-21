<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  ob_start();

  include_once('common.php');
  include_once('mysql.php');
  include_once('html_form.php');  

  //*************************************************************************************************
  // MESG section
  //*************************************************************************************************
  
  //
  // Display 
  //
  
  function mesg_display($delete = '', $new = '', $edit_id = '')
  {
    $data = db_toarray("select message_id, title, message_text from swiss_messages order by added desc");
    
    echo "<h1>Messages</h1>";
    message($delete);
    form_start('index.php', 150, 'mesg');
    form_hidden('section','MESG');
    form_hidden('action','MODIFY');
    form_select_table('message_id',$data,array('class'=>'form_select_tab','width'=>'100%'),'message_id',
                      array('TITLE'=>'','MESSAGE_TEXT'=>''), $edit_id, 'mesg');
    form_submit('Remove Selected Messages',1,'center');
    form_end();
  
    echo '<p><h1>Add a filename<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','MESG');
    form_hidden('action','NEW');
    form_input('title','Title',30,'',un_magic_quote($_REQUEST['title']));
    form_text('text','Message Text',70,5,un_magic_quote($_REQUEST['text']));
    form_submit('Add Message',2);
    form_end();
  }
   
  //
  // Delete/Modify
  //
  
  function mesg_modify()
  {
    $selected = form_select_table_vals('message_id');
    $edit_id = form_select_table_edit('message_id', 'mesg');
    $update_data = form_select_table_update('message_id', 'mesg');
    
    if(!empty($edit_id))
    {
      mesg_display('', '', $edit_id);
    }
    else if(!empty($update_data))
    {
      
      $title = $update_data["TITLE"];
      $text  = $update_data["MESSAGE_TEXT"];
      
      if (empty($title))
        mesg_display('',"!Please enter a title");
      elseif (empty($text))
        mesg_display('',"!Please enter a message");
      else
      {
        db_sqlcommand("update swiss_messages set title='".db_escape_str($title)."' ,message_text='".db_escape_str($text)."' where message_id=".db_escape_str($update_data["MESSAGE_ID"]));
        mesg_display('Message updated');
      }
    }
    else if(!empty($selected))
    {
      foreach ($selected as $id)
        db_sqlcommand("delete from swiss_messages where message_id='".$id."'");

      mesg_display('The selected messages been removed.');
    }
    else
      mesg_display();
  }
  
  //
  // Add
  //
  
  function mesg_new()
  {
    $title = un_magic_quote($_REQUEST["title"]);
    $text  = un_magic_quote($_REQUEST["text"]);
    
    if (empty($title))
      mesg_display('',"!Please enter a title");
    elseif (empty($text))
      mesg_display('',"!Please enter a message");
    else 
    {
      if ( db_insert_row('swiss_messages',array('ADDED'=>db_datestr(),'TITLE'=>$title,'MESSAGE_TEXT'=>$text)) === false)
        mesg_display(db_error());
      else
        mesg_display('Message Added');
    }
  }

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
        fwrite($out, "echo '<hr noshade size=1><font color=red>".trim($line)."</font><hr size=1 noshade>'\n");
        fwrite($out, trim($line)."\n" );
      }
    fclose($out);
    chmod($fsp,0755);
  }
  
  function cmd_display()
  {
    set_time_limit(86400); 
    $cmd = un_magic_quote($_REQUEST["cmd"]);
  
    echo '<p><h1>Enter UNIX Commands<p>';
    message($new);
    form_start('index.php');
    form_hidden('section','CMD');
    form_hidden('action','DISPLAY');
    form_text('cmd','Command',70,5,$cmd);
    form_submit('Run Commands',1);
    form_end();

    echo '<p><h1>Output<p></h1><pre>';
    write_script("_script.sh",$cmd);
    passthru('/bin/bash _script.sh 2>&1');
    echo '</pre>';
  }
  
  //*************************************************************************************************
  // Populate main sections of the webpage
  //*************************************************************************************************

  //
  // Create and amanager the menu (static menu)
  //

 function display_menu()
 {
   echo '<table width="160">';
   menu_item('Messages','section=MESG&action=DISPLAY');
   menu_item('UNIX Commands','section=CMD&action=DISPLAY');
   echo '</table>';
 }
 
 //
 // Calls the correct function for displaying content on the page.
 //
 
 function display_content()
 {
   if (!empty($_REQUEST["section"]))
   {
     $func = (strtoupper($_REQUEST["section"]).'_'.strtoupper($_REQUEST["action"]));
     @$func();
   }
   else 
     mesg_display();
 }
  
//*************************************************************************************************
// Get the database parameters from the ini file as they are needed throughout the script, and 
// then execute the template file
//*************************************************************************************************

  if ($_REQUEST["section"]!='INSTALL' && file_exists('swisscenter.ini'))
  {
    foreach( parse_ini_file('swisscenter.ini') as $k => $v)
      if (!empty($v))
        define (strtoupper($k),$v);
  }

  $page_title = 'SwissCenter ONLINE Configuration Utility';
  $page_width = '750px';
  include("config_template.php");

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
