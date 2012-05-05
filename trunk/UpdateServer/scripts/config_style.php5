<?php

/**************************************************************************************************
                                              Start of file
 ***************************************************************************************************/
  
  function style_display($msg = "")
  {
    echo "<h1>Styles</h1>";
    message($msg);    
    style_add_form();
    style_list_current();
  }
   
  function style_list_current()
  {
    echo '<p><h2>Current Styles</h2><p>';
    $styles = dir_to_array('../styles','.*\.zip',DIR_TO_ARRAY_SHOW_FILES || DIR_TO_ARRAY_FULL_PATH);
    
    $count=1;
    echo '<table border=0 cellpadding="4" cellspacing="4"><tr>';    
    foreach ($styles as $style)
    {
    	echo '<td align="center"><img width="150" src="../styles/'.file_noext($style).'"><br />'
    	   . str_replace('.zip','',$style)
    	   . '</td>';
      if ( $count++ % 4 == 0)
        echo '</tr><tr>';
    }    
    echo '</tr></table>';    
  }

  function style_add_form()
  {
    echo '<p><h2>Upload new style</h2><p>';
    echo '<p><ul>
          <li>If the name specified matches an existing style then that style will be replaced.
          <li>Style thumbnails will be accessed by SwissCenter clients, and so should typically be less than 50kb in size.
          <li><font color="red">Please do not upload any skins which contain copyrighted material.</font>
          </ul>';
    
    form_start('index.php5', 150, '');
    form_hidden('section','STYLE');
    form_hidden('action','NEW');
    form_input('name','Style name',15);
    form_file('zipfile','Style (zipped)');
    form_file('thumb','Style Thumbnail');
    form_submit('Upload Style',1,'center');
    form_end();      
  }
  
  function style_new()
  {
    $name      = $_REQUEST["name"];
    $zipfile   = $_FILES["zipfile"]["tmp_name"];
    $thumbfile = $_FILES["thumb"]["tmp_name"];
    
    if ( empty($name))
      style_display("!Please enter a name for the style");
    elseif ( file_ext($_FILES["zipfile"]["name"]) != 'zip')
      style_display("!Please specify a ZIP file containing the style files");
    elseif ( file_ext($_FILES["thumb"]["name"]) != 'jpg')
      style_display("!Please specify a JPG file for the thumbnail");
    else
    {
      // Check the zipfile does not contain subfolders (by looking for the style.ini file)
      $inifile = exec("unzip -l $zipfile | grep style.ini | awk '{print $4}'");
      if ($inifile != 'style.ini')
      {
        style_display("!The zipfile does not contain a 'style.ini' file.<br><em>Note:</em> Zipfiles should not contain any subdirectories!");
      }
      else 
      {
        // Delete existing style definition.
        if ( file_exists("../styles/$name.zip") )
        {
          @unlink("../styles/$name.jpg");          
          @unlink("../styles/$name.zip");
        }
        else
        {
          // Get style list and strip newlines.
          $style_list = file("../styles/index.txt");          
          for ( $i=0; $i<count($style_list); $i++)
            $style_list[$i] = trim($style_list[$i]);

          // Add new style and write to disk
          $style_list[] = $name;
          sort($style_list);
          debug($style_list);
          array2file( $style_list, "../styles/index.txt");
        }
  
        move_uploaded_file($zipfile,"../styles/$name.zip");
        move_uploaded_file($thumbfile,"../styles/$name.jpg");
        style_display("Style Uploaded");
      }
    }  
  }
  
/**************************************************************************************************
                                               End of file
 ***************************************************************************************************/
