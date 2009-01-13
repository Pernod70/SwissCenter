<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

  require_once( realpath(dirname(__FILE__).'/../ext/flickr/phpFlickr.php'));

  define('FLICKR_API_KEY', '78bacf06f6c11b7da6ac2dde9aaaf509');
  define('FLICKR_API_SECRET', 'c83eb9c5f60a0ee0');

  /**
   * Form URL of buddy icon for contact.
   *
   * Every flickr user has a 48x48 pixel buddyicon used to represent themselves.
   *
   * You can construct the url of a buddyicon once you know the user's NSID, icon server and icon farm, as returned by many api methods.
   *
   * @param array $contact
   * @return string URL
   */
  function flickr_buddy_icon( $contact )
  {
    // If the icon server is greater than zero, the url takes the following format:
    if ( $contact["iconfarm"] > 0 )
      return "http://farm".$contact["iconfarm"].".static.flickr.com/".$contact["iconserver"]."/buddyicons/".$contact["nsid"].".jpg";
    else
      return "http://www.flickr.com/images/buddyicon.jpg";
  }

  /**
   * Form URL of photos.
   *
   * You can construct the source URL to a photo once you know its ID, server ID, farm ID and secret, as returned by many API methods.
   *
   * The letter suffixes are as follows:
   *
   * s small square 75x75
   * t thumbnail, 100 on longest side
   * m small, 240 on longest side
   * - medium, 500 on longest side
   * b large, 1024 on longest side (only exists for very large original images)
   * o original image, either a jpg, gif or png, depending on source format
   *
   * Note: Original photos behave a little differently. They have their own secret (called originalsecret in responses) and a variable
   * file extension (called originalformat in responses). These values are returned via the API only when the caller has permission to
   * view the original size (based on a user preference and various other criteria). The values are returned by the flickr.photos.getInfo
   * method and by any method that returns a list of photos and allows an extras parameter (with a value of original_format), such as
   * flickr.photos.search. The flickr.photos.getSizes method, as always, will return the full original URL where permissions allow.
   *
   * @param array $photo
   * @param string $suffix
   *
   * @return string URL
   */
  function flickr_photo_url( $photo, $suffix )
  {
    if ( isset($photo["primary"]) )
      return "http://farm".$photo["farm"].".static.flickr.com/".$photo["server"]."/".$photo["primary"]."_".$photo["secret"]."_$suffix.jpg";
    elseif ( $suffix == 'o' )
      return "http://farm".$photo["farm"].".static.flickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["originalsecret"]."_$suffix.".$photo["originalformat"];
    else
      return "http://farm".$photo["farm"].".static.flickr.com/".$photo["server"]."/".$photo["id"]."_".$photo["secret"]."_$suffix.jpg";
  }

  /**
   * Returns the largest available sizes for a photo.
   *
   * @param array $photo
   */
  function flickr_get_photo_size( $photo )
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    // Returns the available sizes for a photo.
    $sizes = $flickr->photos_getSizes($photo["id"]);

    return $sizes[count($sizes)-1]["source"];
  }

  /**
   * Creates an array of flickr photo url's.
   *
   * @param string $type - valid types are photoset, interestingness, and favorites
   * @param integer $id - either photoset_id or user_id for favorites
   * @return array
   */
  function flickr_playlist( $type, $id=null )
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    switch ($type)
    {
      case 'interestingness':
        // Returns the list of interesting photos for the most recent day or a user-specified date.
        $photos = $flickr->interestingness_getList();
        break;

      case 'photoset':
        // Get list of photos for selected photoset
        $photos = $flickr->photosets_getPhotos($id);
        break;
            
      case 'favorites':
        // Get a list of favorite public photos for the given user.
        $photos = $flickr->favorites_getPublicList($id);
        break;
    }

    $photo_list = array();
    foreach ($photos["photo"] as $photo)
    {
      // Returns the available sizes for a photo.
      $sizes = $flickr->photos_getSizes($photo["id"]);

      // Use the largest available size for each photo
      $row["FILENAME"] = $sizes[count($sizes)-1]["source"];
      $row["TITLE"]    = $photo["title"];
      $photo_list[] = $row;
    }
    return $photo_list;
  }

  /**
   * Returns a link to start a flickr slideshow.
   *
   * @param string $type - valid types are photoset, interestingness, and favorites
   * @param integer $id - either photoset_id or user_id for favorites
   * @return url
   */
  function flickr_slideshow( $type, $id )
  {
    $params = "spec_type=flickr&flickr_type=$type&flickr_id=$id&".current_session()."&seed=".mt_rand();
    $link   = slideshow_link_by_browser( $params );
  
    return $link;
  }

  /**
   * Functions for managing the flickr navigation history.
   */
  function flickr_hist_init( $url )
  {
    $_SESSION["history"] = array();
    $_SESSION["history"][] = $url;
  }

  function flickr_hist_push( $url )
  {
    $_SESSION["history"][] = $url;
  }
  
  function flickr_hist_pop()
  {
    if (count($_SESSION["history"]) == 0)
      page_error(str('DATABASE_ERROR'));
  
    return array_pop($_SESSION["history"]);
  }
  
  function flickr_hist_most_recent()
  {
    if (count($_SESSION["history"]) == 0)
      page_error(str('DATABASE_ERROR'));
    
    return $_SESSION["history"][count($_SESSION["history"])-1];
  }

  function flickr_page_params()
  {
    // Remove pages from history
    if (isset($_REQUEST["del"]))
      for ($i=0; $i<$_REQUEST["del"]; $i++) 
        flickr_hist_pop();
    
    $this_url = url_remove_param(current_url(), 'del');
    $back_url = url_add_param(flickr_hist_most_recent(), 'del', 2);

    // Add page to history
    flickr_hist_push($this_url);

    return $back_url;
  }

 /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
