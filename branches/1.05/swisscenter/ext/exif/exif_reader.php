<?php
/*  Exif reader v 1.2
    By Richard James Kendall 
    Bugs to richard@richardjameskendall.com 
    Free to use, please acknowledge me 
    
    To use, just include this file (with require, include) and call
    
    exif(filename);
    
    An array called $exif_data will be populated with the exif tags and folders from the image.
*/ 

// holds the formatted data read from the EXIF data area
$exif_data = array();

// holds the number format used in the EXIF data (1 == moto, 0 == intel)
$align = 0;

// holds the lengths and names of the data formats
$format_length = array(0, 1, 1, 2, 4, 8, 1, 1, 2, 4, 8, 4, 8);
$format_type = array("", "BYTE", "STRING", "USHORT", "ULONG", "URATIONAL", "SBYTE", "UNDEFINED", "SSHORT", "SLONG", "SRATIONAL", "SINGLE", "DOUBLE");

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
$exif_enum = array ("Orientation"          => array("", "Normal (0 deg)", "Mirrored", "Upsidedown", "Upsidedown & Mirrored", "", "", "")
                   ,"ResUnit"              => array("", "inches", "inches", "cm", "mm", "um")
                   ,"YCbCrPos"             => array("", "Centre of Pixel Array", "Datum Points")
                   ,"ExpProg"              => array("", "Manual", "Program", "Apeture Priority", "Shutter Priority", "Program Creative", "Program Action", "Portrait", "Landscape")
                   ,"LightSource"          => array("Unknown", "Daylight", "Fluorescent", "Tungsten (incandescent)", "Flash", "Fine Weather", "Cloudy Weather", "Share", "Daylight Fluorescent", "Day White Fluorescent", "Cool White Fluorescent", "White Fluorescent", "Standard Light A", "Standard Light B", "Standard Light C", "D55", "D65", "D75", "D50", "ISO Studio Tungsten")
                   ,"MeterMode"            => array("Unknown", "Average", "Centre Weighted", "Spot", "Multi-Spot", "Pattern", "Partial")
                   ,"RenderingProcess"     => array("Normal Process", "Custom Process")
                   ,"ExposureMode"         => array("Auto", "Manual", "Auto Bracket")
                   ,"WhiteBalance"         => array("Auto", "Manual")
                   ,"SceneCaptureType"     => array("Standard", "Landscape", "Portrait", "Night Scene")
                   ,"GainControl"          => array("None", "Low Gain Up", "High Gain Up", "Low Gain Down", "High Gain Down")
                   ,"Contrast"             => array("Normal", "Soft", "Hard")
                   ,"Saturation"           => array("Normal", "Low Saturation", "High Saturation")
                   ,"Sharpness"            => array("Normal", "Soft", "Hard")
                   ,"SubjectDistanceRange" => array("Unknown", "Macro", "Close View", "Distant View")
                   ,"FocalPlaneResUnit"    => array("", "inches", "inches", "cm", "mm", "um")
                   ,"SensingMethod"        => array("", "Not Defined", "One-chip Colour Area Sensor", "Two-chip Colour Area Sensor", "Three-chip Colour Area Sensor", "Colour Sequential Area Sensor", "Trilinear Sensor", "Colour Sequential Linear Sensor")
                   ,"FlashFired"           => array("Did not fire","Fired")
                   ,"FlashStrobe"          => array("10"=>", Strobe return light not detected" , "11"=>", Strobe return light detected")
                   ,"FlashMode"            => array("01"=>", Compulsory mode" , "10"=>", Compulsory mode" , "11"=>", Auto mode")
                   );

// gets one byte from the file at handle $fp and converts it to a number
function fgetord($fp) {
	return ord(fgetc($fp));
}

// takes $data and pads it from the left so strlen($data) == $shouldbe
function pad($data, $shouldbe, $put) {
	if (strlen($data) == $shouldbe) {
		return $data;
	} else {
		$padding = "";
		for ($i = strlen($data);$i < $shouldbe;$i++) {
			$padding .= $put;
		}
		return $padding . $data;
	}
}

// converts a number from intel (little endian) to motorola (big endian format)
function ii2mm($intel) {
	$mm = "";
	for ($i = 0;$i <= strlen($intel);$i+=2) {
		$mm .= substr($intel, (strlen($intel) - $i), 2);
	}
	return $mm;
}

// gets a number from the EXIF data and converts if to the correct representation
function getnumber($data, $start, $length, $align) {
	$a = bin2hex(substr($data, $start, $length));
	if (!$align) {
		$a = ii2mm($a);
	}
	return hexdec($a);
}

// gets a rational number (num, denom) from the EXIF data and produces a decimal
function getrational($data, $align, $type) {
	$a = bin2hex($data);
	if (!$align) {
		$a = ii2mm($a);
	}
	if ($align == 1) {
		$n = hexdec(substr($a, 0, 8));
		$d = hexdec(substr($a, 8, 8));
	} else {
		$d = hexdec(substr($a, 0, 8));
		$n = hexdec(substr($a, 8, 8));
	}
	if ($type == "S" && $n > 2147483647) {
		$n = $n - 4294967296;
	}
	if ($n == 0) {
		return 0;
	}
	if ($d != 0) {
		return ($n / $d);
	} else {
		return $n . "/" . $d;
	}
}

// opens the JPEG file and attempts to find the EXIF data
function exif($file) {
	$fp = fopen($file, "rb");
	$a = fgetord($fp);
	if ($a != 255 || fgetord($fp) != 216) {
		return false;
	}
	$ef = false;
	while (!feof($fp)) {
		$section_length = 0;
		$section_marker = 0;
		$lh = 0;
		$ll = 0;
		for ($i = 0;$i < 7;$i++) {
			$section_marker = fgetord($fp);
			if ($section_marker != 255) {
				break;
			}
			if ($i >= 6) {
				return false;
			}
		}
		if ($section_marker == 255) {
			return false;
		}
		$lh = fgetord($fp);
		$ll = fgetord($fp);
		$section_length = ($lh << 8) | $ll;
		$data = chr($lh) . chr($ll);
		$t_data = fread($fp, $section_length - 2);
		$data .= $t_data;
		switch ($section_marker) {
			case 225:
		    	return extractEXIFData(substr($data, 2), $section_length);
		    	$ef = true;
				break;
		}
	}
	fclose($fp);
}

// reads the EXIF header and if it is intact it calls readEXIFDir to get the data
function extractEXIFData($data, $length) {
	global $align;
	if (substr($data, 0, 4) == "Exif") {
		if (substr($data, 6, 2) == "II") {
			$align = 0;
		} else {
			if (substr($data, 6, 2) == "MM") {
				$align = 1;
			} else {
				return false;
			}
		}
		$a = getnumber($data, 8, 2, $align);
		if ($a != 0x2a) {
			return false;
		}
		$first_offset = getnumber($data, 10, 4, $align);
		if ($first_offset < 8 || $first_offset > 16) {
			return false;
		}
		readEXIFDir(substr($data, 14), 8, $length - 6);
		return true;
	} else {
		return false;
	}
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

function flashvalue($bin)
{
	return enumvalue("FlashFired",substr($bin, 7, 1), '') 
	     . enumvalue("FlashStrobe",substr($bin, 5, 2), '') 
	     . enumvalue("FlashMode",substr($bin, 3, 2), '')
	     . ( substr(pad(decbin($bin), 8, "0"), 1, 1) ? ", Red eye reduction" : ", No red eye reduction");
}

// takes a tag id along with the format, data and length of the data and deals with it appropriatly
function dealwithtag($tag, $format, $data, $length, $align) {
	global $format_type, $exif_data, $exif_tags;
	
	switch ($format_type[$format])
	{
		case "STRING":
			$val = trim(substr($data, 0, $length));
			break;
		case "ULONG":
		case "SLONG":
			$val = enumvalue($exif_tags[$tag], getnumber($data, 0, 4, $align));
			break;
		case "USHORT":
		case "SSHORT":
			switch ($tag)
			{
				case 0x9209:
					$val = array( getnumber($data, 0, 2, $align)
					            , flashvalue(getnumber($data, 0, 2, $align)));
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
		case "URATIONAL":
			$val = getrational(substr($data, 0, 8), $align, "U");
			break;
		case "SRATIONAL":
			$val = getrational(substr($data, 0, 8), $align, "S");
			break;
		case "UNDEFINED":
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
   	$exif_data[ ($exif_tags[$tag]) ] = $val;
  else 
    $exif_data['_ Tag:'.$tag] = $val;

  // Sorts the array (by key) to make it easier to debug
  ksort($exif_data);
}

// reads the tags from and EXIF IFD and if correct deals with the data
function readEXIFDir($data, $offset_base, $exif_length) {
	global $format_length, $format_type, $align;
	
	$data_in = "";
	$number_dir_entries = getnumber($data, 0, 2, $align);

	for ($i = 0;$i < $number_dir_entries;$i++)
	{
		$dir_entry    = substr($data, 2 + 12 * $i);
		$tag          = getnumber($dir_entry, 0, 2, $align);
		$format       = getnumber($dir_entry, 2, 2, $align);
		$components   = getnumber($dir_entry, 4, 4, $align);
		
		if (($format - 1) >= 12)
			return false;

		$byte_count = $components * $format_length[$format];
		
		if ($byte_count > 4)
		{
			$offset_val = (getnumber($dir_entry, 8, 4, $align)) - $offset_base;
			if (($offset_val + $byte_count) > $exif_length)
				return false;

			$data_in = substr($data, $offset_val);
		}
		else
			$data_in = substr($dir_entry, 8);

	  if ($tag == 0x8769)
	  {
			$tmp = (getnumber($data_in, 0, 4, $align)) - 8;
			readEXIFDir(substr($data, $tmp), $tmp + 8 , $exif_length);
		} 
		else
			dealwithtag($tag, $format, $data_in, $byte_count, $align);
	}
}

?>