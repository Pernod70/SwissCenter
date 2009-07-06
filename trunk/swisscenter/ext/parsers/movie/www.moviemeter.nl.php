<?php
/**************************************************************************************************
   SWISScenter Source

   This is one of a selection of scripts all designed to obtain information from the internet
   relating to movies that the user has added to their database. It typically collects
   information such as title, genre, year of release, synopsis, directors and actors.

   Version history:
   20-May-2009: v1.0:     First public release

 *************************************************************************************************/

require_once( SC_LOCATION."/ext/xmlrpc/xmlrpc.inc" );

// API key registered to SwissCenter project
define('MOVIEMETER_API_KEY', 'wfdk9v1w8dxycgw0g9w9xdq3qt3nu2td');

/**
 * Searches the moviemeter.nl site for movie details
 *
 * @param integer $id
 * @param string $filename
 * @param string $title
 * @return bool
 */

function extra_get_movie_details($id, $filename, $title)
{
  $site_url = 'http://www.moviemeter.nl/';
  $accuracy = 0;

  // Start xmlrpc client
  $client = new xmlrpc_client("http://www.moviemeter.nl/ws");
  $client->return_type = 'phpvals';

  // Start session and retrieve sessionkey
  $message = new xmlrpcmsg("api.startSession", array(new xmlrpcval(MOVIEMETER_API_KEY, "string")));
  $resp = $client->send($message);

  if ($resp->faultCode())
  {
    send_to_log(3,"xmlrpc error:", $resp->faultString());
  }
  else
  {
    $session_info = $resp->value();
    $session_key = $session_info['session_key'];
  }

  // Change the word order
  if ( substr($title,0,4)=='The ' ) $title = substr($title,5).', The';
  if ( substr($title,0,4)=='Der ' ) $title = substr($title,5).', Der';
  if ( substr($title,0,4)=='Die ' ) $title = substr($title,5).', Die';
  if ( substr($title,0,4)=='Das ' ) $title = substr($title,5).', Das';

  // Get search results
  send_to_log(4,"Searching for details about ".$title." online at '$site_url'");

  $filmid = -1;
  if (preg_match("/\[(tt\d+)\]/",$filename, $imdbtt) != 0)
  {
    // Filename includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
    $message = new xmlrpcmsg("film.retrieveByImdb", array(new xmlrpcval($session_key, "string"), new xmlrpcval($imdbtt[1], "string")));
    $resp = $client->send($message);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error:", $resp->faultString());
    }
    else
    {
      $filmid = $resp->value();
    }
  }
  elseif (preg_match("/\[(tt\d+)\]/",$details[0]["TITLE"], $imdbtt) != 0)
  {
    // Film title includes an explicit IMDb title such as '[tt0076759]', use that to find the movie
    $moviedb_id = get_moviedb_id($title, $imdbtt[1]);
    $message = new xmlrpcmsg("film.retrieveByImdb", array(new xmlrpcval($session_key, "string"), new xmlrpcval($imdbtt[1], "string")));
    $resp = $client->send($message);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error:", $resp->faultString());
    }
    else
    {
      $filmid = $resp->value();
    }
  }
  else
  {
    // User moviemeter's internal search to get a list a possible matches
    $message = new xmlrpcmsg("film.search", array(new xmlrpcval($session_key, "string"), new xmlrpcval($title, "string")));
    $resp = $client->send($message);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error:", $resp->faultString());
    }
    else
    {
      $results = $resp->value();
      if ( count($results) > 0 )
        $filmid  = $results[0]["filmId"];
    }
  }

  if ( $filmid == -1 )
  {
    send_to_log(4,"No Match found.");
  }
  else
  {
    // Retrieve movie details
    $message = new xmlrpcmsg("film.retrieveDetails", array(new xmlrpcval($session_key, "string"), new xmlrpcval($filmid, "int")));
    $resp = $client->send($message);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error:", $resp->faultString());
    }
    else
    {
      $results = $resp->value();

      send_to_log(4,"Found details for '".$results["title"]."'");

      // Determine the URL of the art and attempt to download it.
      if ( !empty($results["thumbnail"]) )
        file_save_albumart( $results["thumbnail"]
                          , dirname($filename).'/'.file_noext($filename).'.'.file_ext($results["thumbnail"])
                          , $title);

      // Director(s)
      $directors = array();
      foreach ($results["directors"] as $director)
        $directors[] = $director["name"];
      scdb_add_directors ( $id, $directors );

      // Actor(s)
      $actors = array();
      foreach ($results["actors"] as $actor)
        $actors[] = $actor["name"];
      scdb_add_actors ( $id, $actors );

      // Genre(s)
      scdb_add_genres ( $id, $results["genres"] );

      // IMDb rating
      $message = new xmlrpcmsg("film.retrieveImdb", array(new xmlrpcval($session_key, "string"), new xmlrpcval($filmid, "int")));
      $resp = $client->send($message);

      if ($resp->faultCode())
      {
        send_to_log(3,"xmlrpc error:", $resp->faultString());
      }
      else
      {
        $results_imdb = $resp->value();
      }

      // Close session
      $message = new xmlrpcmsg("api.closeSession", array(new xmlrpcval($session_key, "string")));
      $resp = $client->send($message);

      // Store the single-value attributes in the database
      $columns = array ( "YEAR"              => $results["year"]
                       , "EXTERNAL_RATING_PC"=> floor($results_imdb["score"] * 10)
                       , "DETAILS_AVAILABLE" => 'Y'
                       , "SYNOPSIS"          => $results["plot"] );
      scdb_set_movie_attribs( $id, $columns );

      return true;
    }
  }

  // Mark the file as attempted to get details, but none available
  $columns = array ( "DETAILS_AVAILABLE" => 'N');
  scdb_set_movie_attribs($id, $columns);
  return false;
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
