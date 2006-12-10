<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/
  
  require_once( realpath(dirname(__FILE__).'/base/settings.php'));

  $device = get_sys_pref('LAST_DEVICE','none');
  $key    = $_REQUEST["ctrl"];

  echo '<p><map name="FPMap0">
        <area href="?ctrl=KEY_MUTE" shape="circle" coords="45, 57, 12">
        <area href="?ctrl=error" shape="circle" coords="110, 61, 11">
        <area href="?ctrl=KEY_MUSIC" shape="circle" coords="44, 83, 11">
        <area href="?ctrl=KEY_MOVIE" shape="circle" coords="68, 85, 12">
        <area href="?ctrl=KEY_PHOTO" shape="circle" coords="91, 86, 12">
        <area href="?ctrl=KEY_HOME_PAGE" shape="circle" coords="112, 86, 12">
        <area href="?ctrl=error" shape="circle" coords="50, 108, 11">
        <area href="?ctrl=error" shape="circle" coords="107, 109, 11">
        <area href="?ctrl=KEY_USER" shape="circle" coords="46, 153, 10">
        <area href="?ctrl=KEY_SETUP" shape="circle" coords="68, 151, 10">
        <area href="?ctrl=KEY_GOTO" shape="circle" coords="90, 153, 11">
        <area href="?ctrl=KEY_HELP" shape="circle" coords="112, 156, 7">
        <area href="?ctrl=KEY_FAST_FORWARD" shape="circle" coords="106, 387, 10">
        <area href="?ctrl=KEY_FAST_BACKWARD" shape="circle" coords="49, 387, 11">
        <area href="?ctrl=KEY_PLAYPAUSE" shape="circle" coords="79, 391, 10">
        <area href="?ctrl=KEY_ESCAPE" shape="circle" coords="78, 412, 11">
        <area href="?ctrl=KEY_NEXT_TRACK" shape="circle" coords="106, 408, 11">
        <area href="?ctrl=KEY_PREVIOUS_TRACK" shape="circle" coords="50, 409, 11">
        <area href="?ctrl=KEY_1_RC" shape="circle" coords="50, 291, 11">
        <area href="?ctrl=KEY_2_RC" shape="circle" coords="79, 294, 11">
        <area href="?ctrl=KEY_3_RC" shape="circle" coords="107, 291, 12">
        <area href="?ctrl=KEY_4_RC" shape="circle" coords="49, 316, 11">
        <area href="?ctrl=KEY_5_RC" shape="circle" coords="78, 319, 11">
        <area href="?ctrl=KEY_6_RC" shape="circle" coords="106, 317, 10">
        <area href="?ctrl=KEY_7_RC" shape="circle" coords="51, 340, 12">
        <area href="?ctrl=KEY_8_RC" shape="circle" coords="79, 342, 10">
        <area href="?ctrl=KEY_9_RC" shape="circle" coords="107, 341, 10">
        <area href="?ctrl=KEY_PLUS_10" coords="51, 363, 10" shape="circle">
        <area href="?ctrl=KEY_0_RC" shape="circle" coords="78, 367, 11">
        <area href="?ctrl=KEY_TAB" coords="107, 363, 10" shape="circle">
        <area href="?ctrl=KEY_ENTER" shape="circle" coords="78, 214, 11">
        <area href="?ctrl=KEY_BACKSPACE" shape="polygon" coords="35, 177, 66, 177, 35, 196">
        <area href="?ctrl=KEY_PAGE_UP" shape="polygon" coords="122, 179, 122, 200, 92, 179">
        <area href="?ctrl=KEY_PAGE_DOWN" shape="polygon" coords="123, 231, 90, 248, 123, 248">
        <area href="?ctrl=KEY_RESET" shape="polygon" coords="33, 230, 33, 248, 69, 248">
        <area href="?ctrl=KEY_UP" shape="polygon" coords="53, 194, 66, 202, 91, 202, 105, 194, 80, 185">
        <area href="?ctrl=KEY_DOWN" shape="polygon" coords="66, 226, 55, 232, 80, 241, 106, 232, 93, 225">
        <area href="?ctrl=KEY_RIGHT" shape="polygon" coords="94, 207, 111, 199, 118, 215, 113, 229, 97, 220">
        <area href="?ctrl=KEY_LEFT" shape="polygon" coords="44, 197, 59, 206, 61, 220, 45, 227, 38, 214">
        </map>
        <img border="0" src="/images/rc.jpg" width="144" height="500" usemap="#FPMap0"></p>';
  
  if ( !empty($key) && $key!='error' && $device != 'none')
  {
     // Send the control code over to the hardware device
     @file_get_contents('http://'.$device.':2020/ethernet_rc.cgi??%7Fsim_key='.$key);     
  }

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
