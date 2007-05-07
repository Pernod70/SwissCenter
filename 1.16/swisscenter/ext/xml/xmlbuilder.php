<?
// Simon Willison, 16th April 2003
// Based on Lars Marius Garshol's Python XMLWriter class
// See http://www.xml.com/pub/a/2003/04/09/py-xml.html
//
// Modified by R.Taylor to incorporate the following enhancements:
//  - Support for <![CDATA[]]> tags
//  - Added "xmlentities()" function to handle accents and special characters
//  - Renamed the class to "XmlBuilder" (from "XmlWriter") to avoid a name clash in PHP5

class XmlBuilder
{
  var $xml;
  var $indent;
  var $stack = array();

  function XmlBuilder($indent = '  ') 
  {
    $this->indent = $indent;
    $this->xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
  }

  function _indent() {
    for ($i = 0, $j = count($this->stack); $i < $j; $i++) 
      $this->xml .= $this->indent;
  }

  function xmlentities($string, $quote_style=ENT_QUOTES)
  {
     static $trans;
     if (!isset($trans)) {
         $trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
         foreach ($trans as $key => $value)
             $trans[$key] = '&#'.ord($key).';';
         // dont translate the '&' in case it is part of &xxx;
         $trans[chr(38)] = '&';
     }
     // after the initial translation, _do_ map standalone '&' into '&#38;'
     return preg_replace("/&(?![A-Za-z]{0,4}\w{2,5};|#[0-9]{2,5};)/","&#38;" , strtr($string, $trans));
  }
  
  function push($element, $attributes = array()) 
  {
    $this->_indent();
    $this->xml .= '<'.strtolower($element);

    foreach ($attributes as $key => $value) 
      $this->xml .= ' '.$key.'="'.$this->xmlentities($value).'"';

    $this->xml .= ">\n";
    $this->stack[] = strtolower($element);
  }

  function element($element, $content = '', $attributes = array()) 
  { 
    $this->_indent();
    $this->xml .= '<'.strtolower($element);

    foreach ($attributes as $key => $value) 
      $this->xml .= ' '.$key.'="'.$this->xmlentities($value).'"';

    if (!preg_match("/\<\!\[CDATA\[(.*)\]\]\>/", $content))
      $content = $this->xmlentities($content);

    $this->xml .= '>'.$content.'</'.strtolower($element).'>'."\n";
  }
  
  function element_array( $array )
  {
    foreach ($array as $name => $value)
      $this->element($name, $value);
  }

  function emptyelement($element, $attributes = array()) 
  {
    $this->_indent();
    $this->xml .= '<'.$element;

    foreach ($attributes as $key => $value) 
      $this->xml .= ' '.$key.'="'.$this->xmlentities($value).'"';

    $this->xml .= " />\n";
  }

  function pop() 
  {
    $element = array_pop($this->stack);
    $this->_indent();
    $this->xml .= "</$element>\n";
  }

  function getXml() 
  {
    return $this->xml;
  }

}
  
?>