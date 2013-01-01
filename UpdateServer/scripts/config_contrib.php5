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
  
    $years = array("Developer"=>"00", "Style Guru"=>"99", "2004"=>"04", "2005"=>"05", "2006"=>"06", "2007"=>"07", "2008"=>"08",   "2009"=>"09", "2010"=>"10", "2011"=>"11", "2012"=>"12", "2013"=>"13", "2014"=>"14");
    
    form_start('index.php5', 150, 'mesg');
    form_hidden('section','CONTRIB');
    form_hidden('action','UPDATE');
    form_list_dynamic('id','User to add',"select u.user_id,concat(ifnull(pf_firstname,''),' ',ifnull(pf_lastname,''),' (',u.username,')') username
                                              from phpbb_users u left join phpbb_profile_fields_data p on u.user_id=p.user_id
                                             where username like '$search' or user_email like '$search'
                                          order by username");      
    form_list_static('year','Year',$years,date('y'));
    form_submit('Submit');
    form_end();
    
    echo '<h1>Current Contributors</h1>';
    $current = db_toarray("select year,concat(ifnull(pf_firstname,''),' ',ifnull(pf_lastname,'')) name,username,user_email email
                             from swiss_contributors c, phpbb_users u, phpbb_profile_fields_data p
                            where c.user_id=u.user_id and p.user_id=u.user_id
                         order by year,name");
    
    array_to_table($current,'100%');
  }
  
  function contrib_update()
  {
    if (!db_value("select user_id from swiss_contributors where user_id=".$_REQUEST["id"]." and year='".$_REQUEST["year"]."'"))
    {
      // Update table swiss_contributors
      db_insert_row('swiss_contributors',array("user_id"=>$_REQUEST["id"],"year"=>$_REQUEST["year"]));

      // Assign user to relevant phpBB group
      if ($_REQUEST["year"] == '00')
        $group_name = 'Developer';
      elseif ($_REQUEST["year"] == '99')
        $group_name = 'Style Guru';
      else
        $group_name = 'Contributor 20'.$_REQUEST["year"];
        
      $group_id = db_value("select group_id from phpbb_groups where group_name='".$group_name."'");
     
      db_insert_row('phpbb_user_group',array("group_id"=>$group_id,"user_id"=>$_REQUEST["id"],"group_leader"=>0,"user_pending"=>0));
    }
    contrib_display();
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
