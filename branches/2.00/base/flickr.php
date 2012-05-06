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
  function flickr_get_photo_size( $photo_id )
  {
    $flickr = new phpFlickr(FLICKR_API_KEY,FLICKR_API_SECRET);
    $flickr->enableCache("db");

    // Returns the available sizes for a photo.
    $sizes = $flickr->photos_getSizes($photo_id);

    return $sizes[count($sizes)-1]["source"];
  }

 /**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
