<?
/**************************************************************************************************
   SWISScenter Source                                                              Robert Taylor
 *************************************************************************************************/

  require_once("base/page.php");
  require_once("base/image.php");

  function multiline_text (&$image, $text, $width, $scale, $x, &$y, $colour)
  {
    $line1    = shorten($text,$width,$scale,1,false);
    
    if (strlen($text) > strlen($line1))
    {
      $line1 = substr($line1,0,strrpos($line1,' '));
      $image->text($line1, $x+10, $y+=22, $col_text);   
      $image->text(shorten(substr($text,strlen($line1)+1),$width,$scale), $x+10, $y+=22, $col_text);   
    }
    else 
      $image->text($line1, $x+10, $y+=22, $col_text);   
  }
  
  $image     = new CImage();
  $artfile   = new CImage();
  $info      = db_toarray("select * from mp3s where file_id=".$_REQUEST["music_id"]);
  $col_title = colour(48,96,168);
  $col_text  = colour(1,49,87);

  $art_fsp   = file_albumart($info[0]["DIRNAME"].$info[0]["FILENAME"]);
  if ($art_fsp != '')
    $artfile->load_from_file( $art_fsp );
  else
    $artfile->load_from_file( style_img('IMG_ART_MISSING',true) );

  $image->load_from_file( style_img(strtoupper($_SESSION["opts"]["screen"]).'_PLAYING',true) );

  $x      = 23;
  $y      = 105;
  $text_w = 383 - $x;
  $scale   = 0.92;
  
  // Album Art
  $artfile->resize(198,198);
  $image->copy($artfile,$x,$y);
  
  $x +=219;

  //Track Information
  if (!empty($info[0]["TITLE"]))
  {
    $image->text('Track',  $x, $y+=17, $col_title, 18);
    multiline_text($image, $info[0]["TITLE"],$text_w,$scale, $x+10, $y, $col_text);
  }
  if (!empty($info[0]["ALBUM"]))
  {
    $image->text('Album',  $x, $y+=45, $col_title, 18);
    multiline_text($image, $info[0]["ALBUM"],$text_w,$scale, $x+10, $y, $col_text);
  }
  if (!empty($info[0]["ARTIST"]))
  {
    $image->text('Artist', $x, $y+=45, $col_title, 18);
    multiline_text($image, $info[0]["ARTIST"],$text_w,$scale,$x+10, $y, $col_text);
  }
  
  // Output picture
  $image->output('jpeg');

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>
