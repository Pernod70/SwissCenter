<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  // ----------------------------------------------------------------------------------
  // Displays the privacy policy
  // ----------------------------------------------------------------------------------

  function privacy_display()
  {
    echo '<h1>'.str('PRIVACY_POLICY').'</h1>'.
         '<p>'.str('PRIVACY_DATA_COLLECTION',str('MOVIE_OPTIONS')).
         '<p>'.str('PRIVACY_COMMUNICATION'); 
  }
  
/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>