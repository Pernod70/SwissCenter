<?php
/************************************************************************************************
  Exif reader v 1.2

  Original work by ** Richard James Kendall **
  (optimized and reworked for inclusion in the SwissCenter project by Robert Taylor)

  Usgage: $exif_array = exif('filename');
  
  Returns an ARRAY of EXIF tags and their associated values

************************************************************************************************/ 

$exif_data = array();

// Tag names
$exif_tags = array ( 0x000b => "ACDComment"                 , 0x00fe => "ImageType"
                   , 0x0106 => "PhotometicInterpret"        , 0x010e => "ImageDescription"
                   , 0x010f => "Make"                       , 0x0110 => "Model"
                   , 0x0112 => "Orientation"                , 0x0115 => "SamplesPerPixel"
                   , 0x011a => "XRes"                       , 0x011b => "YRes"
                   , 0x011c => "PlanarConfig"               , 0x0128 => "ResUnit"
                   , 0x0131 => "Software"                   , 0x0132 => "DateTime"
                   , 0x013b => "Artist"                     , 0x013f => "WhitePoint"
                   , 0x0211 => "YCbCrCoefficients"          , 0x0213 => "YCbCrPos"
                   , 0x0214 => "RefBlackWhite"              , 0x8298 => "Copyright"
                   , 0x829a => "ExposureTime"               , 0x829d => "FNumber"
                   , 0x8822 => "ExpProg"                    , 0x8827 => "ISOSpeedRating"
                   , 0x9003 => "DTOpticalCapture"           , 0x9004 => "DTDigitised"
                   , 0x9102 => "CompressedBitsPerPixel"     , 0x9201 => "ShutterSpeed"
                   , 0x9202 => "ApertureWidth"              , 0x9203 => "Brightness"
                   , 0x9204 => "ExposureBias"               , 0x9205 => "MaxApetureWidth"
                   , 0x9206 => "SubjectDistance"            , 0x9207 => "MeterMode"
                   , 0x9208 => "LightSource"                , 0x9209 => "Flash"
                   , 0x920a => "FocalLength"                , 0x9213 => "ImageHistory"
                   , 0x927c => "MakerNote"                  , 0x9286 => "UserComment"
                   , 0x9290 => "SubsecTime"                 , 0x9291 => "SubsecTimeOrig"
                   , 0x9292 => "SubsecTimeDigi"             , 0xa000 => "FlashPixVersion"
                   , 0xa001 => "ColourSpace"                , 0xa002 => "ImageWidth"
                   , 0xa003 => "ImageHeight"                , 0xa20e => "FocalPlaneXRes"
                   , 0xa20f => "FocalPlaneYRes"             , 0xa210 => "FocalPlaneResUnit"
                   , 0xa217 => "SensingMethod"              , 0xa300 => "ImageSource"
                   , 0xa301 => "SceneType"                  , 0xa401 => "RenderingProcess"
                   , 0xa402 => "ExposureMode"               , 0xa403 => "WhiteBalance"
                   , 0xa404 => "DigitalZoomRatio"           , 0xa405 => "FocalLength35mm"
                   , 0xa406 => "SceneCaptureType"           , 0xa407 => "GainControl"
                   , 0xa408 => "Contrast"                   , 0xa409 => "Saturation"
                   , 0xa40a => "Sharpness"                  , 0xa40c => "SubjectDistanceRange");


// data for EXIF enumeations
$exif_enum = array ( "Orientation"          => explode(',' ,  ','.str('EXIFVALS_ORIENTATION').',,,')
                   , "ExpProg"              => explode(',' ,  ','.str('EXIFVALS_EXP_PROG'))
                   , "LightSource"          => explode(',' ,  str('EXIFVALS_LIGHT_SOURCE'))
                   , "MeterMode"            => explode(',' ,  str('EXIFVALS_METER_MODE'))
                   , "ExposureMode"         => explode(',' ,  str('EXIFVALS_EXPOSE_MODE'))
                   , "WhiteBalance"         => explode(',' ,  str('EXIFVALS_WHITE_BALANCE'))
                   , "SceneCaptureType"     => explode(',' ,  str('EXIFVALS_SCENE_TYPE'))
                   , "FlashFired"           => explode(',' ,  str('EXIFVALS_FLASH')) );

                   
// Returns one byte from the file (as a numnber)
function fgetord($fp)
{
	return ord(fgetc($fp));
}

// converts a number from intel (little endian) to motorola (big endian format)
function ii2mm($intel) 
{
	$mm = "";
	for ($i = 0;$i <= strlen($intel);$i+=2) 
	{
		$mm .= substr($intel, (strlen($intel) - $i), 2);
	}
	return $mm;
}

// gets a number from the EXIF data and converts if to the correct representation
function getnumber($data, $start, $length, $align) 
{
	$a = bin2hex(substr($data, $start, $length));
	if (!$align) 
		$a = ii2mm($a);
	return hexdec($a);
}

// gets a rational number (num, denom) from the EXIF data and produces a decimal
function getrational($data, $align, $type) 
{
	$a = bin2hex($data);
	if (!$align)
		$a = ii2mm($a);
	
	if ($align == 1)
	{
		$n = hexdec(substr($a, 0, 8));
		$d = hexdec(substr($a, 8, 8));
	} 
	else 
	{
		$d = hexdec(substr($a, 0, 8));
		$n = hexdec(substr($a, 8, 8));
	}
	
	if ($type == "S" && $n > 2147483647)
		$n = $n - 4294967296;
	
	if ($n == 0)
		return 0;
	
	if ($d != 0)
		return ($n / $d); 
	else 
		return $n . "/" . $d;
}

// ------------------------------------------------------------------------------------------------
// Checks for an enumeration called $tname and returned the value $tvalue from the enumeration.
// If no enumeration exists and $default is false, then the value itself is returned,
// If no enumeration exists and $default is specified, then $default is returned.
// ------------------------------------------------------------------------------------------------

function enumvalue($tname, $tvalue, $default = false)
{
  global $exif_enum;
  if ( isset($exif_enum[$tname][$tvalue]) )
    return $exif_enum[$tname][$tvalue];
  elseif ( $default !== false)
    return $default;
  else
   return $tvalue;
}

// ------------------------------------------------------------------------------------------------
// Takes the flash value, splits it up into its component bits and returns the string it represents
// ------------------------------------------------------------------------------------------------

function flashvalue($dec)
{
  $bin = str_pad(decbin($dec), 8, "0",STR_PAD_LEFT);

  return enumvalue("FlashFired",substr($bin, 7, 1), '') 
	     . enumvalue("FlashStrobe",substr($bin, 5, 2), '') 
	     . enumvalue("FlashMode",substr($bin, 3, 2), '')
	     . enumvalue("RedEye",substr($bin, 8, 1), '') ;
}

// ------------------------------------------------------------------------------------------------
// Takes a tag id along with the format, data and length of the data and deals with it.
// ------------------------------------------------------------------------------------------------

function dealwithtag($tag, $format, $data, $length, $align, &$exif_info) 
{
	global $exif_tags;

	switch ($format)
	{
		case 2: // STRING
			$val = trim(substr($data, 0, $length));
			break;
		case 4: // ULONG
		case 9: // SLONG
			$val = enumvalue($exif_tags[$tag], getnumber($data, 0, 4, $align));
			break;
		case 3: // USHORT
		case 8: // SSHORT
			switch ($tag)
			{
				case 0x9209:
				  $num = getnumber($data, 0, 2, $align);
					$val = array( str_pad(decbin($num), 8, '0',STR_PAD_LEFT), flashvalue($num));
					break;
				case 0x9214:
					break;
				case 0xa001:
					$tmp = getnumber($data, 0, 2, $align);
					$val = ($tmp == 1 ? "sRGB" : "Uncalibrated");
					break;
				default:
					$val = enumvalue($exif_tags[$tag], getnumber($data, 0, 2, $align));
					break;
			} 
			break;
		case 5: // URATIONAL
			$val = getrational(substr($data, 0, 8), $align, "U");
			break;
		case 10: // SRATIONAL
			$val = getrational(substr($data, 0, 8), $align, "S");
			break;
		case 7: // UNDEFINED
			switch ($tag)
			{
				case 0xa300:
					$tmp = getnumber($data, 0, 2, $align);
					$val = ( $tmp == 3 ? "Digital Camera" : "Unknown");
					break;
				case 0xa301:
					$tmp = getnumber($data, 0, 2, $align);
					$val = ( $tmp == 3 ? "Directly Photographed" : "Unknown");
					break;
			  default:
			    $val = "";
			    break;
			}
			break;
	}
	
  if (isset($exif_tags[$tag]))
   	$exif_info[ ($exif_tags[$tag]) ] = $val;
  else 
    $exif_info['_ Tag:'.$tag] = $val;

  // Sorts the array (by key) to make it easier to debug
  ksort($exif_info);
}

// ------------------------------------------------------------------------------------------------
// Reads the tags from and EXIF IFD and if correct deals with the data
// ------------------------------------------------------------------------------------------------

function readEXIFDir($data, $offset_base, $exif_length, $align, &$exif_info) 
{
  // Lookup arrays
  $format_length = array(0, 1, 1, 2, 4, 8, 1, 1, 2, 4, 8, 4, 8);
  
	$data_in = "";
	$number_dir_entries = getnumber($data, 0, 2, $align);

	for ($i = 0;$i < $number_dir_entries;$i++)
	{
		$dir_entry    = substr($data, 2 + 12 * $i);
		$tag          = getnumber($dir_entry, 0, 2, $align);
		$format       = getnumber($dir_entry, 2, 2, $align);
		$components   = getnumber($dir_entry, 4, 4, $align);
		
		if (($format - 1) < 12)
		{
  		$byte_count = $components * $format_length[$format];
  		
  		// Get data
  		if ($byte_count > 4)
  		{
  			$offset_val = (getnumber($dir_entry, 8, 4, $align)) - $offset_base;
  			if (($offset_val + $byte_count) <= $exif_length)
    			$data_in = substr($data, $offset_val);
    	  else  
    	    $data_in = '';
  		}
  		else
  			$data_in = substr($dir_entry, 8);
  
  	  // Process data if present
  	  if ($data_in != '')
  	  {
    	  if ($tag == 0x8769)
    	  {
    			$tmp = (getnumber($data_in, 0, 4, $align)) - 8;
    			readEXIFDir(substr($data, $tmp), $tmp + 8 , $exif_length, $align, $exif_info);
    		} 
    		else
    			dealwithtag($tag, $format, $data_in, $byte_count, $align, $exif_info);
  	  }
		}
	}
}

// ------------------------------------------------------------------------------------------------
// Reads the EXIF header and if it is intact it calls readEXIFDir to get the data
// ------------------------------------------------------------------------------------------------

function extractEXIFData($data, $length, &$exif_info)
{
	global $align;
	if (substr($data, 0, 4) == "Exif")
	{
	  // Determine byte ordering
		$align = (substr($data, 6, 2) == "II" ? 0 : 1);

		// Should we read& process?
		if (getnumber($data, 8, 2, $align) == 0x2a) 
		{
  		$first_offset = getnumber($data, 10, 4, $align);
  		if ($first_offset >= 8 && $first_offset <= 16) 
    		readEXIFDir(substr($data, 14), 8, $length - 6, $align, $exif_info);
		}
	} 
}

// ------------------------------------------------------------------------------------------------
// Opens the JPEG file and attempts to find the EXIF data
// ------------------------------------------------------------------------------------------------

function exif($file)
{
  $exif_info = array();
  
	$fp = fopen($file, "rb");
	if (fgetord($fp) == 255 && fgetord($fp) == 216)
	{
  	while (!feof($fp))
  	{
  		if ( ($section_marker = fgetord($fp)) != 255 ) 
  		{
    		$lh = fgetord($fp);
    		$ll = fgetord($fp);
    		$section_length = ($lh << 8) | $ll;
    		$data =  fread($fp, $section_length - 2);
    		
    		if ($section_marker == 225)
          extractEXIFData( $data, $section_length, $exif_info);
  		}
  	}
	}
	fclose($fp);
	
	return $exif_info;
}

?>