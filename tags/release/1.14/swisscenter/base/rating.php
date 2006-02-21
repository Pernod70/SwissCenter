<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once('users.php');
  require_once('mysql.php');
  
  function get_rating_scheme_name()
  {
    return get_sys_pref('ratings_scheme','BBFC');
  }
  
  function set_rating_scheme_name( $name )
  {
    set_sys_pref('ratings_scheme',$name);
  }
  
  function get_rating_scheme_list_sql()
  {
    return 'select distinct scheme,scheme name from certificates order by 1';
  }

  function get_rating_join()
  {
    return ' inner join media_locations ml on media.location_id = ml.location_id
            left outer join certificates media_cert on media.certificate = media_cert.cert_id
            inner join certificates unrated_cert on ml.unrated = unrated_cert.cert_id ';
  }

  function get_rating_filter()
  {
    return ' AND IFNULL(media_cert.rank,unrated_cert.rank) <= '.get_current_user_rank();
  }
  
  function get_cert_name_sql()
  {
    return 'IFNULL(media_cert.name,unrated_cert.name)';
  }
  
  function get_cert_list_sql()
  {
    return "select cert_id,name from certificates where scheme = '".get_rating_scheme_name()."' order by rank";
  }
 
  function get_cert_from_name($name)
  {
    return db_value("select cert_id from certificates where name='$name'");
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
