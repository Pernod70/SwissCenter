<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( SC_LOCATION.'/base/file.php');
require_once( SC_LOCATION.'/ext/xmlrpc/xmlrpc.inc' );

/**
 * An implementation of the XML-RPC OpenSubtitles.org API.
 *
 * For full details on how to use the API see:
 * http://trac.opensubtitles.org/projects/opensubtitles/wiki/XMLRPC
 *
 * Status codes
 * XMLRPC should always return status code. For 2xx it means operation was sucessful, for 4xx it means there was some error and client should display this error to user.
 *
 * Successful 2xx
 * 200 OK
 * 206 Partial content; message
 *
 * Errors 4xx
 * 401 Unauthorized
 * 402 Subtitles has invalid format
 * 403 SubHashes (content and sent subhash) are not same!
 * 404 Subtitles has invalid language!
 * 405 Not all mandatory parameters was specified
 * 406 No session
 * 407 Download limit reached
 * 408 Invalid parameters
 * 409 Method not found
 * 410 Other or unknown error
 * 411 Empty or invalid useragent
 * 412 %s has invalid format (reason)
 * 413 Invalid ImdbID
 *
 */
class OpenSubtitles
{
  // API useragent registered to SwissCenter project
  var $useragent = 'SwissCenter v1.23';

  var $timeout = 60;

  var $token;
  var $client;

  function OpenSubtitles($username = '', $password = '', $lang = 'en')
  {
    // Start xmlrpc client
    $this->client = new xmlrpc_client("http://api.opensubtitles.org/xml-rpc");
    $this->client->return_type = 'phpvals';

    $response = $this->LogIn($username, $password, $lang, $this->useragent);
    $this->token = $response['token'];
  }

  /**
   * This simple function returns basic server info, it could be used for ping or telling server info to client.
   *
   * @return array
   */
  function ServerInfo()
  {
    $message = new xmlrpcmsg("ServerInfo");
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This will login user. This function should be called always when starting talking with server. It returns token, which must be used in later communication.
   * If user has no account, blank username and password should be OK. As language - use  ISO639 2 letter code and later communication will be done in this
   * language if applicable (error codes and so on).
   *
   * @param string $username
   * @param string $password
   * @param string $language
   * @param string $useragent
   * @return array
   *
   */
  function LogIn( $username, $password, $language, $useragent )
  {
    $message = new xmlrpcmsg("LogIn", array(new xmlrpcval($username, "string"),
                                            new xmlrpcval($password, "string"),
                                            new xmlrpcval($language, "string"),
                                            new xmlrpcval($useragent, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error Login:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This will logout user (ends session id). Good call this function is before ending (closing) clients program.
   *
   * @return array
   */
  function LogOut()
  {
    $message = new xmlrpcmsg("LogOut", array(new xmlrpcval($this->token, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error LogOut:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns information about found subtitles. It is designed making multiple search at once. When nothing is found, 'data' is empty.
   * If sublanguageid is empty, or have value 'all' - it search in every sublanguage, you can set it to multiple languages (e.g. eng,dut,cze).
   *
   * @param array $search
   * @return array
   */
  function SearchSubtitles( $search )
  {
    $xmlrpc_search = array();
    foreach ($search as $item)
    {
      $xmlrpc_search[] = new xmlrpcval(array("sublanguageid" => new xmlrpcval($item["sublanguageid"], "string"),
                                             "moviehash"     => new xmlrpcval($item["moviehash"], "string"),
                                             "moviebytesize" => new xmlrpcval($item["moviebytesize"], "string"),
                                             "imdbid"        => new xmlrpcval($item["imdbid"], "string"),
                                             "query"         => new xmlrpcval($item["query"], "string")), "struct");
    }
    $message = new xmlrpcmsg("SearchSubtitles", array(new xmlrpcval($this->token, "string"),
                                                      new xmlrpcval($xmlrpc_search, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error SearchSubtitles:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This is possible only for logged-in users. Scenario: user have directory with movies, for which he cannot find subtitles.
   * With this function he subscribe to possible results, when someone else will upload matching subtitles. Once a day (or week...based on users profile) will system send subtitle link by mail to user.
   *
   * @param array $sublanguageid
   * @param array $search
   * @return array
   */
  function SearchToMail( $sublanguageid, $search )
  {
    $xmlrpc_sublanguageid = array();
    foreach ($sublanguageid as $item)
    {
      $xmlrpc_sublanguageid[] = new xmlrpcval($item, "string");
    }
    $xmlrpc_search = array();
    foreach ($search as $item)
    {
      $xmlrpc_search[] = new xmlrpcval(array("moviehash" => new xmlrpcval($item["moviehash"], "string"),
                                             "moviesize" => new xmlrpcval($item["moviesize"], "string")), "struct");
    }
    $message = new xmlrpcmsg("SearchToMail", array(new xmlrpcval($this->token, "string"),
                                                   new xmlrpcval($xmlrpc_sublanguageid, "array"),
                                                   new xmlrpcval($xmlrpc_search, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error SearchToMail:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This method returns !IDSubtitleFile, if Subtitle Hash exists in database. If not exists, it returns '0'.
   *
   * @param array $subhash
   * @return array
   */
  function CheckSubHash( $subhash )
  {
    $xmlrpc_subhash = array();
    foreach ($subhash as $item)
    {
      $xmlrpc_subhash[] = new xmlrpcval($item, "string");
    }
    $message = new xmlrpcmsg("CheckSubHash", array(new xmlrpcval($this->token, "string"),
                                                   new xmlrpcval($xmlrpc_subhash, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error CheckSubHash:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This method returns best matching !MovieImdbID, MovieName, MovieYear, if available for each $moviehash. See also CheckMovieHash2().
   *
   * @param array $moviehash
   * @return array
   */
  function CheckMovieHash( $moviehash )
  {
    $xmlrpc_moviehash = array();
    foreach ($moviehash as $item)
    {
      $xmlrpc_moviehash[] = new xmlrpcval($item, "string");
    }
    $message = new xmlrpcmsg("CheckMovieHash", array(new xmlrpcval($this->token, "string"),
                                                     new xmlrpcval($xmlrpc_moviehash, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error CheckMovieHash:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This method returns matching !MovieImdbID, MovieName, MovieYear, if available for each $moviehash, always sorted by SeenCount DESC.
   *
   * @param array $moviehash
   * @return array
   */
  function CheckMovieHash2( $moviehash )
  {
    $xmlrpc_moviehash = array();
    foreach ($moviehash as $item)
    {
      $xmlrpc_moviehash[] = new xmlrpcval($item, "string");
    }
    $message = new xmlrpcmsg("CheckMovieHash2", array(new xmlrpcval($this->token, "string"),
                                                      new xmlrpcval($xmlrpc_moviehash, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error CheckMovieHash2:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Inserts or updates data to tables, which are used for CheckMovieHash() and CheckMovieHash2().
   *
   * @param array $moviehash
   * @return array
   */
  function InsertMovieHash( $moviehash )
  {
    $xmlrpc_movie = array();
    foreach ($moviehash as $item)
    {
      $xmlrpc_movie[] = new xmlrpcval(array("moviehash"     => new xmlrpcval($item["moviehash"], "string"),
                                            "moviebytesize" => new xmlrpcval($item["moviebytesize"], "string"),
                                            "imdbid"        => new xmlrpcval($item["imdbid"], "string"),
                                            "movietimems"   => new xmlrpcval($item["movietimems"], "string"),
                                            "moviefps"      => new xmlrpcval($item["moviefps"], "string"),
                                            "moviefilename" => new xmlrpcval($item["moviefilename"], "string")), "struct");
    }
    $message = new xmlrpcmsg("InsertMovieHash", array(new xmlrpcval($this->token, "string"),
                                                      new xmlrpcval($xmlrpc_movie, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error InsertMovieHash:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This function needs to be called before UploadSubtitles(), because it is possible subtitles already exists on server.
   * It takes 2 parameters, second parameter is array of information for subtitles to be uploaded, minimum cd1 is required.
   *
   * @param array $subtitles
   * @return array
   */
  function TryUploadSubtitles( $subtitles )
  {
    $xmlrpc_subtitles = array();
    foreach ($subtitles as $cd=>$item)
    {
      $xmlrpc_subtitles[$cd] = new xmlrpcval(array("subhash"       => new xmlrpcval($item["subhash"], "string"),
                                                   "subfilename"   => new xmlrpcval($item["subfilename"], "string"),
                                                   "moviehash"     => new xmlrpcval($item["moviehash"], "string"),
                                                   "moviebytesize" => new xmlrpcval($item["moviebytesize"], "string"),
                                                   "movietimems"   => new xmlrpcval($item["movietimems"], "string"),
                                                   "movieframes"   => new xmlrpcval($item["movieframes"], "string"),
                                                   "moviefps"      => new xmlrpcval($item["moviefps"], "string"),
                                                   "moviefilename" => new xmlrpcval($item["moviefilename"], "string")), "struct");
    }
    $message = new xmlrpcmsg("TryUploadSubtitles", array(new xmlrpcval($this->token, "string"),
                                                         new xmlrpcval($xmlrpc_subtitles, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error TryUploadSubtitles:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This function have to be called after TryUploadSubtitles(). Many information are same, important part is subcontent.
   * It should be gzip-ed (without header) and must be base64 encoded.
   *
   * @param array $subtitles
   * @return array
   */
  function UploadSubtitles( $subtitles )
  {
    $xmlrpc_subtitles = array();
    foreach ($subtitles as $cd=>$item)
    {
      switch ($cd)
      {
        case "baseinfo":
          $xmlrpc_subtitles[$cd] = new xmlrpcval(array("idmovieimdb"          => new xmlrpcval($item["idmovieimdb"], "string"),
                                                       "moviereleasename"     => new xmlrpcval($item["moviereleasename"], "string"),
                                                       "movieaka"             => new xmlrpcval($item["movieaka"], "string"),
                                                       "sublanguageid"        => new xmlrpcval($item["sublanguageid"], "string"),
                                                       "subauthorcomment"     => new xmlrpcval($item["subauthorcomment"], "string"),
                                                       "hearingimpaired"      => new xmlrpcval($item["hearingimpaired"], "string"),
                                                       "highdefinition"       => new xmlrpcval($item["highdefinition"], "string"),
                                                       "automatictranslation" => new xmlrpcval($item["automatictranslation"], "string")), "struct");
          break;

        default:
          $xmlrpc_subtitles[$cd] = new xmlrpcval(array("subhash"       => new xmlrpcval($item["subhash"], "string"),
                                                       "subfilename"   => new xmlrpcval($item["subfilename"], "string"),
                                                       "moviehash"     => new xmlrpcval($item["moviehash"], "string"),
                                                       "moviebytesize" => new xmlrpcval($item["moviebytesize"], "string"),
                                                       "movietimems"   => new xmlrpcval($item["movietimems"], "string"),
                                                       "movieframes"   => new xmlrpcval($item["movieframes"], "string"),
                                                       "moviefps"      => new xmlrpcval($item["moviefps"], "string"),
                                                       "moviefilename" => new xmlrpcval($item["moviefilename"], "string"),
                                                       "subcontent"    => new xmlrpcval($item["subcontent"], "string")), "struct");
          break;
      }
    }
    $message = new xmlrpcmsg("UploadSubtitles", array(new xmlrpcval($this->token, "string"),
                                                      new xmlrpcval($xmlrpc_subtitles, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error UploadSubtitles:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns array of MD5 => ISO639-2 language codes, trying to detect language for given $text.
   * Note: $text MUST be base64 encoded and should be gzipped (without header), for save some bandwidth and for better speed.
   *
   * @param array $text
   * @return array
   */
  function DetectLanguage( $text )
  {
    $xmlrpc_text = array();
    foreach ($text as $item)
    {
      $xmlrpc_text[] = new xmlrpcval($item, "string");
    }
    $message = new xmlrpcmsg("DetectLanguage", array(new xmlrpcval($this->token, "string"),
                                                     new xmlrpcval($xmlrpc_text, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error DetectLanguage:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns BASE64 encoded gzipped IDSubtitleFile(s). You need to BASE64 decode and ungzip 'data' to get its contents.
   *
   * @param array $IDSubtitleFile
   * @return array
   */
  function DownloadSubtitles( $IDSubtitleFile )
  {
    $xmlrpc_id = array();
    foreach ($IDSubtitleFile as $item)
    {
      $xmlrpc_id[] = new xmlrpcval($item, "string");
    }
    $message = new xmlrpcmsg("DownloadSubtitles", array(new xmlrpcval($this->token, "string"),
                                                        new xmlrpcval($xmlrpc_id, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error DownloadSubtitles:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This method is needed to report bad hash, e.g. subtitles are right for this movie file, but they are de-synchornized - for other version/release.
   * With this method number of reports is counted in db, and after some number, hash will be automatically deleted from database.
   *
   * @param integer $IDSubMovieFile
   * @return array
   */
  function ReportWrongMovieHash( $IDSubMovieFile )
  {
    $message = new xmlrpcmsg("ReportWrongMovieHash", array(new xmlrpcval($this->token, "string"),
                                                           new xmlrpcval($IDSubMovieFile, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error ReportWrongMovieHash:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns list of allowed subtitle languages, default is english language. Use  ISO639-1 (2 characters code)
   *
   * @param string $language
   * @return array
   */
  function GetSubLanguages( $language = 'en' )
  {
    $message = new xmlrpcmsg("GetSubLanguages", array(new xmlrpcval($language, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error GetSubLanguages:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns array of available translations for a program. In array you can find date of last created string and number of strings.
   * Current supported programs are 'subdownloader' and 'oscar'.
   *
   * @param string $program
   * @return array
   */
  function GetAvailableTranslations( $program )
  {
    $message = new xmlrpcmsg("GetAvailableTranslations", array(new xmlrpcval($this->token, "string"),
                                                               new xmlrpcval($program, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error GetAvailableTranslations:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns base64 encoded strings for language ($iso639) in some $format (mo, po, txt, xml).
   * Use  ISO639-1 (2 characters code), $program should be 'subdownloader' or 'oscar'.
   *
   * @param string $iso639
   * @param string $format
   * @param string $program
   * @return array
   */
  function GetTranslations( $iso639, $format, $program )
  {
    $message = new xmlrpcmsg("GetTranslations", array(new xmlrpcval($this->token, "string"),
                                                      new xmlrpcval($iso639, "string"),
                                                      new xmlrpcval($format, "string"),
                                                      new xmlrpcval($program, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error GetTranslations:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns array of movies, which was found on imdb.com and in opensubtitles internal movie database where id starts at 10000000.
   *
   * @param string $query
   * @return array
   */
  function SearchMoviesOnIMDB( $query )
  {
    $message = new xmlrpcmsg("SearchMoviesOnIMDB", array(new xmlrpcval($this->token, "string"),
                                                         new xmlrpcval($query, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error SearchMoviesOnIMDB:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns array, info for $imdbid from  www.imdb.com.
   *
   * @param string $imdbid
   * @return array
   */
  function GetIMDBMovieDetails( $imdbid )
  {
    $message = new xmlrpcmsg("GetIMDBMovieDetails", array(new xmlrpcval($this->token, "string"),
                                                          new xmlrpcval($imdbid, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error GetIMDBMovieDetails:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Allows logged users insert new movies to opensubtitles internal movie database, which are not in IMDB.com.
   *
   * @param array $movie
   * @return array
   */
  function InsertMovie( $movie )
  {
    $xmlrpc_movie = new xmlrpcval(array("moviename" => new xmlrpcval($movie["moviename"], "string"),
                                        "movieyear" => new xmlrpcval($movie["movieyear"], "string")), "struct");

    $message = new xmlrpcmsg("InsertMovie", array(new xmlrpcval($this->token, "string"),
                                                  new xmlrpcval($xmlrpc_movie, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error InsertMovie:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Allows logged users vote for subtitles. Score must be from interval 1 to 10.
   * If user will vote more than 1 time for same subtitles, next votes will be not counted.
   *
   * @param array $subtitle
   * @return array
   */
  function SubtitlesVote( $subtitle )
  {
    $xmlrpc_vote = new xmlrpcval(array("idsubtitle" => new xmlrpcval($subtitle["idsubtitle"], "string"),
                                       "score"      => new xmlrpcval($subtitle["score"], "string")), "struct");

    $message = new xmlrpcmsg("SubtitlesVote", array(new xmlrpcval($this->token, "string"),
                                                    new xmlrpcval($xmlrpc_vote, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error SubtitlesVote:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Returns comments for subtitles.
   *
   * @param array $idsubtitle
   * @return array
   */
  function GetComments( $idsubtitle )
  {
    $xmlrpc_array = array();
    foreach ($idsubtitle as $item)
    {
      $xmlrpc_array[] = new xmlrpcval($item, "string");
    }
    $message = new xmlrpcmsg("GetComments", array(new xmlrpcval($this->token, "string"),
                                                  new xmlrpcval($xmlrpc_array, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error GetComments:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Allows logged users add new comment to subtitles. badsubtitle is optional, if set to 1, subtitles are marked as bad
   *
   * @param array $comment
   * @return array
   */
  function AddComment( $comment )
  {
    $xmlrpc_comment = new xmlrpcval(array("idsubtitle"  => new xmlrpcval($subtitle["idsubtitle"], "string"),
                                          "comment"     => new xmlrpcval($subtitle["comment"], "string"),
                                          "badsubtitle" => new xmlrpcval($subtitle["badsubtitle"], "string")), "struct");

    $message = new xmlrpcmsg("AddComment", array(new xmlrpcval($this->token, "string"),
                                                 new xmlrpcval($xmlrpc_comment, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error AddComment:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * Add new request for subtitles, user must be logged in. All parameters are mandatory except comment field - you can put there releasename of movie.
   *
   * @param array $request
   * @return array
   */
  function AddRequest( $request )
  {
    $xmlrpc_request = new xmlrpcval(array("sublanguageid" => new xmlrpcval($request["sublanguageid"], "string"),
                                          "idmovieimdb"   => new xmlrpcval($request["idmovieimdb"], "string"),
                                          "comment"       => new xmlrpcval($request["comment"], "string")), "struct");

    $message = new xmlrpcmsg("AddRequest", array(new xmlrpcval($this->token, "string"),
                                                 new xmlrpcval($xmlrpc_request, "array")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error AddRequest:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This function returns latest version with info for $program_name.
   *
   * @param string $program_name
   * @return array
   */
  function AutoUpdate ( $program_name )
  {
    $message = new xmlrpcmsg("AutoUpdate", array(new xmlrpcval($program_name, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error AutoUpdate:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }

  /**
   * This function should be called each 15 minutes after last request to xmlrpc.
   * It is used for not expiring current session. It also returns if current $token is registered.
   *
   * @return array
   */
  function NoOperation()
  {
    $message = new xmlrpcmsg("NoOperation", array(new xmlrpcval($this->token, "string")));
    $resp = $this->client->send($message, $this->timeout);

    if ($resp->faultCode())
    {
      send_to_log(3,"xmlrpc error NoOperation:", $resp->faultString());
    }
    else
    {
      return $resp->value();
    }
  }
}

/**
 * Hash code is based on Media Player Classic. In natural language it calculates: size + 64bit chksum of the first
 * and last 64k (even if they overlap because the file is smaller than 128k).
 * http://trac.opensubtitles.org/projects/opensubtitles/wiki/HashSourceCodes
 *
 * @param string $file
 * @return string
 */
function OpenSubtitlesHash($file)
{
  if ( !file_exists($file) ) { return '0'; }

  set_magic_quotes_runtime(0);

  $handle = fopen($file, "rb");
  $fsize = large_filesize($file);

  $hash = array(3 => 0,
                2 => 0,
                1 => ($fsize >> 16) & 0xFFFF,
                0 => $fsize & 0xFFFF);

  for ($i = 0; $i < 8192; $i++)
  {
    $tmp = ReadUINT64($handle);
    $hash = AddUINT64($hash, $tmp);
  }

  $offset = $fsize - 65536;
  fseek($handle, $offset > 0 ? $offset : 0, SEEK_SET);

  for ($i = 0; $i < 8192; $i++)
  {
    $tmp = ReadUINT64($handle);
    $hash = AddUINT64($hash, $tmp);
  }

  fclose($handle);
  return UINT64FormatHex($hash);
}

function ReadUINT64($handle)
{
  $u = unpack("va/vb/vc/vd", fread($handle, 8));
  return array(0 => $u["a"], 1 => $u["b"], 2 => $u["c"], 3 => $u["d"]);
}

function AddUINT64($a, $b)
{
  $o = array(0 => 0, 1 => 0, 2 => 0, 3 => 0);

  $carry = 0;
  for ($i = 0; $i < 4; $i++)
  {
    if (($a[$i] + $b[$i] + $carry) > 0xffff )
    {
      $o[$i] += ($a[$i] + $b[$i] + $carry) & 0xffff;
      $carry = 1;
    }
    else
    {
      $o[$i] += ($a[$i] + $b[$i] + $carry);
      $carry = 0;
    }
  }

  return $o;
}

function UINT64FormatHex($n)
{
  return sprintf("%04x%04x%04x%04x", $n[3], $n[2], $n[1], $n[0]);
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>