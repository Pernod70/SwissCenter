<?
/**************************************************************************************************

 Description:
 ------------

 Package to generate standard looking forms.

 There are a number of parameters common to most procedures which are detailed as follows.
 Exceptions will be highlighted in the function header comments.

 $param   - The name to use as the parameter of the field when passed to the handling page.
 $prompt  - The text to output to a user before the field.
 $opt     - [opt] TRUE if the field in optional. Defaults to FALSE
 $value   - [opt] The initial value to insert/highlight in the field. Defaults to none

 *************************************************************************************************/

require_once("../base/mysql.php");

#-------------------------------------------------------------------------------------------------
# Displays the prompt for the field to the user, highlighting mandatory fields. Internal to this
# library and should not be used by user functions/pages.
#-------------------------------------------------------------------------------------------------

function form_prompt( $prompt, $opt )
{
  if ($opt)
    return $prompt.' : &nbsp;';
  else
    return '<span class=stdformreq>'.$prompt.' : &nbsp;</span>';
}

#-------------------------------------------------------------------------------------------------
# Starts the form, and creates a table to neatly format the fields.
#
# $url    - the page that the completed form should be sent to.
# $width  - [opt] the width of the column displaying prompts to the user
#-------------------------------------------------------------------------------------------------

function form_start( $url, $width = 150 )
{
  echo '<form enctype="multipart/form-data" action="'.$url.'" method="post">
        <p><table border=0 class="stdform" width="100%" cellspacing=4>
        <tr><td width="'.$width.'"></td><td></td></tr>';
}

#-------------------------------------------------------------------------------------------------
# Creates a normal single line text input field .
#
# $size - the size of the input field.
# $mask - Regular expression that is used to validate the field on the client
#-------------------------------------------------------------------------------------------------

function form_input( $param, $prompt, $size = 15, $maxlength = '',$value ='', $opt = false, $mask = '')
{
  echo '<tr>
          <td>'.form_prompt($prompt,$opt).'</td>
          <td><input '.
              ($opt ? '' : ' required ').
              ($mask != '' ? ' mask="'.$mask.'"' : '').
              ($maxlength != '' ? ' maxlength="'.$maxlength.'"' : '').
             ' size='.$size.
             ' name="'.$param.'"
               value="'.$value.'"></td>
        </tr>';
}

#-------------------------------------------------------------------------------------------------
# Creates a multi-line text field .
#
# $sizex - [opt] the number of columns in the text field (default 120)
# $sizey - [opt] the number of rows in the text field (default 5)
#-------------------------------------------------------------------------------------------------

function form_text( $param, $prompt, $sizex = 100, $sizey = 5, $value ='', $opt = false)
{
   echo '<tr>
           <td colspan="2">'.form_prompt($prompt,$opt).'</td>
         </tr>
         <tr>
           <td colspan="2">
             <textarea '.($opt ? '' : ' required ').' rows="'.$sizey.'" cols="'.$sizex.'" name="'.$param.'">'.$value.'</textarea>
           </td>
         </tr>';
}

#-------------------------------------------------------------------------------------------------
# Creates a password input field .
#
# $size - the size of the input field where the password will be entered.
#-------------------------------------------------------------------------------------------------

function form_password( $param, $prompt, $size = 15, $value ='', $opt = false, $mask ='')
{
  echo '<tr>
          <td>'.form_prompt($prompt,$opt).'</td>
          <td><input '.
          ($mask != '' ? ' mask="'.$mask.'"' : '').
          ($opt ? '' : ' required ').
          ' type="password" size='.$size.' name="'.$param.'" value="'.$value.'"></td>
        </tr>';
}

#-------------------------------------------------------------------------------------------------
# Creates a hidden field for passing variables to the form without the user seeing the details.
#-------------------------------------------------------------------------------------------------

function form_hidden( $param, $value )
{
  echo '<input type=hidden name="'.$param.'" value="'.$value.'">';
}

#-------------------------------------------------------------------------------------------------
# Outputs a large amount of text to the user as a guide on how to fill in a form on a seperate
# row within the table.
#-------------------------------------------------------------------------------------------------

function form_label( $text, $hpos = "R", $vpos = "B" )
{
  # Determine nd set horizontal postion; (L)eft or (R)ight.
  if ( $hpos == "R" )
    echo '<tr class="stdformlabel"><td>&nbsp;</td><td>';
  else
    echo '<tr class="stdformlabel"><td colspan=2>';

  # Determine and set vertical position; (T)op or (B)ottom
  if ( $vpos == "T" )
    echo '&nbsp;<br>'.$text.'</td></tr>';
  else
    echo $text.'<br>&nbsp;</td></tr>';
}

#-------------------------------------------------------------------------------------------------
# Dynamic drop-down lists (list generated from database).
#
# $sql - An SQL statement that retrieves exactly 2 columns from the database. The first column
#        contains the value that will be passed to the form handler, the second column contains
#        the value that will appear in the list for the user to select.
#-------------------------------------------------------------------------------------------------

function form_list_dynamic( $param, $prompt, $sql, $value = "", $opt = false )
{
  echo '<tr><td>'.form_prompt($prompt,$opt).'</td>
        <td><select '.($opt ? '' : ' required ').' name="'.$param.'" size="1">
            <option value="">- Please Specify -';

  $recs = new db_query( $sql );

  if ($recs->db_success() )
  {
    while ($row = $recs->db_fetch_row() )
    {
      $vals = array_values($row);
      echo '<option '.( $vals[0] ==$value ? 'selected ' : '').
           'value="'.$vals[0].'">'.$vals[1];
    }
  }
  else
    page_error( $recs->db_get_error() );

  $recs->destroy();
  echo '  </td></tr>';
}

#-------------------------------------------------------------------------------------------------
# Static drop-down lists (from the specified array).
#
# Note that the value passed by the field will be the array VALUE, while the value
# displayed in the list to the user will be the array KEY.
#
# $list - an array of values to display in the drop-down list.
#-------------------------------------------------------------------------------------------------

function form_list_static( $param, $prompt, $list, $value = "", $opt = false,  $ins = true )
{
  echo '<tr><td>'.form_prompt($prompt,$opt).'</td>
        <td><select '.($opt ? '' : ' required ').' name="'.$param.'" size="1">';

  if ($ins)
    echo '<option value="">- Please Specify -';

  while( list($akey,$avalue) = each($list) )
    echo '<option '.( $avalue ==$value ? 'selected ' : '').'value="'.$avalue.'">'.$akey;

  echo '  </td></tr>';
}

#-------------------------------------------------------------------------------------------------
# Static radio lists (from the specified array).
#
# Note that the value passed by the field will be the array VALUE, while the value
# displayed in the list to the user will be the array KEY.
#
# $list - an array of values to display with radio buttons
#-------------------------------------------------------------------------------------------------

function form_radio_static( $param, $prompt, $list, $value = "", $opt = false )
{
  echo '<tr><td valign="top">'.form_prompt($prompt,$opt).'</td>
        <td>';

  while( list($akey,$avalue) = each($list) )
    echo '<input type="radio" name="'.$param.'" '.( $avalue == $value ? 'checked ' : '').'value="'.$avalue.'">'.$akey.'<br>';

  echo '  </td></tr>';
}

#-------------------------------------------------------------------------------------------------
# Creates a submit button.
#
# text - [opt] The text that should appear on the submit button if different to 'submit'
# col  - [opt] The column that the button should be displayed in (defaults to column 2)
#-------------------------------------------------------------------------------------------------

function form_submit( $text = "Submit", $col = 2, $align = 'left' )
{
  if ($col == 1)
    echo '<tr><td align="'.$align.'" colspan="2"><input type="submit" name="submit_action" value=" '.$text.' "></td></tr>';
  else 
    echo '<tr><td>&nbsp;</td><td align="'.$align.'"><input type="submit" name="submit_action" value=" '.$text.' "></td></tr>';
}

#-------------------------------------------------------------------------------------------------
# Outputs a table containing the data in the $table_contents array (except for the column that
# is indicated by the $id_col variable - this column will instead be used to pass an identifier
# to the form as to which rows in the table were selected by the user.
#-------------------------------------------------------------------------------------------------

function form_select_table ( $param, $table_contents, $table_params, $id_col)
{
  // Process the paramters in the table 
  $param_str ='';
  if (is_array($table_params) && count($table_params) >0)
  {
    foreach ($table_params as $attrib => $value)
      $param_str .= $attrib.'="'.$value.'" ';
  }
  
  // Display the table if there are some rows in the dataset.
  if (count($table_contents) != 0)
  {
    // Table tag and attributes (passed in via the $table_params array)
    echo '<tr><td colspan="2"><table '.$param_str.'>';
    
    // Display headings for the table based on the column names in the dataset
    echo '<tr><th>&nbsp;</th>';
    foreach ($table_contents[0] as $heading => $value)
    {
      if ($heading != strtoupper($id_col))
        echo '<th>'.ucwords(str_replace('_',' ',strtolower($heading))).'</th>';
    }
    echo '</tr>';
    
    // Display the rows in the dataset
    foreach ($table_contents as $row)
    {
      echo '<tr>';
      echo '<td align="center" width="30">'.'<input type="checkbox" name="'.$param.':'.$row[strtoupper($id_col)].'" value="'.$row[strtoupper($id_col)].'"></td>'; // Select Box
      foreach ($row as $cell_name => $cell_value)
      {
        if ($cell_name != strtoupper($id_col))
        {
          echo '<td>';
          echo $cell_value;
          echo '</td>';
        }
      }
      echo '</tr>';
    }
    
    // End the table
    echo '</table></td></tr>';
  }
  else 
  {
    echo '<tr><td colspan="2"><table '.$param_str.'>';
    echo '<tr><th><center>There are no items to display</center></th></tr?';
    echo '</table></td></tr>';
  }
}

function form_select_table_vals( $id_col )
{
  $result = array();
  foreach ($_REQUEST as $key => $val)
  {
    if (strtoupper(substr($key,0,strlen($id_col)+1)) == strtoupper($id_col).':')
      $result[] = $val;
  }
  return $result;
}

#-------------------------------------------------------------------------------------------------
# Ends the table used to format the form, and ends the form itself.
#-------------------------------------------------------------------------------------------------

function form_end()
{
  echo '</table></form>';
}

#-------------------------------------------------------------------------------------------------
# Checks that the passed value matches the given mask, and returns true or false.
# This function is similar the javascript mask feature except it can validate data if
# the user has javascript turned off.
#-------------------------------------------------------------------------------------------------

function form_mask( $value, $mask )
{
  return ( preg_match('/'.$mask.'/',$value) ? true : false );
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>

