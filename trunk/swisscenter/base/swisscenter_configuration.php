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
            $this->xml->appendChild($xpath,'<setting name="'.$row["NAME"].'">'.$row["VALUE"].'</setting>'); 
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
      $data = db_toarray("select u.name username, c.name certificate, c.scheme, u.pin from users u, certificates c where u.maxcert = c.cert_id");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $user_path = $this->xml->appendChild($xpath,'<user name="'.$row["USERNAME"].'"/>');
          $this->xml->appendChild($user_path, '<max_cert scheme="'.$row["SCHEME"].'">'.$row["CERTIFICATE"].'</max_cert>');
          $this->xml->appendChild($user_path, '<pin>'.$row["PIN"].'</pin>');
          $pref_path = $this->xml->appendChild($user_path, '<preferences />');
          $prefs = db_toarray("select * from user_prefs");
          if ($prefs != false && count($prefs)>0)
          {
            foreach ($prefs as $pref)
              $this->xml->appendChild($pref_path,'<setting name="'.$pref["NAME"].'">'.$pref["VALUE"].'</setting>');         
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
             $this->xml->appendChild( $cert_xpath,'<description>'.$row["DESCRIPTION"].'</description>');
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
      $data = db_toarray("select * from categories");
      if ($data !== false && count($data)>0)
      {
        foreach ($data as $row)
        {
          $cat_xpath = $this->xml->appendChild( $xpath,'<category />');
          $this->xml->appendChild($cat_xpath,'<name>'.$row["CAT_NAME"].'</name>');     
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
          $this->xml->appendChild( $loc_xpath, '<path>'.$row["NAME"].'</path>');
          $this->xml->appendChild( $loc_xpath, '<default_certificate scheme="'.$row["SCHEME"].'">'.$row["CERTIFICATE"].'</default_certificate>');
          $this->xml->appendChild( $loc_xpath, '<category>'.$row["CAT_NAME"].'</category>');
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
    }

    /**
     * Imports all categories into the database from the XML document
     *
     */
    
    function import_categories()
    {
      foreach ($this->xml->match('/swisscenter[1]/config[1]/categories[1]/category') as $abspath)
      {
        $name = $this->xml->getData($abspath.'/name[1]');
        $download = $this->xml->getData($abspath.'/download_info[1]');
        if (db_value("select count(*) from categories where cat_name = '$name'") == 0)
          db_insert_row('categories',array("cat_name"=>$name, "download_info"=>$download));
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
          db_insert_row('system_prefs',array("name"=>$attrib["NAME"], "value"=>$value));
        else 
          db_sqlcommand("update system_prefs set value ='$value' where name ='".$attrib["NAME"]."'");
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
        $name = $attrib["NAME"];
        if (db_value("select count(*) from users where name = '$name'") == 0)
        {
          $attrib      = $this->xml->getAttributes($userpath.'/max_cert[1]');
          $cert_scheme = $attrib["SCHEME"];
          $cert_name   = $this->xml->getData($userpath.'/max_cert[1]');
          $pin         = $this->xml->getData($userpath.'/pin[1]');
          
          if (($cert_id = db_value("select cert_id from certificates where name='$cert_name' and scheme='$cert_scheme'")) === false)
          {
            $errors[] = str('IMP_USER_CERT_MISSING',$name,$cert_name,$cert_scheme);
            continue; // Skip to next user.
          }
          else
          {
            db_insert_row('users',array("name"=>$name, "maxcert"=>$cert_id, "pin"=>$pin) );   
          }                    
        }
        
        // Import user preferences
        foreach ($this->xml->match($userpath.'/preferences[1]/setting') as $prefpath)
        {
          $attrib  = $this->xml->getAttributes($prefpath);
          $value   = $this->xml->getData($prefpath);
          $user_id = db_value("select user_id from users where name = '$name'");
          if (db_value("select count(*) from user_prefs where user_id=$user_id and name = '".$attrib["NAME"]."'") == 0)
            db_insert_row('user_prefs',array("user_id"=>$user_id, "name"=>$attrib["NAME"], "value"=>$value));
          else
            db_sqlcommand("update user_prefs set value ='$value' where user_id=$user_id and name ='".$attrib["NAME"]."'");
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
          $desc      = $this->xml->getData($certpath.'/description[1]');
          
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
        $path        = $this->xml->getData($locpath.'/path[1]');
        $cat_name    = $this->xml->getData($locpath.'/category[1]');
        $cert_name   = $this->xml->getData($locpath.'/default_certificate[1]');
        $attrib      = $this->xml->getAttributes($locpath.'/default_certificate[1]');
        $scheme_name = $attrib["SCHEME"];

        if (($cert_id = db_value("select cert_id from certificates where name='$cert_name' and scheme='$scheme_name'")) === false)               
          $errors[] = str('IMP_LOC_CERT_MISSING',$path,$cert_name,$scheme_name);
        elseif (($type_id = db_value("select media_id from media_types where media_name='$type'")) === false)               
          $errors[] = str('IMP_LOC_TYPE_MISSING',$path,$type);
        elseif (($cat_id = db_value("select cat_id from categories where cat_name='$cat_name'")) === false)               
          $errors[] = str('IMP_LOC_CAT_MISSING',$path,$cat_name);
        elseif (db_value("select count(*) from media_locations where name = '$path'") == 0)
          db_insert_row("media_locations", array("name"=>$path, "media_type"=>$type_id, "cat_id"=>$cat_id, "unrated"=>$cert_id));
      }

      if (count($errors)>0)
        send_to_log(1,'Errors importing certificates',$errors);
      return $errors;      
    }
  
    /**
     * Imports all media_locations into the database from the XML document
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
     * Imports all swisscenter configuration data
     *
     */
    
    function import_all()
    {
      $this->import_artfiles();
      $this->import_categories();
      $this->import_sys_prefs();    
      $this->import_certificates();
      
      $errors = array();
      $errors = array_merge($errors, $this->import_media_locations());
      $errors = array_merge($errors, $this->import_users());
      return $errors;      
    }
    
  }  

?>
