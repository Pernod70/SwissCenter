<?php
/**************************************************************************************************
   SWISScenter Source
 *************************************************************************************************/

class parserUtil
{
  public function decodeSpecialCharacters($html) {
    while (strpos($html, '&#x') && strpos($html, ';'))
    {
      $startpos = strpos($html, '&#x');
      $endpos   = strpos($html, ';', $startpos);
      $html = substr($html, 0, $startpos) . '&#' . hexdec(substr($html, $startpos +3, $endpos - $startpos -3)) . substr($html, $endpos, strlen($html));
    }
    return html_entity_decode($html, ENT_QUOTES, 'ISO-8859-15');
  }

  public function decodeSpecialCharactersList($html) {
    for($i = 0; $i< count($html); $i++)
      $html[$i]= self::decodeSpecialCharacters($html[$i]);

    return $html;
  }

 /**
  * Given a string to search for ($needle) and an array of possible matches ($haystack) this
  * function will return an array of index numbers of the best match, accuracy and numbers in title,  and set $accuracy to the value
  * determined (0-100). If no match is found, then this function returns FALSE
  *
  * @param string $needle - title to match against
  * @param array $haystack - Array of values to check against
  * @param integer $accuracy - accuracy level (0-100)
  * @return best match
  */

  function most_likely_match($needle, $haystack, & $accuracy, $year = null) {
    $best_match = array (
      "id" => 0,
      "chars" => 0,
      "pc" => 0
    );
    $pc = 0;
    $title_and_year = $needle . (empty($year) ? "" : " (" . $year . ")");
    $haystack_copy = $haystack;
    $match_array = array();
    $match_array_index = 0;

    for ($i = 0; $i < count($haystack); $i++) {
      $haystack[$i] = $haystack_copy[$i] = trim(html_entity_decode(self :: decodeSpecialCharacters($haystack[$i]), ENT_QUOTES, 'ISO-8859-15'));
      $chars = similar_text(trim($title_and_year), $haystack[$i], $pc);
      $haystack_copy[$i] .= " (" . round($pc, 2) . "%)";

      $match_array[$match_array_index]["pc"] = $pc;
      $match_array[$match_array_index]["id"] = $i;
      $match_array[$match_array_index]["numbers"] = preg_replace("[^0-9]", "", ($year != false && "" != $year ? $haystack[$i] : strip_title($haystack[$i])));
      $match_array[$match_array_index]["name"] = $haystack[$i];
      $match_array_index++;

      if (($chars > $best_match["chars"] && $pc >= $best_match["pc"]) || $pc > $best_match["pc"])
        $best_match = array (
          "id" => $i,
          "chars" => $chars,
          "pc" => $pc
        );
    }

    // If we are sure that we found a good result, then get the file details.
    if ($best_match["pc"] > 75) {
      send_to_log(6, 'Possible matches are:', $haystack_copy);
      send_to_log(6, 'Best guess without taking numbers into account: [' . $best_match["id"] . '] - ' . $haystack[$best_match["id"]]);
      $accuracy = $best_match["pc"];
//      if ($match_array[0]["pc"] > 75) {
//        $index = $match_array[0]["id"];
//        send_to_log(6, "The first result from imdb is >75%, so will use this unless there are numbers involved.");
//      } else
        $index = $best_match["id"];

      $number_in_title = preg_replace("/[^0-9]/", "", $needle);
      $title_numbers = preg_replace("/[^0-9]/", "", $title_and_year);

      // Check if the title contains numbers
      if ($title_numbers != "") {
        $year_numbers = preg_replace("/[^0-9]/", "", $year);
        $number_alias = self :: get_number_roman($number_in_title);
        send_to_log(8, "This title contains numbers, using alias: " . $number_alias);
        $found = false;
        foreach ($match_array AS $key => $secondary_array) {
          $title_number_from_alias = self :: get_number_from_roman_in_title($secondary_array["name"]);
          send_to_log(8, "Comparing numbers... ");
          if (($secondary_array["numbers"] != "" && false !== strpos($title_numbers, $secondary_array["numbers"])) || ($number_alias != false && $title_number_from_alias != false && false !== strpos($secondary_array["name"], $number_alias) && $title_number_from_alias == $number_in_title)) {
            send_to_log(6, "Found a match that contains the number(s), will use this: " . $secondary_array["name"]);
            $index = $secondary_array["id"];
            if ($index != $best_match["id"])
              send_to_log(6, "Overriding the previously selected title will set the accuracy to " . $secondary_array["pc"]);
            break;
          }
        }
      }
      return $match_array[$index]["id"];
    } else {
      send_to_log(4, 'Multiple Matches found, No match > 75%', $haystack_copy);
      return false;
    }
  }

  /**
   * Convert number to roman numerals.
   *
   */

  function get_number_roman($number) {
    $roman = array("1" => "I",
                   "2" => "II",
                   "3" => "III",
                   "4" => "IV",
                   "5" => "V",
                   "6" => "VI",
                   "7" => "VII",
                   "8" => "VIII",
                   "9" => "IX",
                   "10" => "X");

    if (isset($roman[$number]))
      return $roman[$number];
    else
      return false;
  }

  /**
   * Get number from roman numerals in a title.
   *
   */

  function get_number_from_roman_in_title($title) {
    if (false != strpos($title, " VIII"))
      return "8";
    elseif (false != strpos($title, " VII"))
      return "7";
    elseif (false != strpos($title, " VI"))
      return "6";
    elseif (false != strpos($title, " IV"))
      return "4";
    elseif (false != strpos($title, " III"))
      return "3";
    elseif (false != strpos($title, " II"))
      return "2";
    elseif (false != strpos($title, " IX"))
      return "9";
    elseif (false != strpos($title, " X"))
      return "10";
    elseif (false != strpos($title, " V"))
      return "5";
    else
      return false;
  }

  /**
   * Removes metadata from file name.
   *
   */

  function remove_metadata($title) {
    $pos = 0;
    //Edit this array if needed. There must be a blank space in front of the strings to make sure that they are not part of other words.
    $array = array (
      " 201",
      " 200",
      " 199",
      " 198",
      " 197",
      " 196",
      " FS ",
      " WS ",
      " SE ",
      " SPECIAL EDITION ",
      " DC ",
      " DIRCUT ",
      " DIRECTORS ",
      " EXTENDED ",
      " UNCUT ",
      " UNRATED ",
      " ALTERNATE ",
      " BOXSET ",
      " FESTIVAL ",
      " STV ",
      " LIMITED ",
      " PROPER ",
      " REPACK ",
      " RERIP ",
      " REAL ",
      " SUBBED ",
      " INTERNAL ",
      " READNFO ",
      " RETAIL ",
      " REFINED ",
      " DVDRIP ",
      " DVDSCR ",
      " DVD SCREENER ",
      " HDRIP ",
      " BDRIP ",
      " BRRIP ",
      " DVD5 ",
      " R5 ",
      " PPV ",
      " BLURAY ",
      " BLU RAY ",
      " HDTV ",
      " HDDVD ",
      " 1080 ",
      " 720 ",
      " NTSC ",
      " PAL ",
      " DVD R ",
      " DIVX ",
      " XVID ",
      " X264 ",
      " H264 ",
      " TR EN "
    );
    $count = count($array);
    for ($i = 0; $i < $count; $i++) {
      $pos = strpos(strtoupper($title.' '), $array[$i]);
      if ($pos != FALSE)
        $title = substr($title, 0, $pos).' ';
    }
    return trim($title);
  }

  /**
   * Returns the year from the filename.
   *
   * @param $title
   * @return unknown_type
   */
  function get_year_from_title($title) {
    $pos = 0;
    //Edit this array if needed. There must be a blank space in front of the strings to make sure that they are not part of other words.
    $array = array (
      " 20",
      " 19"
    );
    $count = count($array);
    for ($i = 0; $i < $count; $i++) {
      $pos = strpos($title, $array[$i]);
      if ($pos != FALSE) {
        $tempval = substr($title, $pos +1, 4);
        if (self::isValidYear($tempval))
          return $tempval;
      }
    }
    return false;
  }

  /**
   * Gets the folder name where the file is located. Removes CD# of SAMPLE folders if present and assumes that the
   * required info is in the parent folder of the CD# or SAMPLE folder.
   *
   * @param string $filename
   * @return string
   */
  function get_moviefolder_name($filename) {
    $moviefolder_name = strtoupper(dirname($filename));
    //Check if this is a CD# folder. If so, go to parent folder
    if (substr($moviefolder_name, strlen($moviefolder_name) - 4, 3) == "/CD") {
      send_to_log(8, "This is a CD folder " . $moviefolder_name . " Getting parent folder..");
      $moviefolder_name = substr($moviefolder_name, 0, strlen($moviefolder_name) - 4);
      send_to_log(8, "Assuming that this is the right one " . $moviefolder_name . "");
    } else
      if (self::is_sample_folder($filename)) {
        send_to_log(8, "This is a SAMPLE folder " . $moviefolder_name . " Getting parent folder..");
        $moviefolder_name = substr($moviefolder_name, 0, strlen($moviefolder_name) - 7);
        send_to_log(8, "Assuming that this is the right one " . $moviefolder_name . "");
      }
    $moviefolder_name = substr(strrchr($moviefolder_name, '/'), 1);
    $moviefolder_name = self::strip_moviefolder_title($moviefolder_name);
    $moviefolder_name = self::remove_metadata($moviefolder_name);
    $moviefolder_name = self::my_ucwords($moviefolder_name);
    if (substr($moviefolder_name, strlen($moviefolder_name) - 1, 1) !== " ") {
      $moviefolder_name = $moviefolder_name . " ";
    }
    return $moviefolder_name;
  }

  //This is a copy/paste of strip_title but without the assumption that last part is file extension.
  function strip_moviefolder_title($title) {
    $search = array (
      '/\(.*\)/',
      '/\[.*]/',
      '/\s[^\w&$]/',
      '/[^\w&$]\s/',
      '/\sCD[^\w].*/i',
      '/ +$/',
      '/_/',
      '/\./'
    );

    $replace = array (
      ' ',
      ' ',
      ' ',
      ' ',
      ' ',
      '',
      ' ',
      ' '
    );

    return preg_replace($search, $replace, $title);
  }

  /**
   * Determines if directory is a sample dir.
   *
   * @param string $filename
   * @return boolean
   */
  function is_sample_folder($filename) {
    $moviefolder_name = strtoupper(dirname($filename));
    return (substr($moviefolder_name, strlen($moviefolder_name) - 7, 7) == "/SAMPLE");
  }

  /**
   * Tries to determine whether the folder is named for movies.
   *
   * @param string $title
   * @return boolean
   */
  function is_standard_moviefolder_name($title) {
    $array = array (
      str('MOVIE_OPTIONS'),
      str('VIDEO'),
      "MOVIES",
      "MOVIE",
      "FILMS",
      "FILMEN",
      "FILMER",
      "VIDEOS",
      "VIDEO"
    );
    $count = count($array);
    for ($i = 0; $i < $count; $i++) {
      if (strcasecmp(strtoupper(trim($title)), trim($array[$i])) == 0) {
        return true;
      }
    }
    return false;
  }

  /**
   * Handles standard case conversion.
   *
   * @param $str
   * @return string
   */
  function my_ucwords($str) {
    $str = strtoupper($str);
    $all_uppercase = 'Ii|Iii|Iv|Vi|Vii|Viii|Ix|Xi|Xii';
    $all_lowercase = 'A|And|As|By|In|Of|Or|To|The|On';
    $suffixes = "'S";

    // Captialize all first letters
    $str = preg_replace('/\\b(\\w)/e', 'strtoupper("$1")', strtolower($str));

    // Capitalize acronymns and initialisms e.g. PHP
    $str = preg_replace("/\\b($all_uppercase)\\b/e", 'strtoupper("$1")', $str);

    // Decapitalize short words e.g. and
    // First and last word will not be changed to lower case (i.e. titles)
    $str = preg_replace("/(?<=\\W)($all_lowercase)(?=\\W)/e", 'strtolower("$1")', $str);

    // Decapitalize suffixes, and strip slashes added by 'e' modifier.
    $str = preg_replace("/(\\w)($suffixes)\\b/e", '"$1".strtolower("$2")', $str);
    $str = stripslashes($str);

    return $str;
  }

  function getYearFromFilePath($filename) {
    $year = false;
    $filename = basename($filename);

    if ((strpos($filename, "(") && strpos($filename, ")")) && strpos($filename, ")") - strpos($filename, "(") == 5) {
      $yearstring = substr($filename, strpos($filename, "(") + 1, 4);
      if (self::isValidYear($yearstring)) {
        $year = $yearstring;
      }
    } else {
      $yearstring = preg_get('/[^0-9]((19|20)\d{2})[^0-9]/', $filename);
      if (self::isValidYear($yearstring)) {
        $year = $yearstring;
      }
    }

    return $year;
  }

  /**
   * Checks validity of a year, must be between 1900 and 2040.
   *
   * @param string $year
   * @return boolean
   */
  function isValidYear($year) {
    if (is_numeric($year) && $year > 1900 && $year < 2040)
      return true;
    else
      return false;
  }
}
?>

