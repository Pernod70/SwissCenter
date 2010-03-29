<?php
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/config_check.php'));
require_once( realpath(dirname(__FILE__).'/../video_obtain_info.php'));

// ----------------------------------------------------------------------------------
// Display test results
// ----------------------------------------------------------------------------------

function check_parsers_display()
{
  $parser_tests = new test_summary( str('TEST_PARSERS') );
  $parser_tests->set_fail_icon('question.png');

  # ----------------------
  #  Movie Parsers
  # ----------------------

  if (internet_available())
  {
    $movie_parsers = $parser_tests->add_section("Movie Parsers",1);
    $parsers = get_parsers_list('movie');
    foreach ($parsers as $parserclass) {
      set_time_limit(30);
      $parser = new $parserclass(1, '../cache/Sherlock Holmes.avi', array('TITLE' => 'Sherlock Holmes'));
      $fails = array();
      foreach ($parser->supportedProperties as $property) {
        $result = $parser->parseProperty($property);
        if (!isset($result)) {
          $fails[] = array( "parser"   => $parser->getName(),
                            "property" => $property );
        }
      }
      if (count($fails) > 0) {
      	for ($i = 0; $i<count($fails); $i++)
  		    $parser_tests->add_test( $movie_parsers, false, $fails[$i]["parser"].'.'.$fails[$i]["property"], $fails[$i]["parser"].'.'.$fails[$i]["property"],FALSE);
      } else {
        $parser_tests->add_test( $movie_parsers, true, $parser->getName(), $parser->getName(),FALSE);
      }
      $parser->__destruct();
    }
  }

  # ----------------------
  #  TV Parsers
  # ----------------------

  if (internet_available())
  {
    $tv_parsers = $parser_tests->add_section("TV Parsers",2);
    $parsers = get_parsers_list('tv');
    foreach ($parsers as $parserclass) {
      set_time_limit(30);
      $parser = new $parserclass(1, '../cache/Lost.S01E01.Test.avi', array('PROGRAMME' => 'Lost',
                                                                           'SERIES'    => 6,
                                                                           'EPISODE'   => 1,
                                                                           'TITLE'     => 'Test'));
      $fails = array();
      foreach ($parser->supportedProperties as $property) {
        $result = $parser->parseProperty($property);
        if (!isset($result)) {
          $fails[] = array( "parser"   => $parser->getName(),
                            "property" => $property );
        }
      }
      if (count($fails) > 0) {
      	for ($i = 0; $i<count($fails); $i++)
  		    $parser_tests->add_test( $tv_parsers, false, $fails[$i]["parser"].'.'.$fails[$i]["property"], $fails[$i]["parser"].'.'.$fails[$i]["property"],FALSE);
      } else {
        $parser_tests->add_test( $tv_parsers, true, $parser->getName(), $parser->getName(),FALSE);
      }
      $parser->__destruct();
    }
  }

  # ----------------------
  # Internet Radio
  # ----------------------

  if (internet_available())
  {
    set_time_limit(30);
    $iradio = $parser_tests->add_section("Internet Radio Parser",3);
    $parser_tests->add_test( $iradio, check_shoutcast(), str('PASS_SHOUTCAST_TEST'),str('FAIL_SHOUTCAST_TEST').'<p>'.str('IRADIO_SHOUTCAST_DESC','<a href="http://www.shoutcast.com/">www.shoutcast.com</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_liveradio(), str('PASS_LIVERADIO_TEST'),str('FAIL_LIVERADIO_TEST').'<p>'.str('IRADIO_LIVERADIO_DESC','<a href="http://www.live-radio.net/">www.live-radio.net</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_live365(),   str('PASS_LIVE365_TEST'),str('FAIL_LIVE365_TEST').'<p>'.str('IRADIO_LIVE365_DESC','<a href="http://www.live365.com/">www.live365.com</a>'),FALSE);
  }

  # ----------------------
  # Display test results
  # ----------------------

  $parser_tests->display();
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
