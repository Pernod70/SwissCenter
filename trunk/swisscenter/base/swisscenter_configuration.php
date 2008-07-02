<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/page.php'));
  require_once( realpath(dirname(__FILE__).'/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/file.php'));
  require_once( realpath(dirname(__FILE__).'/../ext/xml/XPath.class.php'));

  class Swisscenter_Configuration
  {
    var $xml;
    var $settings_path = '/swisscenter[1]/config[1]';
    
    /**
     * Constructor
     *
     */
    
    function Swisscenter_configuration( $fsp = '')
    {    
      $options = array(XML_OPTION_CASE_FOLDING => TRUE, XML_OPTION_SKIP_WHITE => TRUE);
      $this->xml =& new XPath(FALSE, $options);
      if ($fsp == '')
        $this->xml->importFromString('<?xml version="1.0" encoding="utf-8"?><swisscenter><config /></swisscenter>');
      else 
        $this->xml->importFromFile($fsp);
    }
  
    /**
     * Exports the settings to a file.
     *
     * @param string $filename
     * @return string - returns XML data or FALSE on error.
     */
    
    function save_to_file( $filename )
    {
      return $this->xml->exportToFile( $filename );
    }

    /**
     * Returns the XML representation of the swisscenter configuration.
     *
     * @return string - returns XML data or FALSE on error.
     */
    
    function get_xml()
    {
      return $this->xml->exportAsXml();
    }
    
    /**
     * Inserts the system preferences into the XML document at the specified xpath location
     *
     */
    
    function export_sys_prefs()
    {
      $exceptions = array('	DATABASE_PATCH','	DATABASE_UPDATE','DATABASE_VERSION');
      $xpath = $this->xml->appendChild($this->settings_path,'<system />');
      $data = db_toarray("select * from system_prefs");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          if ( !in_array($row["NAME"],$exceptions) )
            $this->xml->appendChild($xpath,'<setting name="'.$row["NAME"].'">'.utf8_encode(htmlspecialchars($row["VALUE"])).'</setting>'); 
        }
      }
    }
    
    /**
     * Inserts the user details and preferences into the XML document at the specified xpath location.
     *
     */
    
    function export_users()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<users />');
      $data = db_toarray("select u.user_id, u.name username, c.name certificate, c.scheme, u.pin, u.admin from users u, certificates c where u.maxcert = c.cert_id");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $user_path = $this->xml->appendChild($xpath,'<user name="'.utf8_encode(htmlspecialchars($row["USERNAME"]).'"/>'));
          $this->xml->appendChild($user_path, '<max_cert scheme="'.$row["SCHEME"].'">'.$row["CERTIFICATE"].'</max_cert>');
          $this->xml->appendChild($user_path, '<pin>'.$row["PIN"].'</pin>');
          $this->xml->appendChild($user_path, '<admin>'.$row["ADMIN"].'</admin>');
          $pref_path = $this->xml->appendChild($user_path, '<preferences />');
          $prefs = db_toarray("select * from user_prefs where user_id=$row[USER_ID]");
          if ($prefs != false && count($prefs)>0)
          {
            foreach ($prefs as $pref)
              $this->xml->appendChild($pref_path,'<setting name="'.$pref["NAME"].'">'.utf8_encode(htmlspecialchars($pref["VALUE"])).'</setting>');         
          }
        }
      }
    }
    
    /**
     * Inserts the certificates details into the XML document at the specified xpath location.
     *
     */
    
    function export_certificates()
    {
     $xpath = $this->xml->appendChild( $this->settings_path,'<certificates />');
     $data = db_toarray("select distinct scheme from certificates");
     if ($data !== false && count($data)>0)
     {
       foreach ($data as $cert)
       {
         $scheme_xpath = $this->xml->appendChild( $xpath,'<scheme name="'.$cert["SCHEME"].'" />');
         $certs = db_toarray("select * from certificates where scheme='$cert[SCHEME]'");
         if ($certs !== false && count($certs)>0)
         {
           foreach ($certs as $row) 
           {
             $cert_xpath = $this->xml->appendChild( $scheme_xpath,'<certificate name="'.$row["NAME"].'" />');
             $this->xml->appendChild( $cert_xpath,'<rank>'.$row["RANK"].'</rank>');
             $this->xml->appendChild( $cert_xpath,'<description>'.utf8_encode(htmlspecialchars($row["DESCRIPTION"])).'</description>');
           }     
         }
       }
     }
    }
    
    /**
     * Inserts the category details into the XML document at the specified xpath location.
     *
     */
    
    function export_categories()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<categories />');
      $data = db_toarray("select c.cat_name, p.cat_name PARENT, c.download_info, c.parent_id from categories c, categories p where c.parent_id=p.cat_id");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $cat_xpath = $this->xml->appendChild( $xpath,'<category />');
          $this->xml->appendChild($cat_xpath,'<name>'.utf8_encode(htmlspecialchars($row["CAT_NAME"])).'</name>');
          if ( $row["PARENT_ID"] > 0 )
            $this->xml->appendChild($cat_xpath,'<parent>'.utf8_encode(htmlspecialchars($row["PARENT"])).'</parent>');
          else
            $this->xml->appendChild($cat_xpath,'<parent />');
          $this->xml->appendChild($cat_xpath,'<download_info>'.$row["DOWNLOAD_INFO"].'</download_info>');     
        }
      }
    }
  
    /**
     * Inserts the artfile details into the XML document at the specified xpath location.
     *
     * @param object:XPath $this->xml
     * @param string $xpath
     */
    
    function export_artfiles()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<artfiles />');
      $data = db_toarray("select * from art_files");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
          $this->xml->appendChild($xpath,'<filename>'.$row["FILENAME"].'</filename>');     
      }
    }
       
    /**
     * Inserts the media location details into the XML document at the specified xpath location.
     * 
     */
  
    function export_media_locations()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<media_locations />');
      $data = db_toarray("select  ml.name, cat.cat_name, c.scheme, c.name certificate, mt.media_name
                            from media_locations ml, categories cat , certificates c, media_types mt
                           where ml.cat_id = cat.cat_id 
                             and c.cert_id = ml.unrated
                             and ml.media_type = mt.media_id");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $loc_xpath = $this->xml->appendChild( $xpath,'<location />');
          $this->xml->appendChild( $loc_xpath, '<type>'.$row["MEDIA_NAME"].'</type>');
          $this->xml->appendChild( $loc_xpath, '<path>'.utf8_encode(htmlspecialchars($row["NAME"])).'</path>');
          $this->xml->appendChild( $loc_xpath, '<default_certificate scheme="'.$row["SCHEME"].'">'.$row["CERTIFICATE"].'</default_certificate>');
          $this->xml->appendChild( $loc_xpath, '<category>'.utf8_encode(htmlspecialchars($row["CAT_NAME"])).'</category>');
        }
      }
    }
    
    /**
     * Inserts the tv expressions into the XML document at the specified xpath location.
     * 
     */
  
    function export_tv_expressions()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<tv_expressions />');
      $data = db_toarray("select  pos, expression from tv_expressions order by pos");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $loc_xpath = $this->xml->appendChild( $xpath,'<expression />');
          $this->xml->appendChild( $loc_xpath, '<pos>'.$row["POS"].'</pos>');
          $this->xml->appendChild( $loc_xpath, '<expression>'.utf8_encode(htmlspecialchars($row["EXPRESSION"])).'</expression>');
        }
      }
    }
    
    /**
     * Inserts the rss subscription details into the XML document at the specified xpath location.
     * 
     */
  
    function export_rss_subscriptions()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<rss_subscriptions />');
      $data = db_toarray("select rs.*, mt.media_name
                            from rss_subscriptions rs, media_types mt
                           where rs.type = mt.media_id");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $loc_xpath = $this->xml->appendChild( $xpath,'<subscription />');
          $this->xml->appendChild( $loc_xpath, '<type>'.$row["MEDIA_NAME"].'</type>');
          $this->xml->appendChild( $loc_xpath, '<url>'.utf8_encode(htmlspecialchars($row["URL"])).'</url>');
          $this->xml->appendChild( $loc_xpath, '<title>'.utf8_encode(htmlspecialchars($row["TITLE"])).'</title>');
          $this->xml->appendChild( $loc_xpath, '<update>'.$row["UPDATE_FREQUENCY"].'</update>');
          $this->xml->appendChild( $loc_xpath, '<cache>'.$row["CACHE"].'</cache>');
        }
      }
    }
    
    /**
     * Inserts the tvid details into the XML document at the specified xpath location.
     * 
     */
  
    function export_tvid_prefs()
    {
      $xpath = $this->xml->appendChild($this->settings_path,'<tvid_prefs />');
      $data = db_toarray("select * from tvid_prefs where tvid_custom is not null");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $loc_xpath = $this->xml->appendChild( $xpath,'<tvid />');
          $this->xml->appendChild( $loc_xpath, '<player>'.$row["PLAYER_TYPE"].'</player>');
          $this->xml->appendChild( $loc_xpath, '<tvid>'.$row["TVID_SC"].'</tvid>');
          $this->xml->appendChild( $loc_xpath, '<tvid_custom>'.$row["TVID_CUSTOM"].'</tvid_custom>');
        }
      }
    }
    
    /**
     * Exports all swisscenter configuration data
     *
     */

    function export_all()
    {
      $this->export_sys_prefs();
      $this->export_users();
      $this->export_certificates();
      $this->export_categories();
      $this->export_artfiles();
      $this->export_media_locations();
      $this->export_tv_expressions();
      $this->export_rss_subscriptions();
      $this->export_tvid_prefs();
    }

    /**
     * Imports all categories into the database from the XML document
     *
     */
    
    function import_categories()
    {
      $cat_parent = array();
      foreach ($this->xml->match('/swisscenter[1]/config[1]/categories[1]/category') as $abspath)
      {
        $name = html_entity_decode(utf8_decode($this->xml->getData($abspath.'/name[1]')));
        $parent = html_entity_decode(utf8_decode($this->xml->getData($abspath.'/parent[1]')));
        if ( !empty($parent) ) $cat_parent[$name] = $parent;
        $download = $this->xml->getData($abspath.'/download_info[1]');
        if (db_value("select count(*) from categories where cat_name = '".db_escape_str(un_magic_quote($name))."'") == 0)
          db_insert_row('categories',array("cat_name"=>$name, "parent_id"=>0, "download_info"=>$download));
      }
      foreach ($cat_parent as $name=>$parent)
      {
        $cat_id = db_value("select cat_id from categories where cat_name = '".db_escape_str(un_magic_quote($name))."'");
        $parent_id = db_value("select cat_id from categories where cat_name = '".db_escape_str(un_magic_quote($parent))."'");
        db_sqlcommand("update categories set parent_id=$parent_id where cat_id=$cat_id");
      }
    }
    
    /**
     * Imports all system preferences into the database from the XML document
     *
     * @return array - Array of errors
     */
    
    function import_sys_prefs()
    {
      foreach ($this->xml->match('/swisscenter[1]/config[1]/system[1]/setting') as $abspath)
      {
        $attrib = $this->xml->getAttributes($abspath);
        $value = $this->xml->getData($abspath);
        if (db_value("select count(*) from system_prefs where name = '".$attrib["NAME"]."'") == 0)
          db_insert_row('system_prefs',array("name"=>$attrib["NAME"], "value"=>addslashes($value)));
        else 
          db_sqlcommand("update system_prefs set value ='".addslashes($value)."' where name ='".$attrib["NAME"]."'");
      }
    }

    /**
     * Imports all users and user preferences into the database from the XML document
     *
     * @return array - Array of errors
     */

    function import_users()
    {
      $errors = array();
      foreach ($this->xml->match('/swisscenter[1]/config[1]/users[1]/user') as $userpath)
      {
        // Import user
        $attrib = $this->xml->getAttributes($userpath);
        $name = html_entity_decode(utf8_decode($attrib["NAME"]));
        if (db_value("select count(*) from users where name = '".db_escape_str(un_magic_quote($name))."'") == 0)
        {
          $attrib      = $this->xml->getAttributes($userpath.'/max_cert[1]');
          $cert_scheme = $attrib["SCHEME"];
          $cert_name   = $this->xml->getData($userpath.'/max_cert[1]');
          $pin         = $this->xml->getData($userpath.'/pin[1]');
          $admin       = $this->xml->getData($userpath.'/admin[1]');
          
          if (($cert_id = db_value("select cert_id from certificates where name='$cert_name' and scheme='$cert_scheme'")) === false)
          {
            $errors[] = str('IMP_USER_CERT_MISSING',$name,$cert_name,$cert_scheme);
            continue; // Skip to next user.
          }
          else
          {
            db_insert_row('users',array("name"=>$name, "maxcert"=>$cert_id, "pin"=>$pin, "admin"=>$admin) );   
          }                    
        }
        
        // Determine the user_id for importing settings.
        $user_id = db_value("select user_id from users where name = '".db_escape_str(un_magic_quote($name))."'");
        
        // Import user preferences
        foreach ($this->xml->match($userpath.'/preferences[1]/setting') as $prefpath)
        {
          $attrib  = $this->xml->getAttributes($prefpath);
          $value   = $this->xml->getData($prefpath);
          if (db_value("select count(*) from user_prefs where user_id=$user_id and name = '".$attrib["NAME"]."'") == 0)
            db_insert_row('user_prefs',array("user_id"=>$user_id, "name"=>$attrib["NAME"], "value"=>addslashes($value)));
          else
            db_sqlcommand("update user_prefs set value ='".addslashes($value)."' where user_id=$user_id and name ='".$attrib["NAME"]."'");
        }        
      }
      return $errors;      
    }
    
    /**
     * Imports all certificates into the database from the XML document
     *
     */

    function import_certificates()
    {
      foreach ($this->xml->match('/swisscenter[1]/config[1]/certificates[1]/scheme') as $schemepath)
      {
        $attrib      = $this->xml->getAttributes($schemepath);
        $scheme_name = $attrib["NAME"];
        foreach ($this->xml->match($schemepath.'/certificate') as $certpath)
        {
          $attrib    = $this->xml->getAttributes($certpath);
          $cert_name = $attrib["NAME"];
          $rank      = $this->xml->getData($certpath.'/rank[1]');
          $desc      = html_entity_decode(utf8_decode($this->xml->getData($certpath.'/description[1]')));
          
          if (db_value("select count(*) from certificates where name='$cert_name' and scheme='$scheme_name'") == 0)
            db_insert_row("certificates", array("name"=>$cert_name, "rank"=>$rank, "description"=>$desc, "scheme"=>$scheme_name));
        }
      }
    }
    
    /**
     * Imports all media_locations into the database from the XML document
     *
     * @return array - Array of errors
     */

    function import_media_locations()
    {
      $errors = array();
      foreach ($this->xml->match('/swisscenter[1]/config[1]/media_locations[1]/location') as $locpath)
      {
        $type        = $this->xml->getData($locpath.'/type[1]');
        $path        = html_entity_decode(utf8_decode($this->xml->getData($locpath.'/path[1]')));
        $cat_name    = html_entity_decode(utf8_decode($this->xml->getData($locpath.'/category[1]')));
        $cert_name   = $this->xml->getData($locpath.'/default_certificate[1]');
        $attrib      = $this->xml->getAttributes($locpath.'/default_certificate[1]');
        $scheme_name = $attrib["SCHEME"];

        if (($cert_id = db_value("select cert_id from certificates where name='$cert_name' and scheme='$scheme_name'")) === false)               
          $errors[] = str('IMP_LOC_CERT_MISSING',$path,$cert_name,$scheme_name);
        elseif (($type_id = db_value("select media_id from media_types where media_name='$type'")) === false)               
          $errors[] = str('IMP_LOC_TYPE_MISSING',$path,$type);
        elseif (($cat_id = db_value("select cat_id from categories where cat_name='$cat_name'")) === false)               
          $errors[] = str('IMP_LOC_CAT_MISSING',$path,$cat_name);
        elseif (db_value("select count(*) from media_locations where name = '".db_escape_str(un_magic_quote($path))."' and media_type=$type_id") == 0)
        {
          if ( db_insert_row("media_locations", array("name"=>$path, "media_type"=>$type_id, "cat_id"=>$cat_id, "unrated"=>$cert_id)) !== false)
          {
            $id = db_value("select location_id from media_locations where name='".db_escape_str(un_magic_quote($path))."' and media_type=".$type_id);            
            
            if (! is_windows() )
              symlink($path,SC_LOCATION.'media/'.$id);
          }
        }
      }

      if (count($errors)>0)
        send_to_log(1,'Errors importing certificates',$errors);
      return $errors;      
    }
  
    /**
     * Imports all artfile details into the database from the XML document
     *
     * @return array - Array of errors
     */
    
    function import_artfiles()
    {
      $files = db_col_to_list("select filename from art_files");
      foreach ($this->xml->match('/swisscenter[1]/config[1]/artfiles[1]/filename') as $filepath)
      {
        $name = $this->xml->getData($filepath);
        if (!in_array($name,$files))
          db_insert_row("art_files",array("filename"=>$name));
      }
    }
    
    /**
     * Imports all tv expressions into the database from the XML document
     *
     */
    
    function import_tv_expressions()
    {
      foreach ($this->xml->match('/swisscenter[1]/config[1]/tv_expressions[1]/expression') as $expressionpath)
      {
        $pos        = $this->xml->getData($expressionpath.'/pos[1]');
        $expression = $this->xml->getData($expressionpath.'/expression[1]');
        
        if (db_value("select count(*) from tv_expressions where pos = $pos") == 0)
          db_insert_row("tv_expressions", array("pos"=>$pos, "expression"=>addslashes($expression)));
        else 
          db_sqlcommand("update tv_expressions set expression='".addslashes($expression)."' where pos=$pos");
      }
    }
    
    /**
     * Imports all rss_subscriptions into the database from the XML document
     * 
     */
  
    function import_rss_subscriptions()
    {
      foreach ($this->xml->match('/swisscenter[1]/config[1]/rss_subscriptions[1]/subscription') as $rsspath)
      {
        $type    = $this->xml->getData($rsspath.'/type[1]');
        $url     = html_entity_decode(utf8_decode($this->xml->getData($rsspath.'/url[1]')));
        $title   = html_entity_decode(utf8_decode($this->xml->getData($rsspath.'/title[1]')));
        $update  = $this->xml->getData($rsspath.'/update[1]');
        $cache   = $this->xml->getData($rsspath.'/cache[1]');
        $type_id = db_value("select media_id from media_types where media_name='$type'");
        
        if (db_value("select count(*) from rss_subscriptions where type=$type_id and url='".db_escape_str(un_magic_quote($url))."'") == 0)
          db_insert_row("rss_subscriptions", array("type"=>$type_id, "url"=>$url, "title"=>$title, "update_frequency"=>$update, "cache"=>$cache));
      }
    }
    
    /**
     * Imports all tvid preferences into the database from the XML document
     *
     */
    
    function import_tvid_prefs()
    {
      foreach ($this->xml->match('/swisscenter[1]/config[1]/tvid_prefs[1]/tvid') as $tvidpath)
      {
        $player      = $this->xml->getData($tvidpath.'/player[1]');
        $tvid        = $this->xml->getData($tvidpath.'/tvid[1]');
        $tvid_custom = $this->xml->getData($tvidpath.'/tvid_custom[1]');
        
        if ($tvid_id = db_value("select tvid_id from tvid_prefs where player_type='$player' and tvid_sc='$tvid'"));
        {
          $data["tvid_custom"] = $tvid_custom;
          db_sqlcommand("update tvid_prefs set tvid_custom='$tvid_custom' where tvid_id=$tvid_id");
        }
      }
    }
    
    /**
     * Imports all swisscenter configuration data
     *
     */
    
    function import_all()
    {
      $this->import_artfiles();
      $this->import_categories();
      $this->import_sys_prefs();    
      $this->import_certificates();
      $this->import_tv_expressions();
      $this->import_rss_subscriptions();
      $this->import_tvid_prefs();
      
      $errors = array();
      $errors = array_merge($errors, $this->import_media_locations());
      $errors = array_merge($errors, $this->import_users());
      return $errors;      
    }
    
  }  

?>
