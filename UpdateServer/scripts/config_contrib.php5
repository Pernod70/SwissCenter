<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/

  function contrib_display()
  {
    echo '<h1>Add a new contributor</h1>';
    $search = '%'.$_REQUEST["search"].'%';
    form_start('index.php5', 150, 'mesg');
    form_hidden('section','CONTRIB');
    form_hidden('action','DISPLAY');
    form_input('search','Search users');
    form_label('');
    form_submit('Search');
    form_end();
  
    $years = array("Developer"=>"00", "Style Guru"=>"99", "2004"=>"04",   "2005"=>"05",   "2006"=>"06",   "2007"=>"07",   "2008"=>"08",   "2009"=>"09",   "2010"=>"10",   "2011"=>"11",   "2012"=>"12");
    
    form_start('index.php5', 150, 'mesg');
    form_hidden('section','CONTRIB');
    form_hidden('action','UPDATE');
    form_list_dynamic('id','User to add',"select id,concat(name,'  (',username,')') 
                                              from mos_users 
                                             where username like '$search' or name like '$search' or email like '$search'
                                          order by name");      
    form_list_static('year','Year',$years,date('y'));
    form_submit('Submit');
    form_end();
    
    echo '<h1>Current Contributors</h1>';
    $current = db_toarray("select year,name,username,email 
                             from swiss_contributors c, mos_users u 
                            where c.user_id=u.id
                         order by year,name");
    
    array_to_table($current,'100%');       
    
  }
  
  function contrib_update()
  {
    db_insert_row('swiss_contributors',array("user_id"=>$_REQUEST["id"],"year"=>$_REQUEST["year"]));
    contrib_display();
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
