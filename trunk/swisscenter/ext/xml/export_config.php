<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../../base/mysql.php'));
  require_once( realpath(dirname(__FILE__).'/../../base/file.php'));
  require_once( realpath(dirname(__FILE__).'/xmlbuilder.php'));

  function export_config_to_xml()
  {
    // Retrieve configuration data from the database
    $cat_list  = db_toarray("select * from categories");
    $user_list = db_toarray("select c.name cert_name, u.name user_name, u.pin from users u, certificates c where u.maxcert = c.cert_id");
    
    $loc_list  = db_toarray("select ml.name location, mt.media_name, cat.cat_name, c.name certificate
                               from media_locations ml, media_types mt, categories cat, certificates c 
                              where ml.media_type = mt.media_id
                                and ml.cat_id = cat.cat_id
                                and ml.unrated = c.cert_id");
    
    // Array of conversions to a single Yes/No format  
    $yes_no = array ('Y'=>"Yes"
                    , 'N'=>'No' 
                    );
    
    $xml = new XmlBuilder();
    $xml->push('swisscenter_config');
                             
      // Categories
      if ( count($cat_list) > 0)
      {
        $xml->push('categories');
        foreach ($cat_list as $cat) 
        {
          $xml->push('category');  
          $xml->element('name',$cat["CAT_NAME"]);
          $xml->element('download_info',$yes_no[$cat["DOWNLOAD_INFO"]]);
          $xml->pop(); // </category>
        }
        $xml->pop(); // </categories>
      }
                 
      // Media locations
      if ( count($loc_list) > 0 )
      {
        $xml->push('media_locations');
        foreach ($loc_list as $location)
        {
          $xml->push('location');
          $xml->element("directory",$location["LOCATION"]);
          $xml->element("media_type",$location["MEDIA_NAME"]);
          $xml->element("category",$location["CAT_NAME"]);
          $xml->element("unrated_certificate",$location["CERTIFICATE"]);
          $xml->pop(); // </location>
        }
        $xml->pop(); // </media_locations>
      }
      
      // Users
      if ( count($user_list) > 0)
      {
        $xml->push('users');
        foreach ($user_list as $user)
        {
          $xml->push('user');
          $xml->element('name',$user["USER_NAME"]);
          $xml->element('max_certificate',$user["CERT_NAME"]);
          if ( !empty($user["PIN"]) )
            $xml->element('pin',$user["PIN"]);
          $xml->pop(); // </user>
        }
        $xml->pop(); // </users>
      }
      
  	$xml->pop(); // </swisscenter_config>

  	header('Content-Type: text/xml');
    echo $xml->getXml();
  }
?>