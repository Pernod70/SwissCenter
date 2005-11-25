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

function form_start( $url, $width = 150, $name = '' )
{
  echo '<form name="'.$name.'" enctype="multipart/form-data" action="'.$url.'" method="post">
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

function form_text_html( $param, $sizex = 100, $sizey = 5, $value ='')
{
   return '<textarea '.($opt ? '' : ' required ').' rows="'.$sizey.'" cols="'.$sizex.'" name="'.$param.'">'.$value.'</textarea>';
}

function form_text( $param, $prompt, $sizex = 100, $sizey = 5, $value ='', $opt = false)
{
   echo '<tr>
           <td colspan="2">'.form_prompt($prompt,$opt).'</td>
         </tr>
         <tr>
           <td colspan="2">
             '.form_text_html($param,$sizex,$sizey,$value).'
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

function form_list_dynamic_html ( $param, $sql, $value = "", $opt = false, $submit = false, $inital_txt = '')
{
  $html = '<select '.
          ($opt ? '' : ' required ').
          ' name="'.$param.'"'.
          ' size="1"'.
          ($submit ? ' onChange="this.form.submit();"' : '').
          '>'.
          '<option value=""> &lt;'.( empty($inital_txt) ? str('PLEASE_SELECT') : $inital_txt).'&gt; ';

  $recs = new db_query( $sql );
  if ($recs->db_success() )
  {
    while ($row = $recs->db_fetch_row() )
    {
      $vals = array_values($row);
      $html .= '<option '.( $vals[0] ==$value ? 'selected ' : '').'value="'.$vals[0].'">'.$vals[1];
    }
  }
  else
  {
    page_error( $recs->db_get_error() );
  }
  $recs->destroy();

  return $html;
}

function form_list_dynamic( $param, $prompt, $sql, $value = "", $opt = false )
{
  echo '<tr>
          <td>'.form_prompt($prompt,$opt).'</td>
          <td>'.form_list_dynamic_html($param, $sql, $value, $opt).'</td>
       </tr>';
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
    echo '<option value=""> &lt;'.str('PLEASE_SELECT').'&gt; ';

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

function form_radio_static( $param, $prompt, $list, $value = "", $opt = false, $horiz = false )
{
  echo '<tr><td valign="top">'.form_prompt($prompt,$opt).'</td>
        <td>';

  while( list($akey,$avalue) = each($list) )
  {
    echo '<input type="radio" name="'.$param.'" '.( $avalue == $value ? 'checked ' : '').'value="'.$avalue.'">'.$akey;
    echo ( $horiz ? '&nbsp; &nbsp;' : '<br>');
  }

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
#
# Additionally you may pass an associative array of edit options. The keys in the array and the
# names of the table headings and must be all uppercase. The values can be one of three things:
#   * An empty string - This indicates a text editable field
#   * A string - This is a sql statement that returns exactly 2 columns, the first an ID that will
#     be used as the value of the selected item, and the second a string to display. This will be
#     displayed as a drop down list
#   * An array - This array is an array of arrays, each element of the outer array is a single row
#     in a table, the child arrays are column name/value pairs
#
# If an array of edit options is passed then an edit button will be placed on each row. It can
# be determined if the edit button was clicked and for which row by calling form_select_table_edit()
# 
# To display the table in edit mode, pass the edit options with the $edit variable set to the
# value of the ID column that is to be edited, usually obtained from form_select_table_edit
#
# To retrieve the results of an edit call form_select_table_update()
#-------------------------------------------------------------------------------------------------

function form_select_table ( $param, $table_contents, $table_headings, $table_params, $id_col, $edit_options = array(), $edit = 0, $formname = '')
{
  // Process the paramters in the table 
  $editable = (count($edit_options) > 0);
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
    foreach (explode(',',$table_headings) as $value)
    {
        echo '<th>'.ucwords($value).'</th>';
    }

    if($editable)
    {
      echo '<th>&nbsp;</th>';
      
      echo "<script language='javascript'><!--\r\n";
      echo "function edit_".$formname."(val){document.forms.".$formname.".".$formname."_".$param."_edit.value=val;document.forms.".$formname.".submit();}\r\n";
      echo "function update_".$formname."(val){document.forms.".$formname.".".$formname."_".$param."_update.value=val;document.forms.".$formname.".submit();}\r\n";
      echo "function cancel_".$formname."(){document.forms.".$formname.".submit();}\r\n";
      echo "-->\r\n";
      echo "</script>";
      echo '<input type="hidden" name="'.$formname.'_'.$param.'_edit" value="">';
      echo '<input type="hidden" name="'.$formname.'_'.$param.'_update" value="">';
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

          if($editable && ($row[strtoupper($id_col)] == $edit))
          {
            // Check the edit options to see if there are choices for this column or not
            $element_name = strtoupper($param.'_update:'.escape_form_names($cell_name));
            $cell_edit_options = $edit_options[$cell_name];
            if(empty($cell_edit_options))
            {
              echo "<input type='text' name='".$element_name."' value='".$cell_value."'>";
            }
            elseif($cell_edit_options == "*")
            {
              echo "<input type='password' name='".$element_name."' value='".$cell_value."'>";
            }
            else
            {
              if(is_array($cell_edit_options))
                $options = $cell_edit_options;
              else
                $options = db_toarray($cell_edit_options);
                
              echo "<select name='".$element_name."'>";
              
              foreach($options as $option)
              {
                $option_data = array_values($option);
                echo "<option value='".$option_data[0]."'";
                if(strtoupper($cell_value) == strtoupper($option_data[1]))
                  echo " selected='selected'";
                  
                echo ">".$option_data[1]."</option>";
              }
              echo "</select>";
            }
          }
          else
          {
            $cell_edit_options = $edit_options[$cell_name];
            if($cell_edit_options == "*")
              echo "********";
            else
              echo $cell_value;
          }
        
          echo '</td>';
        }
      }
      
      if($editable)
      {
        if(empty($edit))
          echo '<td align="center" width="60"><a href="javascript:edit_'.$formname.'(\''.$row[strtoupper($id_col)].'\');"><img alt="Edit" title="Edit" src="ico_edit.gif" border="0"></a></td>';
        else if($row[strtoupper($id_col)] == $edit)
        {
          echo '<td align="center" width="60"><a href="javascript:update_'.$formname.'(\''.$row[strtoupper($id_col)].'\');"><img alt="Ok" title="Ok" src="ico_tick.gif" border="0"></a>';
          echo '&nbsp;&nbsp;<a href="javascript:cancel_'.$formname.'();"><img alt="Cancel" title="Cancel" src="ico_cross.gif" border="0"></a></td>';
        }
        else
          echo '<td>&nbsp;</td>';
      }  

      echo '</tr>';
    }
    
    // End the table
    echo '</table></td></tr>';
  }
  else 
  {
    echo '<tr><td colspan="2"><table '.$param_str.'>';
    echo '<tr><th><center>'.str('NO_ITEMS_TO_DISPLAY').'</center></th></tr?';
    echo '</table></td></tr>';
  }
}

function escape_form_names($name)
{
  return strtr($name, " ", "_");
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

function form_select_table_edit( $param_name, $formname )
{
  if(!empty($_REQUEST[$formname.'_'.$param_name.'_edit']))
    $result = $_REQUEST[$formname.'_'.$param_name.'_edit'];
    
  return $result;
}

function form_select_table_update( $param_name, $formname )
{
  $result = array();

  if(!empty($_REQUEST[$formname.'_'.$param_name.'_update']))
  {
    $result[strtoupper($param_name)] = $_REQUEST[$formname.'_'.$param_name.'_update'];
  
    foreach($_REQUEST as $key => $val)
    {
      if(strtoupper(substr($key, 0, strlen($param_name)+8)) == strtoupper($param_name).'_UPDATE:')
      {
        $result[substr($key, strlen($param_name)+8)] = $val;
      }
    }
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

