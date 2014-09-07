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
    foreach ($parsers as $parser) {
      set_time_limit(30);
      $parserclass = 'movie_'.$parser;
      $parser = new $parserclass(1, '../cache/Les Misérables.avi', array('TITLE' => 'Les Misérables'));
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
    foreach ($parsers as $parser) {
      set_time_limit(30);
      $parserclass = 'tv_'.$parser;
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
    set_time_limit(60);
    $iradio = $parser_tests->add_section("Internet Radio Parser",3);
    $parser_tests->add_test( $iradio, check_shoutcast(), str('PASS_SHOUTCAST_TEST'),str('FAIL_SHOUTCAST_TEST').'<p>'.str('IRADIO_SHOUTCAST_DESC','<a href="http://www.shoutcast.com/">www.shoutcast.com</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_liveradio(), str('PASS_LIVERADIO_TEST'),str('FAIL_LIVERADIO_TEST').'<p>'.str('IRADIO_LIVERADIO_DESC','<a href="http://www.live-radio.net/">www.live-radio.net</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_live365(),   str('PASS_LIVE365_TEST'),str('FAIL_LIVE365_TEST').'<p>'.str('IRADIO_LIVE365_DESC','<a href="http://www.live365.com/">www.live365.com</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_icecast(),   str('PASS_ICECAST_TEST'),str('FAIL_ICECAST_TEST').'<p>'.str('IRADIO_ICECAST_DESC','<a href="http://dir.xiph.org/index.php/">www.icecast.org</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_steamcast(), str('PASS_STEAMCAST_TEST'),str('FAIL_STEAMCAST_TEST').'<p>'.str('IRADIO_STEAMCAST_DESC','<a href="http://www.steamcast.com/">www.steamcast.com</a>'),FALSE);
    $parser_tests->add_test( $iradio, check_tunein(),    str('PASS_TUNEIN_TEST'),str('FAIL_TUNEIN_TEST').'<p>'.str('IRADIO_TUNEIN_DESC','<a href="http://tunein.com/">www.tunein.com</a>'),FALSE);
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
