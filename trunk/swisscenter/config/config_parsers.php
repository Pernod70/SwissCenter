<?php
/**************************************************************************************************
   SWISScenter Source                                                              Nigel Barnes
 *************************************************************************************************/

require_once( realpath(dirname(__FILE__).'/../video_obtain_info.php'));

/**
 * Display parser settings.
 *
 * @param string $message
 */

function parsers_display( $message = "")
{
  // Get list of all parsers
  $parsers_movie = get_parsers_list('movie');
  $parsers_tv = get_parsers_list('tv');
  $parsers = array_merge($parsers_movie, $parsers_tv);
  unset($parsers['None']);

  echo "<h1>".str('PARSER_OPTIONS')."</h1>";
  message($message);

  form_start('index.php', 150, 'parsers');
  form_hidden('section', 'PARSERS');
  form_hidden('action', 'UPDATE');
  echo '<p>'.str('PARSER_SETTINGS_PROMPT');

  // Determine all parsers that support this property
  foreach ($parsers as $name=>$parserClass)
  {
    $parser = new $parserClass();

    // Parser settings are not stored with 'movie_' and 'tv_' prefixes
    $parserClass = str_replace(array('movie_', 'tv_'), '', $parserClass);

    // Does this parser have settings?
    if (isset($parser->settings) && !empty($parser->settings))
    {
      // Parser name
      echo '<tr><td><b>'.$name.'</b></td></tr>';
      foreach ($parser->settings as $id=>$options)
      {
        $options['options'] = array_flip($options['options']);
        foreach ($options['options'] as $key=>$value)
          $options['options'][$key] = strtoupper($key);
        form_list_static($parserClass.'_'.$id, str($id), $options['options'],
                         get_sys_pref($parserClass.'_'.$id, $options['default']), false, false, false);
      }
    }
  }
  form_submit(str('SAVE_SETTINGS'), 2, 'left', 150);
  form_end();
}

/**
 * Saves the new parser settings
 *
 */

function parsers_update()
{
  // Get list of all parsers
  $parsers_movie = get_parsers_list('movie');
  $parsers_tv = get_parsers_list('tv');
  $parsers = array_merge($parsers_movie, $parsers_tv);
  unset($parsers['None']);

  // Determine all parsers that support this property
  foreach ($parsers as $name=>$parserClass)
  {
    $parser = new $parserClass();

    // Parser settings are not stored with 'movie_' and 'tv_' prefixes
    $parserClass = str_replace(array('movie_', 'tv_'), '', $parserClass);

    // Does this parser have settings?
    if (isset($parser->settings))
    {
      foreach ($parser->settings as $id=>$options)
      {
        set_sys_pref($parserClass.'_'.$id, $_REQUEST[$parserClass.'_'.$id]);
      }
    }
  }
  parsers_display(str('SAVE_SETTINGS_OK'));
}

/**************************************************************************************************
                                               End of file
 **************************************************************************************************/
?>